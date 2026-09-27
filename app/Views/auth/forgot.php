<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-16 sm:py-24">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-h1 font-display">Reset your password</h1>
            <p class="mt-2 text-muted">We'll email you a link to set a new one.</p>
        </div>
        <div class="card p-6 sm:p-8">
            <?php if (session('success')): ?>
                <div class="mb-5 rounded-md bg-success/10 text-success px-4 py-3 text-sm"><?= esc(session('success')) ?></div>
            <?php endif ?>
            <form method="post" action="<?= base_url('forgot-password') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" class="field-input" required>
                </div>
                <button type="submit" class="btn-primary btn-block">Send reset link</button>
            </form>
        </div>
        <p class="text-center text-muted mt-6"><a href="<?= base_url('login') ?>" class="text-wood font-medium hover:text-wood-dark">← Back to sign in</a></p>
    </div>
</div>
<?= $this->endSection() ?>
