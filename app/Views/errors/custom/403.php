<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container-page py-24 text-center">
    <p class="eyebrow">403</p>
    <h1 class="mt-2 text-h1 font-display">This area is staff only.</h1>
    <p class="mt-3 text-muted">You don't have permission to view this page.</p>
    <a href="<?= base_url('/') ?>" class="btn-primary mt-8">Back to home</a>
</div>
<?= $this->endSection() ?>
