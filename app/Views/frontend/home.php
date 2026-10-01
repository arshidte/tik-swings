<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$heroImg = product_image(setting('hero_image'), 'hero.svg');
$rooms = [
    ['living-room', 'Living Room'], ['balcony', 'Balcony'], ['bedroom', 'Bedroom'],
    ['veranda', 'Veranda'], ['courtyard', 'Courtyard'], ['reading-corner', 'Reading Corner'],
];
$craft = [
    ['tikswings-placed-order', 'You Place Your Order', 'Pick your swing, choose your wood and finish, and check out. That is all it takes to set everything in motion.'],
    ['tikswings-customer-received-confirmation-call', 'We Confirm Every Detail', 'Before a single cut is made, we call you to confirm your size, finish and delivery — so nothing is left to chance.'],
    ['tikswings-started-work-in-workshop', 'Your Swing Takes Shape', 'Our artisans begin work in the workshop, shaping and joining your swing by hand from seasoned solid wood.'],
    ['tikswings-packed-and-ready-to-ship', 'Packed and Ready to Ship', 'Finished, quality-checked and carefully packed, your swing leaves our workshop and heads to your door.'],
];
$trust = [
    ['<path d="m17 14 3 3.3a1 1 0 0 1-.7 1.7H4.7a1 1 0 0 1-.7-1.7L7 14h-.3a1 1 0 0 1-.7-1.7L9 9h-.2A1 1 0 0 1 8 7.3L12 3l4 4.3a1 1 0 0 1-.8 1.7H15l3 3.3a1 1 0 0 1-.8 1.7H17Z"/><path d="M12 19v3"/>', 'Solid Wood'],
    ['<path d="m15 12-8.373 8.373a1 1 0 1 1-1.414-1.414L13.586 10.586"/><path d="m18 6-2.121-2.121a1 1 0 0 0-1.414 0l-5.303 5.303a1 1 0 0 0 0 1.414l2.121 2.121a1 1 0 0 0 1.414 0l5.303-5.303a1 1 0 0 0 0-1.414z"/>', 'Handcrafted'],
    ['<path d="M21.3 15.3a2.4 2.4 0 0 1 0 3.4l-2.6 2.6a2.4 2.4 0 0 1-3.4 0L2.7 8.7a2.41 2.41 0 0 1 0-3.4l2.6-2.6a2.41 2.41 0 0 1 3.4 0Z"/><path d="m14.5 12.5 2-2"/><path d="m11.5 9.5 2-2"/><path d="m8.5 6.5 2-2"/><path d="m17.5 15.5 2-2"/>', 'Custom Sizes'],
    ['<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>', 'Pan-India Delivery'],
    ['<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>', 'Secure Payments'],
    ['<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>', 'Installation Available'],
];
?>

<!-- ============================ HERO (§8) ============================ -->
<section class="relative overflow-hidden bg-ink text-bg" style="min-height:clamp(560px,80vh,860px)">
    <div class="absolute inset-0">
        <img src="<?= base_url('assets/images/swing-banner-one-tik.webp') ?>" alt="A handcrafted teak wooden swing in a warm, light-filled Indian living room"
             class="w-full h-full object-cover scale-105" data-parallax="0.12" fetchpriority="high" width="1920" height="1280">
        <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-ink/20 to-transparent"></div>
    </div>
    <div class="relative container-page flex items-end" style="min-height:clamp(560px,80vh,860px)">
        <div class="max-w-2xl pb-16 sm:pb-24">
            <p class="eyebrow text-wood-light animate-fade-in">Handcrafted wooden swings</p>
            <h1 class="mt-4 font-display text-bg text-[clamp(2.75rem,7vw,5.5rem)] leading-[1.02] tracking-tight animate-fade-up">
                <?= esc(setting('hero_headline', 'Make Room for Moments.')) ?>
            </h1>
            <p class="mt-5 text-lg sm:text-xl text-bg/80 max-w-xl animate-fade-up" style="animation-delay:.08s">
                <?= esc(setting('hero_subtext')) ?>
            </p>
            <div class="mt-8 flex flex-wrap gap-3 animate-fade-up" style="animation-delay:.16s">
                <a href="<?= base_url('shop') ?>" class="btn-wood shadow-lift"><?= esc(setting('hero_cta_primary', 'Explore Swings')) ?></a>
                <a href="<?= base_url('custom-swings') ?>" class="btn bg-bg/10 text-bg border border-bg/30 backdrop-blur hover:bg-bg/20"><?= esc(setting('hero_cta_secondary', 'Design Your Space')) ?></a>
            </div>
        </div>
    </div>
</section>

<!-- ======================== TRUST STRIP (§9) ======================== -->
<section class="border-b border-line bg-surface">
    <div class="container-page">
        <ul class="flex gap-8 sm:gap-4 overflow-x-auto no-scrollbar sm:grid sm:grid-cols-3 lg:grid-cols-6 py-5">
            <?php foreach ($trust as [$svg, $label]): ?>
                <li class="flex items-center gap-3 shrink-0 sm:justify-center">
                    <svg class="w-6 h-6 text-wood shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><?= $svg ?></svg>
                    <span class="text-sm font-medium whitespace-nowrap"><?= esc($label) ?></span>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</section>

<!-- ========================= COLLECTIONS (§10) ====================== -->
<section class="section">
    <div class="container-page">
        <div class="flex items-end justify-between gap-4 mb-8 reveal">
            <div>
                <p class="eyebrow">Collections</p>
                <h2 class="mt-2 text-h2">Find Your Kind of Swing.</h2>
            </div>
            <a href="<?= base_url('shop') ?>" class="hidden sm:inline-flex items-center gap-1 text-sm font-medium text-wood hover:text-wood-dark shrink-0">
                View all <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
            </a>
        </div>

        <div class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:overflow-visible -mx-5 px-5 sm:mx-0 sm:px-0">
            <?php foreach ($categories as $i => $c): ?>
                <a href="<?= base_url('category/' . $c['slug']) ?>"
                   class="group relative shrink-0 w-[76%] sm:w-auto snap-start overflow-hidden rounded-lg reveal" data-reveal-delay="<?= ($i % 4) * 60 ?>">
                    <div class="aspect-[4/5] overflow-hidden bg-sand">
                        <img src="<?= esc(product_image($c['image'], 'categories/classic-swings.svg'), 'attr') ?>" alt="<?= esc($c['name'], 'attr') ?> collection"
                             loading="lazy" decoding="async" width="900" height="1100"
                             class="w-full h-full object-cover transition-transform duration-500 ease-out-soft group-hover:scale-[1.05]">
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/70 via-transparent to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 p-5 flex items-end justify-between text-bg">
                        <div>
                            <h3 class="font-display text-xl leading-tight"><?= esc($c['name']) ?></h3>
                            <p class="text-sm text-bg/70 tabular-nums"><?= (int) $c['product_count'] ?> designs</p>
                        </div>
                        <span class="w-9 h-9 rounded-full bg-bg/15 backdrop-blur flex items-center justify-center transition-transform duration-300 group-hover:translate-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </span>
                    </div>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ====================== FEATURED PRODUCTS (§11) =================== -->
<section class="section bg-surface">
    <div class="container-page">
        <div class="text-center max-w-2xl mx-auto mb-10 reveal">
            <p class="eyebrow">Bestsellers</p>
            <h2 class="mt-2 text-h2">Made to Be the Favourite Seat.</h2>
            <p class="mt-3 text-muted">The swings our customers keep coming back to — and keep telling their friends about.</p>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10">
            <?php foreach ($featured as $i => $product): ?>
                <div class="reveal" data-reveal-delay="<?= ($i % 4) * 60 ?>">
                    <?= view('components/product_card', ['product' => $product, 'wishIds' => $wishIds]) ?>
                </div>
            <?php endforeach ?>
        </div>
        <div class="text-center mt-12 reveal">
            <a href="<?= base_url('shop') ?>" class="btn-outline">Explore the full collection</a>
        </div>
    </div>
</section>

<!-- ===================== INSTAGRAM FEED (Elfsight) ===================== -->
<?php
$igUrl    = setting('social_instagram');
$igHandle = ($igUrl && preg_match('#instagram\.com/([A-Za-z0-9_.]+)#i', $igUrl, $m)) ? $m[1] : null;
?>
<section class="section bg-surface" aria-labelledby="instagram-heading">
    <div class="container-page">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10 reveal">
            <div>
                <p class="eyebrow inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 8.5A3.5 3.5 0 1 0 12 15.5 3.5 3.5 0 0 0 12 8.5Zm5-1.2a.9.9 0 1 1-1.8 0 .9.9 0 0 1 1.8 0ZM7 4h10a3 3 0 0 1 3 3v10a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3Z"/></svg>
                    <?= $igHandle ? '@' . esc($igHandle) : 'On Instagram' ?>
                </p>
                <h2 id="instagram-heading" class="mt-2 text-h2">Follow Along on Instagram.</h2>
                <p class="mt-3 text-muted max-w-md">See our swings styled in real homes — and share how yours lives with <?= $igHandle ? '#' . esc($igHandle) : 'us' ?>.</p>
            </div>
            <?php if ($igUrl): ?>
                <a href="<?= esc($igUrl, 'attr') ?>" target="_blank" rel="noopener" class="btn-outline shrink-0 self-start sm:self-auto">
                    Follow <?= $igHandle ? '@' . esc($igHandle) : 'us' ?>
                </a>
            <?php endif ?>
        </div>

        <!-- Elfsight Instagram Feed | Untitled Instagram Feed -->
        <div class="reveal min-h-[200px]">
            <div class="elfsight-app-ff0e0cf3-f946-4c1b-9e14-de31a69f3a53" data-elfsight-app-lazy></div>
        </div>
    </div>
</section>

<!-- =================== IMAGINE IT IN YOUR HOME (§13) ================ -->
<section class="section">
    <div class="container-page">
        <div class="grid lg:grid-cols-2 gap-10 items-center">
            <div class="reveal">
                <p class="eyebrow">See the detail</p>
                <h2 class="mt-2 text-h2">Imagine It in Your Home.</h2>
                <p class="mt-4 text-muted max-w-md">Every swing is a collection of small, deliberate decisions. Tap a point on the swing to see what goes into it.</p>
                <div class="mt-6 space-y-3" data-hotspot-panels>
                    <div class="p-4 rounded-md bg-sand" data-hotspot-panel="1">
                        <h3 class="font-medium">Solid Wood Seat</h3>
                        <p class="text-sm text-muted mt-1">A polished hardwood seat, sealed by hand so the grain reads warm and continuous.</p>
                    </div>
                    <div class="p-4 rounded-md bg-sand/50 hidden" data-hotspot-panel="2">
                        <h3 class="font-medium">Brass Chain Suspension</h3>
                        <p class="text-sm text-muted mt-1">Hand-finished brass chains and fittings, built to swing quietly for years.</p>
                    </div>
                    <div class="p-4 rounded-md bg-sand/50 hidden" data-hotspot-panel="3">
                        <h3 class="font-medium">Turned Wood Legs</h3>
                        <p class="text-sm text-muted mt-1">Lathe-turned legs that finish the frame with a classic, crafted profile.</p>
                    </div>
                </div>
            </div>
            <div class="relative rounded-lg overflow-hidden bg-sand aspect-[1200/780] reveal" data-hotspot-stage>
                <img src="<?= base_url('assets/images/imagine-swing.webp') ?>" alt="A handcrafted wooden swing suspended on brass chains" loading="lazy" width="1200" height="780" class="w-full h-full object-cover">
                <?php
                // Positions are % of the image (top, left) — tuned to the photo's features.     top: 69%;left: 24%;
                $spots = [['1', '60%', '46%', 'Solid Wood Seat'], ['2', '31%', '71%', 'Brass Chain Suspension'], ['3', '69%', '24%', 'Turned Wood Legs']];
                foreach ($spots as [$id, $top, $left, $label]): ?>
                    <button type="button" class="absolute -translate-x-1/2 -translate-y-1/2 group" style="top:<?= $top ?>;left:<?= $left ?>"
                            data-hotspot="<?= $id ?>" aria-label="<?= esc($label, 'attr') ?>">
                        <span class="block w-6 h-6 rounded-full bg-bg shadow-lift ring-2 ring-wood/40 relative">
                            <span class="absolute inset-0 rounded-full bg-wood/40 animate-ping"></span>
                            <span class="absolute inset-1.5 rounded-full bg-wood"></span>
                        </span>
                    </button>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</section>

<!-- ==================== CRAFTSMANSHIP STORY (§14) =================== -->
<section class="section bg-ink text-bg overflow-hidden">
    <div class="container-page">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between reveal">
            <div class="max-w-xl">
                <p class="eyebrow text-wood-light">Your order journey</p>
                <h2 class="mt-2 text-h2 text-bg">From Our Workshop to Your Door.</h2>
            </div>
            <p class="max-w-xs text-sm leading-relaxed text-bg/50">Four simple steps — from the moment you check out to a handcrafted swing arriving at your door.</p>
        </div>

        <div class="relative mt-10 grid grid-cols-1 gap-x-6 gap-y-9 sm:grid-cols-2 lg:mt-14 lg:grid-cols-4">
            <?php foreach ($craft as $i => [$img, $title, $body]): ?>
                <div class="group reveal" data-reveal-delay="<?= ($i % 4) * 90 ?>">
                    <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-wood-dark/40 ring-1 ring-bg/10">
                        <img src="<?= base_url('assets/images/' . $img . '.webp') ?>" alt="<?= esc($title, 'attr') ?>" loading="lazy" width="1200" height="800" class="h-full w-full object-cover transition-transform duration-700 ease-out-soft group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink/50 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>
                    </div>
                    <div class="relative mt-5 flex items-center">
                        <span class="relative z-10 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-wood-light font-display text-lg text-ink shadow-lift">0<?= $i + 1 ?></span>
                        <?php if ($i < count($craft) - 1): ?>
                            <span class="absolute left-11 right-[-1.5rem] top-1/2 hidden h-px -translate-y-1/2 bg-bg/15 lg:block"></span>
                        <?php endif ?>
                    </div>
                    <h3 class="mt-4 font-display text-xl text-bg"><?= esc($title) ?></h3>
                    <p class="mt-2 text-sm leading-relaxed text-bg/60"><?= esc($body) ?></p>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ===================== CUSTOMIZATION TEASER (§15) ================= -->
<section class="section">
    <div class="container-page">
        <div class="rounded-xl bg-sand p-8 sm:p-12 lg:p-16 grid lg:grid-cols-2 gap-10 items-center reveal">
            <div>
                <p class="eyebrow">Made to order</p>
                <h2 class="mt-2 text-h2">Made for Your Space.</h2>
                <p class="mt-4 text-muted max-w-md">Choose the wood, the finish, the rope and the exact size. We craft it to fit your corner — and your life.</p>
                <a href="<?= base_url('custom-swings') ?>" class="btn-primary mt-6">Start customising</a>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <?php
                $opts = [
                    ['Wood', 'Teak · Sheesham · Mango'],
                    ['Finish', 'Natural · Honey · Walnut · Dark'],
                    ['Rope', 'Natural · Beige · Black'],
                    ['Size', 'Standard · Large · Custom'],
                ];
                foreach ($opts as [$k, $v]): ?>
                    <div class="bg-surface rounded-md p-4 border border-line">
                        <p class="font-medium"><?= esc($k) ?></p>
                        <p class="text-muted mt-1"><?= esc($v) ?></p>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</section>

<!-- ====================== ROOM INSPIRATION (§16) =================== -->
<section class="section bg-surface">
    <div class="container-page">
        <div class="text-center max-w-2xl mx-auto mb-10 reveal">
            <p class="eyebrow">Inspiration</p>
            <h2 class="mt-2 text-h2">Where Will You Put Yours?</h2>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
            <?php foreach ($rooms as $i => [$slug, $name]): ?>
                <a href="<?= base_url('shop?room=' . $slug) ?>" class="group relative overflow-hidden rounded-lg reveal" data-reveal-delay="<?= ($i % 3) * 60 ?>">
                    <div class="aspect-[5/4] overflow-hidden bg-sand">
                        <img src="<?= base_url('assets/images/rooms/' . $slug . '.svg') ?>" alt="<?= esc($name, 'attr') ?> swing inspiration" loading="lazy" width="1000" height="800" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/60 to-transparent"></div>
                    <h3 class="absolute bottom-4 left-4 font-display text-xl text-bg"><?= esc($name) ?></h3>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</section>

<!-- ============================ REVIEWS (§17) ======================= -->
<?php if (! empty($reviews)): ?>
<section class="section">
    <div class="container-page">
        <div class="text-center max-w-2xl mx-auto mb-10 reveal">
            <p class="eyebrow">Testimonials</p>
            <h2 class="mt-2 text-h2">Loved by Homes Across India.</h2>
        </div>
        <div class="flex gap-4 overflow-x-auto no-scrollbar snap-x snap-mandatory md:grid md:grid-cols-3 md:overflow-visible -mx-5 px-5 md:mx-0 md:px-0">
            <?php foreach (array_slice($reviews, 0, 6) as $r): ?>
                <figure class="shrink-0 w-[85%] sm:w-[45%] md:w-auto snap-start card p-6 flex flex-col reveal">
                    <?= star_row((float) $r['rating']) ?>
                    <?php if (! empty($r['title'])): ?><figcaption class="font-display text-lg mt-3"><?= esc($r['title']) ?></figcaption><?php endif ?>
                    <blockquote class="text-muted text-[15px] mt-2 flex-1 leading-relaxed">“<?= esc(truncate_words($r['body'], 34)) ?>”</blockquote>
                    <div class="mt-5 flex items-center gap-3">
                        <span class="w-10 h-10 rounded-full bg-wood/15 text-wood-dark font-medium flex items-center justify-center"><?= esc(strtoupper(substr($r['author_name'], 0, 1))) ?></span>
                        <div class="text-sm">
                            <p class="font-medium leading-tight"><?= esc($r['author_name']) ?></p>
                            <p class="text-subtle text-xs"><?= esc($r['city']) ?><?php if ($r['verified_purchase']): ?> · <span class="text-success">Verified</span><?php endif ?></p>
                        </div>
                    </div>
                </figure>
            <?php endforeach ?>
        </div>
    </div>
</section>
<?php endif ?>

<!-- =========================== FINAL CTA (§18) ===================== -->
<section class="relative overflow-hidden">
    <div class="absolute inset-0">
        <img src="<?= base_url('assets/images/cta.svg') ?>" alt="" aria-hidden="true" loading="lazy" width="1920" height="1000" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-ink/60"></div>
    </div>
    <div class="relative container-page py-24 sm:py-32 text-center text-bg">
        <h2 class="font-display text-[clamp(2rem,5vw,4rem)] leading-tight max-w-3xl mx-auto reveal">Your Favourite Corner Is Waiting.</h2>
        <p class="mt-5 text-lg text-bg/80 max-w-xl mx-auto reveal">Bring home a handcrafted swing made for slow mornings, long conversations and everyday moments.</p>
        <a href="<?= base_url('shop') ?>" class="btn-wood mt-8 shadow-lift reveal">Explore the Collection</a>
    </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Lightweight hotspot interaction (§13) — vanilla, no library.
document.querySelectorAll('[data-hotspot]').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.getAttribute('data-hotspot');
        document.querySelectorAll('[data-hotspot-panel]').forEach((p) => {
            const on = p.getAttribute('data-hotspot-panel') === id;
            p.classList.toggle('hidden', !on);
            p.classList.toggle('bg-sand', on);
            p.classList.toggle('bg-sand/50', !on);
        });
        document.querySelectorAll('[data-hotspot] .bg-wood').forEach((d) => d.classList.remove('scale-125'));
    });
});
</script>
<!-- Elfsight platform (loads the Instagram feed widget; async + lazy) -->
<script src="https://elfsightcdn.com/platform.js" async></script>
<?= $this->endSection() ?>
