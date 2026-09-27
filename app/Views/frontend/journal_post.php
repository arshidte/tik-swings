<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<article class="py-10 sm:py-16">
    <div class="container-page max-w-3xl">
        <nav aria-label="Breadcrumb" class="text-sm text-muted mb-6">
            <a href="<?= base_url('journal') ?>" class="hover:text-wood">← Back to Journal</a>
        </nav>
        <p class="eyebrow"><?= esc(date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?> · <?= esc($post['author']) ?></p>
        <h1 class="mt-3 text-h1 font-display leading-tight"><?= esc($post['title']) ?></h1>
    </div>
    <div class="container-page max-w-4xl my-10">
        <div class="aspect-[16/9] rounded-lg overflow-hidden bg-sand">
            <img src="<?= esc(product_image($post['cover_image'], 'lifestyle.svg'), 'attr') ?>" alt="<?= esc($post['title'], 'attr') ?>" class="w-full h-full object-cover">
        </div>
    </div>
    <div class="container-page max-w-prose">
        <div class="text-lg leading-relaxed text-ink/90 space-y-6 prose-content">
            <?= $post['body'] // trusted admin content ?>
        </div>
        <div class="mt-12 pt-8 border-t border-line">
            <a href="<?= base_url('shop') ?>" class="btn-primary">Explore the Collection</a>
        </div>
    </div>
</article>
<?= $this->endSection() ?>
