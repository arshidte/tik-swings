<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Swing Hanging Chain & Hardware Kit (SKU SW-CHAIN) — an accessory
 * that pairs with the wooden swings. Two models (Brass Colour / Steel Colour
 * finish) are represented as a single product with a Finish variant.
 *
 * Also creates a non-featured "Swing Chains" category (shows in the shop nav,
 * not the homepage collections) to give the accessory a home.
 *
 * Idempotent: re-running refreshes the product's images and variants.
 * NOTE: price is a placeholder — no price was supplied. Update in admin.
 */
class SwingChainsSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $slug = 'swing-hanging-chain-kit';
        $sku  = 'SW-CHAIN';
        $base = 'assets/images/products/swing-chains';

        // --- Category: Swing Chains (active, not featured) --------------------
        $catRow = $db->table('categories')->select('id')->where('slug', 'swing-chains')->get()->getRowArray();
        if ($catRow) {
            $catId = (int) $catRow['id'];
            $db->table('categories')->where('id', $catId)->update([
                'image' => "{$base}/brass-finish.webp", 'updated_at' => $now,
            ]);
        } else {
            $maxSort = (int) $db->table('categories')->selectMax('sort_order')->get()->getRow('sort_order');
            $db->table('categories')->insert([
                'name'            => 'Swing Chains',
                'slug'            => 'swing-chains',
                'description'     => 'Hanging chains and mounting hardware to install your wooden swing — in brass colour or steel finishes.',
                'image'           => "{$base}/brass-finish.webp",
                'sort_order'      => $maxSort + 1,
                'featured'        => 0,
                'status'          => 'active',
                'seo_title'       => 'Swing Chains & Hanging Hardware | Swing & Grain',
                'seo_description' => 'Complete hanging chain and mounting hardware kits for wooden swings, in brass colour and steel finishes.',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
            $catId = (int) $db->insertID();
        }

        // --- Attribute: Finish (Brass Colour / Steel Colour) ------------------
        $finishId = $this->ensureAttribute($db, 'Finish', 'metal-finish', 12);
        $finishValues = $this->ensureValues($db, $finishId, [
            'Brass Colour' => '#B08D57', 'Steel Colour' => '#B8BCC0',
        ]);

        // --- Product (upsert) --------------------------------------------------
        $short = 'A complete hanging kit for wooden swings — ceiling eye-bolts, S-hooks, decorative suspension rods and cover caps. Available in Brass Colour and Steel finishes.';
        $long  = "Everything you need to hang your wooden swing safely and beautifully. This suspension kit includes ceiling eye-bolts with rings, S-hooks, decorative hanging rods and finishing cover caps — supplied as a matched set for a clean, cohesive look.\n\n"
            . "Choose the finish that complements your swing and interior: a warm Brass Colour finish for a classic, luxurious look, or a sleek Steel finish for a modern, understated feel.\n\n"
            . "Designed to coordinate with our wooden swings. Please have the kit installed into a sound structural ceiling or beam by a qualified professional.";

        $productData = [
            'category_id'            => $catId,
            'name'                   => 'Swing Hanging Chain & Hardware Kit',
            'slug'                   => $slug,
            'sku'                    => $sku,
            'short_description'      => $short,
            'description'            => $long,
            'price'                  => 2999,    // placeholder — confirm & update
            'compare_price'          => 3999,    // placeholder
            'cost_price'             => null,
            'stock'                  => 40,
            'stock_status'           => 'in_stock',
            'has_variants'           => 1,
            'material'               => 'Metal',
            'wood_type'              => null,
            'finish'                 => null,
            'dimensions'             => null,     // to be confirmed
            'weight_capacity'        => null,     // to be confirmed
            'warranty'               => null,
            'delivery_estimate'      => 'Ships in 3–5 days',
            'installation_available' => 0,
            'is_customizable'        => 0,
            'featured'               => 0,
            'status'                 => 'active',
            'seo_title'              => 'Swing Hanging Chain & Hardware Kit | Brass & Steel Finish',
            'seo_description'        => 'Complete hanging hardware kit for wooden swings — eye-bolts, S-hooks and suspension rods in brass colour or steel finish.',
            'seo_keywords'           => 'swing chain, swing hanging chain, swing chain kit, wooden swing chain, swing suspension kit, swing hanging hardware, brass swing chain, steel swing chain, jhula chain, swing mounting kit',
            'updated_at'             => $now,
        ];

        $existing = $db->table('products')->select('id')->where('slug', $slug)->orWhere('sku', $sku)->get()->getRowArray();
        if ($existing) {
            $pid = (int) $existing['id'];
            $db->table('products')->where('id', $pid)->update($productData);
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

        $db->table('product_categories')->insert(['product_id' => $pid, 'category_id' => $catId]);

        // --- Images ------------------------------------------------------------
        $images = [
            ['brass-finish.webp', 'Brass colour swing hanging chain and hardware kit', 1, 0],
            ['steel-finish.webp', 'Steel finish swing hanging chain and hardware kit', 0, 1],
        ];
        foreach ($images as [$file, $alt, $primary, $so]) {
            $db->table('product_images')->insert([
                'product_id' => $pid,
                'image'      => "{$base}/{$file}",
                'image_type' => 'product',
                'alt_text'   => $alt,
                'sort_order' => $so,
                'is_primary' => $primary,
            ]);
        }

        // --- Variants: Finish (Brass / Steel), each with its own image --------
        $variants = [
            ['Brass Colour', 'BR', 'brass-finish.webp', 1],
            ['Steel Colour', 'ST', 'steel-finish.webp', 0],
        ];
        foreach ($variants as [$finish, $abbr, $img, $isDefault]) {
            $db->table('product_variants')->insert([
                'product_id'    => $pid,
                'sku'           => "{$sku}-{$abbr}",
                'name'          => "Swing Hanging Chain & Hardware Kit — {$finish}",
                'price'         => 2999,
                'compare_price' => 3999,
                'stock'         => 40,
                'image'         => "{$base}/{$img}",
                'is_default'    => $isDefault,
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
            $vid = (int) $db->insertID();
            $db->table('product_variant_attributes')->insert([
                'variant_id' => $vid, 'attribute_id' => $finishId, 'attribute_value_id' => $finishValues[$finish],
            ]);
        }

        echo "Seeded product #{$pid} (SW-CHAIN) with " . count($images) . " images and " . count($variants) . " finish variants, in category #{$catId} (Swing Chains).\n";
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
