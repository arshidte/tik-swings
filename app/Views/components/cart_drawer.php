<div id="cart-drawer" class="fixed inset-0 z-50 hidden" data-cart role="dialog" aria-modal="true" aria-label="Shopping cart">
    <div class="absolute inset-0 bg-ink/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" data-cart-overlay></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-md bg-bg shadow-lift flex flex-col translate-x-full transition-transform duration-300 ease-out-soft" data-cart-panel>
        <div class="flex items-center justify-between h-header px-5 border-b border-line shrink-0">
            <h2 class="font-display text-xl">Your Cart <span data-cart-title-count class="text-subtle text-base"></span></h2>
            <button type="button" class="p-2 -mr-2 hover:text-wood" data-cart-close aria-label="Close cart">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <!-- Items (populated by cart.js) -->
        <div class="flex-1 overflow-y-auto" data-cart-body>
            <!-- Shown right after an add-to-cart -->
            <div class="hidden mx-5 mt-5 items-center gap-2 rounded-md bg-success/10 text-success px-3 py-2.5 text-sm font-medium" data-cart-added role="status">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                <span data-cart-added-text>Added to your cart</span>
            </div>

            <div class="p-5 space-y-4" data-cart-items></div>

            <!-- "Don't forget" — mapped add-ons of cart items that aren't in the cart yet -->
            <div class="hidden mx-5 mb-5 rounded-lg border border-line p-4" data-cart-suggestions aria-labelledby="cart-suggestions-heading">
                <p id="cart-suggestions-heading" class="text-sm font-medium">Don't forget</p>
                <p class="text-xs text-muted mt-0.5 mb-3">Pairs well with what's in your cart.</p>
                <div class="space-y-3" data-cart-suggestion-list></div>
            </div>

            <!-- Empty state (§64) -->
            <div class="hidden flex-col items-center justify-center text-center px-8 py-16" data-cart-empty>
                <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-sand text-wood mb-5">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7Zm3 0a3 3 0 0 1 6 0"/></svg>
                </span>
                <p class="font-display text-xl">Your favourite seat is still waiting.</p>
                <p class="text-muted text-sm mt-2 mb-6">Add a swing you love and it will show up here.</p>
                <button type="button" class="btn-wood" data-cart-close>Continue Shopping</button>
            </div>
        </div>

        <!-- Footer / totals -->
        <div class="border-t border-line p-5 pb-safe shrink-0 hidden" data-cart-footer>
            <div class="flex items-center justify-between mb-1 text-sm text-muted">
                <span>Subtotal</span>
                <span data-cart-subtotal class="tabular-nums text-ink font-medium">—</span>
            </div>
            <p class="text-xs text-muted mb-4">Shipping &amp; taxes calculated at checkout.</p>
            <div class="grid grid-cols-1 gap-2">
                <a href="<?= base_url('checkout') ?>" class="btn-primary btn-block">Proceed to Checkout</a>
                <a href="<?= base_url('cart') ?>" class="btn-outline btn-block">View Cart</a>
            </div>
        </div>
    </div>
</div>
