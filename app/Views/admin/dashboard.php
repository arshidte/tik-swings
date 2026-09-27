<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$cards = [
    ['Total Sales', price($totalSales), 'M3 12h4l3 8 4-16 3 8h4'],
    ['Orders', number_format($orderCount), 'M6 7h12l-1 13H7L6 7Z'],
    ['Customers', number_format($customerCount), 'M12 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM5 20c0-3.3 3.1-6 7-6s7 2.7 7 6'],
    ['Products', number_format($productCount), 'M5 4v6m14-6v6M5 10h14'],
    ['Pending Orders', number_format($pendingOrders), 'M12 7v5l3 2'],
];
$maxSale = max(1, max(array_column($salesByDay, 'value')));
?>
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php foreach ($cards as [$label, $value, $icon]): ?>
        <div class="bg-surface rounded-lg border border-line p-5">
            <div class="flex items-center gap-2 text-muted text-sm mb-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
                <?= esc($label) ?>
            </div>
            <p class="text-2xl font-display tabular-nums"><?= $value ?></p>
        </div>
    <?php endforeach ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <!-- Sales chart -->
    <div class="lg:col-span-2 bg-surface rounded-lg border border-line p-5">
        <h2 class="font-medium mb-4">Sales · last 14 days</h2>
        <div class="flex items-end gap-1.5 h-48">
            <?php foreach ($salesByDay as $d): $h = max(2, ($d['value'] / $maxSale) * 100); ?>
                <div class="flex-1 flex flex-col items-center justify-end group">
                    <div class="w-full bg-wood/80 hover:bg-wood rounded-t transition-all relative" style="height:<?= $h ?>%">
                        <span class="absolute -top-6 left-1/2 -translate-x-1/2 text-[10px] bg-ink text-bg px-1.5 py-0.5 rounded opacity-0 group-hover:opacity-100 whitespace-nowrap tabular-nums"><?= price($d['value']) ?></span>
                    </div>
                    <span class="text-[9px] text-subtle mt-1 rotate-0 whitespace-nowrap"><?= esc($d['label']) ?></span>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <!-- Low stock -->
    <div class="bg-surface rounded-lg border border-line p-5">
        <h2 class="font-medium mb-4">Low stock</h2>
        <?php if (! empty($lowStock)): ?>
            <ul class="space-y-3">
                <?php foreach ($lowStock as $p): ?>
                    <li class="flex items-center justify-between text-sm">
                        <a href="<?= base_url('admin/products/edit/' . $p['id']) ?>" class="hover:text-wood truncate mr-2"><?= esc($p['name']) ?></a>
                        <span class="badge <?= $p['stock'] == 0 ? 'bg-danger/10 text-danger' : 'bg-sand text-wood-dark' ?> tabular-nums"><?= (int) $p['stock'] ?> left</span>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-muted">All products well stocked.</p>
        <?php endif ?>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-surface rounded-lg border border-line p-5">
        <div class="flex items-center justify-between mb-4"><h2 class="font-medium">Recent orders</h2><a href="<?= base_url('admin/orders') ?>" class="text-sm text-wood">View all</a></div>
        <div class="divide-y divide-line">
            <?php foreach ($recentOrders as $o): ?>
                <a href="<?= base_url('admin/orders/' . $o['id']) ?>" class="flex items-center justify-between py-2.5 text-sm hover:text-wood">
                    <span><?= esc($o['order_number']) ?></span>
                    <span class="flex items-center gap-3"><span class="badge <?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span><span class="tabular-nums font-medium"><?= price($o['total']) ?></span></span>
                </a>
            <?php endforeach ?>
            <?php if (empty($recentOrders)): ?><p class="text-sm text-muted py-4">No orders yet.</p><?php endif ?>
        </div>
    </div>
    <div class="bg-surface rounded-lg border border-line p-5">
        <div class="flex items-center justify-between mb-4"><h2 class="font-medium">Recent reviews</h2><a href="<?= base_url('admin/reviews') ?>" class="text-sm text-wood">Moderate</a></div>
        <div class="divide-y divide-line">
            <?php foreach ($recentReviews as $r): ?>
                <div class="py-2.5 text-sm flex items-center justify-between">
                    <span class="truncate mr-2"><?= esc($r['author_name']) ?> · <?= esc(truncate_words($r['body'], 6)) ?></span>
                    <span class="badge <?= $r['status'] === 'approved' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($r['status']) ?></span>
                </div>
            <?php endforeach ?>
            <?php if (empty($recentReviews)): ?><p class="text-sm text-muted py-4">No reviews yet.</p><?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
