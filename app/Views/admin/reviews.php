<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="flex items-center gap-2 mb-5">
    <a href="<?= base_url('admin/reviews') ?>" class="btn-sm <?= ! $status ? 'btn-primary' : 'btn-outline' ?>">All</a>
    <?php foreach (['pending', 'approved', 'rejected'] as $s): ?>
        <a href="<?= base_url('admin/reviews?status=' . $s) ?>" class="btn-sm <?= $status === $s ? 'btn-primary' : 'btn-outline' ?>"><?= ucfirst($s) ?></a>
    <?php endforeach ?>
</div>

<div class="space-y-4">
    <?php foreach ($reviews as $r): ?>
        <div class="bg-surface rounded-lg border border-line p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <?= star_row((float) $r['rating'], 'w-3.5 h-3.5') ?>
                        <span class="text-sm font-medium"><?= esc($r['author_name']) ?></span>
                        <?php if ($r['city']): ?><span class="text-xs text-muted">· <?= esc($r['city']) ?></span><?php endif ?>
                        <span class="badge <?= $r['status'] === 'approved' ? 'bg-success/10 text-success' : ($r['status'] === 'rejected' ? 'bg-danger/10 text-danger' : 'bg-sand text-wood-dark') ?>"><?= esc($r['status']) ?></span>
                    </div>
                    <?php if ($r['title']): ?><p class="font-medium text-sm"><?= esc($r['title']) ?></p><?php endif ?>
                    <p class="text-sm text-muted mt-1"><?= esc($r['body']) ?></p>
                    <p class="text-xs text-subtle mt-2">on <?= esc($r['product_name'] ?? 'product') ?></p>
                </div>
                <form method="post" action="<?= base_url('admin/reviews/moderate/' . $r['id']) ?>" class="flex flex-col gap-2 shrink-0">
                    <?= csrf_field() ?>
                    <button name="action" value="approve" class="btn-sm btn-outline text-success border-success/40">Approve</button>
                    <button name="action" value="reject" class="btn-sm btn-outline text-danger border-danger/40">Reject</button>
                </form>
            </div>
        </div>
    <?php endforeach ?>
    <?php if (empty($reviews)): ?><p class="text-muted">No reviews.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
