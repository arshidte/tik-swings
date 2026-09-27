<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table            = 'orders';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'order_number', 'user_id', 'email', 'phone', 'billing_address', 'shipping_address',
        'subtotal', 'discount', 'shipping', 'tax', 'total', 'coupon_code', 'currency',
        'payment_method', 'payment_status', 'status', 'notes',
    ];
    protected $useTimestamps = true;

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function findByNumber(string $number): ?array
    {
        return $this->where('order_number', $number)->first();
    }

    public function generateOrderNumber(): string
    {
        return 'SW' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    }
}
