<?php
$items = [
    'dashboard' => ['Dashboard', 'account', 'M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-4H4v4ZM14 9h6V4h-6v5Z'],
    'orders'    => ['Orders', 'account/orders', 'M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0'],
    'addresses' => ['Addresses', 'account/addresses', 'M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z'],
];
?>
<nav class="space-y-1" aria-label="Account">
    <?php foreach ($items as $key => [$label, $url, $icon]): ?>
        <a href="<?= base_url($url) ?>" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-medium transition-colors <?= ($active ?? '') === $key ? 'bg-ink text-bg' : 'hover:bg-sand' ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
            <?= esc($label) ?>
        </a>
    <?php endforeach ?>
    <a href="<?= base_url('logout') ?>" class="flex items-center gap-3 px-4 py-3 rounded-md text-sm font-medium text-danger hover:bg-danger/5 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12H4m0 0 4-4m-4 4 4 4m5-11h5a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-5"/></svg>
        Sign Out
    </a>
</nav>
