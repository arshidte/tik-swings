<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="relative bg-ink text-bg">
    <div class="absolute inset-0 opacity-30">
        <img src="<?= esc(product_image($page['cover_image'], 'lifestyle.svg'), 'attr') ?>" alt="" aria-hidden="true" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/80 to-ink/60"></div>
    </div>
    <div class="relative container-page py-20 sm:py-28">
        <p class="eyebrow !text-bg/80 reveal">Swing &amp; Grain</p>
        <h1 class="mt-3 text-display font-display text-bg max-w-3xl reveal"><?= esc($page['title']) ?></h1>
        <?php if (! empty($page['excerpt'])): ?>
            <p class="mt-5 text-lg text-bg/90 max-w-2xl reveal"><?= esc($page['excerpt']) ?></p>
        <?php endif ?>
    </div>
</section>

<article class="section">
    <div class="container-page max-w-prose">
        <div class="prose-content text-lg leading-relaxed text-ink/90 space-y-6 reveal">
            <?= cms_html($page['body']) // sanitized on save; legacy plain text is escaped ?>
        </div>
        <div class="mt-12 flex flex-wrap gap-3 reveal">
            <a href="<?= base_url('shop') ?>" class="btn-primary">Explore the Collection</a>
            <a href="<?= esc(whatsapp_link(), 'attr') ?>" target="_blank" rel="noopener" class="btn-outline">Talk to us</a>
        </div>
    </div>
</article>
<?= $this->endSection() ?>
