<?php /** @var array $reviews */ ?>
<?php foreach ($reviews as $r): ?>
    <figure class="card p-6">
        <?= star_row((float) $r['rating']) ?>
        <?php if ($r['title']): ?><figcaption class="font-display text-lg mt-3"><?= esc($r['title']) ?></figcaption><?php endif ?>
        <blockquote class="text-muted text-[15px] mt-2 leading-relaxed"><?= esc($r['body']) ?></blockquote>
        <div class="mt-4 flex items-center gap-3 text-sm">
            <span class="w-9 h-9 rounded-full bg-wood/15 text-wood-dark font-medium flex items-center justify-center"><?= esc(strtoupper(substr($r['author_name'], 0, 1))) ?></span>
            <div>
                <p class="font-medium leading-tight"><?= esc($r['author_name']) ?></p>
                <p class="text-subtle text-xs"><?= esc($r['city']) ?><?php if ($r['verified_purchase']): ?> · <span class="text-success">Verified</span><?php endif ?></p>
            </div>
        </div>
    </figure>
<?php endforeach ?>
