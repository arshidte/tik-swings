<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * product_addons — "Complete your swing" mapping.
 *
 * A parent product (e.g. a swing) offers other products (e.g. a hanging chain)
 * as optional add-ons on its product page, in the cart drawer and on the cart
 * page. The mapping is one-directional. Add-ons with variants use their own
 * default variant on the storefront (variants are rebuilt on every product
 * save, so variant ids are deliberately not stored here).
 */
class CreateProductAddonsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'       => ['type' => 'BIGINT', 'unsigned' => true],
            'addon_product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'is_preselected'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['product_id', 'addon_product_id']);
        $this->forge->addKey('addon_product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('addon_product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_addons', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_addons', true);
    }
}

