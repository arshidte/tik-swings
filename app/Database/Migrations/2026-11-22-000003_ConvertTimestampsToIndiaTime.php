<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Moves stored timestamps from UTC to India time (+05:30) to match
 * Config\App::$appTimezone = 'Asia/Kolkata'. Every DATETIME column was written
 * by PHP in UTC (none use database defaults), so each gets +330 minutes.
 *
 * Coupon start/end dates are left alone: admins type them as local calendar
 * dates, so they are already India time.
 *
 * Deploy this together with the appTimezone change and run it straight away:
 * rows written by the new code before it runs would be shifted twice.
 */
class ConvertTimestampsToIndiaTime extends Migration
{
    private const OFFSET_MINUTES = 330;

    /** table => columns that hold admin-entered local times. */
    private const LOCAL_ALREADY = [
        'coupons' => ['starts_at', 'expires_at'],
    ];

    public function up(): void
    {
        $this->shift('+');
    }

    public function down(): void
    {
        $this->shift('-');
    }

    private function shift(string $sign): void
    {
        $columns = $this->db->query(
            "SELECT table_name AS t, column_name AS c FROM information_schema.columns
             WHERE table_schema = DATABASE() AND data_type IN ('datetime', 'timestamp')
             ORDER BY table_name, ordinal_position"
        )->getResultArray();

        $this->db->transStart();
        foreach ($columns as $col) {
            if (in_array($col['c'], self::LOCAL_ALREADY[$col['t']] ?? [], true)) {
                continue;
            }
            $t = $this->db->escapeIdentifiers($col['t']);
            $c = $this->db->escapeIdentifiers($col['c']);
            $this->db->query("UPDATE {$t} SET {$c} = {$c} {$sign} INTERVAL " . self::OFFSET_MINUTES . " MINUTE WHERE {$c} IS NOT NULL");
        }
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Shifting timestamps failed; the transaction was rolled back.');
        }
    }
}
