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

        // Cash on delivery is off unless COD_ENABLED=true in .env. Unlisted
        // methods are also rejected at checkout, not just hidden.
        if ((bool) env('COD_ENABLED', false)) {
            $cod = new CodPaymentService();
            $methods[$cod->key()] = $cod;
        }

        return $methods;
    }

    public function get(string $key): ?PaymentServiceInterface
    {
        return $this->available()[$key] ?? null;
    }
}
