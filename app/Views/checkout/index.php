<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php
$default = null;
foreach ($addresses as $a) { if ($a['is_default']) { $default = $a; break; } }
$default = $default ?: ($addresses[0] ?? null);
$errors  = session('errors') ?? [];
?>
<div class="container-page py-8 sm:py-12">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-h1 font-display">Checkout</h1>
        <a href="<?= base_url('cart') ?>" class="text-sm text-muted hover:text-wood">← Back to cart</a>
    </div>

    <?php if (session('error')): ?>
        <div role="alert" class="mb-6 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc(session('error')) ?></div>
    <?php endif ?>

    <form method="post" action="<?= base_url('checkout/place') ?>" class="lg:grid lg:grid-cols-[1fr_380px] lg:gap-10 items-start">
        <?= csrf_field() ?>
        <div class="space-y-8">
            <!-- Address -->
            <section class="card p-6">
                <h2 class="font-display text-xl mb-1">Shipping Address</h2>
                <p class="text-sm text-muted mb-5">Where should we deliver your swing?</p>

                <?php if (! empty($addresses)): ?>
                    <div class="grid sm:grid-cols-2 gap-3 mb-6" data-saved-addresses>
                        <?php foreach ($addresses as $a): ?>
                            <label class="border border-line rounded-md p-4 cursor-pointer hover:border-ink transition has-[:checked]:border-ink has-[:checked]:bg-sand/40 block">
                                <input type="radio" name="saved_address" class="sr-only" value="<?= (int) $a['id'] ?>"
                                       data-address='<?= esc(json_encode($a), 'attr') ?>' <?= ($default && $a['id'] === $default['id']) ? 'checked' : '' ?>>
                                <span class="font-medium text-sm block"><?= esc($a['full_name']) ?><?= $a['is_default'] ? ' · Default' : '' ?></span>
                                <span class="text-sm text-muted block mt-1"><?= esc($a['line1']) ?>, <?= esc($a['city']) ?>, <?= esc($a['state']) ?> <?= esc($a['pincode']) ?></span>
                            </label>
                        <?php endforeach ?>
                        <label class="border border-dashed border-line rounded-md p-4 cursor-pointer hover:border-ink transition flex items-center gap-2 text-sm font-medium has-[:checked]:border-ink">
                            <input type="radio" name="saved_address" class="sr-only" value="new" data-address-new>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                            Use a new address
                        </label>
                    </div>
                <?php endif ?>

                <div class="grid sm:grid-cols-2 gap-4" data-address-fields>
                    <div><label class="field-label" for="full_name">Full name</label><input id="full_name" name="full_name" class="field-input" required value="<?= esc(old('full_name', $default['full_name'] ?? $user['first_name'] . ' ' . $user['last_name'])) ?>"><?php if (isset($errors['full_name'])): ?><p class="field-error"><?= esc($errors['full_name']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="phone">Phone</label><input id="phone" name="phone" type="tel" inputmode="tel" class="field-input" required value="<?= esc(old('phone', $default['phone'] ?? $user['phone'])) ?>"><?php if (isset($errors['phone'])): ?><p class="field-error"><?= esc($errors['phone']) ?></p><?php endif ?></div>
                    <div class="sm:col-span-2"><label class="field-label" for="email">Email</label><input id="email" name="email" type="email" class="field-input" required value="<?= esc(old('email', $user['email'])) ?>"><?php if (isset($errors['email'])): ?><p class="field-error"><?= esc($errors['email']) ?></p><?php endif ?></div>
                    <div class="sm:col-span-2"><label class="field-label" for="line1">Address</label><input id="line1" name="line1" class="field-input" required value="<?= esc(old('line1', $default['line1'] ?? '')) ?>"><?php if (isset($errors['line1'])): ?><p class="field-error"><?= esc($errors['line1']) ?></p><?php endif ?></div>
                    <div class="sm:col-span-2"><label class="field-label" for="line2">Apartment, landmark (optional)</label><input id="line2" name="line2" class="field-input" value="<?= esc(old('line2', $default['line2'] ?? '')) ?>"></div>
                    <div><label class="field-label" for="city">City</label><input id="city" name="city" class="field-input" required value="<?= esc(old('city', $default['city'] ?? '')) ?>"><?php if (isset($errors['city'])): ?><p class="field-error"><?= esc($errors['city']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="state">State</label><input id="state" name="state" class="field-input" required value="<?= esc(old('state', $default['state'] ?? '')) ?>"><?php if (isset($errors['state'])): ?><p class="field-error"><?= esc($errors['state']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="pincode">Pincode</label><input id="pincode" name="pincode" inputmode="numeric" class="field-input" required value="<?= esc(old('pincode', $default['pincode'] ?? '')) ?>"><?php if (isset($errors['pincode'])): ?><p class="field-error"><?= esc($errors['pincode']) ?></p><?php endif ?></div>
                    <label class="sm:col-span-2 flex items-center gap-2.5 text-sm"><input type="checkbox" name="save_address" value="1" class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Save this address to my account</label>
                </div>
            </section>

            <!-- Payment (§30/§31) -->
            <section class="card p-6">
                <h2 class="font-display text-xl mb-5">Payment</h2>
                <div class="space-y-3">
                    <?php foreach ($methods as $key => $m): ?>
                        <label class="flex items-start gap-3 border border-line rounded-md p-4 cursor-pointer hover:border-ink transition has-[:checked]:border-ink has-[:checked]:bg-sand/40">
                            <input type="radio" name="payment_method" value="<?= esc($key, 'attr') ?>" class="mt-1 w-4 h-4 text-wood focus:ring-wood/30" <?= array_key_first($methods) === $key ? 'checked' : '' ?>>
                            <span>
                                <span class="font-medium block"><?= esc($m->label()) ?></span>
                                <span class="text-sm text-muted"><?= $m->isOnline() ? 'Secure online payment' : 'Pay when your swing is delivered' ?></span>
                            </span>
                        </label>
                    <?php endforeach ?>
                </div>
                <div class="mt-5">
                    <label class="field-label" for="notes">Order notes (optional)</label>
                    <textarea id="notes" name="notes" rows="2" class="field-input" placeholder="Delivery instructions, preferred timing…"><?= esc(old('notes')) ?></textarea>
                </div>
            </section>
        </div>

        <!-- Summary -->
        <aside class="mt-8 lg:mt-0 lg:sticky lg:top-[calc(var(--header-height)+1.5rem)]">
            <div class="card p-6">
                <h2 class="font-display text-xl mb-4">Order Summary</h2>
                <div class="divide-y divide-line max-h-64 overflow-y-auto -mx-1 px-1">
                    <?php foreach ($snapshot['items'] as $it): ?>
                        <div class="flex gap-3 py-3">
                            <div class="relative shrink-0">
                                <img src="<?= esc($it['image'], 'attr') ?>" alt="" width="56" height="56" class="w-14 h-14 rounded-md object-cover bg-sand">
                                <span class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-ink text-bg text-[11px] flex items-center justify-center tabular-nums"><?= (int) $it['quantity'] ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium line-clamp-1"><?= esc($it['name']) ?></p>
                                <?php if ($it['variant']): ?><p class="text-xs text-muted"><?= esc($it['variant']) ?></p><?php endif ?>
                            </div>
                            <span class="text-sm tabular-nums"><?= price($it['line_total']) ?></span>
                        </div>
                    <?php endforeach ?>
                </div>
                <dl class="space-y-2 text-sm border-t border-line pt-4 mt-2">
                    <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums"><?= price($snapshot['subtotal']) ?></dd></div>
                    <?php if ($snapshot['discount'] > 0): ?><div class="flex justify-between"><dt class="text-success">Discount<?= ! empty($snapshot['coupon']) ? ' (' . esc($snapshot['coupon']['code']) . ')' : '' ?></dt><dd class="tabular-nums text-success">−<?= price($snapshot['discount']) ?></dd></div><?php endif ?>
                    <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd class="tabular-nums"><?= $snapshot['shipping'] > 0 ? price($snapshot['shipping']) : 'Free' ?></dd></div>
                    <div class="flex justify-between font-medium text-base border-t border-line pt-3 mt-1"><dt>Total</dt><dd class="tabular-nums"><?= price($snapshot['total']) ?></dd></div>
                </dl>
                <button type="submit" class="btn-primary btn-block mt-6" data-place-order>Place Order</button>
                <p class="text-xs text-muted text-center mt-3 flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    Your details are encrypted and secure.
                </p>
            </div>
        </aside>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Fill address fields from a saved-address selection (§30 saved addresses).
document.querySelectorAll('[data-address]').forEach((radio) => {
    radio.addEventListener('change', () => {
        const a = JSON.parse(radio.dataset.address);
        const set = (n, v) => { const el = document.querySelector('[name="' + n + '"]'); if (el) el.value = v || ''; };
        set('full_name', a.full_name); set('phone', a.phone); set('line1', a.line1);
        set('line2', a.line2); set('city', a.city); set('state', a.state); set('pincode', a.pincode);
    });
});
// Prevent double-submit
document.querySelector('form[action$="checkout/place"]')?.addEventListener('submit', (e) => {
    const btn = e.target.querySelector('[data-place-order]');
    if (btn) { btn.disabled = true; btn.textContent = 'Placing order…'; }
});
</script>
<?= $this->endSection() ?>
