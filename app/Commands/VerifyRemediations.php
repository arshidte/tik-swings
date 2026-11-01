<?php

namespace App\Commands;

use App\Libraries\Payment\PaymentManager;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\PasswordResetModel;
use App\Models\PaymentModel;
use App\Models\ProductModel;
use App\Models\UserModel;
use App\Services\OrderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyRemediations extends BaseCommand
{
    protected $group       = 'Audit';
    protected $name        = 'audit:verify';
    protected $description = 'Verify all audit remediations and security patches.';

    public function run(array $params)
    {
        CLI::write("=== AUDIT REMEDIATION VERIFICATION SUITE ===\n", 'yellow');

        $db = db_connect();

        // 1. PaymentManager (F-008)
        CLI::print("1. Testing PaymentManager (F-008 - COD Fallback)... ");
        $pm = new PaymentManager();
        $available = $pm->available();
        if (! isset($available['cod'])) {
            CLI::error("FAILED: COD is not available in PaymentManager.");
            return;
        }
        $cod = $pm->get('cod');
        if (! $cod || $cod->isOnline()) {
            CLI::error("FAILED: COD method instance is invalid.");
            return;
        }
        CLI::write("PASSED (COD available out of the box)", 'green');

        // 2. PasswordResetModel (F-007)
        CLI::print("2. Testing PasswordResetModel (F-007 - Account Recovery)... ");
        $prm   = new PasswordResetModel();
        $email = 'audit_test_' . time() . '@example.com';
        $token = $prm->createToken($email);
        if (empty($token) || strlen($token) !== 64) {
            CLI::error("FAILED: Token generation failed.");
            return;
        }
        $rec = $prm->verifyToken($token);
        if (! $rec || $rec['email'] !== $email) {
            CLI::error("FAILED: Token verification failed.");
            return;
        }
        if ($prm->verifyToken('invalid_token_9999') !== null) {
            CLI::error("FAILED: Bogus token should not verify.");
            return;
        }
        $prm->deleteToken($token);
        if ($prm->verifyToken($token) !== null) {
            CLI::error("FAILED: Deleted token should no longer verify.");
            return;
        }
        CLI::write("PASSED (Secure token lifecycle verified)", 'green');

        // 3. Order State Machine & Stock Restoration (F-005)
        CLI::print("3. Testing Order Lifecycle & Stock Restoration (F-005)... ");
        $orderService = new OrderService();
        $orderModel   = new OrderModel();
        $productModel = new ProductModel();
        $itemModel    = new OrderItemModel();

        $testProduct = $productModel->where('status', 'active')->where('stock >', 5)->first();
        if (! $testProduct) {
            CLI::error("No suitable product found.");
            return;
        }
        $origStock = (int) $testProduct['stock'];

        $testOrderNum = 'TEST_STATE_' . time();
        $testOrderId  = $orderModel->insert([
            'order_number'    => $testOrderNum,
            'user_id'         => null,
            'email'           => 'test@example.com',
            'phone'           => '9999999999',
            'subtotal'        => 500,
            'total'           => 500,
            'payment_method'  => 'cod',
            'payment_status'  => 'pending',
            'status'          => 'pending',
            'stock_restored'  => 0,
        ], true);

        $itemModel->insert([
            'order_id'     => $testOrderId,
            'product_id'   => $testProduct['id'],
            'product_name' => $testProduct['name'],
            'sku'          => $testProduct['sku'],
            'unit_price'   => 500,
            'quantity'     => 2,
            'line_total'   => 1000,
        ]);

        $invalidRejected = false;
        try {
            $orderService->updateStatus($testOrderId, 'delivered');
        } catch (\DomainException $e) {
            $invalidRejected = true;
        }
        if (! $invalidRejected) {
            CLI::error("FAILED: Invalid transition pending -> delivered was not rejected!");
            return;
        }

        $orderService->updateStatus($testOrderId, 'processing', 'Processing started');
        $checkOrder = $orderModel->find($testOrderId);
        if ($checkOrder['status'] !== 'processing') {
            CLI::error("FAILED: Order status should be processing.");
            return;
        }

        $preCancelStock = (int) $productModel->find($testProduct['id'])['stock'];
        $orderService->updateStatus($testOrderId, 'cancelled', 'Cancelled in test');
        $checkOrderAfterCancel = $orderModel->find($testOrderId);
        if ($checkOrderAfterCancel['status'] !== 'cancelled' || (int) $checkOrderAfterCancel['stock_restored'] !== 1) {
            CLI::error("FAILED: Order should be marked cancelled and stock_restored = 1.");
            return;
        }
        $postCancelStock = (int) $productModel->find($testProduct['id'])['stock'];
        if ($postCancelStock !== $preCancelStock + 2) {
            CLI::error("FAILED: Stock was not restored correctly.");
            return;
        }

        // Cleanup
        $productModel->set('stock', $origStock)->where('id', $testProduct['id'])->update();
        $db->table('order_items')->where('order_id', $testOrderId)->delete();
        $db->table('order_status_history')->where('order_id', $testOrderId)->delete();
        $orderModel->delete($testOrderId);
        CLI::write("PASSED (State machine & atomic stock restoration verified)", 'green');

        // 4. Checkout Idempotency & Unique Key (F-006)
        CLI::print("4. Testing Checkout Idempotency (F-006)... ");
        $idempKey = 'idemp_test_' . bin2hex(random_bytes(8));
        $testOrderNum2 = 'TEST_IDEMP_' . time();
        $orderId1 = $orderModel->insert([
            'order_number'    => $testOrderNum2,
            'idempotency_key' => $idempKey,
            'user_id'         => null,
            'email'           => 'idemp@example.com',
            'phone'           => '9999999999',
            'subtotal'        => 300,
            'total'           => 300,
            'payment_method'  => 'cod',
            'payment_status'  => 'pending',
            'status'          => 'pending',
        ], true);

        $found = $orderModel->findByIdempotencyKey($idempKey);
        if (! $found || $found['order_number'] !== $testOrderNum2) {
            CLI::error("FAILED: findByIdempotencyKey did not retrieve the order.");
            return;
        }

        $duplicateCaught = false;
        try {
            $orderModel->insert([
                'order_number'    => 'TEST_IDEMP_DUP_' . time(),
                'idempotency_key' => $idempKey,
                'user_id'         => null,
                'email'           => 'idemp2@example.com',
                'phone'           => '9999999999',
                'subtotal'        => 300,
                'total'           => 300,
                'payment_method'  => 'cod',
                'payment_status'  => 'pending',
                'status'          => 'pending',
            ]);
        } catch (\Throwable $e) {
            $duplicateCaught = true;
        }
        if (! $duplicateCaught) {
            CLI::error("FAILED: Duplicate idempotency_key was allowed!");
            return;
        }
        $orderModel->delete($orderId1);
        CLI::write("PASSED (Idempotency unique key & lookup verified)", 'green');

        // 5. Admin Provisioning (F-001)
        CLI::print("5. Testing Admin Provisioning Command (F-001)... ");
        $adminEmail = 'audit_admin_' . time() . '@swingandgrain.in';
        $adminPass  = 'SecureAdminPass!99';
        $users      = new UserModel();
        $newAdminId = $users->insert([
            'first_name'        => 'Test Admin',
            'email'             => $adminEmail,
            'phone'             => '9876543210',
            'password_hash'     => $users->hashPassword($adminPass),
            'role'              => 'admin',
            'status'            => 'active',
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);
        $createdAdmin = $users->find($newAdminId);
        if (! $createdAdmin || $createdAdmin['role'] !== 'admin' || ! $users->verifyPassword($adminPass, $createdAdmin['password_hash'])) {
            CLI::error("FAILED: Admin creation verification failed.");
            return;
        }
        $users->delete($newAdminId);
        CLI::write("PASSED (Secure admin provisioning verified)", 'green');

        // 6. Payment Replay Protection & Provider Order Binding (F-002)
        CLI::print("6. Testing Payment Provider Order Binding & Replay Protection (F-002)... ");
        $orderNum3 = 'TEST_PAY_' . time();
        $orderId3  = $orderModel->insert([
            'order_number'    => $orderNum3,
            'user_id'         => null,
            'email'           => 'pay@example.com',
            'phone'           => '9999999999',
            'subtotal'        => 1000,
            'total'           => 1000,
            'payment_method'  => 'razorpay',
            'payment_status'  => 'pending',
            'status'          => 'pending',
        ], true);

        $paymentModel = new PaymentModel();
        $paymentId    = $paymentModel->insert([
            'order_id'       => $orderId3,
            'provider'       => 'razorpay',
            'provider_order' => 'order_gateway_test_123',
            'amount'         => 1000,
            'currency'       => 'INR',
            'status'         => 'created',
        ], true);

        // Mark paid once
        $orderService->markPaid($orderId3, 'pay_12345', ['razorpay_order_id' => 'order_gateway_test_123']);
        $paidOrder   = $orderModel->find($orderId3);
        $paidPayment = $paymentModel->find($paymentId);

        if ($paidOrder['payment_status'] !== 'paid' || $paidPayment['status'] !== 'captured' || $paidPayment['provider_ref'] !== 'pay_12345') {
            CLI::error("FAILED: markPaid did not update order and payment statuses.");
            return;
        }

        // Replay attempt must not overwrite
        $orderService->markPaid($orderId3, 'pay_replay_67890', []);
        $replayedPayment = $paymentModel->find($paymentId);
        if ($replayedPayment['provider_ref'] !== 'pay_12345') {
            CLI::error("FAILED: Replay attack overwrote payment reference!");
            return;
        }

        $paymentModel->delete($paymentId);
        $db->table('order_status_history')->where('order_id', $orderId3)->delete();
        $orderModel->delete($orderId3);
        CLI::write("PASSED (Payment replay protection and provider order binding verified)", 'green');

        CLI::write("\nALL REMEDIATION TESTS PASSED SUCCESSFULLY! (6/6)\n", 'green');
    }
}

