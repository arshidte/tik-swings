<?php

namespace App\Database\Seeds;

use App\Models\ReviewModel;
use CodeIgniter\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $db  = $this->db;

        // --- Admin + demo customer --------------------------------------------
        $db->table('users')->insertBatch([
            [
                'first_name' => 'Store', 'last_name' => 'Admin',
                'email' => 'admin@swingandgrain.in', 'phone' => '9000000001',
                'password_hash' => password_hash('admin1234', PASSWORD_DEFAULT),
                'role' => 'admin', 'status' => 'active', 'email_verified_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'first_name' => 'Ananya', 'last_name' => 'Rao',
                'email' => 'customer@example.com', 'phone' => '9000000002',
                'password_hash' => password_hash('password', PASSWORD_DEFAULT),
                'role' => 'customer', 'status' => 'active', 'email_verified_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // --- Reviews (across a range of products) ------------------------------
        $products = $db->table('products')->select('id, slug')->get()->getResultArray();
        $byslug   = array_column($products, 'id', 'slug');

        $reviews = [
            ['aria-teak-wooden-swing', 'Priya Menon', 'Kochi', 5, 'Our favourite corner now', "The teak is gorgeous and the swing is whisper-quiet. My mother claims it every evening. Installation team was punctual and careful."],
            ['aria-teak-wooden-swing', 'Rahul Deshpande', 'Pune', 5, 'Built like an heirloom', "You can feel the weight and the quality the moment it arrives. Worth every rupee."],
            ['aria-teak-wooden-swing', 'Sana Kapoor', 'Delhi', 4, 'Beautiful, slightly delayed', "The swing itself is stunning. Delivery took a little longer than expected but the team kept me updated."],
            ['meera-carved-jhula', 'Lakshmi Iyer', 'Chennai', 5, 'Reminds me of my grandmother\'s home', "The carving is exquisite and the brass chains feel solid. It has completely transformed our veranda."],
            ['nova-modern-rope-swing', 'Kabir Shah', 'Mumbai', 5, 'Perfect for our apartment', "Minimal, light, and exactly the modern look we wanted. Fits our small balcony beautifully."],
            ['raaga-luxury-daybed-swing', 'Meghna Reddy', 'Hyderabad', 5, 'A showstopper', "Guests cannot stop talking about it. The cushion is deep and the movement is so smooth."],
            ['coorg-cane-teak-swing', 'Thomas Varghese', 'Bengaluru', 5, 'The cane seat is a dream', "Breathable, comfortable and beautifully made. Reminds me of home in Coorg."],
            ['heritage-brass-chain-jhula', 'Aarav Gupta', 'Jaipur', 4, 'Grand and sturdy', "Substantial piece — make sure you have the space. The brass chains are the highlight."],
            ['luna-balcony-hanging-chair', 'Ishita Sen', 'Kolkata', 5, 'My reading nook is complete', "It turns gently and feels like a hug. The cushion upgrade is worth it."],
            ['zen-minimal-indoor-swing', 'Neha Joshi', 'Ahmedabad', 5, 'Quietly beautiful', "Exactly the calm, understated piece I was looking for. Blends into the room perfectly."],
        ];

        foreach ($reviews as [$slug, $name, $city, $rating, $title, $body]) {
            if (! isset($byslug[$slug])) {
                continue;
            }
            $db->table('reviews')->insert([
                'product_id' => $byslug[$slug], 'author_name' => $name, 'city' => $city,
                'rating' => $rating, 'title' => $title, 'body' => $body,
                'verified_purchase' => 1, 'status' => 'approved',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Recompute rating aggregates
        $reviewModel = new ReviewModel();
        foreach (array_values($byslug) as $pid) {
            $reviewModel->recalculateProductRating((int) $pid);
        }

        // --- Banners -----------------------------------------------------------
        $db->table('banners')->insertBatch([
            [
                'title' => 'The Monsoon Collection', 'subtitle' => 'Weather-ready swings for balcony season',
                'image' => 'assets/images/lifestyle.svg', 'link' => '/balcony-swings',
                'cta_label' => 'Shop Balcony Swings', 'position' => 'promo', 'sort_order' => 0,
                'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // --- Coupons -----------------------------------------------------------
        $db->table('coupons')->insertBatch([
            [
                'code' => 'WELCOME10', 'description' => '10% off your first swing', 'type' => 'percent',
                'value' => 10, 'min_order' => 15000, 'max_discount' => 5000, 'usage_limit' => 1000,
                'usage_limit_user' => 1, 'used_count' => 0, 'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+3 months')),
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'code' => 'FESTIVE2500', 'description' => '₹2,500 off orders above ₹50,000', 'type' => 'fixed',
                'value' => 2500, 'min_order' => 50000, 'max_discount' => null, 'usage_limit' => 500,
                'usage_limit_user' => 1, 'used_count' => 0, 'status' => 'active',
                'expires_at' => date('Y-m-d H:i:s', strtotime('+2 months')),
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        // --- CMS pages & journal ----------------------------------------------
        $pages = [
            ['page', 'Our Story', 'about', 'Why we build swings, and who we build them with.',
                "Swing & Grain began in a small Jodhpur workshop with a simple belief: that a home needs at least one place made for pausing. We work with families of artisans who have shaped wood for generations, pairing their craft with designs made for the way we live now.\n\nEvery swing is cut from solid, seasoned hardwood — never veneer, never particle board — and finished by hand. We make to order, so nothing sits in a warehouse and every piece carries the small, human marks of the person who made it."],
            ['page', 'The Craft', 'craftsmanship', 'From seasoned timber to a swing that lasts generations.',
                "Great swings begin long before the first cut. We season our teak and sheesham for months so the wood is stable and the grain settles. Our artisans then shape each frame by hand, testing every joint for strength and every curve for comfort.\n\nWe use traditional joinery reinforced with modern hardware, marine-grade rope, and hand-polished brass. The result is a swing engineered to move silently and safely for decades."],
            ['page', 'Design Your Own Swing', 'custom-swings', 'Made to measure, made for your space.',
                "Have a specific corner, a particular wood, or a size in mind? Our custom program lets you shape a swing around your space. Choose the wood, the finish, the rope, the cushion and the exact dimensions, and our team will craft it to order.\n\nShare your space on WhatsApp and we will guide you from first sketch to installation."],
            ['page', 'Frequently Asked Questions', 'faq', 'Delivery, installation, materials and care.',
                "How long does delivery take?\nMost swings are handcrafted to order and ship in 3–5 weeks. Custom pieces may take longer; we will always share a timeline before you order.\n\nDo you install?\nYes. Expert installation is available across most Indian cities and is recommended for ceiling-mounted swings.\n\nWhat woods do you use?\nPrimarily solid teak and sheesham, with mango wood for lighter pieces. We never use veneer or particle board.\n\nHow do I care for my swing?\nWipe with a dry cloth, keep out of direct standing water, and oil the wood once or twice a year. A care guide ships with every order."],
        ];
        foreach ($pages as [$type, $title, $slug, $excerpt, $body]) {
            $db->table('pages')->insert([
                'type' => $type, 'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt,
                'body' => $body, 'cover_image' => 'assets/images/lifestyle.svg',
                'author' => 'Swing & Grain', 'status' => 'published',
                'seo_title' => "{$title} | Swing & Grain", 'seo_description' => $excerpt,
                'published_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $journal = [
            ['How to Choose the Right Swing for Your Balcony', 'choose-swing-for-balcony', 'Size, weight and weather — a practical guide to balcony swings.', 'craft/comfort'],
            ['The Quiet Luxury of Solid Teak', 'quiet-luxury-of-solid-teak', 'Why solid teak ages beautifully and lasts a lifetime.', 'craft/real-wood'],
            ['Five Ways to Style a Jhula Indoors', 'style-a-jhula-indoors', 'Bring a traditional jhula into a modern living room.', 'rooms/living-room'],
        ];
        foreach ($journal as $i => [$title, $slug, $excerpt, $img]) {
            $db->table('pages')->insert([
                'type' => 'journal', 'title' => $title, 'slug' => $slug, 'excerpt' => $excerpt,
                'body' => "<p>{$excerpt}</p><p>Our workshop notes on living well with a handcrafted swing — the details that matter, and the small rituals a swing brings into a home.</p>",
                'cover_image' => "assets/images/{$img}.svg", 'author' => 'Swing & Grain Studio',
                'status' => 'published', 'seo_title' => "{$title} | Journal", 'seo_description' => $excerpt,
                'published_at' => date('Y-m-d H:i:s', strtotime("-{$i} weeks")),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
