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
            $variant   = $line['variant_id'] ? $variants->find($line['variant_id']) : null;
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
                'image'        => $v['line']['image'] ?? null,
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
     * Mark an order paid (F-002). Rejects replayed requests if order is already paid.
     */
    public function markPaid(int $orderId, string $providerRef = '', array $payload = []): void
    {
        $orders = new OrderModel();
        $order  = $orders->find($orderId);
        if (! $order) {
            throw new \RuntimeException('Order not found.');
        }

        // Replay protection: if already paid, do not re-process
        if ($order['payment_status'] === 'paid') {
            return;
        }

        $db = db_connect();
        $db->transStart();

        $orders->update($orderId, ['payment_status' => 'paid', 'status' => 'confirmed']);
        (new PaymentModel())->where('order_id', $orderId)->set([
            'status'       => 'captured',
            'provider_ref' => $providerRef,
            'payload'      => json_encode($payload),
        ])->update();

        $this->addHistory($orderId, 'confirmed', 'Payment received' . ($providerRef ? ' (Ref: ' . $providerRef . ')' : ''));

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('Failed to record payment confirmation.');
        }
    }

    public function confirmCod(int $orderId): void
    {
        (new OrderModel())->update($orderId, ['status' => 'confirmed']);
        $this->addHistory($orderId, 'confirmed', 'Order confirmed (pay on delivery)');
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

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
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
        $variants = new ProductVariantModel();

        foreach ($items as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }
            if (! empty($item['variant_id'])) {
                $variants->set('stock', 'stock + ' . $qty, false)->where('id', $item['variant_id'])->update();
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
