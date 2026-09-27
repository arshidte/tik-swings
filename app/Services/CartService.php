<?php

namespace App\Services;

use App\Models\CartItemModel;
use App\Models\CouponModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;

/**
 * Cart business logic (§27–§28, §40).
 *
 * Logged-in users are backed by the cart_items table; guests use the session.
 * Prices, stock and coupons are ALWAYS resolved from the database — never
 * trusted from the client.
 */
class CartService
{
    private CartItemModel $items;
    private ProductModel $products;
    private ProductVariantModel $variants;
    private $session;

    public function __construct()
    {
        $this->items    = new CartItemModel();
        $this->products = new ProductModel();
        $this->variants = new ProductVariantModel();
        $this->session  = session();
    }

    private function userId(): ?int
    {
        $id = $this->session->get('user_id');

        return $id ? (int) $id : null;
    }

    // ------------------------------------------------------------------ add
    public function add(int $productId, ?int $variantId, int $quantity, ?array $options = null): array
    {
        $quantity = max(1, min(99, $quantity));
        $product  = $this->products->where('status', 'active')->find($productId);
        if (! $product) {
            throw new \RuntimeException('This swing is currently unavailable.');
        }

        $variant = null;
        if ($variantId) {
            $variant = $this->variants->where('product_id', $productId)->find($variantId);
            if (! $variant) {
                throw new \RuntimeException('Selected option is unavailable.');
            }
        } elseif ((int) $product['has_variants'] === 1) {
            $variant   = $this->variants->where('product_id', $productId)->where('is_default', 1)->first()
                ?? $this->variants->where('product_id', $productId)->first();
            $variantId = $variant['id'] ?? null;
        }

        $stock = $variant ? (int) $variant['stock'] : (int) $product['stock'];

        if ($this->userId()) {
            $existing = $this->items->where('user_id', $this->userId())
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->first();
            $newQty = ($existing ? (int) $existing['quantity'] : 0) + $quantity;
            if ($stock > 0 && $newQty > $stock) {
                $newQty = $stock;
            }
            if ($existing) {
                $this->items->update($existing['id'], ['quantity' => $newQty, 'options' => $options ? json_encode($options) : null]);
            } else {
                $this->items->insert([
                    'user_id' => $this->userId(), 'product_id' => $productId, 'variant_id' => $variantId,
                    'quantity' => $newQty, 'options' => $options ? json_encode($options) : null,
                ]);
            }
        } else {
            $cart = $this->session->get('cart') ?? [];
            $key  = $productId . ':' . ($variantId ?? 0) . ':' . md5(json_encode($options ?? []));
            $cur  = $cart[$key]['quantity'] ?? 0;
            $qty  = $cur + $quantity;
            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
            }
            $cart[$key] = [
                'product_id' => $productId, 'variant_id' => $variantId,
                'quantity' => $qty, 'options' => $options,
            ];
            $this->session->set('cart', $cart);
        }

        return $this->snapshot();
    }

    // -------------------------------------------------------------- update
    public function updateQuantity(string $lineId, int $quantity): array
    {
        $quantity = max(0, min(99, $quantity));

        if ($this->userId()) {
            $row = $this->items->where('user_id', $this->userId())->find((int) $lineId);
            if ($row) {
                if ($quantity === 0) {
                    $this->items->delete($row['id']);
                } else {
                    $stock = $this->lineStock($row['product_id'], $row['variant_id']);
                    $this->items->update($row['id'], ['quantity' => $stock > 0 ? min($quantity, $stock) : $quantity]);
                }
            }
        } else {
            $cart = $this->session->get('cart') ?? [];
            if (isset($cart[$lineId])) {
                if ($quantity === 0) {
                    unset($cart[$lineId]);
                } else {
                    $stock                    = $this->lineStock($cart[$lineId]['product_id'], $cart[$lineId]['variant_id']);
                    $cart[$lineId]['quantity'] = $stock > 0 ? min($quantity, $stock) : $quantity;
                }
                $this->session->set('cart', $cart);
            }
        }

        return $this->snapshot();
    }

    public function remove(string $lineId): array
    {
        if ($this->userId()) {
            $row = $this->items->where('user_id', $this->userId())->find((int) $lineId);
            if ($row) {
                $this->items->delete($row['id']);
            }
        } else {
            $cart = $this->session->get('cart') ?? [];
            unset($cart[$lineId]);
            $this->session->set('cart', $cart);
        }

        return $this->snapshot();
    }

    public function clear(): void
    {
        if ($this->userId()) {
            $this->items->where('user_id', $this->userId())->delete();
        }
        $this->session->remove('cart');
        $this->session->remove('coupon');
    }

    private function lineStock(int $productId, ?int $variantId): int
    {
        if ($variantId) {
            $v = $this->variants->find($variantId);

            return $v ? (int) $v['stock'] : 0;
        }
        $p = $this->products->find($productId);

        return $p ? (int) $p['stock'] : 0;
    }

    // ------------------------------------------------------------- read raw
    /**
     * Normalised raw lines: [lineId => [product_id, variant_id, quantity, options]]
     */
    private function rawLines(): array
    {
        if ($this->userId()) {
            $rows = $this->items->where('user_id', $this->userId())->orderBy('id', 'ASC')->findAll();
            $out  = [];
            foreach ($rows as $r) {
                $out[(string) $r['id']] = [
                    'product_id' => (int) $r['product_id'],
                    'variant_id' => $r['variant_id'] ? (int) $r['variant_id'] : null,
                    'quantity'   => (int) $r['quantity'],
                    'options'    => $r['options'] ? json_decode($r['options'], true) : null,
                ];
            }

            return $out;
        }

        return $this->session->get('cart') ?? [];
    }

    // ------------------------------------------------------------- snapshot
    /**
     * Full cart snapshot with server-resolved prices and totals.
     */
    public function snapshot(): array
    {
        $lines = $this->rawLines();
        if ($lines === []) {
            return ['count' => 0, 'subtotal' => 0, 'items' => [], 'discount' => 0, 'coupon' => null, 'shipping' => 0, 'total' => 0];
        }

        $productIds = array_values(array_unique(array_map(static fn ($l) => $l['product_id'], $lines)));
        $products   = $this->products->whereIn('id', $productIds)->findAll();
        $products   = array_column($products, null, 'id');

        // primary images in one query
        $imgs = db_connect()->table('product_images')
            ->select('product_id, image')->whereIn('product_id', $productIds)
            ->orderBy('is_primary', 'DESC')->orderBy('sort_order', 'ASC')->get()->getResultArray();
        $imageByProduct = [];
        foreach ($imgs as $im) {
            $imageByProduct[$im['product_id']] ??= $im['image'];
        }

        $variantIds = array_values(array_filter(array_map(static fn ($l) => $l['variant_id'], $lines)));
        $variantMap = [];
        if ($variantIds) {
            foreach ($this->variants->whereIn('id', $variantIds)->findAll() as $v) {
                $variantMap[$v['id']] = $v;
            }
        }

        $items    = [];
        $subtotal = 0;
        $count    = 0;
        foreach ($lines as $lineId => $l) {
            $p = $products[$l['product_id']] ?? null;
            if (! $p) {
                continue;
            }
            $variant   = $l['variant_id'] ? ($variantMap[$l['variant_id']] ?? null) : null;
            $unitPrice = (float) ($variant['price'] ?? $p['price']);
            $qty       = (int) $l['quantity'];
            $lineTotal = $unitPrice * $qty;
            $subtotal += $lineTotal;
            $count    += $qty;

            $items[] = [
                'id'         => (string) $lineId,
                'product_id' => (int) $p['id'],
                'variant_id' => $l['variant_id'],
                'name'       => $p['name'],
                'variant'    => $variant ? $this->variantLabel($variant) : null,
                'sku'        => $variant['sku'] ?? $p['sku'],
                'image'      => product_image($variant['image'] ?? ($imageByProduct[$p['id']] ?? null)),
                'unit_price' => $unitPrice,
                'quantity'   => $qty,
                'line_total' => $lineTotal,
                'options'    => $l['options'],
                'url'        => site_url('product/' . $p['slug']),
                'stock'      => $variant ? (int) $variant['stock'] : (int) $p['stock'],
            ];
        }

        [$discount, $coupon] = $this->resolveDiscount($subtotal);
        $shipping = $this->shippingFor($subtotal);
        $total    = max(0, $subtotal - $discount) + $shipping;

        return [
            'count'    => $count,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'coupon'   => $coupon,
            'shipping' => $shipping,
            'total'    => $total,
            'items'    => $items,
        ];
    }

    private function variantLabel(array $variant): string
    {
        $attrs = (new ProductVariantModel())->forProduct((int) $variant['product_id']);
        foreach ($attrs as $v) {
            if ((int) $v['id'] === (int) $variant['id']) {
                $parts = array_map(static fn ($a) => $a['value'], $v['attributes'] ?? []);

                return $parts ? implode(' · ', $parts) : ($variant['name'] ?? '');
            }
        }

        return $variant['name'] ?? '';
    }

    public function shippingFor(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0;
        }
        $freeOver = (float) setting('free_shipping_over', 25000);
        if ($freeOver > 0 && $subtotal >= $freeOver) {
            return 0;
        }

        return (float) setting('flat_shipping', 1499);
    }

    // -------------------------------------------------------------- coupons
    public function applyCoupon(string $code): array
    {
        $coupon = (new CouponModel())->findActiveByCode($code);
        $snap   = $this->snapshot();
        if (! $coupon) {
            throw new \RuntimeException('That coupon code is not valid.');
        }
        $now = date('Y-m-d H:i:s');
        if (($coupon['starts_at'] && $coupon['starts_at'] > $now) || ($coupon['expires_at'] && $coupon['expires_at'] < $now)) {
            throw new \RuntimeException('That coupon has expired.');
        }
        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            throw new \RuntimeException('That coupon is no longer available.');
        }
        if ((float) $snap['subtotal'] < (float) $coupon['min_order']) {
            throw new \RuntimeException('Add ' . price((float) $coupon['min_order'] - $snap['subtotal']) . ' more to use this coupon.');
        }
        $this->session->set('coupon', strtoupper($code));

        return $this->snapshot();
    }

    public function removeCoupon(): array
    {
        $this->session->remove('coupon');

        return $this->snapshot();
    }

    /**
     * @return array{0: float, 1: array|null}
     */
    private function resolveDiscount(float $subtotal): array
    {
        $code = $this->session->get('coupon');
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
        $discount = min($discount, $subtotal);

        return [round($discount, 2), ['code' => $coupon['code'], 'description' => $coupon['description'], 'amount' => round($discount, 2)]];
    }

    // ---------------------------------------------------------------- merge
    /**
     * Merge a guest session cart into the user's DB cart after login (§29).
     */
    public function mergeGuestIntoUser(int $userId): void
    {
        $cart = $this->session->get('cart') ?? [];
        foreach ($cart as $l) {
            $existing = $this->items->where('user_id', $userId)
                ->where('product_id', $l['product_id'])
                ->where('variant_id', $l['variant_id'])
                ->first();
            if ($existing) {
                $this->items->update($existing['id'], ['quantity' => (int) $existing['quantity'] + (int) $l['quantity']]);
            } else {
                $this->items->insert([
                    'user_id' => $userId, 'product_id' => $l['product_id'], 'variant_id' => $l['variant_id'],
                    'quantity' => $l['quantity'], 'options' => isset($l['options']) ? json_encode($l['options']) : null,
                ]);
            }
        }
        $this->session->remove('cart');
    }

    /**
     * Keep the header badge count in the session for cheap reads.
     */
    public function syncCount(): int
    {
        $count = (int) ($this->snapshot()['count']);
        $this->session->set('cart_count', $count);

        return $count;
    }
}
