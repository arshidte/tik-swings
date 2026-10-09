<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Concerns\BuildsSwingVariants;
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
    use BuildsSwingVariants;

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

        // --- Attributes: Wood Finish × Size × Polish (shared with SW-01) ------
        $woodFinishId = $this->ensureAttribute($db, 'Wood Finish', 'wood-finish', 10);
        $sizeId       = $this->ensureAttribute($db, 'Size', 'size', 2);
        $polishId     = $this->ensureAttribute($db, 'Polish', 'polish', 1);

        $woodValues = $this->ensureValues($db, $woodFinishId, [
            'Light Teak' => '#C9A26A', 'Dark Teak' => '#7A4B28', 'Walnut' => '#5A3A22',
            'Mahogany'   => '#6E3B2E', 'Black'     => '#2A2320',
        ]);
        $sizeValues = $this->ensureValues($db, $sizeId, [
            '3 Feet' => null, '4 Feet' => null, '5 Feet' => null,
        ]);
        $polishValues = $this->ensureValues($db, $polishId, [
            'Standard Polish' => null, 'Premium Polish' => null, 'Luxury Polish' => null,
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
            'price'                  => 14800,   // cheapest variant (3 Feet / Standard Polish)
            'compare_price'          => null,    // pricelist has no MRP
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

        // --- Variants: Wood Finish × Size × Polish (price by size × polish) ---
        $priceMatrix = [
            '3 Feet' => ['Standard Polish' => 14800, 'Premium Polish' => 16300, 'Luxury Polish' => 20100],
            '4 Feet' => ['Standard Polish' => 16600, 'Premium Polish' => 18100, 'Luxury Polish' => 21900],
            '5 Feet' => ['Standard Polish' => 18400, 'Premium Polish' => 19900, 'Luxury Polish' => 23700],
        ];
        $variantCount = $this->buildSwingVariants(
            $db, $pid, $sku, 'Premium Wooden Hanging Swing Jhula', "{$base}/main/side-rail-01.webp",
            15, $now, $woodFinishId, $sizeId, $polishId,
            $woodValues, $sizeValues, $polishValues, $priceMatrix, 'Light Teak'
        );

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
            . $variantCount . " variants and " . count($media) . " gallery item(s).\n";
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
