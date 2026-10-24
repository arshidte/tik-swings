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

        $amount   = (int) round($order['total'] * 100); // paise
        $currency = $order['currency'] ?? 'INR';
        if ($amount < 100) {
            throw new \RuntimeException('Order amount is below the minimum chargeable value.');
        }

        // Create the order at Razorpay (POST /v1/orders) and hand the gateway
        // order id back to the browser so Checkout opens in "Orders" mode and the
        // callback returns a razorpay_order_id we can verify by signature.
        $gatewayOrderId = $this->createGatewayOrder($amount, $currency, (string) $order['order_number']);

        return [
            'method'           => 'razorpay',
            'key'              => $this->keyId,
            'amount'           => $amount,
            'currency'         => $currency,
            'receipt'          => $order['order_number'],
            'gateway_order_id' => $gatewayOrderId,
        ];
    }

    /**
     * POST to https://api.razorpay.com/v1/orders and return the gateway order id.
     *
     * @throws \RuntimeException on authentication failure or any API error.
     */
    private function createGatewayOrder(int $amount, string $currency, string $receipt): string
    {
        $client = \Config\Services::curlrequest([
            'baseURI'     => 'https://api.razorpay.com/v1/',
            'http_errors' => false, // inspect non-2xx responses ourselves
            'timeout'     => 20,
        ]);

        $response = $client->post('orders', [
            'auth' => [$this->keyId, $this->keySecret], // HTTP Basic: key_id:key_secret
            'json' => [
                'amount'   => $amount,
                'currency' => $currency,
                'receipt'  => $receipt,
            ],
        ]);

        $status = $response->getStatusCode();
        $body   = json_decode((string) $response->getBody(), true) ?: [];

        if ($status === 401) {
            log_message('error', 'Razorpay auth failed creating order: {d}', ['d' => json_encode($body)]);
            throw new \RuntimeException('Payment gateway authentication failed. Please contact support.');
        }
        if ($status < 200 || $status >= 300 || empty($body['id'])) {
            $reason = $body['error']['description'] ?? ('HTTP ' . $status);
            log_message('error', 'Razorpay order creation failed: {r} {d}', ['r' => $reason, 'd' => json_encode($body)]);
            throw new \RuntimeException('We could not start the payment. Please try again.');
        }

        return (string) $body['id'];
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
