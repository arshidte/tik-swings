<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'category_id'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 190],
            'slug'                  => ['type' => 'VARCHAR', 'constraint' => 210],
            'sku'                   => ['type' => 'VARCHAR', 'constraint' => 80],
            'short_description'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'description'           => ['type' => 'TEXT', 'null' => true],
            'price'                 => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'compare_price'         => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'cost_price'            => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'stock'                 => ['type' => 'INT', 'default' => 0],
            'stock_status'          => ['type' => 'ENUM', 'constraint' => ['in_stock', 'out_of_stock', 'made_to_order'], 'default' => 'in_stock'],
            'has_variants'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'material'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'wood_type'             => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'finish'                => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'dimensions'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'weight_capacity'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'warranty'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'delivery_estimate'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'installation_available' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_customizable'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'rating_avg'            => ['type' => 'DECIMAL', 'constraint' => '3,2', 'default' => 0],
            'rating_count'          => ['type' => 'INT', 'default' => 0],
            'featured'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'                => ['type' => 'ENUM', 'constraint' => ['draft', 'active', 'archived'], 'default' => 'active'],
            'seo_title'             => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'seo_description'       => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'seo_keywords'          => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addUniqueKey('sku');
        $this->forge->addKey('category_id');
        $this->forge->addKey('status');
        $this->forge->addKey('featured');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['status', 'featured']);
        $this->forge->addForeignKey('category_id', 'categories', 'id', '', 'SET NULL');
        $this->forge->createTable('products', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('products', true);
    }
}
