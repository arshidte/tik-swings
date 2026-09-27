<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Premium Wooden Hanging Swing Jhula (SKU SW-02) from
 * public/assets/images/products/SW-02/details.md — main images, wood/chain
 * finish variants, and the customer-uploaded gallery.
 *
 * Idempotent: re-running refreshes the product's images, variants and media.
 * NOTE: price/compare_price are placeholders (details.md has no price) — update
 * them in the admin once confirmed. Per details.md we avoid unverified claims
 * (species, "solid wood", dimensions, weight capacity, "handcrafted").
 */
class Sw02ProductSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $slug = 'premium-wooden-hanging-swing-jhula';
        $sku  = 'SW-02';
        $base = 'assets/images/products/SW-02';

        // --- Category ----------------------------------------------------------
        $catId = (int) ($db->table('categories')->select('id')->where('slug', 'traditional-jhulas')->get()->getRow('id') ?? 0);

        // --- Attributes: Wood Finish & Chain Finish (shared with SW-01) --------
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
        $short = 'An elegant wooden hanging swing with a polished platform, decorative slatted side rails and turned-leg detailing. Choose from 5 wood finishes and 2 chain finishes to match your space.';
        $long  = "Bring a sophisticated blend of traditional design and contemporary elegance into your space with this premium wooden hanging swing. Designed to become a statement piece, the swing features a spacious wooden platform, decorative side rails, polished edges, and distinctive turned wooden legs.\n\n"
            . "The swing is suspended using a four-point chain arrangement, with multiple chain and wood-finish options available to complement different interior styles. Choose from five wood finishes — Light Teak, Dark Teak, Walnut, Mahogany, and Black — paired with either a Steel or Brass colour chain finish.\n\n"
            . "Whether you are furnishing a modern home, a traditional Kerala-style residence, a luxury villa, balcony, veranda, courtyard, resort or covered outdoor area, this wooden Jhula adds warmth, character and a relaxing focal point to the space.";

        $productData = [
            'category_id'            => $catId ?: null,
            'name'                   => 'Premium Wooden Hanging Swing Jhula',
            'slug'                   => $slug,
            'sku'                    => $sku,
            'short_description'      => $short,
            'description'            => $long,
            'price'                  => 34999,   // placeholder — confirm & update
            'compare_price'          => 42999,   // placeholder
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
            'seo_title'              => 'Premium Wooden Hanging Swing Jhula with Decorative Side Rails | 5 Wood Finishes',
            'seo_description'        => 'Shop premium wooden hanging swings and Jhulas with decorative side rails. Available in 5 wood finishes and steel or brass colour chain options for indoor and outdoor spaces.',
            'seo_keywords'           => 'wooden hanging swing, wooden swing for home, wooden jhula, wooden hanging jhula, hanging swing for home, premium wooden swing, wooden swing chair, wooden indoor swing, wooden balcony swing, wooden veranda swing, wooden patio swing, luxury wooden swing, traditional wooden swing, wooden swing with chain',
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
            ['side-rail-01.webp',         'product',   1, 0],
            ['side-rail-02.webp',         'product',   0, 1],
            ['side-rail-03.webp',         'lifestyle', 0, 2],
            ['side-rail-04-details.webp', 'detail',    0, 3],
        ];
        foreach ($images as [$file, $type, $primary, $so]) {
            $db->table('product_images')->insert([
                'product_id' => $pid,
                'image'      => "{$base}/main/{$file}",
                'image_type' => $type,
                'alt_text'   => 'Premium Wooden Hanging Swing Jhula — ' . ucfirst($type) . ' view',
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
                    'name'          => "Premium Wooden Hanging Swing Jhula — {$wood} / {$chain} chain",
                    'price'         => 34999,
                    'compare_price' => 42999,
                    'stock'         => 15,
                    'image'         => "{$base}/main/side-rail-01.webp",
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
            ['user-01.webp', 'Anjali, Kochi', 'The decorative side rails make it feel like a proper daybed.'],
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

        echo "Seeded product #{$pid} (SW-02) with " . count($images) . " images, "
            . (count($woodValues) * count($chainValues)) . " variants and " . count($media) . " gallery item(s).\n";
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
