<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<div class="space-y-4">
    <?php foreach ($messages as $m): ?>
        <div class="bg-surface rounded-lg border border-line p-5">
            <div class="flex items-center justify-between gap-4 mb-2">
                <div><span class="font-medium"><?= esc($m['name']) ?></span> <span class="text-muted text-sm">· <?= esc($m['email']) ?><?= $m['phone'] ? ' · ' . esc($m['phone']) : '' ?></span></div>
                <span class="text-xs text-subtle"><?= esc(date('M j, Y', strtotime($m['created_at']))) ?></span>
            </div>
            <?php if ($m['subject']): ?><p class="font-medium text-sm"><?= esc($m['subject']) ?></p><?php endif ?>
            <p class="text-sm text-muted mt-1"><?= nl2br(esc($m['message'])) ?></p>
            <a href="mailto:<?= esc($m['email']) ?>" class="text-sm text-wood hover:text-wood-dark mt-3 inline-block">Reply →</a>
        </div>
    <?php endforeach ?>
    <?php if (empty($messages)): ?><p class="text-muted">No messages yet.</p><?php endif ?>
</div>
<?= $this->endSection() ?>
