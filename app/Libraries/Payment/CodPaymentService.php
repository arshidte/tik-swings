<?php

namespace App\Libraries\Payment;

/**
 * Cash / pay-on-delivery. No online step — the order is confirmed immediately
 * and settled on delivery. Serves as the always-available default so the store
 * is functional without gateway credentials configured.
 */
class CodPaymentService implements PaymentServiceInterface
{
    public function key(): string
    {
        return 'cod';
    }

    public function label(): string
    {
        return 'Cash / Pay on Delivery';
    }

    public function createIntent(array $order): array
    {
        return ['method' => 'cod', 'order_number' => $order['order_number']];
    }

    public function verify(array $payload): bool
    {
        return true; // nothing to verify for COD
    }

    public function isOnline(): bool
    {
        return false;
    }
}
