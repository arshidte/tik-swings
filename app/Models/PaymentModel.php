<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentModel extends Model
{
    protected $table            = 'payments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'order_id', 'provider', 'provider_ref', 'provider_order', 'amount',
        'currency', 'status', 'signature', 'payload',
    ];
    protected $useTimestamps = true;
}
