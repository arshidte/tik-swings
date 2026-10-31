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
            <div>
            <div class="divide-y divide-line border-y border-line" data-cart-lines>
                <?php foreach ($snapshot['items'] as $it): $isAddon = ! empty($it['addon_of']); ?>
                    <div class="flex gap-4 <?= $isAddon ? 'pt-0 pb-5 pl-8 sm:pl-12 !border-t-0' : 'py-5' ?>" data-cart-row data-item-id="<?= esc($it['id'], 'attr') ?>" data-addon-of="<?= esc((string) ($it['addon_of'] ?? ''), 'attr') ?>">
                        <a href="<?= esc($it['url'], 'attr') ?>" class="shrink-0"><img src="<?= esc($it['image'], 'attr') ?>" alt="" width="112" height="112" class="<?= $isAddon ? 'w-16 h-16 sm:w-20 sm:h-20' : 'w-24 h-24 sm:w-28 sm:h-28' ?> rounded-md object-cover bg-sand"></a>
                        <div class="flex-1 min-w-0">
                            <?php if ($isAddon): ?><p class="text-[11px] uppercase tracking-wide text-subtle leading-none mb-1">Add-on</p><?php endif ?>
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

            <!-- "Pairs well with" — mapped add-ons of cart items not yet in the cart -->
            <?php if (! empty($snapshot['suggestions'])): ?>
                <section class="mt-8 rounded-lg border border-line p-5" data-cart-page-suggestions aria-labelledby="pairs-heading">
                    <h2 id="pairs-heading" class="font-display text-lg">Pairs well with your cart</h2>
                    <p class="text-sm text-muted mt-0.5 mb-4">Essentials that go with what you've picked.</p>
                    <div class="grid sm:grid-cols-2 gap-4" data-suggestion-list>
                        <?php foreach ($snapshot['suggestions'] as $s): ?>
                            <div class="flex gap-3 items-start" data-suggestion data-product-id="<?= (int) $s['product_id'] ?>"
                                 data-parent-id="<?= (int) $s['parent_id'] ?>" data-parent-qty="<?= (int) $s['parent_qty'] ?>" data-variant-id="<?= (int) $s['variant_id'] ?: '' ?>">
                                <a href="<?= esc($s['url'], 'attr') ?>" class="shrink-0"><img src="<?= esc($s['image'], 'attr') ?>" alt="" width="64" height="64" loading="lazy" class="w-16 h-16 rounded-md object-cover bg-sand"></a>
                                <div class="flex-1 min-w-0">
                                    <a href="<?= esc($s['url'], 'attr') ?>" class="text-sm font-medium leading-snug line-clamp-2 hover:text-wood"><?= esc($s['name']) ?></a>
                                    <p class="text-sm tabular-nums mt-0.5" data-suggestion-price><?= price($s['price']) ?></p>
                                    <?php if (count($s['variants']) > 1): ?>
                                        <select class="mt-1.5 w-full rounded-md border border-line bg-bg px-2 py-1.5 text-xs focus:border-ink focus:outline-none" data-suggestion-variant aria-label="Choose an option for <?= esc($s['name'], 'attr') ?>">
                                            <?php foreach ($s['variants'] as $v): ?>
                                                <option value="<?= (int) $v['id'] ?>" data-price="<?= esc((string) $v['price'], 'attr') ?>" <?= $v['id'] === $s['variant_id'] ? 'selected' : '' ?> <?= $v['in_stock'] ? '' : 'disabled' ?>>
                                                    <?= esc($v['label']) ?> — <?= price($v['price']) ?><?= $v['in_stock'] ? '' : ' (sold out)' ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    <?php endif ?>
                                </div>
                                <button type="button" class="shrink-0 self-center min-h-[36px] px-3 rounded-md border border-ink text-xs font-medium hover:bg-ink hover:text-bg transition disabled:opacity-50" data-suggestion-add>+ Add</button>
                            </div>
                        <?php endforeach ?>
                    </div>
                </section>
            <?php endif ?>
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
