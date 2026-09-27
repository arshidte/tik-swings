<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCatalogSatelliteTables extends Migration
{
    public function up(): void
    {
        // --- product_categories (many-to-many) ---------------------------------
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'category_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'category_id']);
        $this->forge->addKey('category_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', '', 'CASCADE');
        $this->forge->createTable('product_categories', true);

        // --- product_attributes (Wood, Finish, Size ...) -----------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 90],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('product_attributes', true);

        // --- product_attribute_values (Teak, Natural, Large ...) ---------------
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'attribute_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'value'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'slug'         => ['type' => 'VARCHAR', 'constraint' => 110],
            'swatch'       => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true], // hex or image ref
            'sort_order'   => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('attribute_id');
        $this->forge->addForeignKey('attribute_id', 'product_attributes', 'id', '', 'CASCADE');
        $this->forge->createTable('product_attribute_values', true);

        // --- product_variants --------------------------------------------------
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'sku'           => ['type' => 'VARCHAR', 'constraint' => 90],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'price'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'compare_price' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'stock'         => ['type' => 'INT', 'default' => 0],
            'image'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_default'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'        => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_variants', true);

        // --- product_variant_attributes (variant -> attribute value) -----------
        $this->forge->addField([
            'id'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'variant_id'         => ['type' => 'BIGINT', 'unsigned' => true],
            'attribute_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'attribute_value_id' => ['type' => 'BIGINT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['variant_id', 'attribute_id']);
        $this->forge->addKey('attribute_value_id');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('attribute_id', 'product_attributes', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('attribute_value_id', 'product_attribute_values', 'id', '', 'CASCADE');
        $this->forge->createTable('product_variant_attributes', true);

        // --- product_images ----------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'variant_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'image'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'image_type' => ['type' => 'ENUM', 'constraint' => ['product', 'lifestyle', 'detail', 'craftsmanship', 'dimension'], 'default' => 'product'],
            'alt_text'   => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('variant_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('variant_id', 'product_variants', 'id', '', 'SET NULL');
        $this->forge->createTable('product_images', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_images', true);
        $this->forge->dropTable('product_variant_attributes', true);
        $this->forge->dropTable('product_variants', true);
        $this->forge->dropTable('product_attribute_values', true);
        $this->forge->dropTable('product_attributes', true);
        $this->forge->dropTable('product_categories', true);
    }
}
