<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProductUserMediaTable extends Migration
{
    public function up(): void
    {
        // --- product_user_media -----------------------------------------------
        // Customer-uploaded photos & videos shown on the PDP (§ "As Loved at Home").
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'media_type'  => ['type' => 'ENUM', 'constraint' => ['image', 'video'], 'default' => 'image'],
            'media'       => ['type' => 'VARCHAR', 'constraint' => 255],           // image/video path or url
            'poster'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true], // video poster/thumbnail
            'author_name' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'caption'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['visible', 'hidden'], 'default' => 'visible'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_user_media', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('product_user_media', true);
    }
}
