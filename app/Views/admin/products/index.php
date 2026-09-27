<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-between gap-4 mb-5">
    <form method="get" class="flex-1 max-w-xs">
        <input name="q" value="<?= esc($q) ?>" placeholder="Search products…" class="field-input py-2 min-h-[40px] text-sm">
    </form>
    <a href="<?= base_url('admin/products/create') ?>" class="btn-primary btn-sm">+ New Product</a>
</div>

<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted">
            <tr>
                <th class="px-4 py-3 font-medium">Product</th>
                <th class="px-4 py-3 font-medium hidden sm:table-cell">SKU</th>
                <th class="px-4 py-3 font-medium">Price</th>
                <th class="px-4 py-3 font-medium hidden sm:table-cell">Stock</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($products as $p): ?>
                <tr class="hover:bg-sand/30">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= esc(product_image($p['primary_image'] ?? null), 'attr') ?>" alt="" width="40" height="40" class="w-10 h-10 rounded object-cover bg-sand">
                            <div>
                                <a href="<?= base_url('admin/products/edit/' . $p['id']) ?>" class="font-medium hover:text-wood"><?= esc($p['name']) ?></a>
                                <?php if ($p['featured']): ?><span class="badge-soft ml-1">Featured</span><?php endif ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted hidden sm:table-cell"><?= esc($p['sku']) ?></td>
                    <td class="px-4 py-3 tabular-nums"><?= price($p['price']) ?></td>
                    <td class="px-4 py-3 tabular-nums hidden sm:table-cell"><?= $p['has_variants'] ? '—' : (int) $p['stock'] ?></td>
                    <td class="px-4 py-3"><span class="badge <?= $p['status'] === 'active' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($p['status']) ?></span></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="<?= base_url('product/' . $p['slug']) ?>" target="_blank" class="text-muted hover:text-wood" title="View">↗</a>
                            <a href="<?= base_url('admin/products/edit/' . $p['id']) ?>" class="text-wood hover:text-wood-dark">Edit</a>
                            <form method="post" action="<?= base_url('admin/products/delete/' . $p['id']) ?>" onsubmit="return confirm('Archive this product?')" class="inline">
                                <?= csrf_field() ?><button class="text-danger hover:underline">Archive</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($products)): ?><tr><td colspan="6" class="px-4 py-10 text-center text-muted">No products found.</td></tr><?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($pager): ?><div class="mt-6"><?= $pager->links('default', 'swing_pager') ?></div><?php endif ?>
<?= $this->endSection() ?>
