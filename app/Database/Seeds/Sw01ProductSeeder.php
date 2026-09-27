<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the first real product — the Classic Wooden Hanging Swing (SKU SW-01)
 * from public/assets/images/products/SW-01/details.md — including its main
 * images, wood/chain finish variants, and the customer-uploaded gallery.
 *
 * Idempotent: re-running refreshes the product's images, variants and media.
 * NOTE: price/compare_price are placeholders (details.md has no price) — update
 * them in the admin once confirmed.
 */
class Sw01ProductSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $slug = 'classic-wooden-hanging-swing-metal-chains';
        $sku  = 'SW-01';
        $base = 'assets/images/products/SW-01';

        // --- Category ----------------------------------------------------------
        $catId = (int) ($db->table('categories')->select('id')->where('slug', 'classic-swings')->get()->getRow('id') ?? 0);

        // --- Attributes: Wood Finish & Chain Finish ---------------------------
        $woodFinishId  = $this->ensureAttribute($db, 'Wood Finish', 'wood-finish', 10);
        $chainFinishId = $this->ensureAttribute($db, 'Chain Finish', 'chain-finish', 11);

        $woodValues = $this->ensureValues($db, $woodFinishId, [
            'Light Teak' => '#C9A26A', 'Dark Teak' => '#7A4B28', 'Walnut' => '#5A3A22',
            'Mahogany'   => '#6E3B2E', 'Black'     => '#2A2320',
        ]);
        $chainValues = $this->ensureValues($db, $chainFinishId, [
            'Steel' => '#B8BCC0', 'Brass' => '#B08D57',
        ]);

        // --- Product (upsert) --------------------------------------------------
        $short = 'A classic wooden hanging swing on durable metal chains — an elegant jhula for living rooms, balconies, verandas and patios.';
        $long  = "Bring timeless elegance and natural warmth to your home with the Classic Wooden Hanging Swing with Metal Chains. Designed with a clean, traditional silhouette, this wooden jhula is an ideal addition to living rooms, balconies, verandas, patios, and other indoor or outdoor spaces.\n\n"
            . "The wooden seat features a smooth, polished finish and is suspended securely with durable metal chains. Choose from five wood finishes — Light Teak, Dark Teak, Walnut, Mahogany, and Black — along with Steel or Brass colour chain finishes to complement your interior style.\n\n"
            . "Whether you are creating a relaxing reading corner, an elegant balcony seating area, or a traditional Indian-style living space, this wooden hanging swing combines comfort, durability, and timeless design.";

        $productData = [
            'category_id'            => $catId ?: null,
            'name'                   => 'Classic Wooden Hanging Swing',
            'slug'                   => $slug,
            'sku'                    => $sku,
            'short_description'      => $short,
            'description'            => $long,
            'price'                  => 24999,   // placeholder — confirm & update
            'compare_price'          => 29999,   // placeholder
            'cost_price'             => null,
            'stock'                  => 15,
            'stock_status'           => 'made_to_order',
            'has_variants'           => 1,
            'material'               => 'Wood',
            'wood_type'              => null,     // species not confirmed (details.md)
            'finish'                 => null,     // finish is a selectable option, not fixed
            'dimensions'             => null,     // not confirmed
            'weight_capacity'        => null,     // not confirmed
            'warranty'               => null,
            'delivery_estimate'      => '3–5 weeks (made to order)',
            'installation_available' => 1,
            'is_customizable'        => 1,
            'featured'               => 1,
            'status'                 => 'active',
            'seo_title'              => 'Classic Wooden Hanging Swing with Metal Chains | Wooden Jhula',
            'seo_description'        => 'Shop a classic wooden hanging swing with durable metal chains. Available in 5 wood finishes and steel or brass chain finishes for indoor and outdoor spaces.',
            'seo_keywords'           => 'wooden hanging swing, wooden swing, wooden jhula, wooden swing for home, hanging swing for living room, wooden swing for balcony, indoor wooden swing, outdoor wooden swing, classic wooden swing, wooden swing with metal chains',
            'updated_at'             => $now,
        ];

        $existing = $db->table('products')->select('id')->where('slug', $slug)->orWhere('sku', $sku)->get()->getRowArray();
        if ($existing) {
            $pid = (int) $existing['id'];
            $db->table('products')->where('id', $pid)->update($productData);
            // clear children for a clean re-seed
            $db->table('product_images')->where('product_id', $pid)->delete();
            $db->table('product_user_media')->where('product_id', $pid)->delete();
            $vids = array_column($db->table('product_variants')->select('id')->where('product_id', $pid)->get()->getResultArray(), 'id');
            if ($vids) {
                $db->table('product_variant_attributes')->whereIn('variant_id', $vids)->delete();
            }
            $db->table('product_variants')->where('product_id', $pid)->delete();
            $db->table('product_categories')->where('product_id', $pid)->delete();
        } else {
            $productData['created_at'] = $now;
            $db->table('products')->insert($productData);
            $pid = (int) $db->insertID();
        }

        if ($catId) {
            $db->table('product_categories')->insert(['product_id' => $pid, 'category_id' => $catId]);
        }

        // --- Main images -------------------------------------------------------
        $images = [
            ['classic-swing-01.webp',         'product',   1, 0],
            ['classic-swing-02.webp',         'product',   0, 1],
            ['classic-swing-03.webp',         'lifestyle', 0, 2],
            ['classic-swing-04.webp',         'lifestyle', 0, 3],
            ['classic-swing-05-details.webp', 'detail',    0, 4],
        ];
        foreach ($images as [$file, $type, $primary, $so]) {
            $db->table('product_images')->insert([
                'product_id' => $pid,
                'image'      => "{$base}/main/{$file}",
                'image_type' => $type,
                'alt_text'   => 'Classic Wooden Hanging Swing — ' . ucfirst($type) . ' view',
                'sort_order' => $so,
                'is_primary' => $primary,
            ]);
        }

        // --- Variants: Wood Finish × Chain Finish -----------------------------
        $abbr = static fn (string $s): string => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $s), 0, 2));
        foreach ($woodValues as $wood => $wid) {
            foreach ($chainValues as $chain => $cid) {
                $isDefault = ($wood === 'Light Teak' && $chain === 'Steel') ? 1 : 0;
                $db->table('product_variants')->insert([
                    'product_id'    => $pid,
                    'sku'           => "{$sku}-" . $abbr($wood) . '-' . $abbr($chain),
                    'name'          => "Classic Wooden Hanging Swing — {$wood} / {$chain} chain",
                    'price'         => 24999,
                    'compare_price' => 29999,
                    'stock'         => 15,
                    'image'         => "{$base}/main/classic-swing-01.webp",
                    'is_default'    => $isDefault,
                    'status'        => 'active',
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
                $vid = (int) $db->insertID();
                $db->table('product_variant_attributes')->insert([
                    'variant_id' => $vid, 'attribute_id' => $woodFinishId, 'attribute_value_id' => $wid,
                ]);
                $db->table('product_variant_attributes')->insert([
                    'variant_id' => $vid, 'attribute_id' => $chainFinishId, 'attribute_value_id' => $cid,
                ]);
            }
        }

        // --- Customer gallery (user-uploaded) ---------------------------------
        $media = [
            ['user-01.webp', 'Aarav, Bengaluru',   'Our morning-coffee corner now has a swing.'],
            ['user-02.webp', 'Priya, Pune',        'Fits our balcony perfectly.'],
            ['user-03.webp', 'Nikhil, Hyderabad',  ''],
            ['user-04.webp', 'Sneha, Kochi',       'The kids fight over who sits first!'],
            ['user-05.webp', 'Rahul, Mumbai',      'Brass chains look premium against the wood.'],
            ['user-06.webp', 'Meera, Chennai',     'Reading nook goals.'],
            ['user-07.webp', 'Devika, Jaipur',     ''],
        ];
        $mo = 0;
        foreach ($media as [$file, $author, $caption]) {
            $db->table('product_user_media')->insert([
                'product_id'  => $pid,
                'media_type'  => 'image',
                'media'       => "{$base}/user/{$file}",
                'poster'      => null,
                'author_name' => $author ?: null,
                'caption'     => $caption ?: null,
                'sort_order'  => $mo++,
                'status'      => 'visible',
                'created_at'  => $now,
            ]);
        }

        echo "Seeded product #{$pid} (SW-01) with " . count($images) . " images, "
            . (count($woodValues) * count($chainValues)) . " variants and " . count($media) . " gallery items.\n";
    }

    private function ensureAttribute($db, string $name, string $slug, int $sort): int
    {
        $id = (int) ($db->table('product_attributes')->select('id')->where('slug', $slug)->get()->getRow('id') ?? 0);
        if ($id === 0) {
            $db->table('product_attributes')->insert(['name' => $name, 'slug' => $slug, 'sort_order' => $sort]);
            $id = (int) $db->insertID();
        }

        return $id;
    }

    /** @return array<string,int> value label => id */
    private function ensureValues($db, int $attrId, array $values): array
    {
        $out = [];
        $vo  = 0;
        foreach ($values as $label => $swatch) {
            $vslug = url_title($label, '-', true);
            $row   = $db->table('product_attribute_values')->select('id')
                ->where('attribute_id', $attrId)->where('slug', $vslug)->get()->getRowArray();
            if ($row) {
                $id = (int) $row['id'];
                $db->table('product_attribute_values')->where('id', $id)->update(['value' => $label, 'swatch' => $swatch, 'sort_order' => $vo]);
            } else {
                $db->table('product_attribute_values')->insert([
                    'attribute_id' => $attrId, 'value' => $label, 'slug' => $vslug, 'swatch' => $swatch, 'sort_order' => $vo,
                ]);
                $id = (int) $db->insertID();
            }
            $out[$label] = $id;
            $vo++;
        }

        return $out;
    }
}
