<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<section class="relative bg-ink text-bg">
    <div class="absolute inset-0 opacity-40"><img src="<?= base_url('assets/images/craft/artisans.svg') ?>" alt="" class="w-full h-full object-cover"><div class="absolute inset-0 bg-gradient-to-t from-ink to-ink/30"></div></div>
    <div class="relative container-page py-20 sm:py-28">
        <p class="eyebrow text-wood-light reveal">Made to order</p>
        <h1 class="mt-3 text-display font-display max-w-3xl reveal">Design Your Own Swing.</h1>
        <p class="mt-5 text-lg text-bg/80 max-w-2xl reveal">Choose the wood, the finish, the rope and the exact size. We craft it to fit your corner — and your life.</p>
    </div>
</section>

<section class="section">
    <div class="container-page grid lg:grid-cols-2 gap-12 items-start">
        <!-- Live preview -->
        <div class="lg:sticky lg:top-[calc(var(--header-height)+1.5rem)]">
            <div class="aspect-square rounded-lg overflow-hidden bg-sand relative" data-config-preview>
                <img src="<?= base_url('assets/images/products/aria-teak-wooden-swing-1.svg') ?>" alt="Your custom swing preview" class="w-full h-full object-cover transition-all duration-500" data-config-image>
            </div>
            <div class="mt-4 p-5 rounded-lg bg-sand">
                <p class="text-sm text-muted">Your configuration</p>
                <p class="mt-1 font-medium" data-config-summary>Teak · Natural · Natural rope · Standard</p>
                <p class="mt-3 text-xs text-muted">Custom pieces are quoted individually based on wood, size and detailing. Share your configuration and we'll send a precise quote and timeline.</p>
            </div>
        </div>

        <!-- Options (§15) -->
        <form data-configurator class="space-y-8">
            <?php foreach ($attributes as $attr): ?>
                <fieldset data-config-group data-attr="<?= esc($attr['slug'], 'attr') ?>">
                    <legend class="text-sm font-semibold mb-3"><?= esc($attr['name']) ?></legend>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($attr['values'] as $j => $val): ?>
                            <button type="button" class="min-h-[44px] px-4 rounded-md border text-sm transition data-[selected=true]:border-ink data-[selected=true]:bg-ink data-[selected=true]:text-bg <?= $j === 0 ? '' : 'border-line hover:border-ink' ?>"
                                    data-config-value data-label="<?= esc($val['value'], 'attr') ?>" <?= $j === 0 ? 'data-selected="true"' : '' ?>>
                                <?php if (! empty($val['swatch'])): ?><span class="inline-block w-3.5 h-3.5 rounded-full mr-1.5 align-middle border border-black/10" style="background:<?= esc($val['swatch'], 'attr') ?>"></span><?php endif ?>
                                <?= esc($val['value']) ?>
                            </button>
                        <?php endforeach ?>
                    </div>
                </fieldset>
            <?php endforeach ?>

            <div class="border-t border-line pt-6">
                <label class="field-label" for="custom-notes">Anything else? (dimensions, room, ideas)</label>
                <textarea id="custom-notes" data-config-notes rows="3" class="field-input" placeholder="e.g. I have a 6ft balcony and want a two-seater in dark walnut."></textarea>
            </div>

            <a href="<?= esc(whatsapp_link('Hi! I would like a custom swing.'), 'attr') ?>" target="_blank" rel="noopener" class="btn-wood btn-block" data-config-whatsapp>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.3 4.8 4.9-1.3A10 10 0 1 0 12 2Z"/></svg>
                Get my custom quote on WhatsApp
            </a>
            <p class="text-center text-sm text-muted">or <a href="<?= base_url('contact') ?>" class="text-wood hover:text-wood-dark">send us a message</a></p>
        </form>
    </div>
</section>

<?php if (! empty($examples)): ?>
<section class="section bg-surface">
    <div class="container-page">
        <h2 class="text-h2 font-display mb-8">Recently customised</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8 sm:gap-x-6">
            <?php foreach ($examples as $rp): ?><?= view('components/product_card', ['product' => $rp, 'wishIds' => $wishIds]) ?><?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
    const base = <?= json_encode(whatsapp_link('')) ?>;
    const state = {};
    function refresh() {
        const parts = Object.values(state);
        document.querySelector('[data-config-summary]').textContent = parts.join(' · ') || 'Choose your options';
        const notes = document.querySelector('[data-config-notes]').value.trim();
        let msg = 'Hi! I would like a custom swing: ' + parts.join(', ');
        if (notes) msg += '. Notes: ' + notes;
        const link = document.querySelector('[data-config-whatsapp]');
        link.href = base.replace(/text=.*$/, 'text=' + encodeURIComponent(msg));
    }
    document.querySelectorAll('[data-config-group]').forEach((group) => {
        const attr = group.dataset.attr;
        const btns = group.querySelectorAll('[data-config-value]');
        btns.forEach((b) => {
            if (b.dataset.selected === 'true') state[attr] = b.dataset.label;
            b.addEventListener('click', () => {
                btns.forEach((x) => (x.dataset.selected = 'false'));
                b.dataset.selected = 'true';
                state[attr] = b.dataset.label;
                refresh();
            });
        });
    });
    document.querySelector('[data-config-notes]')?.addEventListener('input', refresh);
    refresh();
})();
</script>
<?= $this->endSection() ?>
