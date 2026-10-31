<?php

namespace App\Services;

use App\Models\CartItemModel;
use App\Models\CouponModel;
use App\Models\ProductAddonModel;
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
    /**
     * Add a product (and optionally its selected add-ons) to the cart.
     *
     * Every line is validated BEFORE anything is written, so a bad add-on
     * never leaves the main product half-added. Add-ons follow the main
     * product's quantity (one chain per swing).
     *
     * @param array $addons [['product_id' => int, 'variant_id' => ?int], ...]
     */
    public function add(int $productId, ?int $variantId, int $quantity, ?array $options = null, array $addons = []): array
    {
        $quantity = max(1, min(99, $quantity));
        $lines    = [$this->resolveLine($productId, $variantId, false) + ['options' => $options]];

        foreach (array_slice($addons, 0, 10) as $a) {
            $aid = (int) ($a['product_id'] ?? 0);
            if ($aid <= 0 || $aid === $productId) {
                continue;
            }
            $avid    = ! empty($a['variant_id']) ? (int) $a['variant_id'] : null;
            $lines[] = $this->resolveLine($aid, $avid, true) + ['options' => null];
        }

        $db = db_connect();
        $db->transStart();
        foreach ($lines as $line) {
            $this->writeLine($line['product_id'], $line['variant_id'], $line['stock'], $quantity, $line['options']);
        }
        $db->transComplete();

        return $this->snapshot(true);
    }

    /**
     * Validate a product/variant pair and resolve its default variant + stock.
     *
     * @return array{product_id: int, variant_id: ?int, stock: int}
     */
    private function resolveLine(int $productId, ?int $variantId, bool $isAddon): array
    {
        $product = $this->products->where('status', 'active')->find($productId);
        if (! $product || ($isAddon && $product['stock_status'] === 'out_of_stock')) {
            throw new \RuntimeException($isAddon && $product
                ? $product['name'] . ' is currently unavailable.'
                : ($isAddon ? 'A selected add-on is currently unavailable.' : 'This swing is currently unavailable.'));
        }

        $variant = null;
        if ($variantId) {
            $variant = $this->variants->where('product_id', $productId)->where('status', 'active')->find($variantId);
            if (! $variant) {
                throw new \RuntimeException($isAddon
                    ? 'The selected option for ' . $product['name'] . ' is unavailable.'
                    : 'Selected option is unavailable.');
            }
        } elseif ((int) $product['has_variants'] === 1) {
            $variant   = $this->variants->where('product_id', $productId)->where('is_default', 1)->first()
                ?? $this->variants->where('product_id', $productId)->first();
            $variantId = isset($variant['id']) ? (int) $variant['id'] : null;
        }

        $stock = $variant ? (int) $variant['stock'] : (int) $product['stock'];
        if ($isAddon && $stock <= 0 && $product['stock_status'] !== 'made_to_order') {
            throw new \RuntimeException($product['name'] . ' is out of stock.');
        }

        return ['product_id' => $productId, 'variant_id' => $variantId, 'stock' => $stock];
    }

    /** Insert or top-up a single cart line (DB for users, session for guests). */
    private function writeLine(int $productId, ?int $variantId, int $stock, int $quantity, ?array $options): void
    {
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

        return $this->snapshot(true);
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

        return $this->snapshot(true);
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
     *
     * Lines that are mapped add-ons of another product in the cart carry
     * `addon_of` (the parent line id) and are ordered right after their
     * parent. With $withSuggestions, `suggestions` lists add-ons of cart
     * products that are not in the cart yet ("Don't forget" / "Pairs well with").
     */
    public function snapshot(bool $withSuggestions = false): array
    {
        $lines = $this->rawLines();
        if ($lines === []) {
            return ['count' => 0, 'subtotal' => 0, 'items' => [], 'discount' => 0, 'coupon' => null, 'shipping' => 0, 'total' => 0, 'suggestions' => []];
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
                'addon_of'   => null,
            ];
        }

        $addons = new ProductAddonModel();
        $items  = $this->groupAddonLines($items, $addons);

        [$discount, $coupon] = $this->resolveDiscount($subtotal);
        $shipping = $this->shippingFor($subtotal);
        $total    = max(0, $subtotal - $discount) + $shipping;

        $inCart = array_values(array_unique(array_column($items, 'product_id')));

        // Suggested add-ons carry their parent's quantity, so "+ Add" can add
        // one per swing in the cart.
        $suggestions = [];
        if ($withSuggestions) {
            $qtyByProduct = [];
            foreach ($items as $it) {
                $qtyByProduct[$it['product_id']] = ($qtyByProduct[$it['product_id']] ?? 0) + $it['quantity'];
            }
            foreach ($addons->storefrontFor($inCart, $inCart, 3) as $s) {
                $s['parent_qty'] = $qtyByProduct[$s['parent_id']] ?? 1;
                $suggestions[]   = $s;
            }
        }

        return [
            'count'       => $count,
            'subtotal'    => $subtotal,
            'discount'    => $discount,
            'coupon'      => $coupon,
            'shipping'    => $shipping,
            'total'       => $total,
            'items'       => $items,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Attach add-on lines to the line of their parent product (when the parent
     * is in the cart) and order them directly after it. Purely presentational:
     * prices are unaffected, and an add-on whose parent was removed simply
     * becomes a normal line again.
     */
    private function groupAddonLines(array $items, ProductAddonModel $addons): array
    {
        $parents = $addons->parentsWithin(array_values(array_unique(array_column($items, 'product_id'))));
        if ($parents === []) {
            return $items;
        }

        // First cart line per product, among lines that are not add-ons themselves.
        $isAddon     = array_fill_keys(array_keys($parents), true);
        $lineOfRoot  = [];
        foreach ($items as $it) {
            if (! isset($isAddon[$it['product_id']])) {
                $lineOfRoot[$it['product_id']] ??= $it['id'];
            }
        }

        $children = [];
        foreach ($items as &$it) {
            foreach ($parents[$it['product_id']] ?? [] as $parentProductId) {
                if (isset($lineOfRoot[$parentProductId])) {
                    $it['addon_of']                             = $lineOfRoot[$parentProductId];
                    $children[$lineOfRoot[$parentProductId]][] = $it['id'];
                    break;
                }
            }
        }
        unset($it);

        $byId    = array_column($items, null, 'id');
        $ordered = [];
        foreach ($items as $it) {
            if ($it['addon_of'] !== null) {
                continue;
            }
            $ordered[] = $it;
            foreach ($children[$it['id']] ?? [] as $childId) {
                $ordered[] = $byId[$childId];
            }
        }

        return $ordered;
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
