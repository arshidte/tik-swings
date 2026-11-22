<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-10 sm:py-14">
    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-h1 font-display">Track your order</h1>
            <p class="text-muted mt-2">Enter the order number from your SMS or order confirmation page.</p>
        </div>

        <form method="get" action="<?= base_url('track-order') ?>" class="card p-5 flex flex-col sm:flex-row gap-3 mb-8">
            <label for="order" class="sr-only">Order number</label>
            <input id="order" name="order" class="field-input flex-1 uppercase" placeholder="e.g. SW261103A1B2C3"
                   value="<?= esc($number) ?>" required autocomplete="off">
            <button type="submit" class="btn-primary">Track</button>
        </form>

        <?php if ($error): ?>
            <div class="mb-6 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc($error) ?></div>
        <?php endif ?>

        <?php if ($order): ?>
            <div class="card p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm text-muted">Order</p>
                        <p class="font-display text-xl"><?= esc($order['order_number']) ?></p>
                        <p class="text-xs text-muted mt-1">Placed on <?= esc(date('F j, Y', strtotime($order['created_at']))) ?></p>
                    </div>
                    <span class="badge <?= order_status_class($order['status']) ?> text-sm px-3 py-1.5"><?= order_status_label($order['status']) ?></span>
                </div>
            </div>

            <?php if ($shipment): ?>
                <div class="card p-6 mb-6">
                    <h2 class="font-display text-lg mb-3">Shipment</h2>
                    <p class="text-sm"><?= esc(courier_label($shipment['carrier'])) ?></p>
                    <p class="text-sm text-muted mb-4">Tracking #: <?= esc($shipment['tracking_number']) ?></p>
                    <a href="<?= esc(courier_tracking_url($shipment['carrier']), 'attr') ?>" target="_blank" rel="noopener" class="btn-outline btn-block">Track with courier</a>
                </div>
            <?php endif ?>

            <?php if (! empty($history)): ?>
                <div class="card p-6 mb-6">
                    <h2 class="font-display text-lg mb-4">Progress</h2>
                    <ol class="space-y-4">
                        <?php foreach ($history as $h): ?>
                            <li class="flex gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-wood mt-1.5 shrink-0"></span>
                                <div>
                                    <p class="font-medium text-sm"><?= order_status_label($h['status']) ?></p>
                                    <p class="text-xs text-muted"><?= esc(date('M j, Y · g:i A', strtotime($h['created_at']))) ?></p>
                                </div>
                            </li>
                        <?php endforeach ?>
                    </ol>
                </div>
            <?php endif ?>

            <div class="card p-6 mb-6">
                <h2 class="font-display text-lg mb-4">Items</h2>
                <div class="divide-y divide-line">
                    <?php foreach ($items as $it): ?>
                        <div class="flex gap-4 py-3">
                            <img src="<?= esc(product_image($it['image']), 'attr') ?>" alt="" width="56" height="56" class="w-14 h-14 rounded-md object-cover bg-sand">
                            <div class="flex-1">
                                <p class="font-medium text-sm"><?= esc($it['product_name']) ?></p>
                                <?php if ($it['variant_name']): ?><p class="text-xs text-muted"><?= esc($it['variant_name']) ?></p><?php endif ?>
                                <p class="text-xs text-muted">Qty <?= (int) $it['quantity'] ?></p>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
                <a href="<?= base_url('login') ?>" class="btn-outline flex-1 text-center">Sign in for full details</a>
                <a href="<?= esc(whatsapp_link('Hi, I have a question about order ' . $order['order_number']), 'attr') ?>" target="_blank" rel="noopener" class="btn-wood flex-1 text-center">Need help? WhatsApp us</a>
            </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>

