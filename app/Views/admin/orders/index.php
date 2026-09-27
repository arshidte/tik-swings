<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="flex items-center gap-2 mb-5 overflow-x-auto no-scrollbar">
    <a href="<?= base_url('admin/orders') ?>" class="btn-sm <?= ! $status ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <?php foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $s): ?>
        <a href="<?= base_url('admin/orders?status=' . $s) ?>" class="btn-sm whitespace-nowrap <?= $status === $s ? 'btn-primary' : 'btn-outline' ?>"><?= order_status_label($s) ?></a>
    <?php endforeach ?>
</div>

<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted">
            <tr><th class="px-4 py-3 font-medium">Order</th><th class="px-4 py-3 font-medium hidden sm:table-cell">Date</th><th class="px-4 py-3 font-medium">Customer</th><th class="px-4 py-3 font-medium">Total</th><th class="px-4 py-3 font-medium">Payment</th><th class="px-4 py-3 font-medium">Status</th></tr>
        </thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($orders as $o): ?>
                <tr class="hover:bg-sand/30 cursor-pointer" onclick="location.href='<?= base_url('admin/orders/' . $o['id']) ?>'">
                    <td class="px-4 py-3 font-medium text-wood"><?= esc($o['order_number']) ?></td>
                    <td class="px-4 py-3 text-muted hidden sm:table-cell"><?= esc(date('M j, Y', strtotime($o['created_at']))) ?></td>
                    <td class="px-4 py-3"><?= esc($o['email']) ?></td>
                    <td class="px-4 py-3 tabular-nums font-medium"><?= price($o['total']) ?></td>
                    <td class="px-4 py-3"><span class="badge <?= $o['payment_status'] === 'paid' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($o['payment_status']) ?></span></td>
                    <td class="px-4 py-3"><span class="badge <?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($orders)): ?><tr><td colspan="6" class="px-4 py-10 text-center text-muted">No orders found.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($pager): ?><div class="mt-6"><?= $pager->links('default', 'swing_pager') ?></div><?php endif ?>
<?= $this->endSection() ?>
