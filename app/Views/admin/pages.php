<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
use App\Models\PageModel;

$now        = date('Y-m-d H:i:s');
$totalPages = array_sum($counts['page'] ?? []);
$totalPosts = array_sum($counts['journal'] ?? []);
$tab = static function (string $label, string $value, int $n) use ($type, $q, $status) {
    $active = $type === $value;
    $qs     = http_build_query(array_filter(['type' => $value, 'q' => $q, 'status' => $status]));
    $cls    = $active ? 'bg-ink text-bg' : 'bg-surface border border-line hover:border-ink';

    return '<a href="' . base_url('admin/pages') . ($qs ? '?' . $qs : '') . '" class="px-3 py-1.5 rounded-full text-sm ' . $cls . '"' . ($active ? ' aria-current="true"' : '') . '>'
        . esc($label) . ' <span class="opacity-60">' . $n . '</span></a>';
};
?>
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex flex-wrap items-center gap-2">
        <?= $tab('All', '', $totalPages + $totalPosts) ?>
        <?= $tab('Pages', 'page', $totalPages) ?>
        <?= $tab('Journal', 'journal', $totalPosts) ?>
    </div>
    <div class="flex items-center gap-2">
        <a href="<?= base_url('admin/pages/create?type=journal') ?>" class="btn-outline btn-sm">+ Journal post</a>
        <a href="<?= base_url('admin/pages/create') ?>" class="btn-primary btn-sm">+ New page</a>
    </div>
</div>

<form method="get" class="flex flex-wrap gap-2 mb-5">
    <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= esc($type, 'attr') ?>"><?php endif ?>
    <input name="q" value="<?= esc($q, 'attr') ?>" placeholder="Search title or URL…" aria-label="Search pages" class="field-input py-2 min-h-[40px] text-sm max-w-xs flex-1">
    <select name="status" aria-label="Filter by status" class="field-input py-2 min-h-[40px] text-sm !w-auto" onchange="this.form.submit()">
        <option value="">Any status</option>
        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
    </select>
    <button class="btn-outline btn-sm">Search</button>
</form>

<div class="bg-surface rounded-lg border border-line overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-sand/50 text-left text-muted">
            <tr>
                <th class="px-4 py-3 font-medium">Title</th>
                <th class="px-4 py-3 font-medium hidden sm:table-cell">Type</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium hidden md:table-cell">Updated</th>
                <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            <?php foreach ($pages as $row):
                $system    = PageModel::isSystem($row['slug']);
                $scheduled = $row['status'] === 'published' && $row['published_at'] && $row['published_at'] > $now;
                $url       = PageModel::urlFor($row);
                $path      = '/' . ltrim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            ?>
                <tr class="hover:bg-sand/30">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <img src="<?= esc(product_image($row['cover_image'], 'lifestyle.svg'), 'attr') ?>" alt="" width="40" height="40" class="w-10 h-10 rounded object-cover bg-sand shrink-0">
                            <div class="min-w-0">
                                <a href="<?= base_url('admin/pages/edit/' . $row['id']) ?>" class="font-medium hover:text-wood"><?= esc($row['title']) ?></a>
                                <p class="text-xs text-muted truncate"><?= esc($path) ?><?= $system ? ' · <span title="Linked from the site navigation">system</span>' : '' ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell"><span class="badge-soft"><?= esc($row['type']) ?></span></td>
                    <td class="px-4 py-3">
                        <?php if ($scheduled): ?>
                            <span class="badge bg-amber-100 text-amber-800" title="Goes live <?= esc($row['published_at'], 'attr') ?>">scheduled</span>
                        <?php else: ?>
                            <span class="badge <?= $row['status'] === 'published' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>"><?= esc($row['status']) ?></span>
                        <?php endif ?>
                    </td>
                    <td class="px-4 py-3 text-muted hidden md:table-cell whitespace-nowrap"><?= esc(date('d M Y', strtotime($row['updated_at'] ?? $row['created_at']))) ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-3 whitespace-nowrap">
                            <?php if ($row['status'] === 'published' && ! $scheduled): ?>
                                <a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener" class="text-muted hover:text-wood" title="View live">View ↗</a>
                            <?php elseif (! ($row['slug'] === 'faq' && $row['type'] === 'page')): ?>
                                <a href="<?= base_url('admin/pages/preview/' . $row['id']) ?>" target="_blank" rel="noopener" class="text-muted hover:text-wood" title="Preview (not public)">Preview ↗</a>
                            <?php endif ?>
                            <a href="<?= base_url('admin/pages/edit/' . $row['id']) ?>" class="text-wood hover:text-wood-dark">Edit</a>
                            <?php if (! $system): ?>
                                <form method="post" action="<?= base_url('admin/pages/delete/' . $row['id']) ?>" onsubmit="return confirm('Delete “<?= esc($row['title'], 'js') ?>” permanently?')" class="inline">
                                    <?= csrf_field() ?><button class="text-danger hover:underline">Delete</button>
                                </form>
                            <?php endif ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
            <?php if (empty($pages)): ?>
                <tr><td colspan="5" class="px-4 py-10 text-center text-muted">Nothing matches. <a href="<?= base_url('admin/pages/create') ?>" class="text-wood hover:underline">Create a page</a>.</td></tr>
            <?php endif ?>
        </tbody>
    </table>
</div>
<?php if ($pager): ?><div class="mt-6"><?= $pager->links('default', 'swing_pager') ?></div><?php endif ?>
<?= $this->endSection() ?>
