<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-page py-10 sm:py-14">
    <h1 class="text-h1 font-display mb-8">My Addresses</h1>
    <div class="lg:grid lg:grid-cols-[240px_1fr] lg:gap-10 items-start">
        <aside class="mb-8 lg:mb-0"><?= view('account/_nav', ['active' => $active]) ?></aside>
        <div>
            <?php if (session('success')): ?><div class="rounded-md bg-success/10 text-success px-4 py-3 text-sm mb-6"><?= esc(session('success')) ?></div><?php endif ?>

            <div class="grid sm:grid-cols-2 gap-4 mb-8">
                <?php foreach ($addresses as $a): ?>
                    <div class="card p-5 relative">
                        <?php if ($a['is_default']): ?><span class="badge-soft absolute top-4 right-4">Default</span><?php endif ?>
                        <p class="font-medium"><?= esc($a['full_name']) ?><?= $a['label'] ? ' · ' . esc($a['label']) : '' ?></p>
                        <address class="not-italic text-sm text-muted mt-2 leading-relaxed">
                            <?= esc($a['line1']) ?><?php if ($a['line2']): ?>, <?= esc($a['line2']) ?><?php endif ?><br>
                            <?= esc($a['city']) ?>, <?= esc($a['state']) ?> <?= esc($a['pincode']) ?><br>
                            <?= esc($a['phone']) ?>
                        </address>
                        <form method="post" action="<?= base_url('account/addresses/delete') ?>" class="mt-4" onsubmit="return confirm('Remove this address?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <button class="text-sm text-danger hover:underline">Remove</button>
                        </form>
                    </div>
                <?php endforeach ?>
            </div>

            <div class="card p-6">
                <h2 class="font-display text-xl mb-4">Add a new address</h2>
                <?php $errors = session('errors') ?? []; ?>
                <?php if ($errors): ?><div role="alert" class="mb-5 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm">Please correct the highlighted fields.</div><?php endif ?>
                <form method="post" action="<?= base_url('account/addresses/save') ?>" class="grid sm:grid-cols-2 gap-4">
                    <?= csrf_field() ?>
                    <div><label class="field-label" for="full_name">Full name</label><input id="full_name" name="full_name" class="field-input" required value="<?= esc(old('full_name')) ?>"><?php if (isset($errors['full_name'])): ?><p class="field-error"><?= esc($errors['full_name']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="phone">Phone</label><input id="phone" name="phone" type="tel" class="field-input" required value="<?= esc(old('phone')) ?>"><?php if (isset($errors['phone'])): ?><p class="field-error"><?= esc($errors['phone']) ?></p><?php endif ?></div>
                    <div class="sm:col-span-2"><label class="field-label" for="line1">Address line 1</label><input id="line1" name="line1" class="field-input" required value="<?= esc(old('line1')) ?>"><?php if (isset($errors['line1'])): ?><p class="field-error"><?= esc($errors['line1']) ?></p><?php endif ?></div>
                    <div class="sm:col-span-2"><label class="field-label" for="line2">Address line 2 (optional)</label><input id="line2" name="line2" class="field-input" value="<?= esc(old('line2')) ?>"></div>
                    <div><label class="field-label" for="city">City</label><input id="city" name="city" class="field-input" required value="<?= esc(old('city')) ?>"><?php if (isset($errors['city'])): ?><p class="field-error"><?= esc($errors['city']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="state">State</label><input id="state" name="state" class="field-input" required value="<?= esc(old('state')) ?>"><?php if (isset($errors['state'])): ?><p class="field-error"><?= esc($errors['state']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="pincode">Pincode</label><input id="pincode" name="pincode" inputmode="numeric" class="field-input" required value="<?= esc(old('pincode')) ?>"><?php if (isset($errors['pincode'])): ?><p class="field-error"><?= esc($errors['pincode']) ?></p><?php endif ?></div>
                    <div><label class="field-label" for="label">Label (optional)</label><input id="label" name="label" class="field-input" placeholder="Home, Work…" value="<?= esc(old('label')) ?>"></div>
                    <label class="sm:col-span-2 flex items-center gap-2.5 text-sm"><input type="checkbox" name="is_default" value="1" class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Make this my default address</label>
                    <div class="sm:col-span-2"><button class="btn-primary">Save address</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
