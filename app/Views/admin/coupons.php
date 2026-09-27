<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="grid lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2 bg-surface rounded-lg border border-line overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-sand/50 text-left text-muted"><tr><th class="px-4 py-3 font-medium">Code</th><th class="px-4 py-3 font-medium">Value</th><th class="px-4 py-3 font-medium">Min order</th><th class="px-4 py-3 font-medium">Used</th><th class="px-4 py-3 font-medium">Status</th></tr></thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($coupons as $c): ?>
                    <tr class="hover:bg-sand/30">
                        <td class="px-4 py-3 font-medium"><?= esc($c['code']) ?><p class="text-xs text-muted font-normal"><?= esc($c['description']) ?></p></td>
                        <td class="px-4 py-3"><?= $c['type'] === 'percent' ? (int) $c['value'] . '%' : price($c['value']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= price($c['min_order']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= (int) $c['used_count'] ?><?= $c['usage_limit'] ? ' / ' . (int) $c['usage_limit'] : '' ?></td>
                        <td class="px-4 py-3"><span class="badge <?= $c['status'] === 'active' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($c['status']) ?></span></td>
                    </tr>
                <?php endforeach ?>
                <?php if (empty($coupons)): ?><tr><td colspan="5" class="px-4 py-8 text-center text-muted">No coupons yet.</td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
    <div class="bg-surface rounded-lg border border-line p-5">
        <h2 class="font-medium mb-4">New coupon</h2>
        <form method="post" action="<?= base_url('admin/coupons/store') ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div><label class="field-label" for="code">Code</label><input id="code" name="code" class="field-input uppercase" required></div>
            <div><label class="field-label" for="description">Description</label><input id="description" name="description" class="field-input"></div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="field-label" for="type">Type</label><select id="type" name="type" class="field-input"><option value="percent">Percent</option><option value="fixed">Fixed ₹</option></select></div>
                <div><label class="field-label" for="value">Value</label><input id="value" name="value" type="number" step="0.01" class="field-input" required></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="field-label" for="min_order">Min order</label><input id="min_order" name="min_order" type="number" class="field-input" value="0"></div>
                <div><label class="field-label" for="max_discount">Max discount</label><input id="max_discount" name="max_discount" type="number" class="field-input"></div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="field-label" for="usage_limit">Usage limit</label><input id="usage_limit" name="usage_limit" type="number" class="field-input"></div>
                <div><label class="field-label" for="expires_at">Expires</label><input id="expires_at" name="expires_at" type="date" class="field-input"></div>
            </div>
            <button class="btn-primary btn-block btn-sm">Create coupon</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
