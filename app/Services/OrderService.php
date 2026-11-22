<?php

namespace App\Services;

use App\Models\CouponModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\PaymentModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

/**
 * Order creation and lifecycle (§32, §33). Prices, stock and coupons are all
 * re-verified from the database here — nothing from the client is trusted (§40).
 */
class OrderService
{
    /**
     * Allowed order state transitions (F-005 state machine).
     */
    public const ALLOWED_TRANSITIONS = [
        'pending'          => ['confirmed', 'processing', 'cancelled'],
        'confirmed'        => ['processing', 'cancelled'],
        'processing'       => ['shipped', 'cancelled', 'refunded'],
        'shipped'          => ['out_for_delivery', 'delivered', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled'],
        'delivered'        => ['refunded'],
        'cancelled'        => [],
        'refunded'         => [],
    ];

    /**
     * Minutes an unpaid online order holds its stock. After that it is
     * cancelled and the stock goes back (releaseUnpaidOnlineOrders()).
     */
    public const UNPAID_HOLD_MINUTES = 30;

    /**
     * Statuses an admin may move this order to. A cancelled order that was
     * nevertheless paid (a payment that arrived after its hold expired and the
     * items had sold out) can still be marked refunded.
     *
     * @return list<string>
     */
    public static function nextStatuses(array $order): array
    {
        if ($order['status'] === 'cancelled' && $order['payment_status'] === 'paid') {
            return ['refunded'];
        }

        return self::ALLOWED_TRANSITIONS[$order['status']] ?? [];
    }

    /**
     * Cancel unpaid online orders older than the hold window and return their
     * stock. Each order is claimed with a conditional update, so a payment
     * confirmed at the same moment wins and that order is left alone.
     *
     * @return int number of orders released
     */
    public function releaseUnpaidOnlineOrders(int $minutes = self::UNPAID_HOLD_MINUTES): int
    {
        $db     = db_connect();
        $cutoff = date('Y-m-d H:i:s', time() - $minutes * 60);
        $ids    = array_column($db->table('orders')->select('id')
            ->where('status', 'pending')->where('payment_status', 'pending')
            ->where('payment_method !=', 'cod')->where('created_at <', $cutoff)
            ->get()->getResultArray(), 'id');

        $released = 0;
        foreach ($ids as $id) {
            $db->transStart();
            $db->table('orders')->where('id', $id)
                ->where('status', 'pending')->where('payment_status', 'pending')
                ->update(['status' => 'cancelled', 'stock_restored' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
            if ($db->affectedRows() === 0) {
                $db->transComplete();

                continue;
            }
            $this->restoreStock((int) $id);
            $this->addHistory((int) $id, 'cancelled', "Payment not completed within {$minutes} minutes; items released back to stock.");
            $db->transComplete();

            if ($db->transStatus()) {
                $released++;
            } else {
                log_message('error', 'Releasing unpaid order {id} failed.', ['id' => $id]);
            }
        }

        return $released;
    }

    /**
     * releaseUnpaidOnlineOrders() at most once a minute, for page loads that
     * show stock (a cron job running `php spark orders:release-unpaid` is the
     * primary trigger).
     */
    public function releaseUnpaidOnlineOrdersThrottled(): void
    {
        $cache = cache();
        if ($cache->get('unpaid_orders_released_at')) {
            return;
        }
        $cache->save('unpaid_orders_released_at', time(), 60);
        $this->releaseUnpaidOnlineOrders();
    }

    /**
     * Create an order from the current cart snapshot for a user.
     * Enforces server-side idempotency (F-006) and atomic inventory / coupon reservation (F-003, F-004).
     *
     * @throws \RuntimeException on empty cart or stock failure.
     */
    public function createFromCart(
        ?int $userId,
        array $customer,
        array $shipping,
        string $paymentMethod,
        ?string $note = null,
        ?string $idempotencyKey = null
    ): array {
        $orders = new OrderModel();

        // Free stock held by abandoned online payments before checking stock.
        // Runs first so an expired order found by its idempotency key below is
        // returned as cancelled (the caller then asks the customer to re-order).
        $this->releaseUnpaidOnlineOrders();

        // 1. Check idempotency key before any operations (F-006).
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $existing = $orders->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                return $existing;
            }
        }

        $cart     = new CartService();
        $snapshot = $cart->snapshot();
        if (empty($snapshot['items'])) {
            throw new \RuntimeException('Your cart is empty.');
        }

        $db = db_connect();
        $db->transStart();

        $products = new ProductModel();
        $variants = new ProductVariantModel();

        // Re-verify each line server-side against live data.
        $subtotal = 0;
        $verified = [];
        foreach ($snapshot['items'] as $line) {
            $product = $products->find($line['product_id']);
            if (! $product || $product['status'] !== 'active') {
                throw new \RuntimeException('“' . ($line['name'] ?? 'An item') . '” is no longer available.');
            }
            $variant = $line['variant_id'] ? $variants->find($line['variant_id']) : null;
            if ((int) $product['has_variants'] === 1
                && (! $variant || (int) $variant['product_id'] !== (int) $product['id'] || $variant['status'] !== 'active')) {
                throw new \RuntimeException('The option you chose for “' . $product['name'] . '” is no longer available. Please remove it and add it again.');
            }
            $unitPrice = (float) ($variant['price'] ?? $product['price']); // trusted price
            $stock     = $variant ? (int) $variant['stock'] : (int) $product['stock'];
            $qty       = (int) $line['quantity'];

            // F-003: Strict zero-stock and insufficient-stock check for non-made-to-order items.
            if ($product['stock_status'] !== 'made_to_order') {
                if ($stock <= 0) {
                    throw new \RuntimeException('“' . $product['name'] . '” is currently out of stock.');
                }
                if ($qty > $stock) {
                    throw new \RuntimeException('Only ' . $stock . ' left of “' . $product['name'] . '”.');
                }
            }

            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;
            $verified[] = compact('product', 'variant', 'unitPrice', 'qty', 'lineTotal') + ['line' => $line];
        }

        // F-004: Re-verify coupon limits, dates, and per-user usage server-side.
        [$discount, $couponRow] = $this->verifyCoupon($subtotal, $userId, $customer['email'] ?? '');
        $shippingCost = $cart->shippingFor($subtotal);
        $total        = max(0, $subtotal - $discount) + $shippingCost;

        $orderNumber = $orders->generateOrderNumber();

        try {
            $orderId = $orders->insert([
                'order_number'     => $orderNumber,
                'idempotency_key'  => $idempotencyKey ?: null,
                'user_id'          => $userId,
                'email'            => $customer['email'],
                'phone'            => $customer['phone'],
                'billing_address'  => json_encode($shipping),
                'shipping_address' => json_encode($shipping),
                'subtotal'         => $subtotal,
                'discount'         => $discount,
                'shipping'         => $shippingCost,
                'tax'              => 0,
                'total'            => $total,
                'coupon_code'      => $couponRow['code'] ?? null,
                'currency'         => 'INR',
                'payment_method'   => $paymentMethod,
                'payment_status'   => 'pending',
                'status'           => 'pending',
                'stock_restored'   => 0,
                'notes'            => $note,
            ], true);
        } catch (\Throwable $e) {
            // Concurrent submission race condition handling for unique idempotency key
            if ($idempotencyKey && $existing = $orders->findByIdempotencyKey($idempotencyKey)) {
                $db->transRollback();
                return $existing;
            }
            $db->transRollback();
            throw $e;
        }

        // Order items with immutable product/price snapshots (§32).
        $orderItems = new OrderItemModel();
        foreach ($verified as $v) {
            $orderItems->insert([
                'order_id'     => $orderId,
                'product_id'   => $v['product']['id'],
                'variant_id'   => $v['variant']['id'] ?? null,
                'product_name' => $v['product']['name'],
                'variant_name' => $v['variant']['name'] ?? null,
                'sku'          => $v['variant']['sku'] ?? $v['product']['sku'],
                'image'        => $v['line']['image_path'] ?? null,
                'options'      => ! empty($v['line']['options']) ? json_encode($v['line']['options']) : null,
                'unit_price'   => $v['unitPrice'],
                'quantity'     => $v['qty'],
                'line_total'   => $v['lineTotal'],
            ]);

            // F-003: Atomic conditional stock decrement with affected-rows check.
            if ($v['variant']) {
                $variantQuery = $variants->set('stock', 'stock - ' . (int) $v['qty'], false)
                    ->where('id', $v['variant']['id']);
                if ($v['product']['stock_status'] !== 'made_to_order') {
                    $variantQuery->where('stock >=', (int) $v['qty']);
                }
                $variantQuery->update();
            } else {
                $productQuery = $products->set('stock', 'stock - ' . (int) $v['qty'], false)
                    ->where('id', $v['product']['id']);
                if ($v['product']['stock_status'] !== 'made_to_order') {
                    $productQuery->where('stock >=', (int) $v['qty']);
                }
                $productQuery->update();
            }

            if ($v['product']['stock_status'] !== 'made_to_order' && $db->affectedRows() === 0) {
                $db->transRollback();
                throw new \RuntimeException('“' . $v['product']['name'] . '” sold out during checkout. Please adjust your cart.');
            }
        }

        // F-004: Atomic conditional coupon usage increment with limit guard.
        if ($couponRow) {
            $couponQuery = $db->table('coupons')
                ->set('used_count', 'used_count + 1', false)
                ->where('id', $couponRow['id']);
            if ($couponRow['usage_limit'] !== null) {
                $couponQuery->where('used_count <', (int) $couponRow['usage_limit']);
            }
            $couponQuery->update();

            if ($couponRow['usage_limit'] !== null && $db->affectedRows() === 0) {
                $db->transRollback();
                throw new \RuntimeException('The coupon “' . $couponRow['code'] . '” reached its maximum usage limit.');
            }

            $db->table('coupon_usages')->insert([
                'coupon_id'  => $couponRow['id'],
                'user_id'    => $userId,
                'order_id'   => $orderId,
                'discount'   => $discount,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->addHistory($orderId, 'pending', 'Order placed');

        // Payment record.
        (new PaymentModel())->insert([
            'order_id' => $orderId,
            'provider' => $paymentMethod,
            'amount'   => $total,
            'currency' => 'INR',
            'status'   => 'created',
        ]);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('We could not place your order. Please try again.');
        }

        return $orders->find($orderId);
    }

    /**
     * Mark an order paid (F-002). Returns true only for the call that actually
     * confirmed it, so callers can fire one-time side effects (e.g. SMS).
     * Replays and concurrent duplicates return false and change nothing.
     *
     * A payment can still land after the unpaid hold expired and the stock was
     * released. Then the items are reserved again if still in stock and the
     * order is confirmed; if they sold out, the payment is recorded, the order
     * stays cancelled with a "refund this payment" note, and this returns false.
     */
    public function markPaid(int $orderId, string $providerRef = '', array $payload = []): bool
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order) {
            throw new \RuntimeException('Order not found.');
        }

        // Replay protection: if already paid, do not re-process
        if ($order['payment_status'] === 'paid') {
            return false;
        }

        $expired = $order['status'] === 'cancelled' && (int) $order['stock_restored'] === 1;

        $db = db_connect();
        $db->transStart();

        $reserved = $expired ? $this->reserveStock($orderId) : true;
        $status   = $reserved ? 'confirmed' : 'cancelled';
        $update   = ['payment_status' => 'paid', 'status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($expired && $reserved) {
            $update['stock_restored'] = 0;
        }

        // Conditional update: only one concurrent caller can flip the order to paid.
        $db->table('orders')
            ->where('id', $orderId)
            ->where('payment_status !=', 'paid')
            ->update($update);

        if ($db->affectedRows() === 0) {
            $db->transRollback(); // also undoes any stock reserved above

            return false;
        }

        (new PaymentModel())->where('order_id', $orderId)->set([
            'status'       => 'captured',
            'provider_ref' => $providerRef,
            'payload'      => json_encode($payload),
        ])->update();

        $ref = $providerRef ? ' (Ref: ' . $providerRef . ')' : '';
        $this->addHistory($orderId, $status, match (true) {
            ! $expired => 'Payment received' . $ref,
            $reserved  => 'Payment received after the ' . self::UNPAID_HOLD_MINUTES . '-minute hold expired' . $ref . '; items reserved again.',
            default    => 'Payment received after the ' . self::UNPAID_HOLD_MINUTES . '-minute hold expired' . $ref . ', but the items had sold out. Refund this payment.',
        });

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to record payment confirmation.');
        }

        return $reserved;
    }

    /**
     * Take an order's items out of stock again (all or nothing). Made-to-order
     * products may go below zero, matching checkout.
     */
    private function reserveStock(int $orderId): bool
    {
        $db       = db_connect();
        $products = new ProductModel();
        $taken    = [];

        foreach ((new OrderItemModel())->forOrder($orderId) as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }
            $product = $item['product_id'] ? $products->withDeleted()->find($item['product_id']) : null;
            [$table, $id] = $item['variant_id'] ? ['product_variants', $item['variant_id']] : ['products', $item['product_id']];

            $query = $db->table($table)->set('stock', 'stock - ' . $qty, false)->where('id', $id);
            if (! $product || $product['stock_status'] !== 'made_to_order') {
                $query->where('stock >=', $qty);
            }
            $query->update();

            if ($db->affectedRows() === 0) {
                foreach ($taken as [$t, $tid, $n]) {
                    $db->table($t)->set('stock', 'stock + ' . $n, false)->where('id', $tid)->update();
                }

                return false;
            }
            $taken[] = [$table, $id, $qty];
        }

        return true;
    }

    /**
     * Confirm a pay-on-delivery order. Returns true only when this call moved it
     * from pending to confirmed (retries/duplicates return false).
     */
    public function confirmCod(int $orderId): bool
    {
        $db = db_connect();
        $db->table('orders')
            ->where('id', $orderId)
            ->where('status', 'pending')
            ->update(['status' => 'confirmed', 'updated_at' => date('Y-m-d H:i:s')]);

        if ($db->affectedRows() === 0) {
            return false;
        }

        $this->addHistory($orderId, 'confirmed', 'Order confirmed (pay on delivery)');

        return true;
    }

    /**
     * Update order status following an explicit state machine (F-005).
     * Restores stock atomically if transitioning to cancelled/refunded.
     */
    public function updateStatus(int $orderId, string $status, ?string $note = null, ?int $adminId = null): void
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order) {
            throw new \RuntimeException('Order not found.');
        }

        $currentStatus = $order['status'];
        if ($currentStatus === $status) {
            return;
        }

        $allowed = self::nextStatuses($order);
        if (! in_array($status, $allowed, true)) {
            throw new \DomainException("Cannot change order status from '{$currentStatus}' to '{$status}'.");
        }

        $db = db_connect();
        $db->transStart();

        $updateData = ['status' => $status];

        // Stock recovery for cancelled or refunded orders (F-005)
        if (in_array($status, ['cancelled', 'refunded'], true)) {
            if ((int) ($order['stock_restored'] ?? 0) === 0) {
                $this->restoreStock($orderId);
                $updateData['stock_restored'] = 1;
            }

            if ($order['payment_status'] === 'paid') {
                $updateData['payment_status'] = 'refunded';
                (new PaymentModel())->where('order_id', $orderId)->set(['status' => 'refunded'])->update();
            }
        }

        $orders->update($orderId, $updateData);
        $this->addHistory($orderId, $status, $note, $adminId);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to update order status.');
        }
    }

    /**
     * Restore inventory for cancelled / refunded order items (F-005).
     */
    private function restoreStock(int $orderId): void
    {
        $items    = (new OrderItemModel())->forOrder($orderId);
        $products = new ProductModel();
        $db       = db_connect();

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }
            if (! empty($item['variant_id'])) {
                $db->table('product_variants')->set('stock', 'stock + ' . $qty, false)
                    ->where('id', $item['variant_id'])->update();
                // Variant IDs from before variants were updated in place may no
                // longer exist; match the product's current variant by SKU.
                if ($db->affectedRows() === 0 && ! empty($item['product_id']) && ! empty($item['sku'])) {
                    $db->table('product_variants')->set('stock', 'stock + ' . $qty, false)
                        ->where('product_id', $item['product_id'])->where('sku', $item['sku'])->update();
                }
            } elseif (! empty($item['product_id'])) {
                $products->set('stock', 'stock + ' . $qty, false)->where('id', $item['product_id'])->update();
            }
        }
    }

    private function addHistory(int $orderId, string $status, ?string $note = null, ?int $adminId = null): void
    {
        (new OrderStatusHistoryModel())->insert([
            'order_id' => $orderId, 'status' => $status, 'note' => $note, 'created_by' => $adminId,
        ]);
    }

    /**
     * Re-verify coupon eligibility, dates, global limits, and per-user limits (F-004).
     *
     * @return array{0: float, 1: array|null}
     */
    private function verifyCoupon(float $subtotal, ?int $userId = null, string $email = ''): array
    {
        $code = session()->get('coupon');
        if (! $code) {
            return [0.0, null];
        }
        $coupon = (new CouponModel())->findActiveByCode($code);
        if (! $coupon || $subtotal < (float) $coupon['min_order']) {
            return [0.0, null];
        }

        $now = date('Y-m-d H:i:s');
        if (! empty($coupon['starts_at']) && $coupon['starts_at'] > $now) {
            return [0.0, null];
        }
        if (! empty($coupon['expires_at']) && $coupon['expires_at'] < $now) {
            return [0.0, null];
        }

        // Global usage limit check
        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            return [0.0, null];
        }

        // Per-user usage limit check
        if (! empty($coupon['usage_limit_user'])) {
            $db = db_connect();
            $usagesBuilder = $db->table('coupon_usages')->where('coupon_id', $coupon['id']);
            if ($userId) {
                $usagesBuilder->where('user_id', $userId);
            } elseif ($email !== '') {
                // For guest checkouts, check by matching email on orders
                $orders = $db->table('orders')->select('id')->where('email', strtolower(trim($email)))->get()->getResultArray();
                if (! empty($orders)) {
                    $usagesBuilder->whereIn('order_id', array_column($orders, 'id'));
                } else {
                    $usagesBuilder->where('id', 0); // No prior orders for this guest
                }
            }
            if ($usagesBuilder->countAllResults() >= (int) $coupon['usage_limit_user']) {
                return [0.0, null];
            }
        }

        if ($coupon['type'] === 'percent') {
            $discount = $subtotal * ((float) $coupon['value'] / 100);
            if ($coupon['max_discount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
        } else {
            $discount = (float) $coupon['value'];
        }

        return [round(min($discount, $subtotal), 2), $coupon];
    }
}
