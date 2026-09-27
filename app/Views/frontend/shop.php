<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$sorts = [
    'featured'   => 'Featured',
    'newest'     => 'Newest',
    'price_low'  => 'Price: Low to High',
    'price_high' => 'Price: High to Low',
    'rating'     => 'Top Rated',
];
?>
<div class="container-page py-6 sm:py-10">
    <!-- Breadcrumbs -->
    <nav aria-label="Breadcrumb" class="text-sm text-muted mb-5">
        <ol class="flex items-center gap-2 flex-wrap">
            <li><a href="<?= base_url('/') ?>" class="hover:text-wood">Home</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="<?= base_url('shop') ?>" class="hover:text-wood">Shop</a></li>
            <?php if (! empty($category)): ?>
                <li aria-hidden="true">/</li>
                <li class="text-ink" aria-current="page"><?= esc($category['name']) ?></li>
            <?php endif ?>
        </ol>
    </nav>

    <header class="mb-8">
        <h1 class="text-h1 font-display"><?= esc($title) ?></h1>
        <?php if (! empty($category['description'])): ?>
            <p class="mt-3 text-muted max-w-2xl"><?= esc($category['description']) ?></p>
        <?php endif ?>
    </header>

    <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-10">
        <!-- ===== Desktop filter sidebar ===== -->
        <aside class="hidden lg:block">
            <form id="filter-form" data-filter-form class="sticky top-[calc(var(--header-height)+1.5rem)] space-y-7"
                  action="<?= base_url('shop/filter') ?>"
                  data-category="<?= esc($category['slug'] ?? '', 'attr') ?>">
                <?= $this->include('frontend/partials/filter_fields') ?>
                <button type="button" class="text-sm text-muted hover:text-danger" data-clear-filters>Clear all filters</button>
            </form>
        </aside>

        <!-- ===== Results column ===== -->
        <div>
            <!-- Toolbar -->
            <div class="flex items-center justify-between gap-3 mb-6">
                <button type="button" class="lg:hidden btn-outline btn-sm" data-filter-open aria-haspopup="dialog">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
                    Filters
                </button>
                <div class="ml-auto flex items-center gap-2">
                    <label for="sort" class="text-sm text-muted hidden sm:inline">Sort</label>
                    <select id="sort" data-sort class="field-input py-2 min-h-[40px] w-auto text-sm pr-9">
                        <?php foreach ($sorts as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($sort === $val) ? 'selected' : '' ?>><?= esc($label) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <!-- Results (AJAX target) -->
            <div data-results aria-live="polite">
                <?= $this->include('frontend/partials/product_results') ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== Mobile filter bottom sheet (§19) ===== -->
<div id="filter-sheet" class="fixed inset-0 z-50 lg:hidden hidden" data-filter-sheet role="dialog" aria-modal="true" aria-label="Filters">
    <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" data-filter-overlay></div>
    <div class="absolute inset-x-0 bottom-0 max-h-[85vh] bg-bg rounded-t-xl flex flex-col translate-y-full transition-transform duration-300 ease-out-soft" data-filter-panel data-hide="translate-y-full">
        <div class="flex items-center justify-between px-5 h-14 border-b border-line shrink-0">
            <h2 class="font-display text-lg">Filters</h2>
            <button type="button" class="p-2 -mr-2" data-filter-close aria-label="Close filters">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-5">
            <form id="filter-form-mobile" data-filter-form-mobile class="space-y-7" data-category="<?= esc($category['slug'] ?? '', 'attr') ?>">
                <?= $this->include('frontend/partials/filter_fields', ['idPrefix' => 'm']) ?>
            </form>
        </div>
        <div class="p-4 border-t border-line grid grid-cols-2 gap-3 pb-safe shrink-0">
            <button type="button" class="btn-outline" data-clear-filters>Clear</button>
            <button type="button" class="btn-primary" data-filter-apply>Show results</button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= base_url('assets/js/filters.js') ?>"></script>
<?= $this->endSection() ?>
