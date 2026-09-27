<?php
$nav = [
    ['Dashboard', 'admin', 'M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-4H4v4ZM14 9h6V4h-6v5Z'],
    ['Products', 'admin/products', 'M5 4v6m14-6v6M5 10h14M8 10l-1 9m10-9l1 9'],
    ['Categories', 'admin/categories', 'M4 6h16M4 12h16M4 18h10'],
    ['Orders', 'admin/orders', 'M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0'],
    ['Customers', 'admin/customers', 'M12 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM5 20c0-3.3 3.1-6 7-6s7 2.7 7 6'],
    ['Reviews', 'admin/reviews', 'M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z'],
    ['Coupons', 'admin/coupons', 'M4 8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z'],
    ['Pages', 'admin/pages', 'M6 3h9l3 3v15H6zM9 8h6M9 12h6M9 16h4'],
    ['Messages', 'admin/messages', 'M3 7l9 6 9-6M4 6h16v12H4z'],
    ['Subscribers', 'admin/subscribers', 'M3 7l9 6 9-6M4 6h16v12H4z'],
    ['Settings', 'admin/settings', 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM4 12h2m12 0h2M12 4v2m0 12v2'],
];
$current = uri_string();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title><?= esc($title ?? 'Admin') ?> · <?= esc(store_name()) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="bg-sand/40 text-ink font-sans min-h-dvh">
<div class="flex min-h-dvh">
    <!-- Sidebar -->
    <aside class="hidden lg:flex flex-col w-60 bg-ink text-bg/80 shrink-0 sticky top-0 h-dvh">
        <div class="h-16 flex items-center gap-2 px-5 border-b border-white/10">
            <img src="<?= base_url('assets/images/logo.png') ?>" alt="<?= esc(store_name()) ?>" width="600" height="143" class="h-7 w-auto">
            <span class="font-display text-bg/70 text-sm border-l border-white/20 pl-2">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">
            <?php foreach ($nav as [$label, $url, $icon]):
                $isActive = ($url === 'admin') ? ($current === 'admin') : str_starts_with($current, $url); ?>
                <a href="<?= base_url($url) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-md text-sm transition-colors <?= $isActive ? 'bg-white/10 text-bg' : 'hover:bg-white/5' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
                    <?= esc($label) ?>
                </a>
            <?php endforeach ?>
        </nav>
        <div class="p-3 border-t border-white/10">
            <a href="<?= base_url('/') ?>" class="flex items-center gap-2 px-3 py-2 text-sm hover:text-bg" target="_blank">View store ↗</a>
            <a href="<?= base_url('logout') ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-red-300 hover:text-red-200">Sign out</a>
        </div>
    </aside>

    <div class="flex-1 min-w-0">
        <!-- Topbar -->
        <header class="h-16 bg-surface border-b border-line flex items-center justify-between px-5 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <details class="lg:hidden relative">
                    <summary class="list-none cursor-pointer p-2"><svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg></summary>
                    <nav class="absolute left-0 top-full mt-1 w-56 bg-surface rounded-md shadow-lift border border-line py-2 z-30">
                        <?php foreach ($nav as [$label, $url]): ?><a href="<?= base_url($url) ?>" class="block px-4 py-2 text-sm hover:bg-sand"><?= esc($label) ?></a><?php endforeach ?>
                    </nav>
                </details>
                <h1 class="font-display text-xl"><?= esc($title ?? 'Dashboard') ?></h1>
            </div>
            <div class="text-sm text-muted">Signed in as <span class="text-ink font-medium"><?= esc(session('user_name')) ?></span></div>
        </header>

        <main class="p-5 sm:p-7 max-w-7xl">
            <?php if (session('success')): ?><div class="mb-5 rounded-md bg-success/10 text-success px-4 py-3 text-sm"><?= esc(session('success')) ?></div><?php endif ?>
            <?php if (session('error')): ?><div class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc(session('error')) ?></div><?php endif ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
<script>window.SG={baseUrl:'<?= rtrim(base_url(), '/') ?>/',csrf:{name:'<?= esc(csrf_token()) ?>',hash:'<?= csrf_hash() ?>',header:'X-CSRF-TOKEN'}};</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
