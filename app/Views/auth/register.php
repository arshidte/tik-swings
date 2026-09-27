<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-16 sm:py-24">
    <div class="max-w-md mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-h1 font-display">Create your account</h1>
            <p class="mt-2 text-muted">Save your favourites and check out faster.</p>
        </div>

        <div class="card p-6 sm:p-8">
            <?php $errors = session('errors') ?? []; ?>
            <?php if ($errors): ?>
                <div role="alert" class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm">Please correct the highlighted fields.</div>
            <?php endif ?>

            <form method="post" action="<?= base_url('register') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label" for="first_name">First name</label>
                        <input id="first_name" name="first_name" class="field-input" required value="<?= esc(old('first_name')) ?>">
                        <?php if (isset($errors['first_name'])): ?><p class="field-error"><?= esc($errors['first_name']) ?></p><?php endif ?>
                    </div>
                    <div>
                        <label class="field-label" for="last_name">Last name</label>
                        <input id="last_name" name="last_name" class="field-input" value="<?= esc(old('last_name')) ?>">
                    </div>
                </div>
                <div>
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" class="field-input" required value="<?= esc(old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= esc($errors['email']) ?></p><?php endif ?>
                </div>
                <div>
                    <label class="field-label" for="phone">Phone</label>
                    <input id="phone" name="phone" type="tel" autocomplete="tel" class="field-input" value="<?= esc(old('phone')) ?>">
                    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= esc($errors['phone']) ?></p><?php endif ?>
                </div>
                <div>
                    <label class="field-label" for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="field-input" required>
                    <p class="field-help">At least 8 characters.</p>
                    <?php if (isset($errors['password'])): ?><p class="field-error"><?= esc($errors['password']) ?></p><?php endif ?>
                </div>
                <div>
                    <label class="field-label" for="password_confirm">Confirm password</label>
                    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" class="field-input" required>
                    <?php if (isset($errors['password_confirm'])): ?><p class="field-error"><?= esc($errors['password_confirm']) ?></p><?php endif ?>
                </div>
                <button type="submit" class="btn-primary btn-block">Create Account</button>
            </form>
        </div>

        <p class="text-center text-muted mt-6">Already have an account? <a href="<?= base_url('login') ?>" class="text-wood font-medium hover:text-wood-dark">Sign in</a></p>
    </div>
</div>
<?= $this->endSection() ?>
