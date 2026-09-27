<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-16 sm:py-24">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-h1 font-display">Welcome back</h1>
            <p class="mt-2 text-muted">Sign in to track orders and save your favourites.</p>
        </div>

        <div class="card p-6 sm:p-8">
            <?php if (session('error')): ?>
                <div role="alert" class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm"><?= esc(session('error')) ?></div>
            <?php endif ?>
            <?php if (session('info')): ?>
                <div class="mb-5 rounded-md bg-sand text-wood-dark px-4 py-3 text-sm"><?= esc(session('info')) ?></div>
            <?php endif ?>
            <?php $errors = session('errors') ?? []; ?>

            <form method="post" action="<?= base_url('login') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" class="field-input" required value="<?= esc(old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= esc($errors['email']) ?></p><?php endif ?>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="field-label mb-0" for="password">Password</label>
                        <a href="<?= base_url('forgot-password') ?>" class="text-sm text-wood hover:text-wood-dark">Forgot?</a>
                    </div>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" class="field-input pr-12" required>
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-subtle hover:text-ink" data-toggle-password aria-label="Show password">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= esc($errors['password']) ?></p><?php endif ?>
                </div>
                <button type="submit" class="btn-primary btn-block">Sign In</button>
            </form>
        </div>

        <p class="text-center text-muted mt-6">New here? <a href="<?= base_url('register') ?>" class="text-wood font-medium hover:text-wood-dark">Create an account</a></p>
        <p class="text-center text-xs text-subtle mt-4">Demo: customer@example.com · password</p>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-toggle-password]').forEach((b) => b.addEventListener('click', () => {
    const input = b.closest('.relative').querySelector('input');
    input.type = input.type === 'password' ? 'text' : 'password';
}));
</script>
<?= $this->endSection() ?>
