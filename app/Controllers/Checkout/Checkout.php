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

    public function index()
    {
        $snapshot = (new CartService())->snapshot();
        if (empty($snapshot['items'])) {
            return redirect()->to(site_url('cart'))->with('info', 'Your cart is empty.');
        }

        return view('checkout/index', [
            'meta'      => ['title' => 'Checkout — ' . store_name()],
            'snapshot'  => $snapshot,
            'addresses' => (new AddressModel())->forUser($this->uid()),
            'user'      => (new UserModel())->find($this->uid()),
            'methods'   => (new PaymentManager())->available(),
            'bodyClass' => 'bg-sand/30',
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

        // Optionally save the address to the account.
        if ($this->request->getPost('save_address')) {
            (new AddressModel())->insert(['user_id' => $this->uid()] + $shipping);
        }

        try {
            $order = (new OrderService())->createFromCart(
                $this->uid(), $customer, $shipping, $method->key(), $this->request->getPost('notes')
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        // Online payment: hand off to the gateway (intent).
        if ($method->isOnline()) {
            session()->set('pending_order', $order['order_number']);
            $intent = $method->createIntent($order);

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
     * Gateway callback verification (CSRF-exempt; verified by signature §31).
     */
    public function verifyPayment()
    {
        $payload = $this->request->getJSON(true) ?: $this->request->getPost();
        $number  = session()->get('pending_order') ?? ($payload['order_number'] ?? '');
        $order   = (new OrderModel())->findByNumber((string) $number);
        if (! $order) {
            return $this->jsonError('Order not found.', 404);
        }

        $method = (new PaymentManager())->get($order['payment_method']);
        if (! $method || ! $method->verify($payload)) {
            return $this->jsonError('Payment verification failed.', 400);
        }

        (new OrderService())->markPaid((int) $order['id'], $payload['razorpay_payment_id'] ?? '', $payload);
        (new CartService())->clear();
        session()->remove('pending_order');

        return $this->jsonSuccess('Payment successful.', ['redirect' => site_url('checkout/confirmation/' . $order['order_number'])]);
    }

    public function confirmation(string $number)
    {
        $order = (new OrderModel())->where('order_number', $number)->where('user_id', $this->uid())->first();
        if (! $order) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('checkout/confirmation', [
            'meta'  => ['title' => 'Order Confirmed — ' . store_name()],
            'order' => $order,
            'items' => (new OrderItemModel())->forOrder((int) $order['id']),
        ]);
    }
}
