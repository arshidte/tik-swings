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
        'order_number', 'idempotency_key', 'user_id', 'email', 'phone', 'billing_address', 'shipping_address',
        'subtotal', 'discount', 'shipping', 'tax', 'total', 'coupon_code', 'currency',
        'payment_method', 'payment_status', 'status', 'stock_restored', 'notes',
    ];
    protected $useTimestamps = true;

    public function forUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    public function countForUser(int $userId): int
    {
        return $this->where('user_id', $userId)->countAllResults();
    }

    public function recentForUser(int $userId, int $limit = 3): array
    {
        return $this->where('user_id', $userId)->orderBy('created_at', 'DESC')->findAll($limit);
    }

    public function findByNumber(string $number): ?array
    {
        return $this->where('order_number', $number)->first();
    }

    public function findByIdempotencyKey(?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        return $this->where('idempotency_key', $key)->first();
    }

    public function generateOrderNumber(): string
    {
        return 'SW' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    }
}
