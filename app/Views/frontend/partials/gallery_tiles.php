<?php
/**
 * Customer-gallery tiles (§ user-uploaded media). Rendered on load and returned
 * by the AJAX batch endpoint (Frontend\Product::media). Emits bare tiles so they
 * can be appended straight into the horizontal strip.
 *
 * @var array  $items       product_user_media rows
 * @var string $productName
 */
foreach ($items as $m):
    $isVideo = ($m['media_type'] ?? 'image') === 'video';
    $full    = product_image($m['media']);
    $poster  = $m['poster'] ? product_image($m['poster']) : $full;
    $cap     = trim(($m['author_name'] ?: '') . ($m['caption'] ? ($m['author_name'] ? ' — ' : '') . $m['caption'] : ''));
?>
    <button type="button"
            class="group relative shrink-0 w-28 sm:w-32 aspect-square rounded-lg overflow-hidden bg-sand ring-1 ring-line snap-start focus:outline-none focus-visible:ring-2 focus-visible:ring-wood"
            data-gallery-media
            data-type="<?= $isVideo ? 'video' : 'image' ?>"
            data-src="<?= esc($full, 'attr') ?>"
            data-poster="<?= esc($poster, 'attr') ?>"
            data-caption="<?= esc($cap, 'attr') ?>"
            aria-label="<?= $isVideo ? 'Play customer video' : 'View customer photo' ?><?= $cap ? ': ' . esc($cap, 'attr') : '' ?>">
        <?php if ($isVideo): ?>
            <?php if ($m['poster']): ?>
                <img src="<?= esc($poster, 'attr') ?>" alt="<?= esc($cap ?: $productName, 'attr') ?>" loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
            <?php else: ?>
                <video src="<?= esc($full, 'attr') ?>#t=0.1" muted playsinline preload="metadata" class="w-full h-full object-cover transition duration-500 group-hover:scale-105"></video>
            <?php endif ?>
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="w-9 h-9 rounded-full bg-ink/55 backdrop-blur flex items-center justify-center text-white transition group-hover:bg-wood">
                    <svg class="w-4 h-4 translate-x-0.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
        <?php else: ?>
            <img src="<?= esc($full, 'attr') ?>" alt="<?= esc($cap ?: $productName, 'attr') ?>" loading="lazy" class="w-full h-full object-cover transition duration-500 group-hover:scale-105">
        <?php endif ?>
        <?php if ($cap): ?>
            <span class="absolute inset-x-0 bottom-0 p-2 pt-5 bg-gradient-to-t from-ink/75 to-transparent text-bg text-[11px] leading-snug opacity-0 group-hover:opacity-100 transition text-left line-clamp-2"><?= esc($cap) ?></span>
        <?php endif ?>
    </button>
<?php endforeach ?>
