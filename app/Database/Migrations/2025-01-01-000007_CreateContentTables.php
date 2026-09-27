<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContentTables extends Migration
{
    public function up(): void
    {
        // --- reviews -----------------------------------------------------------
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id'        => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'order_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'author_name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'rating'            => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 5],
            'title'             => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'body'              => ['type' => 'TEXT', 'null' => true],
            'verified_purchase' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'            => ['type' => 'ENUM', 'constraint' => ['pending', 'approved', 'rejected'], 'default' => 'pending'],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('reviews', true);

        // --- review_images -----------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'review_id'  => ['type' => 'BIGINT', 'unsigned' => true],
            'image'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('review_id');
        $this->forge->addForeignKey('review_id', 'reviews', 'id', '', 'CASCADE');
        $this->forge->createTable('review_images', true);

        // --- product_questions -------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'product_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'question'   => ['type' => 'TEXT'],
            'answer'     => ['type' => 'TEXT', 'null' => true],
            'status'     => ['type' => 'ENUM', 'constraint' => ['pending', 'answered', 'hidden'], 'default' => 'pending'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('product_id', 'products', 'id', '', 'CASCADE');
        $this->forge->createTable('product_questions', true);

        // --- contact_messages --------------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'message'    => ['type' => 'TEXT'],
            'status'     => ['type' => 'ENUM', 'constraint' => ['new', 'read', 'replied', 'archived'], 'default' => 'new'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('contact_messages', true);

        // --- newsletter_subscribers --------------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'status'     => ['type' => 'ENUM', 'constraint' => ['subscribed', 'unsubscribed'], 'default' => 'subscribed'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('newsletter_subscribers', true);

        // --- pages (CMS: about, faq, journal, etc.) ----------------------------
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'type'            => ['type' => 'ENUM', 'constraint' => ['page', 'journal'], 'default' => 'page'],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 190],
            'slug'            => ['type' => 'VARCHAR', 'constraint' => 210],
            'excerpt'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'body'            => ['type' => 'LONGTEXT', 'null' => true],
            'cover_image'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'author'          => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['draft', 'published'], 'default' => 'published'],
            'seo_title'       => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'seo_description' => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'published_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->addKey('type');
        $this->forge->addKey('status');
        $this->forge->createTable('pages', true);

        // --- banners -----------------------------------------------------------
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'subtitle'    => ['type' => 'VARCHAR', 'constraint' => 320, 'null' => true],
            'image'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'link'        => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cta_label'   => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'position'    => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'hero'],
            'sort_order'  => ['type' => 'INT', 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('position');
        $this->forge->addKey('status');
        $this->forge->createTable('banners', true);

        // --- settings (key-value store) ----------------------------------------
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'group'      => ['type' => 'VARCHAR', 'constraint' => 60, 'default' => 'general'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('key');
        $this->forge->addKey('group');
        $this->forge->createTable('settings', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('settings', true);
        $this->forge->dropTable('banners', true);
        $this->forge->dropTable('pages', true);
        $this->forge->dropTable('newsletter_subscribers', true);
        $this->forge->dropTable('contact_messages', true);
        $this->forge->dropTable('product_questions', true);
        $this->forge->dropTable('review_images', true);
        $this->forge->dropTable('reviews', true);
    }
}
