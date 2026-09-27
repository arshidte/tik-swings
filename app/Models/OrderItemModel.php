<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table            = 'order_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'order_id', 'product_id', 'variant_id', 'product_name', 'variant_name', 'sku',
        'image', 'options', 'unit_price', 'quantity', 'line_total',
    ];
    protected $useTimestamps = true;
    protected $updatedField   = '';

    public function forOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)->findAll();
    }
}
