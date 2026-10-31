<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<form method="get" class="flex flex-wrap items-center gap-2 mb-5">
    <input name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search email…" aria-label="Search subscribers" class="field-input py-2 min-h-[40px] text-sm max-w-xs flex-1">
    <button class="btn-outline btn-sm">Search</button>
    <?php if ($q !== ''): ?><a href="<?= base_url('admin/subscribers') ?>" class="text-sm text-wood hover:underline">Clear</a><?php endif ?>
    <span class="text-sm text-muted ml-auto tabular-nums"><?= (int) $total ?> subscriber<?= $total === 1 ? '' : 's' ?></span>
</form>
<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted"><tr><th class="px-4 py-3 font-medium">Email</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3 font-medium">Subscribed</th></tr></thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($subscribers as $s): ?>
                <tr class="hover:bg-sand/30">
                    <td class="px-4 py-3"><?= esc($s['email']) ?></td>
                    <td class="px-4 py-3"><span class="badge <?= $s['status'] === 'subscribed' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($s['status']) ?></span></td>
                    <td class="px-4 py-3 text-muted"><?= esc(date('M j, Y', strtotime($s['created_at']))) ?></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($subscribers)): ?><tr><td colspan="3" class="px-4 py-10 text-center text-muted">No subscribers yet.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($pager && $pager->getPageCount() > 1): ?><div class="mt-6"><?= $pager->links('default', 'swing_pager') ?></div><?php endif ?>
<?= $this->endSection() ?>
