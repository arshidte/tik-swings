<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductOptionValueModel extends Model
{
    protected $table         = 'product_option_values';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'attribute_value_id', 'price_adjust', 'badge'];
    protected $useTimestamps = false;

    /** Price adjustments for a product, keyed by attribute_value_id. */
    public function forProduct(int $productId): array
    {
        $rows = $this->where('product_id', $productId)->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[(int) $r['attribute_value_id']] = (float) $r['price_adjust'];
        }

        return $out;
    }

    /** Value badges for a product (e.g. "Most Popular"), keyed by attribute_value_id. */
    public function badgesForProduct(int $productId): array
    {
        $rows = $this->where('product_id', $productId)->findAll();
        $out  = [];
        foreach ($rows as $r) {
            if (($r['badge'] ?? '') !== '') {
                $out[(int) $r['attribute_value_id']] = (string) $r['badge'];
            }
        }

        return $out;
    }

    /** Replace all adjustment rows for a product. */
    public function replaceForProduct(int $productId, array $values): void
    {
        $this->where('product_id', $productId)->delete();
        if ($values !== []) {
            $this->insertBatch($values);
        }
    }
}
