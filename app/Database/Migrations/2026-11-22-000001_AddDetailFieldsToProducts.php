<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-product overrides for three product-page accordion sections
 * (Craftsmanship, Returns, Care Instructions) that previously showed the same
 * hardcoded text on every product. Null/blank keeps that default text
 * (see App\Views\frontend\product's $accordions).
 */
class AddDetailFieldsToProducts extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('products', [
            'craftsmanship' => [
                'type'    => 'MEDIUMTEXT',
                'null'    => true,
                'after'   => 'description',
            ],
            'returns_policy' => [
                'type'  => 'MEDIUMTEXT',
                'null'  => true,
                'after' => 'craftsmanship',
            ],
            'care_instructions' => [
                'type'  => 'MEDIUMTEXT',
                'null'  => true,
                'after' => 'returns_policy',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('products', ['craftsmanship', 'returns_policy', 'care_instructions']);
    }
}
