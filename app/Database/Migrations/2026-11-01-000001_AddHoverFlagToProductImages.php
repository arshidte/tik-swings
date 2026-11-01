<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * product_images.is_hover — the photo a product card swaps to on hover.
 * (The card's default photo is the existing is_primary flag.)
 *
 * Backfill keeps today's storefront unchanged: until now the card used the
 * product's second photo for hover, so that photo is flagged for products
 * that have one.
 */
class AddHoverFlagToProductImages extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('product_images', [
            'is_hover' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_primary'],
        ]);

        $rows = $this->db->table('product_images')
            ->select('id, product_id')
            ->orderBy('product_id', 'ASC')->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $seen = [];
        foreach ($rows as $r) {
            $pid = (int) $r['product_id'];
            $seen[$pid] = ($seen[$pid] ?? 0) + 1;
            if ($seen[$pid] === 2) {
                $this->db->table('product_images')->where('id', $r['id'])->update(['is_hover' => 1]);
            }
        }
    }

    public function down(): void
    {
        $this->forge->dropColumn('product_images', 'is_hover');
    }
}
