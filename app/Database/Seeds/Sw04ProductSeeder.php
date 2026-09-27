<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Premium Wooden Hanging Swing with Safety Railing & Brass Chains
 * (SKU SW-04) from public/assets/images/products/SW-04/details.txt — main
 * images, wood-finish variants, and the customer-uploaded gallery.
 *
 * Unlike SW-01/SW-02 this swing has a single customisation axis (Wood Finish);
 * the brass-finish chains are fixed, so there is no chain-finish option.
 *
 * Idempotent: re-running refreshes the product's images, variants and media.
 * NOTE: price/compare_price are placeholders (details have no price) — update
 * them in admin. Per details.txt we keep species, dimensions, load capacity,
 * weight and warranty unset ("to be confirmed").
 */
class Sw04ProductSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $slug = 'premium-wooden-hanging-swing';
        $sku  = 'SW-04';
        $base = 'assets/images/products/SW-04';

        // --- Category ----------------------------------------------------------
        $catId = (int) ($db->table('categories')->select('id')->where('slug', 'balcony-swings')->get()->getRow('id') ?? 0);

        // --- Attribute: Wood Finish (shared with SW-01/SW-02) -----------------
        $woodFinishId = $this->ensureAttribute($db, 'Wood Finish', 'wood-finish', 10);
        $woodValues   = $this->ensureValues($db, $woodFinishId, [
            'Light Teak' => '#C9A26A', 'Dark Teak' => '#7A4B28', 'Walnut' => '#5A3A22',
            'Mahogany'   => '#6E3B2E', 'Black'     => '#2A2320',
        ]);

        // --- Product (upsert) --------------------------------------------------
        $short = 'A premium wooden hanging swing with a polished platform, protective safety railing and brass-finish chains. Available in 5 wood finishes for indoor and covered outdoor spaces.';
        $long  = "Transform an ordinary corner into a relaxing retreat with this Premium Wooden Hanging Swing. Designed around a classic swing aesthetic, it combines a spacious seating platform with a distinctive safety railing to create a beautiful addition to modern and traditional spaces.\n\n"
            . "The swing features a smooth polished wooden finish that brings warmth and character to the space, while the surrounding railing provides a supportive enclosure around the seating area. The broad platform offers generous room for comfortable sitting and relaxing.\n\n"
            . "It is suspended using decorative brass-finish chains and sturdy hanging hardware, adding premium visual appeal while complementing the warm tones of the wood. Choose from five wood finishes — Light Teak, Dark Teak, Walnut, Mahogany and Black — to suit a warm traditional or modern minimal décor.\n\n"
            . "From a contemporary balcony to a traditional veranda, garden seating area or indoor relaxation space, this wooden swing becomes a distinctive focal point.";

        $productData = [
            'category_id'            => $catId ?: null,
            'name'                   => 'Premium Wooden Hanging Swing with Safety Railing',
            'slug'                   => $slug,
            'sku'                    => $sku,
            'short_description'      => $short,
            'description'            => $long,
            'price'                  => 39999,   // placeholder — confirm & update
            'compare_price'          => 47999,   // placeholder
            'cost_price'             => null,
            'stock'                  => 15,
            'stock_status'           => 'made_to_order',
            'has_variants'           => 1,
            'material'               => 'Wood',
            'wood_type'              => null,     // species not confirmed (details.txt)
            'finish'                 => null,     // finish is a selectable option, not fixed
            'dimensions'             => null,     // to be confirmed
            'weight_capacity'        => null,     // to be confirmed
            'warranty'               => null,     // to be confirmed
            'delivery_estimate'      => '3–5 weeks (made to order)',
            'installation_available' => 1,
            'is_customizable'        => 1,
            'featured'               => 1,
            'status'                 => 'active',
            'seo_title'              => 'Premium Wooden Hanging Swing with Brass Chains | Wooden Jhula',
            'seo_description'        => 'Shop premium wooden hanging swings with polished wood finish, safety railing and brass-finish chains. Available in 5 elegant finishes for indoor and outdoor spaces.',
            'seo_keywords'           => 'premium wooden hanging swing, wooden swing, wooden hanging swing, wooden jhula, wooden garden swing, wooden balcony swing, premium wooden swing, wooden swing with railing, wooden swing with brass chains, indoor wooden swing, outdoor wooden swing, wooden swing for veranda, luxury wooden swing',
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
            ['railing-swing-01.webp',          'product',   1, 0],
            ['railing-swing-02.webp',          'product',   0, 1],
            ['railing-swing-03.webp',          'lifestyle', 0, 2],
            ['railing-swing-04.webp',          'product',   0, 3],
            ['railing-swing-05-finishes.webp', 'detail',    0, 4],
        ];
        foreach ($images as [$file, $type, $primary, $so]) {
            $db->table('product_images')->insert([
                'product_id' => $pid,
                'image'      => "{$base}/main/{$file}",
                'image_type' => $type,
                'alt_text'   => 'Premium wooden hanging swing with safety railing and brass chains — ' . ucfirst($type) . ' view',
                'sort_order' => $so,
                'is_primary' => $primary,
            ]);
        }

        // --- Variants: Wood Finish only (brass chains are fixed) --------------
        $abbr = static fn (string $s): string => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $s), 0, 2));
        foreach ($woodValues as $wood => $wid) {
            $isDefault = ($wood === 'Light Teak') ? 1 : 0;
            $db->table('product_variants')->insert([
                'product_id'    => $pid,
                'sku'           => "{$sku}-" . $abbr($wood),
                'name'          => "Premium Wooden Hanging Swing — {$wood}",
                'price'         => 39999,
                'compare_price' => 47999,
                'stock'         => 15,
                'image'         => "{$base}/main/railing-swing-01.webp",
                'is_default'    => $isDefault,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $vid = (int) $db->insertID();
            $db->table('product_variant_attributes')->insert([
                'variant_id' => $vid, 'attribute_id' => $woodFinishId, 'attribute_value_id' => $wid,
            ]);
        }

        // --- Customer gallery (client-uploaded) -------------------------------
        $media = [
            ['user-01.webp', 'Rohit, Coimbatore', 'Looks even better in our balcony than in the photos.'],
            ['user-02.webp', 'Lakshmi, Thrissur', 'The railing makes it so relaxing to lean back.'],
            ['user-03.webp', 'Sana, Calicut',     ''],
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

        echo "Seeded product #{$pid} (SW-04) with " . count($images) . " images, "
            . count($woodValues) . " variants and " . count($media) . " gallery items.\n";
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
