<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-14 sm:py-20">
    <div class="grid lg:grid-cols-2 gap-12">
        <div>
            <p class="eyebrow">Say hello</p>
            <h1 class="mt-2 text-h1 font-display">Let's find your swing.</h1>
            <p class="mt-4 text-muted max-w-md">Questions about a piece, a custom size, delivery to your city, or installation? Our workshop team is happy to help.</p>

            <dl class="mt-8 space-y-5">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-wood mt-0.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M3 7l9 6 9-6M4 6h16v12H4z"/></svg>
                    <div><dt class="text-sm text-subtle">Email</dt><dd><a href="mailto:<?= esc(setting('store_email')) ?>" class="hover:text-wood"><?= esc(setting('store_email')) ?></a></dd></div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-wood mt-0.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 5c0 8 7 15 15 15l0-4-4-1-2 2a12 12 0 0 1-6-6l2-2-1-4z"/></svg>
                    <div><dt class="text-sm text-subtle">Phone</dt><dd><a href="tel:<?= esc(preg_replace('/\s+/', '', setting('store_phone'))) ?>" class="hover:text-wood"><?= esc(setting('store_phone')) ?></a></dd></div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-wood mt-0.5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                    <div><dt class="text-sm text-subtle">Workshop</dt><dd class="max-w-xs"><?= nl2br(esc(setting('store_address'))) ?></dd></div>
                </div>
            </dl>

            <a href="<?= esc(whatsapp_link(), 'attr') ?>" target="_blank" rel="noopener" class="btn-wood mt-8">Chat on WhatsApp</a>
        </div>

        <div class="card p-6 sm:p-8">
            <?php if (session('success')): ?>
                <div class="badge-success mb-5 w-full justify-start px-4 py-3 rounded-md"><?= esc(session('success')) ?></div>
            <?php endif ?>
            <?php $errors = session('errors') ?? []; ?>
            <?php if ($errors): ?>
                <div role="alert" class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm">Please correct the highlighted fields.</div>
            <?php endif ?>

            <form method="post" action="<?= base_url('contact') ?>" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="field-label" for="c-name">Name <span class="text-danger">*</span></label>
                        <input id="c-name" name="name" class="field-input" required value="<?= esc(old('name')) ?>">
                        <?php if (isset($errors['name'])): ?><p class="field-error"><?= esc($errors['name']) ?></p><?php endif ?>
                    </div>
                    <div>
                        <label class="field-label" for="c-phone">Phone</label>
                        <input id="c-phone" name="phone" type="tel" class="field-input" value="<?= esc(old('phone')) ?>">
                    </div>
                </div>
                <div>
                    <label class="field-label" for="c-email">Email <span class="text-danger">*</span></label>
                    <input id="c-email" name="email" type="email" autocomplete="email" class="field-input" required value="<?= esc(old('email')) ?>">
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= esc($errors['email']) ?></p><?php endif ?>
                </div>
                <div>
                    <label class="field-label" for="c-subject">Subject</label>
                    <input id="c-subject" name="subject" class="field-input" value="<?= esc(old('subject')) ?>">
                </div>
                <div>
                    <label class="field-label" for="c-message">Message <span class="text-danger">*</span></label>
                    <textarea id="c-message" name="message" rows="5" class="field-input" required><?= esc(old('message')) ?></textarea>
                    <?php if (isset($errors['message'])): ?><p class="field-error"><?= esc($errors['message']) ?></p><?php endif ?>
                </div>
                <button type="submit" class="btn-primary btn-block">Send message</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
