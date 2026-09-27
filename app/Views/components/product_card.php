<?php
/**
 * Reusable product card (§11 / §12).
 * Expects: $product (array). Optional: $wishIds (array of product ids).
 */
$wishIds  = $wishIds ?? wishlist_ids();
$p        = $product;
$url      = base_url('product/' . $p['slug']);
$img      = product_image($p['primary_image'] ?? null);
$hover    = ! empty($p['hover_image']) ? product_image($p['hover_image']) : null;
$discount = discount_percent($p['price'], $p['compare_price'] ?? 0);
$inWish   = in_array((int) $p['id'], array_map('intval', $wishIds), true);
$rating   = (float) ($p['rating_avg'] ?? 0);
$rcount   = (int) ($p['rating_count'] ?? 0);
?>
<article class="group relative flex flex-col" data-product-id="<?= (int) $p['id'] ?>">
    <div class="relative aspect-square overflow-hidden rounded-lg bg-sand">
        <a href="<?= esc($url, 'attr') ?>" class="block w-full h-full" aria-label="<?= esc($p['name'], 'attr') ?>">
            <img src="<?= esc($img, 'attr') ?>" alt="<?= esc($p['primary_alt'] ?? $p['name'], 'attr') ?>"
                 width="1000" height="1000" loading="lazy" decoding="async"
                 class="w-full h-full object-cover transition-transform duration-500 ease-out-soft group-hover:scale-[1.04] <?= $hover ? 'group-hover:opacity-0' : '' ?>">
            <?php if ($hover): ?>
                <img src="<?= esc($hover, 'attr') ?>" alt="" aria-hidden="true"
                     width="1000" height="1000" loading="lazy" decoding="async"
                     class="absolute inset-0 w-full h-full object-cover opacity-0 scale-[1.04] transition-opacity duration-500 ease-out-soft group-hover:opacity-100">
            <?php endif ?>
        </a>

        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-1.5">
            <?php if ($discount > 0): ?><span class="badge-sale">-<?= $discount ?>%</span><?php endif ?>
            <?php if (($p['stock_status'] ?? '') === 'made_to_order'): ?><span class="badge-soft">Made to order</span><?php endif ?>
        </div>

        <!-- Wishlist -->
        <button type="button"
                class="absolute top-3 right-3 w-10 h-10 rounded-full bg-bg/90 backdrop-blur flex items-center justify-center shadow-soft hover:bg-white transition-colors <?= $inWish ? 'text-terracotta' : 'text-ink' ?>"
                data-wishlist-toggle="<?= (int) $p['id'] ?>" aria-pressed="<?= $inWish ? 'true' : 'false' ?>"
                aria-label="<?= $inWish ? 'Remove from wishlist' : 'Add to wishlist' ?>">
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="<?= $inWish ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.6" aria-hidden="true" data-heart><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-9.3C5 7.9 7 6 9.3 6c1.4 0 2.7.7 3.4 1.9C13.4 6.7 14.7 6 16.1 6 18.4 6 20 7.9 20 10.7 20 15.6 12 20 12 20Z"/></svg>
        </button>

        <!-- Quick add (desktop hover / always tappable on mobile) -->
        <div class="absolute inset-x-3 bottom-3 sm:translate-y-3 sm:opacity-0 sm:group-hover:translate-y-0 sm:group-hover:opacity-100 transition-all duration-300 ease-out-soft">
            <?php if (! empty($p['has_variants']) || ! empty($p['is_customizable'])): ?>
                <a href="<?= esc($url, 'attr') ?>" class="btn bg-ink/90 text-bg backdrop-blur btn-sm btn-block hover:bg-ink">Choose options</a>
            <?php else: ?>
                <button type="button" class="btn bg-ink/90 text-bg backdrop-blur btn-sm btn-block hover:bg-ink"
                        data-add-to-cart data-product-id="<?= (int) $p['id'] ?>">
                    <span data-btn-label>Quick add</span>
                </button>
            <?php endif ?>
        </div>
    </div>

    <!-- Meta -->
    <div class="pt-3.5 flex flex-col gap-1">
        <div class="flex items-center gap-2 text-xs text-subtle">
            <?php if (! empty($p['wood_type'])): ?><span><?= esc($p['wood_type']) ?></span><?php endif ?>
            <?php if (! empty($p['wood_type']) && $rcount > 0): ?><span aria-hidden="true">·</span><?php endif ?>
            <?php if ($rcount > 0): ?>
                <span class="inline-flex items-center gap-1"><?= star_row($rating, 'w-3.5 h-3.5') ?><span class="tabular-nums"><?= number_format($rating, 1) ?></span></span>
            <?php endif ?>
        </div>
        <h3 class="font-display text-lg leading-snug">
            <a href="<?= esc($url, 'attr') ?>" class="hover:text-wood transition-colors after:absolute after:inset-0 sm:after:hidden"><?= esc($p['name']) ?></a>
        </h3>
        <?php if (! empty($p['short_description'])): ?>
            <p class="text-sm text-muted line-clamp-1"><?= esc($p['short_description']) ?></p>
        <?php endif ?>
        <div class="flex items-baseline gap-2 mt-1">
            <span class="font-medium text-ink tabular-nums"><?= price($p['price']) ?></span>
            <?php if ($discount > 0): ?>
                <span class="text-sm text-subtle line-through tabular-nums"><?= price($p['compare_price']) ?></span>
            <?php endif ?>
        </div>
    </div>
</article>
