<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-20 max-w-lg text-center">
    <h1 class="text-h1 font-display">Complete your payment</h1>
    <p class="mt-3 text-muted">Order <?= esc($order['order_number']) ?> · <?= price($order['total']) ?></p>
    <div class="card p-8 mt-8">
        <p class="text-muted mb-6">You'll be redirected to our secure payment partner to finish paying.</p>
        <button type="button" class="btn-primary btn-block" data-pay-now>Pay <?= price($order['total']) ?> securely</button>
        <p class="text-sm text-muted mt-4" data-hold-note>We're holding your items until <?= esc(date('g:i A', $holdUntil)) ?>. Please pay before then.</p>
        <p class="text-xs text-muted mt-2">Do not close or refresh this window.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Razorpay checkout handoff (loads only when an online method is active). -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const intent = <?= json_encode($intent) ?>;
// Seconds left on the stock hold, from the server clock (not the browser's).
const holdDeadline = Date.now() + <?= max(0, $holdUntil - time()) ?> * 1000;
const payBtn = document.querySelector('[data-pay-now]');
function holdSecondsLeft() { return Math.floor((holdDeadline - Date.now()) / 1000); }
function expireHold() {
    payBtn.disabled = true;
    document.querySelector('[data-hold-note]').textContent =
        'This payment window has expired and your items were released. Please go back to the cart and place your order again.';
}
if (holdSecondsLeft() <= 0) expireHold(); else setTimeout(expireHold, holdSecondsLeft() * 1000);

payBtn.addEventListener('click', () => {
    if (holdSecondsLeft() <= 0) return expireHold();
    const rzp = new Razorpay({
        // Close the payment window when the hold runs out.
        timeout: Math.max(60, holdSecondsLeft()),
        key: intent.key,
        amount: intent.amount,
        currency: intent.currency,
        name: <?= json_encode(store_name()) ?>,
        description: 'Order ' + intent.receipt,
        order_id: intent.gateway_order_id || undefined,
        handler: async (response) => {
            try {
                const res = await fetch(window.SG.baseUrl + 'checkout/payment/verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ...response, order_number: intent.receipt }),
                }).then((r) => r.json());
                if (res.success) window.location.href = res.data.redirect;
                else alert(res.message || 'Payment could not be verified. If money was debited it will be refunded.');
            } catch (e) {
                alert('We could not confirm your payment. Please contact support with your order number.');
            }
        },
        modal: {
            // User closed the Razorpay window without paying.
            ondismiss: () => alert('Payment cancelled. Your order is saved — you can pay again from here.'),
        },
    });
    // Gateway reported the payment attempt failed (card declined, etc.).
    rzp.on('payment.failed', (resp) => {
        const err = (resp && resp.error) || {};
        alert('Payment failed: ' + (err.description || 'please try a different method.'));
    });
    rzp.open();
});
</script>
<?= $this->endSection() ?>
