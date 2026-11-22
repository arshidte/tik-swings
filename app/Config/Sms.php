<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Transactional SMS (Fast2SMS, DLT route).
 *
 * Secrets come from .env only — never from the database or this file:
 *   SMS_ENABLED        = true            (master switch; leave unset locally)
 *   FAST2SMS_API_KEY   = your_api_key
 *   FAST2SMS_SENDER_ID = TIKSHP          (optional, defaults below)
 *
 * To add a new SMS: register the DLT template, then add one entry to
 * $templates with its Fast2SMS "Message ID" and the variable keys in the
 * exact order the {#var#} placeholders appear in the approved text.
 */
class Sms extends BaseConfig
{
    /** Master switch. False unless SMS_ENABLED=true, so development never texts customers. */
    public bool $enabled = false;

    public string $apiKey   = '';
    public string $senderId = 'TIKSHP';
    public string $route    = 'dlt';
    public string $endpoint = 'https://www.fast2sms.com/dev/bulkV2';

    /** Seconds to wait for Fast2SMS before giving up (checkout must not hang). */
    public int $timeout = 10;

    /**
     * DLT templates keyed by event.
     *
     * - message_id: Fast2SMS Message ID of the approved template. Empty = event is skipped (logged).
     * - variables:  data keys from SmsService::orderData(), in {#var#} order.
     *               Available keys: order_number, amount, name, track_url (+ any $extra passed in).
     *
     * @var array<string, array{message_id: string, variables: list<string>}>
     */
    public array $templates = [
        // Customer: order confirmed (online / prepaid payment).
        // "Thank you for choosing TIK Swings! Your order {#var#} for Rs.{#var#} is confirmed. ...
        //  Track: https://shop.theinteriorkampany.com/track-order?order={#var#}"
        'order_confirmed' => [
            'message_id' => '232169',
            'variables'  => ['order_number', 'amount', 'order_number'],
        ],

        // Customer: cash-on-delivery order placed. Add the Message ID once the
        // template is approved (and confirm the variable order matches it).
        // "Your TIK Swings COD order {#var#} of Rs.{#var#} is placed! ..."
        'order_placed_cod' => [
            'message_id' => '',
            'variables'  => ['order_number', 'amount'],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        $this->enabled  = (bool) env('SMS_ENABLED', $this->enabled);
        $this->apiKey   = trim((string) env('FAST2SMS_API_KEY', $this->apiKey));
        $this->senderId = trim((string) env('FAST2SMS_SENDER_ID', $this->senderId));
    }
}
