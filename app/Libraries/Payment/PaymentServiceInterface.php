<?php

namespace App\Libraries\Payment;

/**
 * Payment gateway contract (§31). Checkout depends only on this interface, so a
 * new provider can be added without rewriting the checkout flow.
 */
interface PaymentServiceInterface
{
    /**
     * A short identifier stored on the order (e.g. "razorpay", "cod").
     */
    public function key(): string;

    /**
     * Human label shown at checkout.
     */
    public function label(): string;

    /**
     * Create a payment intent/order at the gateway for the given order.
     *
     * @param array $order The persisted order row.
     * @return array Data the frontend needs to proceed (e.g. gateway order id, key).
     */
    public function createIntent(array $order): array;

    /**
     * Verify a gateway callback server-side (signature check, §31).
     *
     * @param array $payload Raw callback data from the client/gateway.
     * @return bool True when the payment is genuine and captured.
     */
    public function verify(array $payload): bool;

    /**
     * Whether this method requires an online payment step before confirmation.
     */
    public function isOnline(): bool;
}
