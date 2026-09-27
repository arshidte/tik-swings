<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="grid lg:grid-cols-3 gap-6 items-start">
    <div class="lg:col-span-2 bg-surface rounded-lg border border-line overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-sand/50 text-left text-muted"><tr><th class="px-4 py-3 font-medium">Name</th><th class="px-4 py-3 font-medium">Slug</th><th class="px-4 py-3 font-medium">Order</th><th class="px-4 py-3 font-medium">Status</th></tr></thead>
            <tbody class="divide-y divide-line">
                <?php foreach ($categories as $c): ?>
                    <tr class="hover:bg-sand/30">
                        <td class="px-4 py-3 font-medium"><?= esc($c['name']) ?><?php if ($c['featured']): ?> <span class="badge-soft">Featured</span><?php endif ?></td>
                        <td class="px-4 py-3 text-muted"><?= esc($c['slug']) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= (int) $c['sort_order'] ?></td>
                        <td class="px-4 py-3"><span class="badge <?= $c['status'] === 'active' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($c['status']) ?></span></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <div class="bg-surface rounded-lg border border-line p-5">
        <h2 class="font-medium mb-4">Add category</h2>
        <form method="post" action="<?= base_url('admin/categories/store') ?>" class="space-y-3">
            <?= csrf_field() ?>
            <div><label class="field-label" for="name">Name</label><input id="name" name="name" class="field-input" required></div>
            <div><label class="field-label" for="slug">Slug (optional)</label><input id="slug" name="slug" class="field-input"></div>
            <div><label class="field-label" for="description">Description</label><textarea id="description" name="description" rows="2" class="field-input"></textarea></div>
            <div><label class="field-label" for="sort_order">Sort order</label><input id="sort_order" name="sort_order" type="number" value="0" class="field-input"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="featured" value="1" class="w-4 h-4 rounded border-line text-wood"> Featured</label>
            <button class="btn-primary btn-block btn-sm">Add category</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
