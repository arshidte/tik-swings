<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
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
<?= $this->endSection() ?>
