<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCommerceTables extends Migration
{
    public function up(): void
    {
        // --- addresses ---------------------------------------------------------
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'BIGINT', 'unsigned' => true],
            'label'        => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true], // Home, Work
            'full_name'    => ['type' => 'VARCHAR', 'constraint' => 120],
            'phone'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'line1'        => ['type' => 'VARCHAR', 'constraint' => 190],
            'line2'        => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'city'         => ['type' => 'VARCHAR', 'constraint' => 80],
            'state'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'pincode'      => ['type' => 'VARCHAR', 'constraint' => 12],
            'country'      => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'India'],
            'is_default'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('addresses', true);

        // --- wishlists ---------------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'product_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('wishlists', true);

        // --- cart_items (persistent cart for logged-in users) ------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'session_id' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'quantity'   => ['type' => 'INT', 'default' => 1],
            'options'    => ['type' => 'TEXT', 'null' => true], // JSON customization snapshot
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('session_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'SET NULL');
        $this->forge->createTable('cart_items', true);

        // --- coupons -----------------------------------------------------------
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'code'             => ['type' => 'VARCHAR', 'constraint' => 40],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['percent', 'fixed'], 'default' => 'percent'],
            'value'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'min_order'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'max_discount'     => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'usage_limit'      => ['type' => 'INT', 'null' => true],
            'usage_limit_user' => ['type' => 'INT', 'null' => true],
            'used_count'       => ['type' => 'INT', 'default' => 0],
            'starts_at'        => ['type' => 'DATETIME', 'null' => true],
            'expires_at'       => ['type' => 'DATETIME', 'null' => true],
            'status'           => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('status');
        $this->forge->createTable('coupons', true);

        // --- coupon_usages -----------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'coupon_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'discount'   => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('coupon_id');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('coupon_id', 'coupons', 'id', '', 'CASCADE');
        $this->forge->createTable('coupon_usages', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('coupon_usages', true);
        $this->forge->dropTable('coupons', true);
        $this->forge->dropTable('cart_items', true);
        $this->forge->dropTable('wishlists', true);
        $this->forge->dropTable('addresses', true);
    }
}
