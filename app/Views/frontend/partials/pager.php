<?php $pager->setSurroundCount(1); ?>
<nav aria-label="Pagination" class="inline-flex items-center gap-1">
    <?php if ($pager->hasPrevious()): ?>
        <a href="<?= $pager->getPreviousPage() ?>" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-md border border-line hover:border-ink text-sm" aria-label="Previous page" data-page-link>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="m14 6-6 6 6 6"/></svg>
        </a>
    <?php endif ?>

    <?php foreach ($pager->links() as $link): ?>
        <a href="<?= $link['uri'] ?>" data-page-link
           class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-md text-sm tabular-nums <?= $link['active'] ? 'bg-ink text-bg' : 'border border-line hover:border-ink' ?>"
           <?= $link['active'] ? 'aria-current="page"' : '' ?>>
            <?= $link['title'] ?>
        </a>
    <?php endforeach ?>

    <?php if ($pager->hasNext()): ?>
        <a href="<?= $pager->getNextPage() ?>" class="inline-flex items-center justify-center min-w-[40px] h-10 px-3 rounded-md border border-line hover:border-ink text-sm" aria-label="Next page" data-page-link>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="m10 6 6 6-6 6"/></svg>
        </a>
    <?php endif ?>
</nav>
