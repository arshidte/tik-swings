<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-14 sm:py-20">
    <header class="max-w-2xl mb-12">
        <p class="eyebrow">Journal</p>
        <h1 class="mt-2 text-h1 font-display">Stories from the workshop.</h1>
        <p class="mt-3 text-muted">Guides, ideas and notes on living well with a handcrafted swing.</p>
    </header>

    <?php if (! empty($posts)): ?>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($posts as $post): ?>
                <a href="<?= base_url('journal/' . $post['slug']) ?>" class="group reveal">
                    <div class="aspect-[4/3] rounded-lg overflow-hidden bg-sand mb-4">
                        <img src="<?= esc(product_image($post['cover_image'], 'lifestyle.svg'), 'attr') ?>" alt="<?= esc($post['title'], 'attr') ?>" loading="lazy" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                    <p class="text-xs text-subtle"><?= esc(date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?></p>
                    <h2 class="font-display text-xl mt-1 leading-snug group-hover:text-wood transition-colors"><?= esc($post['title']) ?></h2>
                    <p class="text-muted text-sm mt-2 line-clamp-2"><?= esc($post['excerpt']) ?></p>
                </a>
            <?php endforeach ?>
        </div>
    <?php else: ?>
        <p class="text-muted">New stories are on the way.</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
