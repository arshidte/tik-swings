<?php

namespace App\Libraries\Payment;

/**
 * Resolves available payment methods. Adding a provider is a one-line change
 * here plus a new PaymentServiceInterface implementation (§31, §69).
 */
class PaymentManager
{
    /**
     * @return array<string, PaymentServiceInterface>
     */
    public function available(): array
    {
        $methods = [];

        $razorpay = new RazorpayPaymentService();
        if ($razorpay->isConfigured()) {
            $methods[$razorpay->key()] = $razorpay;
        }

        // COD is always available as a fallback so the store works out of the box.
        $cod = new CodPaymentService();
        $methods[$cod->key()] = $cod;

        return $methods;
    }

    public function get(string $key): ?PaymentServiceInterface
    {
        return $this->available()[$key] ?? null;
    }
}
