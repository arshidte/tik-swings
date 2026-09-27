<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-14 sm:py-20">
    <header class="mb-10">
        <h1 class="text-h1 font-display">Your Wishlist</h1>
        <p class="mt-2 text-muted">The swings you're dreaming about.</p>
    </header>

    <?php if (! empty($products)): ?>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10">
            <?php foreach ($products as $product): ?>
                <?= view('components/product_card', ['product' => $product, 'wishIds' => $wishIds]) ?>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <div class="text-center py-20 border border-dashed border-line rounded-lg max-w-lg mx-auto">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-sand text-terracotta mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-9.3C5 7.9 7 6 9.3 6c1.4 0 2.7.7 3.4 1.9C13.4 6.7 14.7 6 16.1 6 18.4 6 20 7.9 20 10.7 20 15.6 12 20 12 20Z"/></svg>
            </span>
            <p class="font-display text-xl">Save the swings you love.</p>
            <p class="text-muted mt-2 mb-6">Tap the heart on any swing and come back when you're ready.</p>
            <a href="<?= base_url('shop') ?>" class="btn-wood">Browse Swings</a>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
