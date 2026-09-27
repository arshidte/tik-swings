<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php $ship = $order['shipping_address'] ? json_decode($order['shipping_address'], true) : null; ?>
<a href="<?= base_url('admin/orders') ?>" class="text-sm text-muted hover:text-wood">← All orders</a>

<div class="grid lg:grid-cols-3 gap-6 mt-4 items-start">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-surface rounded-lg border border-line p-5">
            <div class="flex items-center justify-between mb-4">
                <div><h2 class="font-display text-xl"><?= esc($order['order_number']) ?></h2><p class="text-sm text-muted"><?= esc(date('F j, Y · g:i A', strtotime($order['created_at']))) ?></p></div>
                <span class="badge <?= order_status_class($order['status']) ?>"><?= order_status_label($order['status']) ?></span>
            </div>
            <div class="divide-y divide-line">
                <?php foreach ($items as $it): ?>
                    <div class="flex gap-3 py-3">
                        <img src="<?= esc(product_image($it['image']), 'attr') ?>" alt="" width="48" height="48" class="w-12 h-12 rounded object-cover bg-sand">
                        <div class="flex-1"><p class="text-sm font-medium"><?= esc($it['product_name']) ?></p><p class="text-xs text-muted"><?= esc($it['variant_name'] ?: $it['sku']) ?> · Qty <?= (int) $it['quantity'] ?></p></div>
                        <span class="text-sm tabular-nums"><?= price($it['line_total']) ?></span>
                    </div>
                <?php endforeach ?>
            </div>
            <dl class="space-y-1.5 text-sm border-t border-line pt-4 mt-2">
                <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums"><?= price($order['subtotal']) ?></dd></div>
                <?php if ($order['discount'] > 0): ?><div class="flex justify-between"><dt class="text-success">Discount</dt><dd class="tabular-nums text-success">−<?= price($order['discount']) ?></dd></div><?php endif ?>
                <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd class="tabular-nums"><?= $order['shipping'] > 0 ? price($order['shipping']) : 'Free' ?></dd></div>
                <div class="flex justify-between font-medium text-base border-t border-line pt-2 mt-1"><dt>Total</dt><dd class="tabular-nums"><?= price($order['total']) ?></dd></div>
            </dl>
        </div>

        <?php if (! empty($history)): ?>
            <div class="bg-surface rounded-lg border border-line p-5">
                <h2 class="font-medium mb-4">History</h2>
                <ol class="space-y-3">
                    <?php foreach ($history as $h): ?>
                        <li class="flex gap-3 text-sm"><span class="w-2 h-2 rounded-full bg-wood mt-1.5"></span><div><p class="font-medium"><?= order_status_label($h['status']) ?></p><p class="text-xs text-muted"><?= esc(date('M j, g:i A', strtotime($h['created_at']))) ?><?= $h['note'] ? ' — ' . esc($h['note']) : '' ?></p></div></li>
                    <?php endforeach ?>
                </ol>
            </div>
        <?php endif ?>
    </div>

    <div class="space-y-6">
        <div class="bg-surface rounded-lg border border-line p-5">
            <h2 class="font-medium mb-3">Update status</h2>
            <form method="post" action="<?= base_url('admin/orders/status/' . $order['id']) ?>" class="space-y-3">
                <?= csrf_field() ?>
                <select name="status" class="field-input">
                    <?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= order_status_label($s) ?></option><?php endforeach ?>
                </select>
                <input name="note" class="field-input" placeholder="Note (optional)">
                <button class="btn-primary btn-block btn-sm">Update</button>
            </form>
        </div>
        <div class="bg-surface rounded-lg border border-line p-5">
            <h2 class="font-medium mb-3">Customer</h2>
            <p class="text-sm"><?= esc($order['email']) ?></p>
            <p class="text-sm text-muted"><?= esc($order['phone']) ?></p>
            <p class="text-sm text-muted mt-1">Payment: <?= esc($order['payment_method']) ?> · <?= esc($order['payment_status']) ?></p>
        </div>
        <?php if ($ship): ?>
            <div class="bg-surface rounded-lg border border-line p-5">
                <h2 class="font-medium mb-3">Shipping</h2>
                <address class="not-italic text-sm text-muted leading-relaxed"><span class="text-ink"><?= esc($ship['full_name'] ?? '') ?></span><br><?= esc($ship['line1'] ?? '') ?><br><?= esc($ship['city'] ?? '') ?>, <?= esc($ship['state'] ?? '') ?> <?= esc($ship['pincode'] ?? '') ?></address>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
