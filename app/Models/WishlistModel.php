<?php

namespace App\Models;

use CodeIgniter\Model;

class WishlistModel extends Model
{
    protected $table            = 'wishlists';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['user_id', 'product_id'];
    protected $useTimestamps    = true;
    protected $updatedField     = '';

    public function productIdsForUser(int $userId): array
    {
        return array_column(
            $this->select('product_id')->where('user_id', $userId)->findAll(),
            'product_id'
        );
    }

    public function toggle(int $userId, int $productId): bool
    {
        $existing = $this->where('user_id', $userId)->where('product_id', $productId)->first();
        if ($existing) {
            $this->delete($existing['id']);

            return false; // now removed
        }
        $this->insert(['user_id' => $userId, 'product_id' => $productId]);

        return true; // now added
    }
}
