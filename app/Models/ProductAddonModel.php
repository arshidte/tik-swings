<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Product → add-on product mapping ("Complete your swing").
 */
class ProductAddonModel extends Model
{
    protected $table         = 'product_addons';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'addon_product_id', 'is_preselected', 'sort_order'];
    protected $useTimestamps = true;

    /** Max add-ons rendered in one storefront block. */
    public const STOREFRONT_LIMIT = 6;

    // ------------------------------------------------------------------ admin
    /**
     * Mapped add-ons for the admin editor (any status, with name/sku/image).
     */
    public function forProductAdmin(int $productId): array
    {
        $rows = $this->db->table('product_addons pa')
            ->select('pa.addon_product_id, pa.is_preselected, pa.sort_order, p.name, p.sku, p.price, p.status, p.has_variants')
            ->join('products p', 'p.id = pa.addon_product_id')
            ->where('pa.product_id', $productId)
            ->where('p.deleted_at', null)
            ->orderBy('pa.sort_order', 'ASC')
            ->get()->getResultArray();

        $images = $this->primaryImages(array_column($rows, 'addon_product_id'));
        foreach ($rows as &$r) {
            $r['image'] = product_image($images[$r['addon_product_id']] ?? null);
        }

        return $rows;
    }

    /**
     * Replace all add-on rows for a product. $rows: [[addon_product_id, is_preselected], ...]
     * in display order. Self-references and duplicates are dropped.
     */
    public function replaceForProduct(int $productId, array $rows): void
    {
        $this->where('product_id', $productId)->delete();

        $seen   = [];
        $insert = [];
        $now    = date('Y-m-d H:i:s');
        foreach (array_values($rows) as $i => $r) {
            $addonId = (int) ($r['addon_product_id'] ?? 0);
            if ($addonId <= 0 || $addonId === $productId || isset($seen[$addonId])) {
                continue;
            }
            $seen[$addonId] = true;
            $insert[]       = [
                'product_id'       => $productId,
                'addon_product_id' => $addonId,
                'is_preselected'   => ! empty($r['is_preselected']) ? 1 : 0,
                'sort_order'       => $i,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }
        if ($insert !== []) {
            $this->db->table('product_addons')->insertBatch($insert);
        }
    }

    // ------------------------------------------------------------- storefront
    /**
     * Map of add-on product id => first parent product id, restricted to the
     * given set of products (used to group cart lines under their parent).
     *
     * @param int[] $productIds
     *
     * @return array<int, int[]> addon_product_id => parent product ids (in mapping order)
     */
    public function parentsWithin(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $rows = $this->select('product_id, addon_product_id')
            ->whereIn('product_id', $productIds)
            ->whereIn('addon_product_id', $productIds)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['addon_product_id']][] = (int) $r['product_id'];
        }

        return $out;
    }

    /**
     * Purchasable add-on payloads for one or more parent products, ready to be
     * rendered by the storefront (PDP block, cart drawer, cart page).
     *
     * Each payload carries everything the client needs to pick a variant and
     * show a live price without another round-trip. Unavailable add-ons are
     * skipped; out-of-stock variants are flagged.
     *
     * @param int[] $parentIds
     * @param int[] $excludeIds product ids to leave out (e.g. already in cart)
     */
    public function storefrontFor(array $parentIds, array $excludeIds = [], int $limit = self::STOREFRONT_LIMIT): array
    {
        if ($parentIds === []) {
            return [];
        }

        $builder = $this->db->table('product_addons pa')
            ->select('pa.product_id AS parent_id, pa.is_preselected, p.*')
            ->join('products p', 'p.id = pa.addon_product_id')
            ->whereIn('pa.product_id', $parentIds)
            ->where('p.status', 'active')
            ->where('p.deleted_at', null)
            ->where('p.stock_status !=', 'out_of_stock')
            ->orderBy('pa.sort_order', 'ASC');
        if ($excludeIds !== []) {
            $builder->whereNotIn('p.id', $excludeIds);
        }
        $rows = $builder->get()->getResultArray();

        // One entry per add-on product even if several parents map it.
        $unique = [];
        foreach ($rows as $r) {
            $unique[(int) $r['id']] ??= $r;
        }
        if ($unique === []) {
            return [];
        }

        $images   = $this->primaryImages(array_keys($unique));
        $variants = new ProductVariantModel();
        $out      = [];

        foreach ($unique as $id => $p) {
            $madeToOrder = $p['stock_status'] === 'made_to_order';
            $payload     = [
                'product_id'     => $id,
                'parent_id'      => (int) $p['parent_id'],
                'name'           => $p['name'],
                'url'            => site_url('product/' . $p['slug']),
                'image'          => product_image($images[$id] ?? null),
                'is_preselected' => (int) $p['is_preselected'] === 1,
                'price'          => (float) $p['price'],
                'variant_id'     => null,
                'groups'         => [],
                'variants'       => [],
            ];

            if ((int) $p['has_variants'] === 1) {
                $list = $variants->forProduct($id);
                if ($list === []) {
                    continue;
                }
                $groups  = [];
                $default = null;
                foreach ($list as $v) {
                    $inStock = (int) $v['stock'] > 0 || $madeToOrder;
                    $labels  = [];
                    $valIds  = [];
                    foreach ($v['attributes'] ?? [] as $a) {
                        $groups[$a['attribute_slug']]['name'] = $a['attribute'];
                        $groups[$a['attribute_slug']]['slug'] = $a['attribute_slug'];
                        $groups[$a['attribute_slug']]['values'][(int) $a['attribute_value_id']] = [
                            'id' => (int) $a['attribute_value_id'], 'label' => $a['value'], 'swatch' => $a['swatch'],
                        ];
                        $labels[] = $a['value'];
                        $valIds[] = (int) $a['attribute_value_id'];
                    }
                    $row = [
                        'id'       => (int) $v['id'],
                        'label'    => $labels ? implode(' · ', $labels) : (string) $v['name'],
                        'price'    => (float) $v['price'],
                        'in_stock' => $inStock,
                        'image'    => $v['image'] ? product_image($v['image']) : null,
                        'values'   => $valIds,
                    ];
                    $payload['variants'][] = $row;
                    // Prefer the admin's default variant, falling back to the first in stock.
                    if ($inStock && ($default === null || (! empty($v['is_default']) && ! $default['_default']))) {
                        $default = $row + ['_default' => ! empty($v['is_default'])];
                    }
                }
                if ($default === null) {
                    continue; // every variant sold out
                }
                foreach ($groups as &$g) {
                    $g['values'] = array_values($g['values']);
                }
                unset($g);
                $payload['groups']     = array_values($groups);
                $payload['variant_id'] = $default['id'];
                $payload['price']      = $default['price'];
                if ($default['image']) {
                    $payload['image'] = $default['image'];
                }
            } elseif ((int) $p['stock'] <= 0 && ! $madeToOrder) {
                continue;
            }

            $out[] = $payload;
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /** @return array<int, string> product_id => primary image path */
    private function primaryImages(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }
        $imgs = $this->db->table('product_images')
            ->select('product_id, image')->whereIn('product_id', $productIds)
            ->orderBy('is_primary', 'DESC')->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        $out = [];
        foreach ($imgs as $im) {
            $out[(int) $im['product_id']] ??= $im['image'];
        }

        return $out;
    }
}

