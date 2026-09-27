<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$ship = $order['shipping_address'] ? json_decode($order['shipping_address'], true) : null;
?>
<div class="container-page py-10 sm:py-14">
    <a href="<?= base_url('account/orders') ?>" class="text-sm text-muted hover:text-wood">← Back to orders</a>
    <div class="flex flex-wrap items-center justify-between gap-4 mt-4 mb-8">
        <div>
            <h1 class="text-h1 font-display">Order <?= esc($order['order_number']) ?></h1>
            <p class="text-muted mt-1">Placed on <?= esc(date('F j, Y', strtotime($order['created_at']))) ?></p>
        </div>
        <span class="badge <?= order_status_class($order['status']) ?> text-sm px-3 py-1.5"><?= order_status_label($order['status']) ?></span>
    </div>

    <div class="lg:grid lg:grid-cols-[1fr_340px] lg:gap-10 items-start">
        <div class="space-y-8">
            <!-- Status timeline -->
            <?php if (! empty($history)): ?>
                <div class="card p-6">
                    <h2 class="font-display text-lg mb-4">Progress</h2>
                    <ol class="space-y-4">
                        <?php foreach ($history as $h): ?>
                            <li class="flex gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-wood mt-1.5 shrink-0"></span>
                                <div>
                                    <p class="font-medium text-sm"><?= order_status_label($h['status']) ?></p>
                                    <p class="text-xs text-muted"><?= esc(date('M j, Y · g:i A', strtotime($h['created_at']))) ?><?= $h['note'] ? ' — ' . esc($h['note']) : '' ?></p>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ol>
                </div>
            <?php endif ?>

            <!-- Items -->
            <div class="card p-6">
                <h2 class="font-display text-lg mb-4">Items</h2>
                <div class="divide-y divide-line">
                    <?php foreach ($items as $it): ?>
                        <div class="flex gap-4 py-4">
                            <img src="<?= esc(product_image($it['image']), 'attr') ?>" alt="" width="72" height="72" class="w-18 h-18 w-[72px] h-[72px] rounded-md object-cover bg-sand">
                            <div class="flex-1">
                                <p class="font-medium"><?= esc($it['product_name']) ?></p>
                                <?php if ($it['variant_name']): ?><p class="text-sm text-muted"><?= esc($it['variant_name']) ?></p><?php endif ?>
                                <p class="text-sm text-muted">Qty <?= (int) $it['quantity'] ?> × <?= price($it['unit_price']) ?></p>
                            </div>
                            <span class="font-medium tabular-nums"><?= price($it['line_total']) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <aside class="mt-8 lg:mt-0 space-y-6">
            <div class="card p-6">
                <h2 class="font-display text-lg mb-4">Summary</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums"><?= price($order['subtotal']) ?></dd></div>
                    <?php if ($order['discount'] > 0): ?><div class="flex justify-between"><dt class="text-success">Discount</dt><dd class="tabular-nums text-success">−<?= price($order['discount']) ?></dd></div><?php endif ?>
                    <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd class="tabular-nums"><?= $order['shipping'] > 0 ? price($order['shipping']) : 'Free' ?></dd></div>
                    <div class="flex justify-between font-medium border-t border-line pt-2 mt-1 text-base"><dt>Total</dt><dd class="tabular-nums"><?= price($order['total']) ?></dd></div>
                </dl>
                <p class="text-xs text-muted mt-4">Payment: <?= esc(ucfirst($order['payment_method'] ?? 'N/A')) ?> · <?= esc(ucfirst($order['payment_status'])) ?></p>
            </div>

            <?php if ($ship): ?>
                <div class="card p-6">
                    <h2 class="font-display text-lg mb-3">Shipping to</h2>
                    <address class="not-italic text-sm text-muted leading-relaxed">
                        <span class="text-ink font-medium"><?= esc($ship['full_name'] ?? '') ?></span><br>
                        <?= esc($ship['line1'] ?? '') ?><?php if (! empty($ship['line2'])): ?>, <?= esc($ship['line2']) ?><?php endif ?><br>
                        <?= esc($ship['city'] ?? '') ?>, <?= esc($ship['state'] ?? '') ?> <?= esc($ship['pincode'] ?? '') ?><br>
                        <?= esc($ship['phone'] ?? '') ?>
                    </address>
                </div>
            <?php endif ?>

            <a href="<?= esc(whatsapp_link('Hi, I have a question about order ' . $order['order_number']), 'attr') ?>" target="_blank" rel="noopener" class="btn-outline btn-block">Need help with this order?</a>
        </aside>
    </div>
</div>
<?= $this->endSection() ?>
