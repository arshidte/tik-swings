<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductOptionPricingTables extends Migration
{
    public function up(): void
    {
        // --- product_options: which options a product uses + price behaviour --
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'attribute_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'affects_price' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'    => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'attribute_id']);
        $this->forge->addKey('attribute_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('attribute_id', 'product_attributes', 'id', '', 'CASCADE');
        $this->forge->createTable('product_options', true);

        // --- product_option_values: product-specific price adjustment per value -
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'attribute_value_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'price_adjust'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'attribute_value_id']);
        $this->forge->addKey('attribute_value_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('attribute_value_id', 'product_attribute_values', 'id', '', 'CASCADE');
        $this->forge->createTable('product_option_values', true);

        // Per-option pricing replaces the product-level toggle added earlier.
        if ($this->db->fieldExists('variant_pricing', 'products')) {
            $this->forge->dropColumn('products', 'variant_pricing');
        }
    }

    public function down(): void
    {
        $this->forge->addColumn('products', [
            'variant_pricing' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'has_variants'],
        ]);
        $this->forge->dropTable('product_option_values', true);
        $this->forge->dropTable('product_options', true);
    }
}
