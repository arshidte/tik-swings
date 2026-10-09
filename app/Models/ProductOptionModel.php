<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductOptionModel extends Model
{
    protected $table         = 'product_options';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['product_id', 'attribute_id', 'affects_price', 'sort_order'];
    protected $useTimestamps = false;

    /** Options this product uses, keyed by attribute_id. */
    public function forProduct(int $productId): array
    {
        $rows = $this->where('product_id', $productId)->orderBy('sort_order')->findAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[(int) $r['attribute_id']] = $r;
        }

        return $out;
    }

    /** Replace all option rows for a product. */
    public function replaceForProduct(int $productId, array $options): void
    {
        $this->where('product_id', $productId)->delete();
        if ($options !== []) {
            $this->insertBatch($options);
        }
    }
}
