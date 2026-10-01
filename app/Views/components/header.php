<?php
$cats        = nav_categories();
$cartCount   = cart_count();
$wishCount   = count(wishlist_ids());
$currentPath = '/' . trim(uri_string(), '/');
$isActive    = static fn (string $p): string => str_starts_with($currentPath, $p) ? 'text-wood' : 'text-ink';
?>
<!-- Announcement bar -->
<div class="bg-ink text-bg text-center text-[13px] tracking-wide py-2 px-4">
    <span class="opacity-90">Handcrafted to order · Pan-India delivery · Expert installation available</span>
</div>

<header class="sticky top-0 z-40 bg-bg/90 backdrop-blur-md border-b border-line" data-header>
    <nav class="container-page flex items-center gap-2 sm:gap-4 h-header" aria-label="Primary">
        <!-- Mobile: menu -->
        <button type="button" class="lg:hidden -ml-2 p-2 text-ink" data-menu-open aria-label="Open menu" aria-controls="mobile-menu" aria-expanded="false">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>

        <!-- Logo -->
        <a href="<?= base_url('/') ?>" class="flex items-center mr-auto lg:mr-0 min-w-0 shrink" aria-label="<?= esc(store_name()) ?> home">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="<?= esc(store_name()) ?>" width="600" height="143" class="h-7 sm:h-9 lg:h-10 w-auto max-w-full" fetchpriority="high">
        </a>

        <!-- Desktop nav -->
        <ul class="hidden lg:flex items-center gap-7 mx-auto text-[15px] font-medium">
            <li class="relative group">
                <a href="<?= base_url('shop') ?>" class="<?= $isActive('/shop') ?> hover:text-wood inline-flex items-center gap-1 py-2">
                    Shop
                    <svg class="w-4 h-4 opacity-60" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="m6 9 6 6 6-6"/></svg>
                </a>
                <!-- Mega dropdown -->
                <div class="absolute left-1/2 -translate-x-1/2 top-full pt-3 opacity-0 invisible translate-y-2 group-hover:opacity-100 group-hover:visible group-hover:translate-y-0 transition-all duration-200 ease-out-soft">
                    <div class="w-[560px] bg-surface rounded-lg shadow-lift border border-line p-5 grid grid-cols-2 gap-1">
                        <?php foreach ($cats as $c): ?>
                            <a href="<?= base_url('category/' . $c['slug']) ?>" class="flex items-center justify-between rounded-md px-3 py-2.5 hover:bg-sand transition-colors">
                                <span class="text-ink"><?= esc($c['name']) ?></span>
                                <span class="text-xs text-subtle tabular-nums"><?= (int) $c['product_count'] ?></span>
                            </a>
                        <?php endforeach ?>
                        <a href="<?= base_url('shop') ?>" class="col-span-2 mt-2 text-sm font-medium text-wood hover:text-wood-dark inline-flex items-center gap-1 px-3">
                            View all swings
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </a>
                    </div>
                </div>
            </li>
            <li><a href="<?= base_url('custom-swings') ?>" class="<?= $isActive('/custom-swings') ?> hover:text-wood py-2">Custom Swings</a></li>
            <li><a href="<?= base_url('craftsmanship') ?>" class="<?= $isActive('/craftsmanship') ?> hover:text-wood py-2">Craftsmanship</a></li>
            <li><a href="<?= base_url('journal') ?>" class="<?= $isActive('/journal') ?> hover:text-wood py-2">Journal</a></li>
            <li><a href="<?= base_url('about') ?>" class="<?= $isActive('/about') ?> hover:text-wood py-2">About</a></li>
        </ul>

        <!-- Actions -->
        <div class="flex items-center gap-1 sm:gap-2 ml-auto lg:ml-0">
            <button type="button" class="p-2 sm:p-2.5 rounded-full hover:bg-sand transition-colors" data-search-open aria-label="Search products">
                <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-3.5-3.5"/></svg>
            </button>
            <a href="<?= base_url('account') ?>" class="hidden sm:inline-flex p-2.5 rounded-full hover:bg-sand transition-colors" aria-label="Your account">
                <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" d="M5 20c0-3.3 3.1-6 7-6s7 2.7 7 6"/></svg>
            </a>
            <a href="<?= base_url('wishlist') ?>" class="relative p-2 sm:p-2.5 rounded-full hover:bg-sand transition-colors" aria-label="Wishlist">
                <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20s-7-4.4-7-9.3C5 7.9 7 6 9.3 6c1.4 0 2.7.7 3.4 1.9C13.4 6.7 14.7 6 16.1 6 18.4 6 20 7.9 20 10.7 20 15.6 12 20 12 20Z"/></svg>
                <span data-wishlist-count class="<?= $wishCount ? '' : 'hidden' ?> absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-wood text-white text-[11px] font-semibold inline-flex items-center justify-center tabular-nums"><?= $wishCount ?></span>
            </a>
            <button type="button" class="relative p-2 sm:p-2.5 rounded-full hover:bg-sand transition-colors" data-cart-open aria-label="Open cart">
                <svg class="w-[22px] h-[22px]" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0"/></svg>
                <span data-cart-count class="<?= $cartCount ? '' : 'hidden' ?> absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-terracotta text-white text-[11px] font-semibold inline-flex items-center justify-center tabular-nums"><?= $cartCount ?></span>
            </button>
        </div>
    </nav>
</header>

<!-- Mobile menu drawer -->
<div id="mobile-menu" class="fixed inset-0 z-50 lg:hidden hidden" data-menu>
    <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" data-menu-overlay></div>
    <div class="absolute inset-y-0 left-0 w-[86%] max-w-sm bg-bg shadow-lift flex flex-col -translate-x-full transition-transform duration-300 ease-out-soft" data-menu-panel>
        <div class="flex items-center justify-between h-header px-5 border-b border-line">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="<?= esc(store_name()) ?>" width="600" height="143" class="h-9 w-auto">
            <button type="button" class="p-2 -mr-2" data-menu-close aria-label="Close menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-4">
            <p class="eyebrow mb-2">Collections</p>
            <ul class="mb-6 divide-y divide-line">
                <?php foreach ($cats as $c): ?>
                    <li><a href="<?= base_url('category/' . $c['slug']) ?>" class="flex items-center justify-between py-3 text-lg"><?= esc($c['name']) ?><span class="text-sm text-subtle tabular-nums"><?= (int) $c['product_count'] ?></span></a></li>
                <?php endforeach ?>
            </ul>
            <p class="eyebrow mb-2">More</p>
            <ul class="divide-y divide-line">
                <li><a href="<?= base_url('shop') ?>" class="block py-3 text-lg">Shop All</a></li>
                <li><a href="<?= base_url('custom-swings') ?>" class="block py-3 text-lg">Custom Swings</a></li>
                <li><a href="<?= base_url('craftsmanship') ?>" class="block py-3 text-lg">Craftsmanship</a></li>
                <li><a href="<?= base_url('journal') ?>" class="block py-3 text-lg">Journal</a></li>
                <li><a href="<?= base_url('about') ?>" class="block py-3 text-lg">About</a></li>
                <li><a href="<?= base_url('contact') ?>" class="block py-3 text-lg">Contact</a></li>
                <li><a href="<?= base_url('account') ?>" class="block py-3 text-lg">My Account</a></li>
            </ul>
        </div>
        <div class="p-5 border-t border-line pb-safe">
            <a href="<?= base_url('shop') ?>" class="btn-wood btn-block">Shop Swings</a>
        </div>
    </div>
</div>
