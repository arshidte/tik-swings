<?php $popular = ['Teak swing', 'Balcony jhula', 'Modern rope swing', 'Custom swing', 'Kids swing']; ?>
<div id="search-overlay" class="fixed inset-0 z-50 hidden" data-search role="dialog" aria-modal="true" aria-label="Search products">
    <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" data-search-overlay></div>
    <div class="relative bg-bg shadow-lift -translate-y-4 opacity-0 transition-all duration-300 ease-out-soft" data-search-panel>
        <div class="container-page py-5">
            <form class="flex items-center gap-3" data-search-form role="search">
                <svg class="w-6 h-6 text-subtle shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
                <label for="search-input" class="sr-only">Search for swings</label>
                <input id="search-input" type="search" name="q" autocomplete="off" placeholder="Search for teak swings, jhulas, balcony swings…"
                       class="flex-1 bg-transparent text-lg sm:text-xl py-2 focus:outline-none placeholder:text-subtle" data-search-input>
                <button type="button" class="p-2 text-ink hover:text-wood" data-search-close aria-label="Close search">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </form>
        </div>
        <div class="border-t border-line max-h-[70vh] overflow-y-auto">
            <div class="container-page py-6">
                <!-- Default state: popular searches -->
                <div data-search-default>
                    <p class="eyebrow mb-3">Popular searches</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($popular as $p): ?>
                            <button type="button" class="badge-soft hover:bg-wood hover:text-white transition-colors" data-search-term="<?= esc($p, 'attr') ?>"><?= esc($p) ?></button>
                        <?php endforeach ?>
                    </div>
                </div>
                <!-- Live results injected by search.js -->
                <div data-search-results class="hidden"></div>
            </div>
        </div>
    </div>
</div>
