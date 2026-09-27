<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table            = 'product_variants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'product_id', 'sku', 'name', 'price', 'compare_price',
        'stock', 'image', 'is_default', 'status',
    ];
    protected $useTimestamps = true;

    /**
     * Variants for a product, each with its attribute-value selections resolved.
     */
    public function forProduct(int $productId): array
    {
        $variants = $this->where('product_id', $productId)
            ->where('status', 'active')
            ->orderBy('is_default', 'DESC')
            ->orderBy('price', 'ASC')
            ->findAll();

        if ($variants === []) {
            return [];
        }

        $ids  = array_column($variants, 'id');
        $attrs = db_connect()->table('product_variant_attributes pva')
            ->select('pva.variant_id, pa.name AS attribute, pa.slug AS attribute_slug, pav.value, pav.slug AS value_slug, pav.swatch')
            ->join('product_attributes pa', 'pa.id = pva.attribute_id')
            ->join('product_attribute_values pav', 'pav.id = pva.attribute_value_id')
            ->whereIn('pva.variant_id', $ids)
            ->get()->getResultArray();

        $byVariant = [];
        foreach ($attrs as $a) {
            $byVariant[$a['variant_id']][] = $a;
        }

        foreach ($variants as &$variant) {
            $variant['attributes'] = $byVariant[$variant['id']] ?? [];
        }

        return $variants;
    }
}
