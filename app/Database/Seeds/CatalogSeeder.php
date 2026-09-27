<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        helper('url');
        $now = date('Y-m-d H:i:s');
        $db  = $this->db;

        // --- Categories --------------------------------------------------------
        $categories = [
            ['Classic Swings', 'classic-swings', 'Timeless wooden swings with clean silhouettes and honest joinery.', 1],
            ['Modern Swings', 'modern-swings', 'Contemporary lines and minimal frames for the modern Indian home.', 1],
            ['Traditional Jhulas', 'traditional-jhulas', 'Hand-carved jhulas that carry generations of craft.', 1],
            ['Indoor Swings', 'indoor-swings', 'Swings sized and finished for living rooms and reading corners.', 0],
            ['Balcony Swings', 'balcony-swings', 'Weather-friendly swings made for open balconies and verandas.', 1],
            ['Luxury Swings', 'luxury-swings', 'Statement daybed swings in premium hardwood and brass.', 1],
            ['Kids Swings', 'kids-swings', 'Safe, rounded, playful swings crafted for little ones.', 0],
            ['Custom Swings', 'custom-swings', 'Made-to-measure swings designed around your space.', 1],
        ];
        $catIds = [];
        $order  = 0;
        foreach ($categories as [$name, $slug, $desc, $featured]) {
            $db->table('categories')->insert([
                'name' => $name, 'slug' => $slug, 'description' => $desc,
                'image' => "assets/images/categories/{$slug}.svg",
                'sort_order' => $order++, 'featured' => $featured, 'status' => 'active',
                'seo_title' => "{$name} — Handcrafted Wooden Swings | Swing & Grain",
                'seo_description' => $desc,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $catIds[$slug] = (int) $db->insertID();
        }

        // --- Attributes & values ----------------------------------------------
        $attributes = [
            'wood'    => ['Wood', ['Teak' => '#A97449', 'Sheesham' => '#7A4F2F', 'Mango Wood' => '#C99C5F']],
            'finish'  => ['Finish', ['Natural' => '#C9A580', 'Honey' => '#C99C5F', 'Walnut' => '#5F4530', 'Dark' => '#3C2A1C']],
            'size'    => ['Size', ['Standard' => '', 'Large' => '', 'Custom' => '']],
            'rope'    => ['Rope', ['Natural' => '#C9A580', 'Beige' => '#E7DFD3', 'Black' => '#2A231E']],
            'cushion' => ['Cushion', ['None' => '', 'Beige' => '#E7DFD3', 'Cream' => '#FBF7F1', 'Custom' => '']],
        ];
        $attrIds  = [];
        $valueIds = [];
        $ao       = 0;
        foreach ($attributes as $slug => [$name, $values]) {
            $db->table('product_attributes')->insert(['name' => $name, 'slug' => $slug, 'sort_order' => $ao++]);
            $aid            = (int) $db->insertID();
            $attrIds[$slug] = $aid;
            $vo             = 0;
            foreach ($values as $val => $swatch) {
                $vslug = url_title($val, '-', true);
                $db->table('product_attribute_values')->insert([
                    'attribute_id' => $aid, 'value' => $val, 'slug' => $vslug,
                    'swatch' => $swatch ?: null, 'sort_order' => $vo++,
                ]);
                $valueIds[$slug][$val] = (int) $db->insertID();
            }
        }

        // --- Products ----------------------------------------------------------
        // [name, slug, category, price, compare, wood, finish, dims, capacity, featured, customizable, short, long]
        $products = [
            ['Aria Teak Wooden Swing', 'aria-teak-wooden-swing', 'classic-swings', 32999, 41999, 'Teak', 'Honey', '150 × 60 × 180 cm', '200 kg', 1, 1,
                'A gently curved solid-teak seat suspended on hand-braided rope.',
                "The Aria is our most-loved everyday swing — a gently curved solid-teak seat suspended on hand-braided cotton rope. Each frame is cut from a single seasoned teak beam and finished by hand, so the grain reads warm and continuous across the seat. Made to hold a slow morning coffee, an afternoon nap, or a long conversation that runs past dinner."],
            ['Meera Carved Jhula', 'meera-carved-jhula', 'traditional-jhulas', 58999, 72999, 'Sheesham', 'Walnut', '180 × 70 × 200 cm', '250 kg', 1, 1,
                'A hand-carved sheesham jhula with heritage motifs and brass chains.',
                "The Meera revives the classic Indian jhula in seasoned sheesham. Artisans hand-carve the backrest and armrests with heritage floral motifs, then hang the seat on solid brass chains. A piece that anchors a veranda and quietly becomes the heart of the home."],
            ['Nova Modern Rope Swing', 'nova-modern-rope-swing', 'modern-swings', 24999, 29999, 'Mango Wood', 'Natural', '120 × 55 × 160 cm', '150 kg', 1, 1,
                'Minimal mango-wood plank on clean natural rope — quietly modern.',
                "The Nova strips the swing back to its essentials: a single clean mango-wood plank on natural rope, with no ornament to get in the way. It reads light in a room and looks as good empty as it does in use."],
            ['Veranda Single-Seater Swing', 'veranda-single-seater-swing', 'balcony-swings', 27999, 33999, 'Teak', 'Natural', '90 × 60 × 180 cm', '160 kg', 0, 1,
                'A compact single-seater sized for balconies and small verandas.',
                "Designed for real Indian balconies, the Veranda gives you a proper swing in a compact footprint. Weather-resistant teak and a marine-grade rope mean it takes the sun and the monsoon in its stride."],
            ['Heritage Brass-Chain Jhula', 'heritage-brass-chain-jhula', 'traditional-jhulas', 64999, 79999, 'Sheesham', 'Dark', '190 × 75 × 210 cm', '280 kg', 1, 0,
                'A grand sheesham jhula on polished brass chains for the courtyard.',
                "The Heritage is our most substantial jhula — a broad sheesham seat with carved side panels, hung on hand-polished brass chains rated for generations of use. Built for courtyards and large verandas where a swing becomes the gathering point."],
            ['Luna Balcony Hanging Chair', 'luna-balcony-hanging-chair', 'balcony-swings', 18999, 23999, 'Mango Wood', 'Honey', '80 × 80 × 120 cm', '120 kg', 0, 1,
                'A cocooning hanging chair for one, with an optional cushion.',
                "The Luna wraps you in a gentle curve of steam-bent mango wood. It hangs from a single point, turns softly, and makes even a narrow balcony feel like a retreat."],
            ['Raaga Luxury Daybed Swing', 'raaga-luxury-daybed-swing', 'luxury-swings', 124999, 149999, 'Teak', 'Walnut', '210 × 120 × 200 cm', '350 kg', 1, 1,
                'A full daybed swing in premium teak with brass detailing.',
                "The Raaga is a daybed that swings — a statement piece in premium teak with inlaid brass detailing and a deep, full-length cushion. Wide enough for two to stretch out, engineered to move without a whisper."],
            ['Kiddo Mango-Wood Swing', 'kiddo-mango-wood-swing', 'kids-swings', 9999, 12999, 'Mango Wood', 'Natural', '60 × 40 × 150 cm', '80 kg', 0, 0,
                'A rounded, splinter-free swing crafted for little ones.',
                "The Kiddo is built for small adventurers — every edge is rounded, every surface sanded smooth, and the rope is soft on little hands. Non-toxic finishes throughout, tested to hold up to years of enthusiastic swinging."],
            ['Coorg Cane & Teak Swing', 'coorg-cane-teak-swing', 'classic-swings', 38999, 46999, 'Teak', 'Honey', '160 × 65 × 185 cm', '210 kg', 1, 1,
                'A teak frame with a hand-woven cane seat for breezy comfort.',
                "The Coorg pairs a solid teak frame with a hand-woven natural cane seat, so it breathes in the heat and cradles you gently. Inspired by the plantation verandas of the Western Ghats."],
            ['Ellora Sheesham Jhula', 'ellora-sheesham-jhula', 'indoor-swings', 44999, 54999, 'Sheesham', 'Honey', '170 × 70 × 190 cm', '240 kg', 0, 1,
                'A refined indoor jhula that suits both classic and modern rooms.',
                "The Ellora is a jhula tuned for indoor living — cleaner carving, a warmer honey finish, and a seat height set for easy everyday use. Equally at home beneath a chandelier or beside a bookshelf."],
            ['Zen Minimal Indoor Swing', 'zen-minimal-indoor-swing', 'indoor-swings', 21999, 26999, 'Mango Wood', 'Natural', '110 × 50 × 155 cm', '140 kg', 0, 1,
                'A pared-back indoor swing for reading corners and calm spaces.',
                "The Zen is a study in restraint — a slim mango-wood seat on fine natural rope, designed to disappear into a room until you need it. The reading corner it was made for."],
            ['Maharaja Carved Swing', 'maharaja-carved-swing', 'luxury-swings', 154999, 189999, 'Sheesham', 'Dark', '220 × 110 × 215 cm', '380 kg', 1, 1,
                'An heirloom carved swing in sheesham with intricate brass inlay.',
                "The Maharaja is our flagship — an heirloom swing in seasoned sheesham, hand-carved over weeks and finished with intricate brass inlay. A commission piece that turns a room into a story."],
        ];

        $skuN = 1000;
        foreach ($products as $p) {
            [$name, $slug, $cat, $price, $compare, $wood, $finish, $dims, $cap, $featured, $custom, $short, $long] = $p;
            $sku = 'SG-' . str_pad((string) $skuN++, 4, '0', STR_PAD_LEFT);

            $db->table('products')->insert([
                'category_id' => $catIds[$cat], 'name' => $name, 'slug' => $slug, 'sku' => $sku,
                'short_description' => $short, 'description' => $long,
                'price' => $price, 'compare_price' => $compare, 'cost_price' => round($price * 0.55),
                'stock' => random_int(4, 30), 'stock_status' => 'in_stock', 'has_variants' => $custom ? 1 : 0,
                'material' => 'Solid ' . $wood, 'wood_type' => $wood, 'finish' => $finish,
                'dimensions' => $dims, 'weight_capacity' => $cap,
                'warranty' => '3-year structural warranty', 'delivery_estimate' => '3–5 weeks (handcrafted to order)',
                'installation_available' => 1, 'is_customizable' => $custom,
                'rating_avg' => 0, 'rating_count' => 0,
                'featured' => $featured, 'status' => 'active',
                'seo_title' => "{$name} — Handcrafted {$wood} Swing | Swing & Grain",
                'seo_description' => $short,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $pid = (int) $db->insertID();

            // Link to category (M2M)
            $db->table('product_categories')->insert(['product_id' => $pid, 'category_id' => $catIds[$cat]]);

            // Images (product, hover, detail)
            $imgs = [
                ["assets/images/products/{$slug}-1.svg", 'product', 1, 0],
                ["assets/images/products/{$slug}-2.svg", 'lifestyle', 0, 1],
                ["assets/images/products/{$slug}-3.svg", 'detail', 0, 2],
            ];
            foreach ($imgs as [$path, $type, $primary, $so]) {
                $db->table('product_images')->insert([
                    'product_id' => $pid, 'image' => $path, 'image_type' => $type,
                    'alt_text' => $name . ' — ' . ucfirst($type) . ' view',
                    'sort_order' => $so, 'is_primary' => $primary,
                ]);
            }

            // Variants for customizable products (Wood × Size affects price)
            if ($custom) {
                $variantDefs = [
                    ['Standard', 'Standard', 0, 1],
                    ['Large', 'Large', round($price * 0.22), 0],
                ];
                foreach ($variantDefs as [$sizeVal, $vname, $delta, $isDefault]) {
                    $vsku = $sku . '-' . strtoupper(substr($sizeVal, 0, 3));
                    $db->table('product_variants')->insert([
                        'product_id' => $pid, 'sku' => $vsku, 'name' => $name . ' — ' . $vname,
                        'price' => $price + $delta, 'compare_price' => $compare + $delta,
                        'stock' => random_int(3, 15),
                        'image' => "assets/images/products/{$slug}-1.svg",
                        'is_default' => $isDefault, 'status' => 'active',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $vid = (int) $db->insertID();
                    // attach size + wood attribute values
                    $db->table('product_variant_attributes')->insert([
                        'variant_id' => $vid, 'attribute_id' => $attrIds['size'],
                        'attribute_value_id' => $valueIds['size'][$sizeVal],
                    ]);
                    if (isset($valueIds['wood'][$wood])) {
                        $db->table('product_variant_attributes')->insert([
                            'variant_id' => $vid, 'attribute_id' => $attrIds['wood'],
                            'attribute_value_id' => $valueIds['wood'][$wood],
                        ]);
                    }
                }
            }
        }
    }
}
