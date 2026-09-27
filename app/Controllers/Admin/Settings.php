<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SettingModel;

class Settings extends BaseController
{
    /** Whitelisted, editable settings (§40 — never trust arbitrary keys). */
    private array $fields = [
        'store_name', 'store_tagline', 'store_email', 'store_phone', 'whatsapp_number',
        'store_address', 'free_shipping_over', 'flat_shipping',
        'social_instagram', 'social_facebook', 'social_pinterest', 'social_youtube',
        'seo_title', 'seo_description',
        'hero_headline', 'hero_subtext', 'hero_cta_primary', 'hero_cta_secondary',
        'razorpay_key_id', 'razorpay_key_secret',
    ];

    public function index(): string
    {
        return view('admin/settings', [
            'title'  => 'Settings',
            'fields' => $this->fields,
        ]);
    }

    public function save()
    {
        $model = new SettingModel();
        foreach ($this->fields as $key) {
            if ($this->request->getPost($key) !== null) {
                $model->put($key, (string) $this->request->getPost($key));
            }
        }
        cache()->delete('settings_map');

        return redirect()->to(site_url('admin/settings'))->with('success', 'Settings saved.');
    }
}
