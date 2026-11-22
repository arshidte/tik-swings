<?php

namespace App\Commands;

use App\Services\OrderService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Cancel online orders left unpaid past the hold window and return their
 * stock. Schedule it every 5–10 minutes, e.g. a cPanel cron job:
 *   *\/5 * * * * cd /path/to/app && php spark orders:release-unpaid
 */
class ReleaseUnpaidOrders extends BaseCommand
{
    protected $group       = 'Orders';
    protected $name        = 'orders:release-unpaid';
    protected $description = 'Cancel online orders still unpaid after the hold window and return their stock.';
    protected $usage       = 'orders:release-unpaid [--minutes N]';
    protected $options     = [
        '--minutes' => 'Hold window in minutes (default: OrderService::UNPAID_HOLD_MINUTES)',
    ];

    public function run(array $params)
    {
        $option  = CLI::getOption('minutes');
        $minutes = ($option === null || $option === true) ? OrderService::UNPAID_HOLD_MINUTES : (int) $option;
        if ($minutes < 1) {
            CLI::error('--minutes must be at least 1.');

            return EXIT_USER_INPUT;
        }

        $released = (new OrderService())->releaseUnpaidOnlineOrders($minutes);
        CLI::write("Released {$released} unpaid order(s) older than {$minutes} minutes.", $released ? 'green' : null);

        return EXIT_SUCCESS;
    }
}
