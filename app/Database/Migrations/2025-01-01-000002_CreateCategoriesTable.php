<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCategoriesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'parent_id'        => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'             => ['type' => 'VARCHAR', 'constraint' => 150],
            'slug'             => ['type' => 'VARCHAR', 'constraint' => 170],
            'description'      => ['type' => 'TEXT', 'null' => true],
            'image'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'icon'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'sort_order'       => ['type' => 'INT', 'default' => 0],
            'featured'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'           => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'seo_title'        => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'seo_description'  => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('parent_id');
        $this->forge->addKey('status');
        $this->forge->addKey('featured');
        $this->forge->createTable('categories', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('categories', true);
    }
}
