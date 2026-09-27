<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$groups = [
    'Store' => ['store_name', 'store_tagline', 'store_email', 'store_phone', 'whatsapp_number', 'store_address'],
    'Shipping' => ['free_shipping_over', 'flat_shipping'],
    'Homepage hero' => ['hero_headline', 'hero_subtext', 'hero_cta_primary', 'hero_cta_secondary'],
    'SEO defaults' => ['seo_title', 'seo_description'],
    'Social links' => ['social_instagram', 'social_facebook', 'social_pinterest', 'social_youtube'],
    'Payments (Razorpay)' => ['razorpay_key_id', 'razorpay_key_secret'],
];
$multiline = ['store_address', 'hero_subtext', 'seo_description'];
$help = [];
?>
<form method="post" action="<?= base_url('admin/settings/save') ?>" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>
    <?php foreach ($groups as $group => $keys): ?>
        <div class="bg-surface rounded-lg border border-line p-5">
            <h2 class="font-medium mb-4"><?= esc($group) ?></h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <?php foreach ($keys as $key):
                    $label = ucwords(str_replace('_', ' ', $key));
                    $span  = in_array($key, $multiline, true) ? 'sm:col-span-2' : '';
                    $type  = str_contains($key, 'secret') ? 'password' : 'text'; ?>
                    <div class="<?= $span ?>">
                        <label class="field-label" for="<?= $key ?>"><?= esc($label) ?></label>
                        <?php if (in_array($key, $multiline, true)): ?>
                            <textarea id="<?= $key ?>" name="<?= $key ?>" rows="<?= $key === 'instagram_reels' ? 5 : 2 ?>" class="field-input"><?= esc(setting($key)) ?></textarea>
                        <?php else: ?>
                            <input id="<?= $key ?>" name="<?= $key ?>" type="<?= $type ?>" class="field-input" value="<?= esc(setting($key)) ?>">
                        <?php endif ?>
                        <?php if (! empty($help[$key])): ?><p class="field-help"><?= esc($help[$key]) ?></p><?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    <?php endforeach ?>
    <button class="btn-primary">Save settings</button>
</form>
<?= $this->endSection() ?>
