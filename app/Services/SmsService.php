<?php

namespace App\Services;

use Config\Sms as SmsConfig;

/**
 * Transactional SMS via Fast2SMS (DLT route).
 *
 *   (new SmsService())->sendForOrder('order_confirmed', $order);
 *   (new SmsService())->send('order_confirmed', '9876543210', ['order_number' => …, 'amount' => …]);
 *
 * Never throws: an SMS failure is logged and must never break checkout or admin actions.
 * Templates and their variable order live in Config\Sms::$templates.
 */
class SmsService
{
    private SmsConfig $config;

    public function __construct(?SmsConfig $config = null)
    {
        $this->config = $config ?? config(SmsConfig::class);
    }

    /**
     * Send a template SMS for an order, using the order's phone number and data.
     *
     * @param array<string, scalar|null> $extra Additional/override variables (e.g. courier, tracking_number)
     */
    public function sendForOrder(string $template, array $order, array $extra = []): bool
    {
        return $this->send($template, (string) ($order['phone'] ?? ''), self::orderData($order, $extra));
    }

    /**
     * Send a DLT template SMS. Returns true only when Fast2SMS accepted the message.
     *
     * @param array<string, scalar|null> $data Values for the template's variable keys
     */
    public function send(string $template, string $phone, array $data): bool
    {
        try {
            $tpl = $this->template($template);

            if ($tpl['message_id'] === '') {
                log_message('info', 'SMS [{t}] skipped: no Fast2SMS message ID configured.', ['t' => $template]);

                return false;
            }

            $number = self::normalizePhone($phone)
                ?? throw new \InvalidArgumentException('invalid Indian mobile number "' . self::mask($phone) . '"');

            $fields = [
                'sender_id'        => $this->config->senderId,
                'message'          => $tpl['message_id'],
                'route'            => $this->config->route,
                'numbers'          => $number,
                'variables_values' => $this->buildVariables($template, $data),
            ];

            if (! $this->config->enabled) {
                log_message('info', 'SMS [{t}] not sent (SMS_ENABLED is off). Would send to {n}: {v}', [
                    't' => $template, 'n' => self::mask($number), 'v' => $fields['variables_values'],
                ]);

                return false;
            }

            if ($this->config->apiKey === '') {
                throw new \RuntimeException('FAST2SMS_API_KEY is not set in .env');
            }

            return $this->post($template, $fields);
        } catch (\Throwable $e) {
            log_message('error', 'SMS [{t}] failed: {m}', ['t' => $template, 'm' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Build the pipe-separated variables_values string for a template, in {#var#} order.
     *
     * @param array<string, scalar|null> $data
     *
     * @throws \InvalidArgumentException on an unknown template or a missing/empty variable.
     */
    public function buildVariables(string $template, array $data): string
    {
        $values = [];
        foreach ($this->template($template)['variables'] as $key) {
            $value = isset($data[$key]) ? self::cleanVariable((string) $data[$key]) : '';
            if ($value === '') {
                throw new \InvalidArgumentException('missing value for SMS variable "' . $key . '"');
            }
            $values[] = $value;
        }

        return implode('|', $values);
    }

    /**
     * Standard variables available to every order SMS.
     *
     * @param array<string, scalar|null> $extra Merged over the defaults (extra wins)
     *
     * @return array<string, scalar|null>
     */
    public static function orderData(array $order, array $extra = []): array
    {
        helper('format');

        $ship = json_decode((string) ($order['shipping_address'] ?? ''), true);
        $name = trim((string) (is_array($ship) ? ($ship['full_name'] ?? '') : ''));

        return $extra + [
            'order_number' => (string) ($order['order_number'] ?? ''),
            'amount'       => price($order['total'] ?? 0, false), // e.g. "20,200" (template has "Rs.")
            'name'         => $name !== '' ? strtok($name, ' ') : 'Customer',
            'track_url'    => site_url('track-order') . '?order=' . rawurlencode((string) ($order['order_number'] ?? '')),
        ];
    }

    /**
     * Normalise an Indian mobile number to the 10 digits Fast2SMS expects, or null if invalid.
     * Accepts "9876543210", "+91 98765 43210", "919876543210", "09876543210".
     */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 11 && $digits[0] === '0') {
            $digits = substr($digits, 1);
        }

        return preg_match('/^[6-9]\d{9}$/', $digits) ? $digits : null;
    }

    /** @return array{message_id: string, variables: list<string>} */
    private function template(string $template): array
    {
        $tpl = $this->config->templates[$template] ?? null;
        if (! is_array($tpl)) {
            throw new \InvalidArgumentException('unknown SMS template "' . $template . '"');
        }

        return [
            'message_id' => trim((string) ($tpl['message_id'] ?? '')),
            'variables'  => array_values((array) ($tpl['variables'] ?? [])),
        ];
    }

    /** POST to Fast2SMS and interpret the response. */
    private function post(string $template, array $fields): bool
    {
        // Non-shared client: the shared instance may carry another integration's
        // options (e.g. Razorpay) within the same request.
        $client = \Config\Services::curlrequest([
            'timeout'         => $this->config->timeout,
            'connect_timeout' => 5,
            'http_errors'     => false,
        ], null, null, false);

        $response = $client->post($this->config->endpoint, [
            'headers'     => ['authorization' => $this->config->apiKey, 'accept' => 'application/json'],
            'form_params' => $fields,
        ]);

        $status = $response->getStatusCode();
        $raw    = (string) $response->getBody();
        $body   = json_decode($raw, true);

        if ($status === 200 && is_array($body) && ($body['return'] ?? false) === true) {
            log_message('info', 'SMS [{t}] sent to {n} (request_id {r}).', [
                't' => $template, 'n' => self::mask($fields['numbers']), 'r' => (string) ($body['request_id'] ?? '-'),
            ]);

            return true;
        }

        $message = is_array($body) ? ($body['message'] ?? '') : substr($raw, 0, 200);
        log_message('error', 'SMS [{t}] rejected by Fast2SMS (HTTP {s}, code {c}): {m}', [
            't' => $template,
            's' => $status,
            'c' => is_array($body) ? (string) ($body['status_code'] ?? '-') : '-',
            'm' => is_array($message) ? implode(' ', $message) : (string) $message,
        ]);

        return false;
    }

    /** DLT variables are pipe-separated and single-line: strip "|" and collapse whitespace. */
    private static function cleanVariable(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace('|', ' ', $value)) ?? '');
    }

    /** Mask a phone number for logs: "******3210". */
    private static function mask(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return strlen($digits) > 4 ? str_repeat('*', strlen($digits) - 4) . substr($digits, -4) : '****';
    }
}
