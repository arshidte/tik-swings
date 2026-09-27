<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderStatusHistoryModel extends Model
{
    protected $table            = 'order_status_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['order_id', 'status', 'note', 'created_by'];
    protected $useTimestamps    = true;
    protected $updatedField     = '';

    public function forOrder(int $orderId): array
    {
        return $this->where('order_id', $orderId)->orderBy('created_at', 'ASC')->findAll();
    }
}
