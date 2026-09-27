<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'first_name'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'password_hash'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'              => ['type' => 'ENUM', 'constraint' => ['customer', 'admin'], 'default' => 'customer'],
            'status'            => ['type' => 'ENUM', 'constraint' => ['active', 'suspended'], 'default' => 'active'],
            'email_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'remember_token'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'last_login_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('role');
        $this->forge->addKey('status');
        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
