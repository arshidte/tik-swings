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
     * Create an order from the current cart snapshot for a user.
     *
     * @throws \RuntimeException on empty cart or stock failure.
     */
    public function createFromCart(int $userId, array $customer, array $shipping, string $paymentMethod, ?string $note = null): array
    {
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

            if ($product['stock_status'] !== 'made_to_order' && $stock > 0 && $qty > $stock) {
                throw new \RuntimeException('Only ' . $stock . ' left of “' . $product['name'] . '”.');
            }

            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;
            $verified[] = compact('product', 'variant', 'unitPrice', 'qty', 'lineTotal') + ['line' => $line];
        }

        // Re-verify coupon + shipping server-side.
        [$discount, $couponRow] = $this->verifyCoupon($subtotal);
        $shippingCost = $cart->shippingFor($subtotal);
        $total        = max(0, $subtotal - $discount) + $shippingCost;

        $orders = new OrderModel();
        $orderNumber = $orders->generateOrderNumber();
        $orderId = $orders->insert([
            'order_number'     => $orderNumber,
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
            'notes'            => $note,
        ], true);

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

            // Decrement stock.
            if ($v['variant']) {
                $variants->set('stock', 'stock - ' . (int) $v['qty'], false)->where('id', $v['variant']['id'])->where('stock >=', $v['qty'])->update();
            } else {
                $products->set('stock', 'stock - ' . (int) $v['qty'], false)->where('id', $v['product']['id'])->where('stock >=', $v['qty'])->update();
            }
        }

        // Record coupon usage.
        if ($couponRow) {
            (new CouponModel())->set('used_count', 'used_count + 1', false)->where('id', $couponRow['id'])->update();
            $db->table('coupon_usages')->insert([
                'coupon_id' => $couponRow['id'], 'user_id' => $userId, 'order_id' => $orderId,
                'discount' => $discount, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->addHistory($orderId, 'pending', 'Order placed');

        // Payment record.
        (new PaymentModel())->insert([
            'order_id' => $orderId, 'provider' => $paymentMethod, 'amount' => $total,
            'currency' => 'INR', 'status' => 'created',
        ]);

        $db->transComplete();
        if ($db->transStatus() === false) {
            throw new \RuntimeException('We could not place your order. Please try again.');
        }

        return $orders->find($orderId);
    }

    public function markPaid(int $orderId, string $providerRef = '', array $payload = []): void
    {
        (new OrderModel())->update($orderId, ['payment_status' => 'paid', 'status' => 'confirmed']);
        (new PaymentModel())->where('order_id', $orderId)->set([
            'status' => 'captured', 'provider_ref' => $providerRef, 'payload' => json_encode($payload),
        ])->update();
        $this->addHistory($orderId, 'confirmed', 'Payment received');
    }

    public function confirmCod(int $orderId): void
    {
        (new OrderModel())->update($orderId, ['status' => 'confirmed']);
        $this->addHistory($orderId, 'confirmed', 'Order confirmed (pay on delivery)');
    }

    public function updateStatus(int $orderId, string $status, ?string $note = null, ?int $adminId = null): void
    {
        (new OrderModel())->update($orderId, ['status' => $status]);
        $this->addHistory($orderId, $status, $note, $adminId);
    }

    private function addHistory(int $orderId, string $status, ?string $note = null, ?int $adminId = null): void
    {
        (new OrderStatusHistoryModel())->insert([
            'order_id' => $orderId, 'status' => $status, 'note' => $note, 'created_by' => $adminId,
        ]);
    }

    /**
     * @return array{0: float, 1: array|null}
     */
    private function verifyCoupon(float $subtotal): array
    {
        $code = session()->get('coupon');
        if (! $code) {
            return [0.0, null];
        }
        $coupon = (new CouponModel())->findActiveByCode($code);
        if (! $coupon || $subtotal < (float) $coupon['min_order']) {
            return [0.0, null];
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
