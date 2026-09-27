<?php
/** Shared filter controls for desktop sidebar + mobile sheet. */
$idPrefix = $idPrefix ?? 'd';
?>
<!-- Category -->
<fieldset>
    <legend class="text-sm font-semibold mb-3">Category</legend>
    <ul class="space-y-1.5 text-sm">
        <li><a href="<?= base_url('shop') ?>" class="<?= empty($category) ? 'text-wood font-medium' : 'text-muted hover:text-ink' ?>">All Swings</a></li>
        <?php foreach ($categories as $c): ?>
            <li class="flex items-center justify-between">
                <a href="<?= base_url('category/' . $c['slug']) ?>" class="<?= (! empty($category) && $category['id'] == $c['id']) ? 'text-wood font-medium' : 'text-muted hover:text-ink' ?>"><?= esc($c['name']) ?></a>
                <span class="text-xs text-subtle tabular-nums"><?= (int) $c['product_count'] ?></span>
            </li>
        <?php endforeach ?>
    </ul>
</fieldset>

<!-- Price -->
<fieldset class="border-t border-line pt-6">
    <legend class="text-sm font-semibold mb-3">Price (₹)</legend>
    <div class="flex items-center gap-2">
        <label class="sr-only" for="<?= $idPrefix ?>-min">Minimum price</label>
        <input id="<?= $idPrefix ?>-min" type="number" name="min_price" inputmode="numeric" min="<?= $priceBounds['min'] ?>" max="<?= $priceBounds['max'] ?>"
               value="<?= esc($filters['min_price'] ?? '', 'attr') ?>" placeholder="<?= number_format($priceBounds['min']) ?>"
               class="field-input py-2 min-h-[40px] text-sm" data-filter-input>
        <span class="text-subtle">–</span>
        <label class="sr-only" for="<?= $idPrefix ?>-max">Maximum price</label>
        <input id="<?= $idPrefix ?>-max" type="number" name="max_price" inputmode="numeric" min="<?= $priceBounds['min'] ?>" max="<?= $priceBounds['max'] ?>"
               value="<?= esc($filters['max_price'] ?? '', 'attr') ?>" placeholder="<?= number_format($priceBounds['max']) ?>"
               class="field-input py-2 min-h-[40px] text-sm" data-filter-input>
    </div>
</fieldset>

<!-- Wood type -->
<?php if (! empty($woodTypes)): ?>
<fieldset class="border-t border-line pt-6">
    <legend class="text-sm font-semibold mb-3">Wood</legend>
    <div class="space-y-2">
        <?php foreach ($woodTypes as $w): ?>
            <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                <input type="checkbox" name="wood_type[]" value="<?= esc($w, 'attr') ?>" data-filter-input
                       <?= in_array($w, $filters['wood_type'] ?? [], true) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30">
                <span><?= esc($w) ?></span>
            </label>
        <?php endforeach ?>
    </div>
</fieldset>
<?php endif ?>

<!-- Finish -->
<?php if (! empty($finishes)): ?>
<fieldset class="border-t border-line pt-6">
    <legend class="text-sm font-semibold mb-3">Finish</legend>
    <div class="space-y-2">
        <?php foreach ($finishes as $f): ?>
            <label class="flex items-center gap-2.5 text-sm cursor-pointer">
                <input type="checkbox" name="finish[]" value="<?= esc($f, 'attr') ?>" data-filter-input
                       <?= in_array($f, $filters['finish'] ?? [], true) ? 'checked' : '' ?>
                       class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30">
                <span><?= esc($f) ?></span>
            </label>
        <?php endforeach ?>
    </div>
</fieldset>
<?php endif ?>

<!-- Availability + customizable -->
<fieldset class="border-t border-line pt-6 space-y-2">
    <legend class="text-sm font-semibold mb-3">More</legend>
    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
        <input type="checkbox" name="availability" value="in_stock" data-filter-input
               <?= ($filters['availability'] ?? '') === 'in_stock' ? 'checked' : '' ?>
               class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30">
        <span>In stock only</span>
    </label>
    <label class="flex items-center gap-2.5 text-sm cursor-pointer">
        <input type="checkbox" name="customizable" value="1" data-filter-input
               <?= ! empty($filters['customizable']) ? 'checked' : '' ?>
               class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30">
        <span>Customizable</span>
    </label>
</fieldset>
