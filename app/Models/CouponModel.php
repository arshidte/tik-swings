<?php

namespace App\Models;

use CodeIgniter\Model;

class CouponModel extends Model
{
    protected $table            = 'coupons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'code', 'description', 'type', 'value', 'min_order', 'max_discount',
        'usage_limit', 'usage_limit_user', 'used_count', 'starts_at', 'expires_at', 'status',
    ];
    protected $useTimestamps = true;

    public function findActiveByCode(string $code): ?array
    {
        return $this->where('code', strtoupper(trim($code)))
            ->where('status', 'active')
            ->first();
    }
}
