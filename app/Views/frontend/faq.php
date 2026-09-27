<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-14 sm:py-20 max-w-3xl">
    <header class="text-center mb-12">
        <p class="eyebrow">Help</p>
        <h1 class="mt-2 text-h1 font-display"><?= esc($page['title']) ?></h1>
        <p class="mt-3 text-muted"><?= esc($page['excerpt']) ?></p>
    </header>

    <div class="divide-y divide-line border-t border-b border-line" data-accordions>
        <?php foreach ($faqs as $i => $f): ?>
            <div>
                <h2>
                    <button type="button" class="w-full flex items-center justify-between gap-4 py-5 text-left font-medium text-lg"
                            aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="faq-<?= $i ?>" data-accordion-trigger>
                        <?= esc($f['q']) ?>
                        <svg class="w-5 h-5 text-muted shrink-0 transition-transform duration-300 <?= $i === 0 ? 'rotate-180' : '' ?>" data-accordion-icon fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="m6 9 6 6 6-6"/></svg>
                    </button>
                </h2>
                <div id="faq-<?= $i ?>" class="grid <?= $i === 0 ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]' ?> transition-all duration-300 ease-out-soft" data-accordion-panel>
                    <div class="overflow-hidden"><p class="pb-5 text-muted leading-relaxed"><?= nl2br(esc($f['a'])) ?></p></div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="text-center mt-12">
        <p class="text-muted">Still have a question?</p>
        <a href="<?= base_url('contact') ?>" class="btn-primary mt-4">Contact the workshop</a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-accordion-trigger]').forEach((t) => {
    t.addEventListener('click', () => {
        const panel = t.closest('div').parentElement.querySelector('[data-accordion-panel]');
        const icon = t.querySelector('[data-accordion-icon]');
        const open = t.getAttribute('aria-expanded') === 'true';
        t.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel.classList.toggle('grid-rows-[1fr]', !open);
        panel.classList.toggle('grid-rows-[0fr]', open);
        icon.classList.toggle('rotate-180', !open);
    });
});
</script>
<?= $this->endSection() ?>
