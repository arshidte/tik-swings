<?php
/** Shared results block — rendered on load and returned by the AJAX filter (§20). */
$from = $total ? (($page - 1) * $perPage) + 1 : 0;
$to   = min($page * $perPage, $total);
?>
<div data-results-inner>
    <p class="text-sm text-muted mb-5" data-results-count>
        <?php if ($total): ?>
            Showing <span class="tabular-nums"><?= $from ?>–<?= $to ?></span> of <span class="tabular-nums"><?= $total ?></span> swings
        <?php else: ?>
            No swings match your filters
        <?php endif ?>
    </p>

    <?php if ($products): ?>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-8 sm:gap-x-6 sm:gap-y-10">
            <?php foreach ($products as $product): ?>
                <?= view('components/product_card', ['product' => $product, 'wishIds' => $wishIds]) ?>
            <?php endforeach ?>
        </div>

        <?php if ($pager && $pager->getPageCount('default') > 1): ?>
            <div class="mt-12 flex justify-center">
                <?= $pager->only(['category', 'wood_type', 'finish', 'min_price', 'max_price', 'sort', 'availability', 'customizable'])->links('default', 'swing_pager') ?>
            </div>
        <?php endif ?>
    <?php else: ?>
        <!-- Empty state (§64) -->
        <div class="text-center py-20 border border-dashed border-line rounded-lg">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-sand text-wood mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
            </span>
            <p class="font-display text-xl">We couldn't find a swing for that.</p>
            <p class="text-muted mt-2">Try clearing a filter or widening your price range.</p>
            <button type="button" class="btn-outline mt-6" data-clear-filters>Clear all filters</button>
        </div>
    <?php endif ?>
</div>
