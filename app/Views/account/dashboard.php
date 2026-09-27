<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-10 sm:py-14">
    <h1 class="text-h1 font-display mb-8">My Account</h1>
    <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-10 items-start">
        <aside class="mb-8 lg:mb-0"><?= view('account/_nav', ['active' => $active]) ?></aside>
        <div class="space-y-8">
            <?php if (session('success')): ?><div class="rounded-md bg-success/10 text-success px-4 py-3 text-sm"><?= esc(session('success')) ?></div><?php endif ?>

            <div>
                <p class="text-muted">Welcome back,</p>
                <p class="font-display text-2xl"><?= esc($user['first_name'] . ' ' . $user['last_name']) ?></p>
            </div>

            <div class="grid sm:grid-cols-3 gap-4">
                <a href="<?= base_url('account/orders') ?>" class="card p-5 hover:shadow-card transition-shadow">
                    <p class="text-3xl font-display tabular-nums"><?= (int) $orderCount ?></p>
                    <p class="text-muted text-sm mt-1">Orders</p>
                </a>
                <a href="<?= base_url('account/addresses') ?>" class="card p-5 hover:shadow-card transition-shadow">
                    <p class="text-3xl font-display tabular-nums"><?= (int) $addressCount ?></p>
                    <p class="text-muted text-sm mt-1">Saved addresses</p>
                </a>
                <a href="<?= base_url('wishlist') ?>" class="card p-5 hover:shadow-card transition-shadow">
                    <p class="text-3xl font-display tabular-nums"><?= count(wishlist_ids()) ?></p>
                    <p class="text-muted text-sm mt-1">Wishlist items</p>
                </a>
            </div>

            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display text-xl">Recent Orders</h2>
                    <a href="<?= base_url('account/orders') ?>" class="text-sm text-wood hover:text-wood-dark">View all</a>
                </div>
                <?php if (! empty($recentOrders)): ?>
                    <div class="divide-y divide-line border-y border-line">
                        <?php foreach ($recentOrders as $o): ?>
                            <a href="<?= base_url('account/orders/' . $o['order_number']) ?>" class="flex items-center justify-between py-4 hover:bg-sand/40 -mx-2 px-2 rounded">
                                <div>
                                    <p class="font-medium"><?= esc($o['order_number']) ?></p>
                                    <p class="text-sm text-muted"><?= esc(date('M j, Y', strtotime($o['created_at']))) ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="font-medium tabular-nums"><?= price($o['total']) ?></p>
                                    <span class="badge <?= order_status_class($o['status']) ?> mt-1"><?= order_status_label($o['status']) ?></span>
                                </div>
                            </a>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 border border-dashed border-line rounded-lg">
                        <p class="text-muted">You haven't placed an order yet.</p>
                        <a href="<?= base_url('shop') ?>" class="btn-wood mt-4">Start shopping</a>
                    </div>
                <?php endif ?>
            </div>

            <div>
                <h2 class="font-display text-xl mb-4">Profile</h2>
                <form method="post" action="<?= base_url('account/profile') ?>" class="card p-6 grid sm:grid-cols-2 gap-4">
                    <?= csrf_field() ?>
                    <div><label class="field-label" for="fn">First name</label><input id="fn" name="first_name" class="field-input" value="<?= esc($user['first_name']) ?>"></div>
                    <div><label class="field-label" for="ln">Last name</label><input id="ln" name="last_name" class="field-input" value="<?= esc($user['last_name']) ?>"></div>
                    <div><label class="field-label" for="ph">Phone</label><input id="ph" name="phone" class="field-input" value="<?= esc($user['phone']) ?>"></div>
                    <div><label class="field-label" for="em">Email</label><input id="em" class="field-input bg-sand/50" value="<?= esc($user['email']) ?>" readonly></div>
                    <div class="sm:col-span-2"><button class="btn-primary">Save changes</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
