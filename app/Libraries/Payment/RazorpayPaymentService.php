<?php

namespace App\Libraries\Payment;

/**
 * Razorpay integration (§31). Credentials come from settings/env, never
 * hard-coded. Callback signatures are verified server-side with HMAC-SHA256.
 *
 * The SDK/API call is intentionally isolated in createIntent(); wiring the live
 * HTTP call is a drop-in once keys are configured.
 */
class RazorpayPaymentService implements PaymentServiceInterface
{
    private string $keyId;
    private string $keySecret;

    public function __construct()
    {
        $this->keyId     = (string) (getenv('RAZORPAY_KEY_ID') ?: setting('razorpay_key_id', ''));
        $this->keySecret = (string) (getenv('RAZORPAY_KEY_SECRET') ?: setting('razorpay_key_secret', ''));
    }

    public function key(): string
    {
        return 'razorpay';
    }

    public function label(): string
    {
        return 'Card / UPI / Netbanking (Razorpay)';
    }

    public function isConfigured(): bool
    {
        return $this->keyId !== '' && $this->keySecret !== '';
    }

    public function createIntent(array $order): array
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException('Online payment is not configured yet.');
        }

        // Live implementation would POST to https://api.razorpay.com/v1/orders
        // with amount (in paise), currency and receipt, returning the gateway
        // order id. Kept isolated so the HTTP client is the only thing to add.
        return [
            'method'    => 'razorpay',
            'key'       => $this->keyId,
            'amount'    => (int) round($order['total'] * 100),
            'currency'  => $order['currency'] ?? 'INR',
            'receipt'   => $order['order_number'],
        ];
    }

    /**
     * Verify razorpay_signature = HMAC_SHA256(order_id|payment_id, secret).
     */
    public function verify(array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        $orderId   = $payload['razorpay_order_id'] ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $signature = $payload['razorpay_signature'] ?? '';
        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);

        return hash_equals($expected, $signature);
    }

    public function isOnline(): bool
    {
        return true;
    }
}
