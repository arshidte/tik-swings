<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-14 sm:py-20">
    <header class="mb-10">
        <p class="eyebrow">Search</p>
        <h1 class="mt-2 text-h1 font-display">
            <?php if ($query !== ''): ?>Results for “<?= esc($query) ?>”<?php else: ?>Search our swings<?php endif ?>
        </h1>
        <?php if ($query !== ''): ?><p class="mt-2 text-muted"><?= count($products) ?> swing<?= count($products) === 1 ? '' : 's' ?> found</p><?php endif ?>
    </header>

    <?php if (! empty($products)): ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10">
            <?php foreach ($products as $product): ?>
                <?= view('components/product_card', ['product' => $product, 'wishIds' => $wishIds]) ?>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 border border-dashed border-line rounded-lg max-w-lg mx-auto">
            <p class="font-display text-2xl">We couldn't find that swing.</p>
            <p class="text-muted mt-2 mb-6">Try another search — like “teak”, “balcony” or “jhula”.</p>
            <a href="<?= base_url('shop') ?>" class="btn-wood">Browse all swings</a>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
