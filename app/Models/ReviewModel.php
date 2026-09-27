<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table            = 'reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'product_id', 'user_id', 'order_id', 'author_name', 'city', 'rating',
        'title', 'body', 'verified_purchase', 'status',
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'author_name' => 'required|max_length[120]',
        'rating'      => 'required|integer|greater_than[0]|less_than_equal_to[5]',
        'body'        => 'required|max_length[2000]',
    ];

    public function approvedForProduct(int $productId, int $limit = 10): array
    {
        return $this->where('product_id', $productId)
            ->where('status', 'approved')
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Featured approved reviews across the store (for the homepage section).
     */
    public function featured(int $limit = 6): array
    {
        return $this->where('status', 'approved')
            ->where('body !=', '')
            ->orderBy('rating', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    /**
     * Recompute a product's rating aggregate from approved reviews.
     */
    public function recalculateProductRating(int $productId): void
    {
        $row = $this->select('COUNT(*) AS c, AVG(rating) AS a')
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->get()->getRowArray();

        (new ProductModel())->update($productId, [
            'rating_count' => (int) ($row['c'] ?? 0),
            'rating_avg'   => round((float) ($row['a'] ?? 0), 2),
        ]);
    }
}
