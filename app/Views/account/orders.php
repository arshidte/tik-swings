<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-10 sm:py-14">
    <h1 class="text-h1 font-display mb-8">My Orders</h1>
    <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-10 items-start">
        <aside class="mb-8 lg:mb-0"><?= view('account/_nav', ['active' => $active]) ?></aside>
        <div>
            <?php if (! empty($orders)): ?>
                <div class="space-y-4">
                    <?php foreach ($orders as $o): ?>
                        <a href="<?= base_url('account/orders/' . $o['order_number']) ?>" class="card p-5 flex flex-wrap items-center justify-between gap-4 hover:shadow-card transition-shadow">
                            <div>
                                <p class="font-medium"><?= esc($o['order_number']) ?></p>
                                <p class="text-sm text-muted"><?= esc(date('F j, Y', strtotime($o['created_at']))) ?></p>
                            </div>
                            <div class="flex items-center gap-6">
                                <span class="badge <?= order_status_class($o['status']) ?>"><?= order_status_label($o['status']) ?></span>
                                <span class="font-medium tabular-nums"><?= price($o['total']) ?></span>
                                <svg class="w-5 h-5 text-subtle" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="m10 6 6 6-6 6"/></svg>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php else: ?>
                <div class="text-center py-16 border border-dashed border-line rounded-lg">
                    <p class="font-display text-xl">No orders yet.</p>
                    <p class="text-muted mt-2 mb-6">When you place an order, it will appear here.</p>
                    <a href="<?= base_url('shop') ?>" class="btn-wood">Browse Swings</a>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
