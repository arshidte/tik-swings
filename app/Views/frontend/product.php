<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$p        = $product;
$images   = $p['images'] ?: [['image' => null, 'alt_text' => $p['name']]];
$discount = discount_percent($p['price'], $p['compare_price'] ?? 0);
$inWish   = in_array((int) $p['id'], array_map('intval', $wishIds), true);
$inStock  = $p['stock_status'] !== 'out_of_stock' && ((int) $p['stock'] > 0 || $p['stock_status'] === 'made_to_order' || ! empty($p['variants']));

// Group variant attributes into selectable option groups.
$optionGroups = [];
foreach ($p['variants'] as $v) {
    foreach ($v['attributes'] ?? [] as $a) {
        $optionGroups[$a['attribute']]['slug'] = $a['attribute_slug'];
        $optionGroups[$a['attribute']]['values'][$a['value']] = [
            'id' => $a['attribute_value_id'] ?? null, 'value' => $a['value'], 'swatch' => $a['swatch'] ?? null,
        ];
    }
}
$defaultVariant = null;
foreach ($p['variants'] as $v) {
    if (! empty($v['is_default'])) { $defaultVariant = $v; break; }
}
$defaultVariant = $defaultVariant ?: ($p['variants'][0] ?? null);

$accordions = array_filter([
    'Description'      => $p['description'],
    'Dimensions'      => $p['dimensions'] ? "Overall size: {$p['dimensions']}. Weight capacity: {$p['weight_capacity']}." : null,
    'Materials'       => $p['material'] ? implode(' · ', array_filter([$p['material'], $p['wood_type'] ?: null, $p['finish'] ? "{$p['finish']} finish" : null])) . '.' : null,
    'Craftsmanship'   => 'Hand-cut, hand-joined and hand-finished by our artisans. Every piece carries the small marks of the person who made it.',
    'Installation'    => $p['installation_available'] ? 'Expert installation available across most Indian cities. Recommended for ceiling-mounted swings.' : 'Self-assembly with included hardware and instructions.',
    'Delivery'        => ($p['delivery_estimate'] ?: '3–5 weeks') . '. We keep you updated at every step.',
    'Returns'         => '7-day returns on unused, undamaged pieces. Custom orders are made to your spec and are non-returnable.',
    'Warranty'        => $p['warranty'] ?: '3-year structural warranty.',
    'Care Instructions' => 'Wipe with a dry cloth, keep out of standing water, and oil the wood once or twice a year.',
]);
?>

<div class="container-page py-5 sm:py-8"
     data-product-root data-product-id="<?= (int) $p['id'] ?>"
     data-has-variants="<?= ! empty($p['variants']) ? '1' : '0' ?>"
     data-default-variant="<?= (int) ($defaultVariant['id'] ?? 0) ?>">

    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" class="text-sm text-muted mb-5">
        <ol class="flex items-center gap-2 flex-wrap">
            <li><a href="<?= base_url('/') ?>" class="hover:text-wood">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="<?= base_url('shop') ?>" class="hover:text-wood">Shop</a></li>
            <?php if (! empty($p['category'])): ?>
                <li aria-hidden="true">/</li>
                <li><a href="<?= base_url('category/' . $p['category']['slug']) ?>" class="hover:text-wood"><?= esc($p['category']['name']) ?></a></li>
            <?php endif ?>
        </ol>
    </nav>

    <div class="lg:grid lg:grid-cols-2 lg:gap-12 xl:gap-16">
        <!-- ===================== GALLERY (§24) ===================== -->
        <div class="lg:sticky lg:top-[calc(var(--header-height)+1.5rem)] lg:self-start">
            <div class="flex flex-col-reverse sm:flex-row-reverse lg:flex-row-reverse gap-3">
                <!-- Main image -->
                <div class="flex-1 relative">
                    <div class="relative rounded-lg overflow-hidden bg-sand" data-gallery-main>
                        <img src="<?= esc(product_image($images[0]['image']), 'attr') ?>" alt="<?= esc($images[0]['alt_text'] ?: $p['name'], 'attr') ?>"
                             fetchpriority="high"
                             class="w-full h-auto block cursor-zoom-in" data-gallery-image>
                        <?php if ($discount > 0): ?><span class="badge-sale absolute top-4 left-4">-<?= $discount ?>%</span><?php endif ?>
                        <button type="button" class="absolute bottom-4 right-4 w-10 h-10 rounded-full bg-bg/90 backdrop-blur flex items-center justify-center shadow-soft hover:bg-white" data-gallery-zoom aria-label="View full screen">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                        </button>
                    </div>
                </div>
                <!-- Thumbnails -->
                <?php if (count($images) > 1): ?>
                <div class="flex sm:flex-col gap-3 overflow-auto no-scrollbar" role="tablist" aria-label="Product images">
                    <?php foreach ($images as $i => $img): ?>
                        <button type="button" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                                class="shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-md overflow-hidden bg-sand ring-2 transition <?= $i === 0 ? 'ring-wood' : 'ring-transparent hover:ring-line' ?>"
                                data-gallery-thumb data-image="<?= esc(product_image($img['image']), 'attr') ?>" data-alt="<?= esc($img['alt_text'] ?: $p['name'], 'attr') ?>">
                            <img src="<?= esc(product_image($img['image']), 'attr') ?>" alt="" width="80" height="80" loading="lazy" class="w-full h-full object-cover">
                        </button>
                    <?php endforeach ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- ===================== INFO (§23) ===================== -->
        <div class="mt-8 lg:mt-0">
            <?php if (! empty($p['wood_type'])): ?><p class="eyebrow"><?= esc($p['wood_type']) ?> · <?= esc($p['finish']) ?> finish</p><?php endif ?>
            <h1 class="mt-2 text-h1 font-display leading-tight"><?= esc($p['name']) ?></h1>

            <?php if ((int) $p['rating_count'] > 0): ?>
                <div class="flex items-center gap-2 mt-3">
                    <?= star_row((float) $p['rating_avg']) ?>
                    <a href="#reviews" class="text-sm text-muted hover:text-wood"><?= number_format($p['rating_avg'], 1) ?> · <?= (int) $p['rating_count'] ?> reviews</a>
                </div>
            <?php endif ?>

            <!-- Price -->
            <div class="flex items-baseline gap-3 mt-5">
                <span class="text-2xl sm:text-3xl font-display tabular-nums" data-price><?= price($p['price']) ?></span>
                <?php if ($discount > 0): ?>
                    <span class="text-lg text-subtle line-through tabular-nums" data-compare-price><?= price($p['compare_price']) ?></span>
                    <span class="badge-sale" data-discount-badge>-<?= $discount ?>%</span>
                <?php endif ?>
            </div>
            <p class="text-xs text-muted mt-1">Inclusive of all taxes · <span data-sku>SKU: <?= esc($defaultVariant['sku'] ?? $p['sku']) ?></span></p>

            <?php if (! empty($p['short_description'])): ?>
                <p class="mt-5 text-muted leading-relaxed max-w-prose"><?= esc($p['short_description']) ?></p>
            <?php endif ?>

            <!-- Variant options (§15) -->
            <?php if (! empty($optionGroups)): ?>
                <div class="mt-6 space-y-5" data-variant-options>
                    <?php foreach ($optionGroups as $groupName => $group): ?>
                        <div data-option-group data-attribute="<?= esc($group['slug'], 'attr') ?>">
                            <p class="text-sm font-medium mb-2"><?= esc($groupName) ?>: <span class="text-muted" data-option-selected></span></p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($group['values'] as $val): ?>
                                    <button type="button" class="min-h-[40px] px-4 rounded-md border border-line text-sm hover:border-ink transition data-[selected=true]:border-ink data-[selected=true]:bg-ink data-[selected=true]:text-bg"
                                            data-option-value data-value-id="<?= (int) $val['id'] ?>" data-value-label="<?= esc($val['value'], 'attr') ?>">
                                        <?php if (! empty($val['swatch'])): ?><span class="inline-block w-3.5 h-3.5 rounded-full mr-1.5 align-middle border border-black/10" style="background:<?= esc($val['swatch'], 'attr') ?>"></span><?php endif ?>
                                        <?= esc($val['value']) ?>
                                    </button>
                                <?php endforeach ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <!-- Quantity + CTAs -->
            <div class="mt-7 flex items-stretch gap-3">
                <div class="inline-flex items-center border border-line rounded-md" data-qty-selector>
                    <button type="button" class="w-11 h-12 flex items-center justify-center text-muted hover:text-ink text-lg" data-qty-dec aria-label="Decrease quantity">–</button>
                    <input type="text" inputmode="numeric" value="1" class="w-10 text-center bg-transparent font-medium tabular-nums focus:outline-none" data-qty-input aria-label="Quantity" readonly>
                    <button type="button" class="w-11 h-12 flex items-center justify-center text-muted hover:text-ink text-lg" data-qty-inc aria-label="Increase quantity">+</button>
                </div>
                <button type="button" class="btn-primary flex-1" data-pdp-add <?= $inStock ? '' : 'disabled' ?>>
                    <span data-btn-label><?= $inStock ? 'Add to Cart' : 'Currently Unavailable' ?></span>
                </button>
                <button type="button" class="w-12 h-12 shrink-0 rounded-md border border-line flex items-center justify-center hover:border-ink transition <?= $inWish ? 'text-terracotta' : 'text-ink' ?>"
                        data-wishlist-toggle="<?= (int) $p['id'] ?>" aria-pressed="<?= $inWish ? 'true' : 'false' ?>" aria-label="Add to wishlist">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="<?= $inWish ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.6" data-heart><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-9.3C5 7.9 7 6 9.3 6c1.4 0 2.7.7 3.4 1.9C13.4 6.7 14.7 6 16.1 6 18.4 6 20 7.9 20 10.7 20 15.6 12 20 12 20Z"/></svg>
                </button>
            </div>
            <button type="button" class="btn-wood btn-block mt-3" data-pdp-buy <?= $inStock ? '' : 'disabled' ?>>Buy Now</button>

            <!-- Assurance strip -->
            <ul class="mt-7 grid grid-cols-2 gap-y-3 gap-x-4 text-sm border-t border-line pt-6">
                <?php
                $assur = [
                    ['M4 13l4 4L20 7', $p['delivery_estimate'] ?: 'Handcrafted to order'],
                    ['M12 3l7 4v5c0 4-3 7-7 8-4-1-7-4-7-8V7z', $p['warranty'] ?: '3-year warranty'],
                    ['M5 4v6m14-6v6M5 10h14', $p['material'] ?: 'Solid wood'],
                    ['M3 12h18', $p['installation_available'] ? 'Installation available' : 'Easy self-assembly'],
                ];
                foreach ($assur as [$icon, $label]): ?>
                    <li class="flex items-center gap-2 text-muted">
                        <svg class="w-5 h-5 text-wood shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
                        <span><?= esc($label) ?></span>
                    </li>
                <?php endforeach ?>
            </ul>

            <!-- Accordions (§25) -->
            <div class="mt-8 divide-y divide-line border-t border-line" data-accordions>
                <?php foreach ($accordions as $heading => $body): $id = url_title($heading, '-', true); ?>
                    <div>
                        <h3>
                            <button type="button" class="w-full flex items-center justify-between py-4 text-left font-medium"
                                    aria-expanded="false" aria-controls="acc-<?= $id ?>" data-accordion-trigger>
                                <?= esc($heading) ?>
                                <svg class="w-5 h-5 text-muted transition-transform duration-300" data-accordion-icon fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </h3>
                        <div id="acc-<?= $id ?>" class="grid grid-rows-[0fr] transition-all duration-300 ease-out-soft" data-accordion-panel>
                            <div class="overflow-hidden">
                                <div class="pb-5 text-muted leading-relaxed max-w-prose"><?= nl2br(esc($body)) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <!-- ============== CUSTOMER GALLERY (user-uploaded media) ============== -->
    <?php if (! empty($product['user_media'])):
        $galleryAll   = $product['user_media'];
        $galleryTotal = count($galleryAll);
        $initialCount = 10;                       // lazy-load beyond 10 (§ requirement)
        $initial      = array_slice($galleryAll, 0, $initialCount);
        $hasMore      = $galleryTotal > $initialCount;
    ?>
        <section id="customer-gallery" class="mt-16 lg:mt-24 border-t border-line pt-12">
            <div class="flex items-end justify-between gap-4 mb-6">
                <div>
                    <p class="eyebrow">Shared by our community</p>
                    <h2 class="text-h2 font-display mt-2">From Our Customers' Homes</h2>
                    <p class="text-muted mt-2 max-w-prose">Real photos and videos from people who live with this swing every day.</p>
                </div>
            </div>
            <!-- Horizontal strip: ≥6 tiles visible on desktop, scroll for more, lazy-load past 10 -->
            <div class="flex gap-3 overflow-x-auto no-scrollbar snap-x snap-mandatory -mx-1 px-1 pb-2 scroll-smooth"
                 data-gallery-strip
                 data-product-id="<?= (int) $p['id'] ?>">
                <?= view('frontend/partials/gallery_tiles', ['items' => $initial, 'productName' => $p['name']]) ?>
                <?php if ($hasMore): ?>
                    <div class="shrink-0 w-28 sm:w-32 aspect-square flex items-center justify-center text-subtle"
                         data-gallery-sentinel data-offset="<?= $initialCount ?>" aria-hidden="true">
                        <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                    </div>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <!-- ===================== REVIEWS (§17) ===================== -->
    <section id="reviews" class="mt-16 lg:mt-24 border-t border-line pt-12">
        <h2 class="text-h2 font-display mb-8">What Our Customers Say</h2>
        <?php if (! empty($reviews)): ?>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($reviews as $r): ?>
                    <figure class="card p-6">
                        <?= star_row((float) $r['rating']) ?>
                        <?php if ($r['title']): ?><figcaption class="font-display text-lg mt-3"><?= esc($r['title']) ?></figcaption><?php endif ?>
                        <blockquote class="text-muted text-[15px] mt-2 leading-relaxed"><?= esc($r['body']) ?></blockquote>
                        <div class="mt-4 flex items-center gap-3 text-sm">
                            <span class="w-9 h-9 rounded-full bg-wood/15 text-wood-dark font-medium flex items-center justify-center"><?= esc(strtoupper(substr($r['author_name'], 0, 1))) ?></span>
                            <div>
                                <p class="font-medium leading-tight"><?= esc($r['author_name']) ?></p>
                                <p class="text-subtle text-xs"><?= esc($r['city']) ?><?php if ($r['verified_purchase']): ?> · <span class="text-success">Verified</span><?php endif ?></p>
                            </div>
                        </div>
                    </figure>
                <?php endforeach ?>
            </div>
        <?php else: ?>
            <p class="text-muted">No reviews yet — be the first to share how this swing lives in your home.</p>
        <?php endif ?>
    </section>

    <!-- ===================== RELATED ===================== -->
    <?php if (! empty($related)): ?>
        <section class="mt-16 lg:mt-24">
            <h2 class="text-h2 font-display mb-8">You May Also Like</h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8 sm:gap-x-6">
                <?php foreach ($related as $rp): ?>
                    <?= view('components/product_card', ['product' => $rp, 'wishIds' => $wishIds]) ?>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
</div>

<!-- ===================== MOBILE STICKY BAR (§26) ===================== -->
<div class="lg:hidden fixed inset-x-0 bottom-0 z-30 bg-surface/95 backdrop-blur border-t border-line px-4 py-3 pb-safe translate-y-full transition-transform duration-300 ease-out-soft" data-sticky-bar>
    <div class="flex items-center gap-3">
        <div class="shrink-0">
            <p class="font-display text-lg tabular-nums leading-none" data-sticky-price><?= price($p['price']) ?></p>
        </div>
        <button type="button" class="btn-primary flex-1 btn-sm" data-pdp-add <?= $inStock ? '' : 'disabled' ?>>Add to Cart</button>
        <button type="button" class="btn-wood flex-1 btn-sm" data-pdp-buy <?= $inStock ? '' : 'disabled' ?>>Buy Now</button>
    </div>
</div>

<!-- Fullscreen gallery (§24) -->
<div class="fixed inset-0 z-[60] bg-ink/95 hidden items-center justify-center" data-lightbox>
    <button type="button" class="absolute top-4 right-4 w-11 h-11 rounded-full bg-white/10 text-white flex items-center justify-center z-10" data-lightbox-close aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
    </button>
    <figure class="flex flex-col items-center gap-3 max-w-[92vw] max-h-[90vh]">
        <img src="" alt="" class="max-w-[92vw] max-h-[82vh] object-contain" data-lightbox-image>
        <video controls playsinline class="max-w-[92vw] max-h-[82vh] object-contain hidden" data-lightbox-video></video>
        <figcaption class="text-bg/80 text-sm text-center hidden" data-lightbox-caption></figcaption>
    </figure>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= base_url('assets/js/product.js') ?>"></script>
<?= $this->endSection() ?>
