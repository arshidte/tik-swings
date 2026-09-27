<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-20 max-w-lg text-center">
    <h1 class="text-h1 font-display">Complete your payment</h1>
    <p class="mt-3 text-muted">Order <?= esc($order['order_number']) ?> · <?= price($order['total']) ?></p>
    <div class="card p-8 mt-8">
        <p class="text-muted mb-6">You'll be redirected to our secure payment partner to finish paying.</p>
        <button type="button" class="btn-primary btn-block" data-pay-now>Pay <?= price($order['total']) ?> securely</button>
        <p class="text-xs text-muted mt-4">Do not close or refresh this window.</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Razorpay checkout handoff (loads only when an online method is active). -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const intent = <?= json_encode($intent) ?>;
document.querySelector('[data-pay-now]').addEventListener('click', () => {
    const rzp = new Razorpay({
        key: intent.key,
        amount: intent.amount,
        currency: intent.currency,
        name: <?= json_encode(store_name()) ?>,
        description: 'Order ' + intent.receipt,
        order_id: intent.gateway_order_id || undefined,
        handler: async (response) => {
            const res = await fetch(window.SG.baseUrl + 'checkout/payment/verify', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ ...response, order_number: intent.receipt }),
            }).then((r) => r.json());
            if (res.success) window.location.href = res.data.redirect;
            else alert(res.message || 'Payment could not be verified.');
        },
    });
    rzp.open();
});
</script>
<?= $this->endSection() ?>
