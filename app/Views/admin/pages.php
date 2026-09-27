<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted"><tr><th class="px-4 py-3 font-medium">Title</th><th class="px-4 py-3 font-medium">Type</th><th class="px-4 py-3 font-medium">Slug</th><th class="px-4 py-3 font-medium">Status</th><th class="px-4 py-3"></th></tr></thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($pages as $p): ?>
                <tr class="hover:bg-sand/30">
                    <td class="px-4 py-3 font-medium"><?= esc($p['title']) ?></td>
                    <td class="px-4 py-3"><span class="badge-soft"><?= esc($p['type']) ?></span></td>
                    <td class="px-4 py-3 text-muted"><?= esc($p['slug']) ?></td>
                    <td class="px-4 py-3"><span class="badge <?= $p['status'] === 'published' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($p['status']) ?></span></td>
                    <td class="px-4 py-3 text-right"><a href="<?= base_url(($p['type'] === 'journal' ? 'journal/' : '') . $p['slug']) ?>" target="_blank" class="text-wood hover:text-wood-dark">View ↗</a></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<p class="text-sm text-muted mt-4">Page content is seeded and can be extended with a full CMS editor. Journal posts and static pages render from the <code>pages</code> table.</p>
<?= $this->endSection() ?>
