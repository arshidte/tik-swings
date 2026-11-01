<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAuditRemediationFields extends Migration
{
    public function up(): void
    {
        // 1. Add idempotency_key and stock_restored to orders table
        if (! $this->db->fieldExists('idempotency_key', 'orders')) {
            $this->forge->addColumn('orders', [
                'idempotency_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                    'after'      => 'order_number',
                ],
            ]);
            $this->db->query('ALTER TABLE `orders` ADD UNIQUE INDEX `idx_orders_idempotency_key` (`idempotency_key`)');
        }

        if (! $this->db->fieldExists('stock_restored', 'orders')) {
            $this->forge->addColumn('orders', [
                'stock_restored' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'status',
                ],
            ]);
        }

        // 2. Create password_resets table
        if (! $this->db->tableExists('password_resets')) {
            $this->forge->addField([
                'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'email'      => ['type' => 'VARCHAR', 'constraint' => 190],
                'token_hash' => ['type' => 'VARCHAR', 'constraint' => 64],
                'created_at' => ['type' => 'DATETIME'],
                'expires_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('email');
            $this->forge->addUniqueKey('token_hash');
            $this->forge->createTable('password_resets', true);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('idempotency_key', 'orders')) {
            $this->forge->dropColumn('orders', 'idempotency_key');
        }
        if ($this->db->fieldExists('stock_restored', 'orders')) {
            $this->forge->dropColumn('orders', 'stock_restored');
        }
        $this->forge->dropTable('password_resets', true);
    }
}

