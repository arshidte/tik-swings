<?php

namespace App\Models;

use CodeIgniter\Model;

class ShipmentModel extends Model
{
    protected $table            = 'shipments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['order_id', 'carrier', 'tracking_number', 'mobile', 'status', 'shipped_at', 'delivered_at'];
    protected $useTimestamps    = true;

    public function forOrder(int $orderId): ?array
    {
        return $this->where('order_id', $orderId)->orderBy('id', 'DESC')->first();
    }

    /**
     * Create or update the single shipment record for an order.
     */
    public function saveForOrder(int $orderId, string $carrier, string $trackingNumber, string $mobile): void
    {
        $existing = $this->forOrder($orderId);
        $data     = [
            'order_id'        => $orderId,
            'carrier'         => $carrier,
            'tracking_number' => $trackingNumber,
            'mobile'          => $mobile,
            'status'          => 'shipped',
            'shipped_at'      => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
