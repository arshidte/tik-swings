<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrderTables extends Migration
{
    public function up(): void
    {
        // --- orders ------------------------------------------------------------
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_number'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'user_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'email'            => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone'            => ['type' => 'VARCHAR', 'constraint' => 20],
            'billing_address'  => ['type' => 'TEXT', 'null' => true],  // JSON snapshot
            'shipping_address' => ['type' => 'TEXT', 'null' => true],  // JSON snapshot
            'subtotal'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'discount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'shipping'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'tax'              => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'total'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'coupon_code'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'currency'         => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'INR'],
            'payment_method'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'payment_status'   => ['type' => 'ENUM', 'constraint' => ['pending', 'paid', 'failed', 'refunded'], 'default' => 'pending'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refunded'], 'default' => 'pending'],
            'notes'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('order_number');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addKey('payment_status');
        $this->forge->addKey('created_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('orders', true);

        // --- order_items (with product/variant/price snapshots) ----------------
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'variant_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'product_name'  => ['type' => 'VARCHAR', 'constraint' => 190],
            'variant_name'  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'sku'           => ['type' => 'VARCHAR', 'constraint' => 90, 'null' => true],
            'image'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'options'       => ['type' => 'TEXT', 'null' => true], // JSON customization snapshot
            'unit_price'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'quantity'      => ['type' => 'INT', 'default' => 1],
            'line_total'    => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'SET NULL');
        $this->forge->createTable('order_items', true);

        // --- order_status_history ----------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 30],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->createTable('order_status_history', true);

        // --- payments ----------------------------------------------------------
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'provider'       => ['type' => 'VARCHAR', 'constraint' => 40],
            'provider_ref'   => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true], // gateway payment id
            'provider_order' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true], // gateway order id
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'currency'       => ['type' => 'VARCHAR', 'constraint' => 3, 'default' => 'INR'],
            'status'         => ['type' => 'ENUM', 'constraint' => ['created', 'authorized', 'captured', 'failed', 'refunded'], 'default' => 'created'],
            'signature'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'payload'        => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addKey('provider_ref');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->createTable('payments', true);

        // --- shipments ---------------------------------------------------------
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'order_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'carrier'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'tracking_number' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status'          => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'pending'],
            'shipped_at'      => ['type' => 'DATETIME', 'null' => true],
            'delivered_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('order_id');
        $this->forge->addForeignKey('order_id', 'orders', 'id', '', 'CASCADE');
        $this->forge->createTable('shipments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('shipments', true);
        $this->forge->dropTable('payments', true);
        $this->forge->dropTable('order_status_history', true);
        $this->forge->dropTable('order_items', true);
        $this->forge->dropTable('orders', true);
    }
}
