<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-10 sm:py-14" data-cart-page>
    <h1 class="text-h1 font-display mb-8">Your Cart</h1>

    <?php if (empty($snapshot['items'])): ?>
        <div class="text-center py-20 border border-dashed border-line rounded-lg max-w-lg mx-auto">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-sand text-wood mb-5">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0"/></svg>
            </span>
            <p class="font-display text-xl">Your favourite seat is still waiting.</p>
            <p class="text-muted mt-2 mb-6">Add a swing you love and it will show up here.</p>
            <a href="<?= base_url('shop') ?>" class="btn-wood">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="lg:grid lg:grid-cols-[1fr_360px] lg:gap-10 items-start">
            <!-- Items -->
            <div class="divide-y divide-line border-y border-line" data-cart-lines>
                <?php foreach ($snapshot['items'] as $it): ?>
                    <div class="flex gap-4 py-5" data-cart-row data-item-id="<?= esc($it['id'], 'attr') ?>">
                        <a href="<?= esc($it['url'], 'attr') ?>" class="shrink-0"><img src="<?= esc($it['image'], 'attr') ?>" alt="" width="112" height="112" class="w-24 h-24 sm:w-28 sm:h-28 rounded-md object-cover bg-sand"></a>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between gap-3">
                                <a href="<?= esc($it['url'], 'attr') ?>" class="font-display text-lg leading-snug hover:text-wood"><?= esc($it['name']) ?></a>
                                <button type="button" class="text-subtle hover:text-danger p-1 shrink-0" data-cart-remove aria-label="Remove item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                                </button>
                            </div>
                            <?php if (! empty($it['variant'])): ?><p class="text-sm text-muted mt-0.5"><?= esc($it['variant']) ?></p><?php endif ?>
                            <p class="text-xs text-subtle mt-0.5">SKU: <?= esc($it['sku']) ?></p>
                            <div class="flex items-center justify-between mt-3">
                                <div class="inline-flex items-center border border-line rounded-md">
                                    <button type="button" class="w-9 h-9 flex items-center justify-center text-muted hover:text-ink" data-qty-dec aria-label="Decrease quantity">–</button>
                                    <span class="w-9 text-center text-sm tabular-nums" data-qty-val><?= (int) $it['quantity'] ?></span>
                                    <button type="button" class="w-9 h-9 flex items-center justify-center text-muted hover:text-ink" data-qty-inc aria-label="Increase quantity">+</button>
                                </div>
                                <span class="font-medium tabular-nums" data-line-total><?= price($it['line_total']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>

            <!-- Summary -->
            <aside class="mt-8 lg:mt-0 lg:sticky lg:top-[calc(var(--header-height)+1.5rem)]">
                <div class="card p-6">
                    <h2 class="font-display text-xl mb-4">Order Summary</h2>

                    <!-- Coupon (§27) -->
                    <form class="flex gap-2 mb-5" data-coupon-form>
                        <input type="text" name="code" placeholder="Coupon code" class="field-input py-2 min-h-[44px] text-sm uppercase" value="<?= esc($snapshot['coupon']['code'] ?? '') ?>">
                        <button type="submit" class="btn-outline btn-sm px-4">Apply</button>
                    </form>
                    <p class="text-xs text-muted -mt-3 mb-5">Try <button type="button" class="text-wood font-medium" data-coupon-suggest>WELCOME10</button></p>

                    <dl class="space-y-2.5 text-sm border-t border-line pt-4">
                        <div class="flex justify-between"><dt class="text-muted">Subtotal</dt><dd class="tabular-nums" data-sum-subtotal><?= price($snapshot['subtotal']) ?></dd></div>
                        <div class="flex justify-between <?= $snapshot['discount'] > 0 ? '' : 'hidden' ?>" data-sum-discount-row><dt class="text-success">Discount<?= ! empty($snapshot['coupon']) ? ' (' . esc($snapshot['coupon']['code']) . ')' : '' ?></dt><dd class="tabular-nums text-success" data-sum-discount>−<?= price($snapshot['discount']) ?></dd></div>
                        <div class="flex justify-between"><dt class="text-muted">Shipping</dt><dd class="tabular-nums" data-sum-shipping><?= $snapshot['shipping'] > 0 ? price($snapshot['shipping']) : 'Free' ?></dd></div>
                        <div class="flex justify-between text-base font-medium border-t border-line pt-3 mt-1"><dt>Total</dt><dd class="tabular-nums" data-sum-total><?= price($snapshot['total']) ?></dd></div>
                    </dl>

                    <a href="<?= base_url('checkout') ?>" class="btn-primary btn-block mt-6">Proceed to Checkout</a>
                    <a href="<?= base_url('shop') ?>" class="btn-ghost btn-block mt-2">Continue Shopping</a>
                    <p class="text-xs text-muted text-center mt-4 flex items-center justify-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="2"/><path stroke-linecap="round" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        Secure checkout · Free shipping over <?= price(setting('free_shipping_over', 25000)) ?>
                    </p>
                </div>
            </aside>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script type="module" src="<?= base_url('assets/js/cart_page.js') ?>"></script>
<?= $this->endSection() ?>
