<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBadgeToProductOptionValues extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('product_option_values', [
            'badge' => [
                'type'       => 'VARCHAR',
                'constraint' => 40,
                'null'       => true,
                'after'      => 'price_adjust',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('product_option_values', 'badge');
    }
}
