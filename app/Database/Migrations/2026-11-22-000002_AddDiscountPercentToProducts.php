<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A product's discount becomes one percentage that applies to the product and
 * every variant: each compare-at price is worked out from that row's own
 * selling price (price / (1 - pct/100), whole rupees). `compare_price` stays as
 * a derived, stored column so storefront queries keep reading it.
 *
 * Backfill: the percentage is taken from each product's current price and
 * compare price, then product and variant compare prices are recomputed from
 * it. Rows without a valid discount get NULL for both, so no variant can show
 * a "was" price below its selling price.
 */
class AddDiscountPercentToProducts extends Migration
{
    public function up(): void
    {
        // DDL commits implicitly in MySQL, so a failed backfill leaves the
        // column behind; skip re-adding it when the migration is re-run.
        if (! $this->db->fieldExists('discount_percent', 'products')) {
            $this->forge->addColumn('products', [
                'discount_percent' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                    'after'      => 'compare_price',
                ],
            ]);
        }

        $compareFor = static function (float $price, ?float $pct): ?float {
            if ($pct === null || $price <= 0 || $pct <= 0 || $pct >= 100) {
                return null;
            }

            return round($price / (1 - $pct / 100));
        };

        $this->db->transStart();

        $products = $this->db->table('products')->select('id, price, compare_price')->get()->getResultArray();
        foreach ($products as $p) {
            $price   = (float) $p['price'];
            $compare = (float) $p['compare_price'];
            $pct     = ($price > 0 && $compare > $price) ? round(($compare - $price) / $compare * 100, 2) : null;

            $this->db->table('products')->where('id', $p['id'])->update([
                'discount_percent' => $pct,
                'compare_price'    => $compareFor($price, $pct),
            ]);

            $variants = $this->db->table('product_variants')->select('id, price')
                ->where('product_id', $p['id'])->get()->getResultArray();
            foreach ($variants as $v) {
                $this->db->table('product_variants')->where('id', $v['id'])
                    ->update(['compare_price' => $compareFor((float) $v['price'], $pct)]);
            }
        }

        $this->db->transComplete();
        if (! $this->db->transStatus()) {
            throw new \RuntimeException('Backfilling discount_percent failed; the transaction was rolled back.');
        }
    }

    public function down(): void
    {
        // Recomputed compare prices are kept: they are valid on their own and
        // the pre-migration values are in the database backup if needed.
        $this->forge->dropColumn('products', 'discount_percent');
    }
}
