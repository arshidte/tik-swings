<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductUserMediaModel extends Model
{
    protected $table            = 'product_user_media';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'product_id', 'media_type', 'media', 'poster',
        'author_name', 'caption', 'sort_order', 'status',
    ];
    protected $useTimestamps = true;
    protected $updatedField  = '';

    /** Visible media for the public PDP, ordered. */
    public function forProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->where('status', 'visible')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** A visible-media page for lazy-loading the PDP strip. */
    public function visibleBatch(int $productId, int $offset, int $limit): array
    {
        return $this->where('product_id', $productId)
            ->where('status', 'visible')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll($limit, max(0, $offset));
    }

    /** All media (any status) for the admin manager, ordered. */
    public function forProductAdmin(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** Next sort_order value for appending. */
    public function nextSortOrder(int $productId): int
    {
        $max = $this->where('product_id', $productId)->selectMax('sort_order')->get()->getRow('sort_order');

        return (int) $max + 1;
    }
}
