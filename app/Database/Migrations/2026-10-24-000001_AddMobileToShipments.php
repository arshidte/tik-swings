<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMobileToShipments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('shipments', [
            'mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'tracking_number'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('shipments', 'mobile');
    }
}
