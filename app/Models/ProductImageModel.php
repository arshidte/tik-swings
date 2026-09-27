<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductImageModel extends Model
{
    protected $table            = 'product_images';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'product_id', 'variant_id', 'image', 'image_type',
        'alt_text', 'sort_order', 'is_primary',
    ];
    protected $useTimestamps = false;

    public function forProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }
}
