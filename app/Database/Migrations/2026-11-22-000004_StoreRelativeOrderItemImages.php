<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Order items used to store the image as a full URL with the site's host baked
 * in (e.g. http://localhost:8080/uploads/...), which breaks after a domain
 * change. Convert those to the stored path ("uploads/...", "assets/..."); views
 * render it through product_image(). Other external URLs are left as they are.
 */
class StoreRelativeOrderItemImages extends Migration
{
    public function up(): void
    {
        $rows = $this->db->table('order_items')->select('id, image')
            ->like('image', '://', 'both')->get()->getResultArray();

        $this->db->transStart();
        foreach ($rows as $row) {
            if (preg_match('#^https?://[^?\#]*?/((?:assets|uploads)/.+)$#i', (string) $row['image'], $m)) {
                $this->db->table('order_items')->where('id', $row['id'])->update(['image' => $m[1]]);
            }
        }
        $this->db->transComplete();

        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Converting order item images failed; the transaction was rolled back.');
        }
    }

    public function down(): void
    {
        // Nothing to undo: relative paths render correctly with earlier code
        // too, since order views have always gone through product_image().
    }
}
