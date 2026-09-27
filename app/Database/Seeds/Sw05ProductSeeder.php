<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the Luxury Wooden Sofa Swing with Cushioned Seating (SKU SW-05) from
 * public/assets/images/products/SW-05/details.md — main images, wood/chain
 * finish variants, and the customer-uploaded gallery.
 *
 * Idempotent: re-running refreshes the product's images, variants and media.
 * NOTE: price/compare_price are placeholders (details have no price) — update
 * them in admin. Per details.md we keep species, dimensions, load capacity,
 * weight, installation and warranty unset ("to be confirmed").
 */
class Sw05ProductSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $db  = $this->db;
        $now = date('Y-m-d H:i:s');

        $slug = 'premium-wooden-sofa-swing';
        $sku  = 'SW-05';
        $base = 'assets/images/products/SW-05';

        // --- Category ----------------------------------------------------------
        $catId = (int) ($db->table('categories')->select('id')->where('slug', 'luxury-swings')->get()->getRow('id') ?? 0);

        // --- Attributes: Wood Finish & Chain Finish (shared) ------------------
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
        $short = 'A luxury wooden sofa swing with a polished wooden frame, plush cushioned seat and backrest, and elegant side railings. Choose from 5 wood finishes and 2 chain finishes.';
        $long  = "Create a dedicated space for relaxation with the Luxury Wooden Sofa Swing — a statement piece that combines the warmth of polished wood with the comfort of plush upholstered seating.\n\n"
            . "The swing features a styled wooden frame with smooth rounded edges, vertical side and back detailing, and an elevated platform base. A generously cushioned seat and supportive back cushion turn the traditional hanging swing into a comfortable sofa-style seating experience, while the surrounding wooden railing adds character and a more enclosed, supportive design.\n\n"
            . "Decorative hanging chains complete the look and let you customise the appearance with either a modern Steel finish or a classic Brass colour finish. The wooden frame is available in five finishes — Light Teak, Dark Teak, Walnut, Mahogany and Black.\n\n"
            . "Whether placed in a bright balcony, an elegant veranda, a spacious living area, a covered patio or a relaxation corner, this wooden sofa swing adds both comfort and visual warmth.";

        $productData = [
            'category_id'            => $catId ?: null,
            'name'                   => 'Luxury Wooden Sofa Swing with Cushioned Seating',
            'slug'                   => $slug,
            'sku'                    => $sku,
            'short_description'      => $short,
            'description'            => $long,
            'price'                  => 49999,   // placeholder — confirm & update
            'compare_price'          => 59999,   // placeholder
            'cost_price'             => null,
            'stock'                  => 12,
            'stock_status'           => 'made_to_order',
            'has_variants'           => 1,
            'material'               => 'Wood',
            'wood_type'              => null,     // species not confirmed (details.md)
            'finish'                 => null,     // finish is a selectable option, not fixed
            'dimensions'             => null,     // to be confirmed
            'weight_capacity'        => null,     // to be confirmed
            'warranty'               => null,     // to be confirmed
            'delivery_estimate'      => '4–6 weeks (made to order)',
            'installation_available' => 1,
            'is_customizable'        => 1,
            'featured'               => 1,
            'status'                 => 'active',
            'seo_title'              => 'Premium Wooden Sofa Swing with Cushions | Hanging Swing',
            'seo_description'        => 'Discover a premium wooden sofa swing with plush cushions, supportive railing and polished wooden frame. Available in 5 wood finishes and 2 chain finish options.',
            'seo_keywords'           => 'premium wooden sofa swing, wooden sofa swing, wooden hanging sofa swing, luxury wooden swing, wooden swing with cushions, cushioned wooden swing, wooden swing for living room, wooden swing for balcony, wooden jhula with cushions, premium wooden jhula, wooden swing with railing, indoor wooden swing, outdoor wooden swing, luxury jhula for home',
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
            ['sofa-swing-01.webp', 'product',   1, 0],
            ['sofa-swing-02.webp', 'product',   0, 1],
            ['sofa-swing-03.webp', 'lifestyle', 0, 2],
            ['sofa-swing-04.webp', 'lifestyle', 0, 3],
        ];
        foreach ($images as [$file, $type, $primary, $so]) {
            $db->table('product_images')->insert([
                'product_id' => $pid,
                'image'      => "{$base}/main/{$file}",
                'image_type' => $type,
                'alt_text'   => 'Luxury wooden sofa swing with cushions and hanging chains — ' . ucfirst($type) . ' view',
                'sort_order' => $so,
                'is_primary' => $primary,
            ]);
        }

        // --- Variants: Wood Finish × Chain Finish -----------------------------
        $abbr = static fn (string $s): string => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $s), 0, 2));
        foreach ($woodValues as $wood => $wid) {
            foreach ($chainValues as $chain => $cid) {
                $isDefault = ($wood === 'Light Teak' && $chain === 'Brass') ? 1 : 0;
                $db->table('product_variants')->insert([
                    'product_id'    => $pid,
                    'sku'           => "{$sku}-" . $abbr($wood) . '-' . $abbr($chain),
                    'name'          => "Luxury Wooden Sofa Swing — {$wood} / {$chain} chain",
                    'price'         => 49999,
                    'compare_price' => 59999,
                    'stock'         => 12,
                    'image'         => "{$base}/main/sofa-swing-01.webp",
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

        // --- Customer gallery (client-uploaded) -------------------------------
        $media = [
            ['user-01.webp', 'Fathima, Malappuram', 'Exactly like the pictures — so comfortable with the cushions.'],
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

        echo "Seeded product #{$pid} (SW-05) with " . count($images) . " images, "
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
