<?php

namespace App\Commands;

use App\Models\OrderModel;
use App\Services\SmsService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Verify a DLT SMS template end-to-end:
 *   php spark sms:test order_confirmed 9876543210
 *   php spark sms:test order_confirmed 9876543210 --order SW261122ABC123
 */
class SmsTest extends BaseCommand
{
    protected $group       = 'SMS';
    protected $name        = 'sms:test';
    protected $description = 'Preview and send a test SMS for a template, to verify the Fast2SMS setup and variable order.';
    protected $usage       = 'sms:test <template> <phone> [--order ORDER_NUMBER]';
    protected $arguments   = [
        'template' => 'Template key from Config\Sms::$templates (e.g. order_confirmed)',
        'phone'    => 'Mobile number that should receive the test SMS',
    ];
    protected $options = [
        '--order' => "Use a real order's data instead of sample values",
    ];

    public function run(array $params)
    {
        $config   = config('Sms');
        $template = $params[0] ?? '';
        $phone    = $params[1] ?? '';

        if ($template === '' || $phone === '') {
            CLI::error('Usage: php spark ' . $this->usage);
            CLI::write('Templates: ' . implode(', ', array_keys($config->templates)));

            return EXIT_USER_INPUT;
        }

        $orderNumber = CLI::getOption('order');
        if (is_string($orderNumber) && $orderNumber !== '') {
            $order = (new OrderModel())->findByNumber(strtoupper(trim($orderNumber)));
            if (! $order) {
                CLI::error('Order ' . $orderNumber . ' not found.');

                return EXIT_ERROR;
            }
            $data = SmsService::orderData($order);
        } else {
            $data = SmsService::orderData([
                'order_number'     => 'SW261122TEST01',
                'total'            => 20200,
                'shipping_address' => json_encode(['full_name' => 'Test Customer']),
            ]);
        }

        $sms    = new SmsService($config);
        $number = SmsService::normalizePhone($phone);

        try {
            $variables = $sms->buildVariables($template, $data);
        } catch (\InvalidArgumentException $e) {
            CLI::error(ucfirst($e->getMessage()) . '.');

            return EXIT_ERROR;
        }

        $messageId = $config->templates[$template]['message_id'] ?? '';
        CLI::write('Template:   ' . $template);
        CLI::write('Message ID: ' . ($messageId !== '' ? $messageId : '(not set: this SMS is skipped)'));
        CLI::write('Sender ID:  ' . $config->senderId);
        CLI::write('Number:     ' . ($number ?? 'INVALID'));
        CLI::write('Variables:  ' . $variables);
        CLI::write('Enabled:    ' . ($config->enabled ? 'yes' : 'no (set SMS_ENABLED=true in .env to send)'));
        CLI::write('API key:    ' . ($config->apiKey !== '' ? 'set' : 'MISSING (set FAST2SMS_API_KEY in .env)'));

        if ($number === null || $messageId === '' || ! $config->enabled || $config->apiKey === '') {
            CLI::write('Nothing sent.', 'yellow');

            return $number === null ? EXIT_USER_INPUT : EXIT_SUCCESS;
        }

        if ($sms->send($template, $phone, $data)) {
            CLI::write('Sent. Check the phone and your Fast2SMS delivery report.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::error('Not sent. The Fast2SMS response is in writable/logs.');

        return EXIT_ERROR;
    }
}
