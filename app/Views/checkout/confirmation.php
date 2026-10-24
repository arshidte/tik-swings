<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $ship = $order['shipping_address'] ? json_decode($order['shipping_address'], true) : null; ?>
<div class="container-page py-14 sm:py-20 max-w-2xl">
    <div class="text-center">
        <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success/10 text-success mb-5 animate-fade-up">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
        </span>
        <h1 class="text-h1 font-display">Thank you<?= session('user_name') ? ', ' . esc(explode(' ', session('user_name'))[0]) : '' ?>!</h1>
        <p class="mt-3 text-muted">Your order is confirmed. We've emailed the details to <span class="text-ink"><?= esc($order['email']) ?></span>.</p>
        <p class="mt-1 text-muted">Order number: <span class="font-medium text-ink"><?= esc($order['order_number']) ?></span></p>
    </div>

    <div class="card p-6 mt-10">
        <h2 class="font-display text-lg mb-4">Order Summary</h2>
        <div class="divide-y divide-line">
            <?php foreach ($items as $it): ?>
                <div class="flex gap-3 py-3">
                    <img src="<?= esc(product_image($it['image']), 'attr') ?>" alt="" width="56" height="56" class="w-14 h-14 rounded-md object-cover bg-sand">
                    <div class="flex-1">
                        <p class="text-sm font-medium"><?= esc($it['product_name']) ?></p>
                        <?php if ($it['variant_name']): ?><p class="text-xs text-muted"><?= esc($it['variant_name']) ?></p><?php endif ?>
                        <p class="text-xs text-muted">Qty <?= (int) $it['quantity'] ?></p>
                    </div>
                    <span class="text-sm tabular-nums"><?= price($it['line_total']) ?></span>
                </div>
            <?php endforeach ?>
        </div>
        <dl class="space-y-2 text-sm border-t border-line pt-4 mt-2">
            <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums"><?= price($order['subtotal']) ?></dd></div>
            <?php if ($order['discount'] > 0): ?><div class="flex justify-between"><dt class="text-success">Discount</dt><dd class="tabular-nums text-success">−<?= price($order['discount']) ?></dd></div><?php endif ?>
            <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd class="tabular-nums"><?= $order['shipping'] > 0 ? price($order['shipping']) : 'Free' ?></dd></div>
            <div class="flex justify-between font-medium text-base border-t border-line pt-3 mt-1"><dt>Total</dt><dd class="tabular-nums"><?= price($order['total']) ?></dd></div>
        </dl>
        <p class="text-sm text-muted mt-4">Payment: <?= esc(ucwords(str_replace('_', ' ', $order['payment_method']))) ?> · <?= esc(ucfirst($order['payment_status'])) ?></p>
        <?php if ($ship): ?>
            <p class="text-sm text-muted mt-2">Delivering to: <?= esc($ship['line1']) ?>, <?= esc($ship['city']) ?>, <?= esc($ship['state']) ?> <?= esc($ship['pincode']) ?></p>
        <?php endif ?>
    </div>

    <?php if (($accountState ?? 'none') === 'create'): ?>
        <?php $errors = session('errors') ?? []; ?>
        <div class="card p-6 mt-6 border border-wood/20 bg-sand/30">
            <h2 class="font-display text-lg mb-1">Save your details for next time</h2>
            <p class="text-sm text-muted mb-4">Set a password to track this order and check out faster later.</p>
            <?php if (session('error')): ?>
                <div role="alert" class="mb-4 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <form method="post" action="<?= base_url('checkout/claim-account') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="order_number" value="<?= esc($order['order_number'], 'attr') ?>">
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label" for="acc_email">Email</label>
                        <input id="acc_email" type="email" class="field-input bg-line/30" value="<?= esc($order['email'], 'attr') ?>" readonly>
                    </div>
                    <div>
                        <label class="field-label" for="acc_phone">Phone</label>
                        <input id="acc_phone" type="tel" class="field-input bg-line/30" value="<?= esc($order['phone'], 'attr') ?>" readonly>
                    </div>
                    <div>
                        <label class="field-label" for="acc_password">Password</label>
                        <input id="acc_password" name="password" type="password" class="field-input" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['password'])): ?><p class="field-error"><?= esc($errors['password']) ?></p><?php endif ?>
                    </div>
                    <div>
                        <label class="field-label" for="acc_password_confirm">Confirm password</label>
                        <input id="acc_password_confirm" name="password_confirm" type="password" class="field-input" required minlength="8" autocomplete="new-password">
                        <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= esc($errors['password_confirm']) ?></p><?php endif ?>
                    </div>
                </div>
                <button type="submit" class="btn-primary btn-block">Create my account</button>
            </form>
        </div>
    <?php elseif (($accountState ?? 'none') === 'existing'): ?>
        <div class="card p-6 mt-6 border border-wood/20 bg-sand/30 text-center">
            <h2 class="font-display text-lg mb-1">You already have an account</h2>
            <p class="text-sm text-muted mb-4">Sign in with <span class="text-ink"><?= esc($order['email']) ?></span> to track this order.</p>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="<?= base_url('login') ?>" class="btn-primary">Sign in</a>
                <a href="<?= base_url('forgot-password?email=' . rawurlencode($order['email'])) ?>" class="btn-outline">Forgot your password?</a>
            </div>
        </div>
    <?php endif ?>

    <div class="flex flex-wrap gap-3 justify-center mt-8">
        <?php if (is_logged_in()): ?>
            <a href="<?= base_url('account/orders/' . $order['order_number']) ?>" class="btn-primary">Track your order</a>
        <?php endif ?>
        <a href="<?= base_url('shop') ?>" class="btn-outline">Continue shopping</a>
    </div>
</div>
<?= $this->endSection() ?>
