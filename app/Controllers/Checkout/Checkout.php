<?php

namespace App\Controllers\Checkout;

use App\Controllers\BaseController;
use App\Libraries\Payment\PaymentManager;
use App\Models\AddressModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\UserModel;
use App\Services\CartService;
use App\Services\OrderService;

class Checkout extends BaseController
{
    private function uid(): int
    {
        return (int) session()->get('user_id');
    }

    /** Current user id, or null for a guest checkout. */
    private function uidOrNull(): ?int
    {
        $id = $this->uid();

        return $id > 0 ? $id : null;
    }

    public function index()
    {
        $snapshot = (new CartService())->snapshot();
        if (empty($snapshot['items'])) {
            return redirect()->to(site_url('cart'))->with('info', 'Your cart is empty.');
        }

        $idempotencyKey = bin2hex(random_bytes(16));

        return view('checkout/index', [
            'meta'           => ['title' => 'Checkout — ' . store_name()],
            'snapshot'       => $snapshot,
            'addresses'      => (new AddressModel())->forUser($this->uid()),
            'user'           => (new UserModel())->find($this->uid()) ?: [],
            'methods'        => (new PaymentManager())->available(),
            'idempotencyKey' => $idempotencyKey,
            'bodyClass'      => 'bg-sand/30',
        ]);
    }

    public function place()
    {
        $rules = [
            'full_name' => 'required|max_length[120]',
            'phone'     => 'required|min_length[10]|max_length[15]',
            'email'     => 'required|valid_email',
            'line1'     => 'required|max_length[190]',
            'city'      => 'required|max_length[80]',
            'state'     => 'required|max_length[80]',
            'pincode'   => 'required|min_length[4]|max_length[12]',
            'payment_method' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $method = (new PaymentManager())->get($this->request->getPost('payment_method'));
        if (! $method) {
            return redirect()->back()->withInput()->with('error', 'Please choose a valid payment method.');
        }

        $shipping = [
            'full_name' => $this->request->getPost('full_name'),
            'phone'     => $this->request->getPost('phone'),
            'line1'     => $this->request->getPost('line1'),
            'line2'     => $this->request->getPost('line2'),
            'city'      => $this->request->getPost('city'),
            'state'     => $this->request->getPost('state'),
            'pincode'   => $this->request->getPost('pincode'),
            'country'   => 'India',
        ];
        $customer = ['email' => $this->request->getPost('email'), 'phone' => $shipping['phone']];

        // Optionally save the address to the account (logged-in users only).
        if ($this->uidOrNull() !== null && $this->request->getPost('save_address')) {
            (new AddressModel())->insert(['user_id' => $this->uid()] + $shipping);
        }

        $idempotencyKey = (string) $this->request->getPost('idempotency_key');

        try {
            $order = (new OrderService())->createFromCart(
                $this->uidOrNull(),
                $customer,
                $shipping,
                $method->key(),
                $this->request->getPost('notes'),
                $idempotencyKey ?: null
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // Remember the order in this session so a guest can view its
        // confirmation and later claim it into a new account.
        $this->rememberOrder($order['order_number']);

        // Online payment: hand off to the gateway (intent).
        if ($method->isOnline()) {
            session()->set('pending_order', $order['order_number']);
            try {
                $intent = $method->createIntent($order);
                // F-002: Persist gateway order ID with payment record immediately
                if (! empty($intent['gateway_order_id'])) {
                    (new \App\Models\PaymentModel())->where('order_id', $order['id'])
                        ->set(['provider_order' => $intent['gateway_order_id']])->update();
                }
            } catch (\RuntimeException $e) {
                session()->remove('pending_order');

                return redirect()->to(site_url('checkout'))->with('error', $e->getMessage());
            }

            return view('checkout/pay', [
                'meta'   => ['title' => 'Complete Payment — ' . store_name()],
                'order'  => $order,
                'intent' => $intent,
            ]);
        }

        // Offline (COD): confirm immediately and clear the cart.
        (new OrderService())->confirmCod((int) $order['id']);
        (new CartService())->clear();

        return redirect()->to(site_url('checkout/confirmation/' . $order['order_number']));
    }

    /**
     * Gateway callback verification (CSRF-exempt; verified by signature §31, F-002).
     */
    public function verifyPayment()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $number  = session()->get('pending_order') ?? ($payload['order_number'] ?? '');
        $order   = (new OrderModel())->findByNumber((string) $number);
        if (! $order) {
            return $this->jsonError('Order not found.', 404);
        }

        // Replay protection: if already paid, return success redirect without re-processing
        if ($order['payment_status'] === 'paid') {
            return $this->jsonSuccess('Payment already confirmed.', ['redirect' => site_url('checkout/confirmation/' . $order['order_number'])]);
        }

        $paymentModel = new \App\Models\PaymentModel();
        $payment      = $paymentModel->where('order_id', $order['id'])->first();
        if (! $payment) {
            return $this->jsonError('Payment record not found.', 404);
        }

        // F-002: Validate submitted gateway order ID matches persisted provider_order
        $submittedOrderId = (string) ($payload['razorpay_order_id'] ?? '');
        if ($payment['provider_order'] && $payment['provider_order'] !== $submittedOrderId) {
            log_message('critical', "Razorpay order ID mismatch on order {$order['order_number']}: expected {$payment['provider_order']}, got {$submittedOrderId}");
            return $this->jsonError('Payment verification failed: order mismatch.', 400);
        }

        // Verify cryptographic HMAC signature
        $method = (new PaymentManager())->get($order['payment_method']);
        if (! $method || ! $method->verify($payload)) {
            return $this->jsonError('Payment verification failed.', 400);
        }

        $paymentId = (string) ($payload['razorpay_payment_id'] ?? '');

        // F-002: Ensure payment ID has not been used on any other completed order
        if ($paymentId !== '') {
            $duplicate = $paymentModel->where('provider_ref', $paymentId)->where('status', 'captured')->first();
            if ($duplicate && (int) $duplicate['order_id'] !== (int) $order['id']) {
                log_message('critical', "Duplicate payment ID {$paymentId} attempted for order {$order['order_number']}");
                return $this->jsonError('This payment transaction has already been applied.', 400);
            }
        }

        // F-002: Server-to-server reconciliation with Razorpay API (amount, currency, captured/authorized status)
        if ($method instanceof \App\Libraries\Payment\RazorpayPaymentService && $paymentId !== '') {
            $remote = $method->fetchPayment($paymentId);
            if ($remote) {
                $expectedPaise = (int) round($order['total'] * 100);
                if ((int) ($remote['amount'] ?? 0) !== $expectedPaise) {
                    log_message('critical', "Razorpay amount mismatch for order {$order['order_number']}: expected {$expectedPaise}, got {$remote['amount']}");
                    return $this->jsonError('Payment verification failed: amount mismatch.', 400);
                }
                if (($remote['currency'] ?? '') !== ($order['currency'] ?? 'INR')) {
                    return $this->jsonError('Payment verification failed: currency mismatch.', 400);
                }
                if (! in_array($remote['status'] ?? '', ['captured', 'authorized'], true)) {
                    return $this->jsonError('Payment is not captured or authorized.', 400);
                }
            }
        }

        (new OrderService())->markPaid((int) $order['id'], $paymentId, $payload);
        (new CartService())->clear();
        session()->remove('pending_order');

        return $this->jsonSuccess('Payment successful.', ['redirect' => site_url('checkout/confirmation/' . $order['order_number'])]);
    }

    public function confirmation(string $number)
    {
        $order = (new OrderModel())->findByNumber($number);
        if (! $order || ! $this->ownsOrder($order)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Post-purchase account offer, for guest orders only.
        $accountState = 'none'; // logged-in buyers already have an account
        if (! is_logged_in() && empty($order['user_id'])) {
            $accountState = (new UserModel())->findByEmail($order['email']) ? 'existing' : 'create';
        }

        return view('checkout/confirmation', [
            'meta'         => ['title' => 'Order Confirmed — ' . store_name()],
            'order'        => $order,
            'items'        => (new OrderItemModel())->forOrder((int) $order['id']),
            'accountState' => $accountState,
        ]);
    }

    /**
     * Create an account for a guest who just placed an order, link their
     * guest order(s) to it, and sign them in. Email comes from the order
     * (not the form) so it can't be spoofed.
     */
    public function claimAccount()
    {
        $rules = [
            'order_number'     => 'required',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];
        $messages = [
            'password'         => ['min_length' => 'Password must be at least 8 characters.'],
            'password_confirm' => ['matches' => 'Passwords do not match.'],
        ];
        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $number = (string) $this->request->getPost('order_number');
        $order  = (new OrderModel())->findByNumber($number);

        // Only the guest who placed this order (this session) may claim it.
        if (! $order || ! $this->ownsOrder($order) || ! empty($order['user_id'])) {
            return redirect()->to(site_url('checkout/confirmation/' . $number))
                ->with('error', 'This order can no longer be linked to a new account.');
        }

        $users = new UserModel();
        if ($users->findByEmail($order['email'])) {
            return redirect()->to(site_url('login'))
                ->with('info', 'You already have an account with this email — please sign in to see your order.');
        }

        // Derive a name from the shipping address.
        $ship  = $order['shipping_address'] ? json_decode($order['shipping_address'], true) : [];
        $full  = trim($ship['full_name'] ?? '');
        $first = $full !== '' ? strtok($full, ' ') : 'Customer';
        $last  = trim((string) substr($full, strlen($first)));

        $userId = $users->insert([
            'first_name'    => $first,
            'last_name'     => $last !== '' ? $last : null,
            'email'         => strtolower(trim($order['email'])),
            'phone'         => $order['phone'],
            'password_hash' => $users->hashPassword((string) $this->request->getPost('password')),
            'role'          => 'customer',
            'status'        => 'active',
        ]);

        // Link this order and any other guest orders that used the same email.
        (new OrderModel())->where('email', $order['email'])->where('user_id', null)
            ->set(['user_id' => $userId])->update();

        // Sign in using the same session contract as Auth::establishSession.
        $user = $users->find($userId);
        session()->set([
            'user_id'    => $user['id'],
            'user_name'  => trim($user['first_name'] . ' ' . ($user['last_name'] ?? '')),
            'user_email' => $user['email'],
            'user_role'  => $user['role'],
        ]);

        return redirect()->to(site_url('account/orders/' . $number))
            ->with('success', 'Your account is ready — welcome to ' . store_name() . '.');
    }

    /** Record an order placed in this session (for guest confirmation access). */
    private function rememberOrder(string $number): void
    {
        $mine = session()->get('my_orders') ?? [];
        if (! in_array($number, $mine, true)) {
            $mine[] = $number;
            session()->set('my_orders', $mine);
        }
    }

    /** A buyer owns an order if logged in as its user, or placed it this session. */
    private function ownsOrder(array $order): bool
    {
        $uid = $this->uidOrNull();
        if ($uid !== null && (int) $order['user_id'] === $uid) {
            return true;
        }

        return in_array($order['order_number'], session()->get('my_orders') ?? [], true);
    }
}
