<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-16 sm:py-24">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-h1 font-display">Set a new password</h1>
            <p class="mt-2 text-muted">Create a strong new password for your account.</p>
        </div>
        <div class="card p-6 sm:p-8">
            <?php if (session('error')): ?>
                <div class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('errors')): ?>
                <div class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm space-y-1">
                    <?php foreach (session('errors') as $err): ?>
                        <p><?= esc($err) ?></p>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <form method="post" action="<?= base_url('reset-password/' . esc($token, 'attr')) ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="field-label" for="password">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="field-input" required minlength="8" placeholder="At least 8 characters">
                </div>
                <div>
                    <label class="field-label" for="password_confirm">Confirm new password</label>
                    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" class="field-input" required minlength="8" placeholder="Repeat new password">
                </div>
                <button type="submit" class="btn-primary btn-block">Update password</button>
            </form>
        </div>
        <p class="text-center text-muted mt-6"><a href="<?= base_url('login') ?>" class="text-wood font-medium hover:text-wood-dark">← Back to sign in</a></p>
    </div>
</div>
<?= $this->endSection() ?>

