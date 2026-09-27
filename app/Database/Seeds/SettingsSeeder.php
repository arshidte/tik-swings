<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $settings = [
            // Store identity
            'store_name'        => 'Swing & Grain',
            'store_tagline'     => 'Handcrafted wooden swings for the moments that matter.',
            'store_email'       => 'hello@swingandgrain.in',
            'store_phone'       => '+91 90000 00000',
            'whatsapp_number'   => '919000000000',
            'store_address'     => 'Workshop No. 14, Artisan Lane, Jodhpur, Rajasthan 342001',
            'currency'          => 'INR',
            'free_shipping_over' => '25000',
            'flat_shipping'     => '1499',
            'gst_percent'       => '0',

            // Social
            'social_instagram'  => 'https://instagram.com',
            'social_facebook'   => 'https://facebook.com',
            'social_pinterest'  => 'https://pinterest.com',
            'social_youtube'    => 'https://youtube.com',

            // SEO defaults
            'seo_title'         => 'Swing & Grain — Handcrafted Wooden Swings & Jhulas',
            'seo_description'   => 'Premium handcrafted wooden swings and jhulas made from solid teak and sheesham. Custom sizes, pan-India delivery and expert installation.',
            'og_image'          => 'assets/images/hero.svg',

            // Homepage hero (admin editable — §51)
            'hero_headline'     => 'Make Room for Moments.',
            'hero_subtext'      => 'Beautifully handcrafted wooden swings made to bring warmth, comfort and character to your home.',
            'hero_cta_primary'  => 'Explore Swings',
            'hero_cta_secondary' => 'Design Your Space',
            'hero_image'        => 'assets/images/hero.svg',
        ];

        $rows = [];
        foreach ($settings as $key => $value) {
            $rows[] = [
                'key'        => $key,
                'value'      => $value,
                'group'      => 'general',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->db->table('settings')->insertBatch($rows);
    }
}
