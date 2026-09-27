<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted"><tr><th class="px-4 py-3 font-medium">Name</th><th class="px-4 py-3 font-medium">Email</th><th class="px-4 py-3 font-medium hidden sm:table-cell">Phone</th><th class="px-4 py-3 font-medium">Orders</th><th class="px-4 py-3 font-medium">Spent</th></tr></thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-sand/30">
                    <td class="px-4 py-3 font-medium"><?= esc(trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
                    <td class="px-4 py-3 text-muted"><?= esc($c['email']) ?></td>
                    <td class="px-4 py-3 text-muted hidden sm:table-cell"><?= esc($c['phone']) ?></td>
                    <td class="px-4 py-3 tabular-nums"><?= (int) $c['order_count'] ?></td>
                    <td class="px-4 py-3 tabular-nums font-medium"><?= price($c['spent']) ?></td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($customers)): ?><tr><td colspan="5" class="px-4 py-10 text-center text-muted">No customers yet.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($pager): ?><div class="mt-6"><?= $pager->links('default', 'swing_pager') ?></div><?php endif ?>
<?= $this->endSection() ?>
