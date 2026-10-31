<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$errors  = session('errors') ?? [];
$val     = static fn ($k, $d = '') => esc(old($k, $p[$k] ?? $d), 'attr');
$isNew   = empty($p['id']);
$action  = $isNew ? base_url('admin/pages/store') : base_url('admin/pages/update/' . $p['id']);
$type    = old('type', $p['type']);
$status  = old('status', $p['status']);
$pubAt   = old('published_at', $p['published_at'] ? date('Y-m-d\TH:i', strtotime($p['published_at'])) : '');
// After a failed save the posted HTML comes back; never echo it unsanitized.
$bodyVal = old('body') ? (new \App\Services\HtmlSanitizer())->clean((string) old('body')) : $bodyHtml;
$cover   = $p['cover_image'] ?? null;
$prefix  = $type === 'journal' ? 'journal/' : ($isSystem ? '' : 'page/');
$err     = static fn ($k) => isset($errors[$k]) ? '<p class="field-error">' . esc($errors[$k]) . '</p>' : '';
?>
<a href="<?= base_url('admin/pages') ?>" class="text-sm text-muted hover:text-wood">← Back to pages</a>

<?php if ($errors): ?><div class="mt-4 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm" role="alert">Please correct the highlighted fields.</div><?php endif ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="mt-4 grid lg:grid-cols-3 gap-6 items-start" data-cms-form
      data-new="<?= $isNew ? '1' : '0' ?>" data-locked="<?= $isSystem ? '1' : '0' ?>">
    <?= csrf_field() ?>

    <div class="lg:col-span-2 space-y-6 min-w-0">
        <!-- Basics -->
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <div>
                <label class="field-label" for="title">Title</label>
                <input id="title" name="title" class="field-input !text-lg" required maxlength="190" value="<?= $val('title') ?>" data-title>
                <?= $err('title') ?>
            </div>
            <div>
                <label class="field-label" for="slug">URL</label>
                <div class="flex items-center rounded-md border border-line bg-bg focus-within:border-ink overflow-hidden">
                    <span class="pl-3 text-sm text-muted whitespace-nowrap select-none" data-slug-prefix><?= esc(rtrim(base_url(), '/') . '/' . $prefix) ?></span>
                    <input id="slug" name="slug" class="flex-1 min-w-0 bg-transparent px-1 py-2.5 text-sm focus:outline-none disabled:text-muted"
                           value="<?= $val('slug') ?>" placeholder="auto-from-title" maxlength="190" data-slug <?= $isSystem ? 'disabled' : '' ?> aria-describedby="slug-help">
                </div>
                <p id="slug-help" class="field-help"><?= $isSystem ? 'This page is linked from the site navigation, so its URL is fixed.' : 'Leave blank to generate from the title. Lowercase letters, numbers and hyphens.' ?></p>
                <?= $err('slug') ?>
            </div>
            <div>
                <div class="flex items-end justify-between">
                    <label class="field-label" for="excerpt">Summary</label>
                    <span class="text-xs text-muted tabular-nums mb-1.5" data-count-for="excerpt" data-max="500"></span>
                </div>
                <textarea id="excerpt" name="excerpt" rows="2" maxlength="500" class="field-input" aria-describedby="excerpt-help"><?= esc(old('excerpt', $p['excerpt'] ?? '')) ?></textarea>
                <p id="excerpt-help" class="field-help">Shown under the page title<?= $type === 'journal' ? ' and on the Journal listing' : '' ?>, and used as the search description when none is set below.</p>
                <?= $err('excerpt') ?>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-surface rounded-lg border border-line p-5">
            <?php if ($isFaq): ?>
                <h2 class="font-medium">Questions &amp; answers</h2>
                <p class="field-help !mt-0.5 mb-4">Each pair becomes an expandable question on the FAQ page and is added to the page's search-engine FAQ data. Blank lines inside an answer are collapsed into a line break.</p>
                <ul class="space-y-3" data-faq-list></ul>
                <button type="button" class="btn-wood text-sm mt-3" data-faq-add>+ Add question</button>
                <script type="application/json" data-faq-state><?= json_encode(array_values(old('faq') ?: $faqs), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
            <?php else: ?>
                <div class="flex items-end justify-between">
                    <label class="field-label" id="body-label">Content</label>
                    <span class="text-xs text-muted tabular-nums mb-1.5" data-rte-stats></span>
                </div>
                <div class="rounded-md border border-line focus-within:border-ink overflow-hidden bg-bg" data-rte>
                    <div role="toolbar" aria-label="Text formatting" aria-controls="rte-area" class="flex flex-wrap items-center gap-1 border-b border-line bg-sand/40 px-2 py-1.5" data-rte-toolbar>
                        <?php
                        $btn = static function (string $cmd, string $label, string $inner, string $extra = '') {
                            return '<button type="button" class="rte-btn" data-cmd="' . $cmd . '" ' . $extra . ' title="' . esc($label, 'attr') . '" aria-label="' . esc($label, 'attr') . '" aria-pressed="false">' . $inner . '</button>';
                        };
                        ?>
                        <?= $btn('undo', 'Undo', '↶') ?>
                        <?= $btn('redo', 'Redo', '↷') ?>
                        <span class="rte-sep" aria-hidden="true"></span>
                        <select class="rte-select" aria-label="Paragraph style" data-rte-block>
                            <option value="p">Paragraph</option>
                            <option value="h2">Heading</option>
                            <option value="h3">Subheading</option>
                            <option value="h4">Small heading</option>
                        </select>
                        <span class="rte-sep" aria-hidden="true"></span>
                        <?= $btn('bold', 'Bold (Ctrl+B)', '<b>B</b>') ?>
                        <?= $btn('italic', 'Italic (Ctrl+I)', '<i>I</i>') ?>
                        <?= $btn('underline', 'Underline (Ctrl+U)', '<u>U</u>') ?>
                        <?= $btn('strikeThrough', 'Strikethrough', '<s>S</s>') ?>
                        <span class="rte-sep" aria-hidden="true"></span>
                        <?= $btn('insertUnorderedList', 'Bulleted list', '• List') ?>
                        <?= $btn('insertOrderedList', 'Numbered list', '1. List') ?>
                        <?= $btn('blockquote', 'Quote', '❝ Quote') ?>
                        <span class="rte-sep" aria-hidden="true"></span>
                        <?= $btn('link', 'Insert link (Ctrl+K)', 'Link') ?>
                        <?= $btn('unlink', 'Remove link', 'Unlink') ?>
                        <?= $btn('image', 'Insert image', 'Image') ?>
                        <?= $btn('hr', 'Horizontal line', '―') ?>
                        <span class="rte-sep" aria-hidden="true"></span>
                        <?= $btn('clear', 'Clear formatting', 'Tx') ?>
                        <?= $btn('source', 'Edit HTML source', '&lt;/&gt;') ?>
                    </div>
                    <div id="rte-area" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="body-label"
                         class="prose-content text-base leading-relaxed text-ink/90 space-y-4 px-5 py-4 min-h-[22rem] max-h-[70vh] overflow-y-auto focus:outline-none"
                         data-rte-area><?= $bodyVal // sanitized HTML; re-sanitized on save ?></div>
                    <textarea class="hidden w-full min-h-[22rem] max-h-[70vh] p-4 font-mono text-sm bg-bg focus:outline-none" spellcheck="false" aria-label="HTML source" data-rte-source></textarea>
                    <textarea name="body" hidden data-rte-value></textarea>
                </div>
                <p class="field-help">Tip: paste or drop an image straight into the text. Pasted formatting from Word or Google Docs is cleaned up when you save.</p>
            <?php endif ?>
        </div>

        <!-- Cover -->
        <div class="bg-surface rounded-lg border border-line p-5">
            <h2 class="font-medium mb-1">Cover image</h2>
            <p class="field-help !mt-0 mb-4">Shown at the top of the page<?= $type === 'journal' ? ' and on the Journal listing' : '' ?>. Wide images (16:9) work best. JPG/PNG/WebP, max 6 MB.</p>
            <div class="flex flex-wrap items-start gap-4">
                <div class="w-56 aspect-[16/9] rounded-md overflow-hidden bg-sand border border-line shrink-0">
                    <img src="<?= esc(product_image($cover, 'lifestyle.svg'), 'attr') ?>" alt="Cover preview" class="w-full h-full object-cover" data-cover-preview data-original="<?= esc(product_image($cover, 'lifestyle.svg'), 'attr') ?>">
                </div>
                <div class="space-y-3 min-w-0">
                    <input type="file" name="cover" accept="image/jpeg,image/png,image/webp" aria-label="Upload cover image" data-cover-file
                           class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-ink file:text-bg file:text-sm hover:file:bg-wood-dark file:cursor-pointer">
                    <?php if ($cover): ?>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remove_cover" value="1" class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30" data-cover-remove> Remove cover (use the default image)</label>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">Search appearance</h2>
            <div class="rounded-md border border-line bg-bg p-4" aria-hidden="true">
                <p class="text-xs text-muted truncate" data-snippet-url></p>
                <p class="text-lg text-[#1a0dab] leading-snug truncate" data-snippet-title></p>
                <p class="text-sm text-ink/70 line-clamp-2" data-snippet-desc></p>
            </div>
            <div>
                <div class="flex items-end justify-between">
                    <label class="field-label" for="seo_title">SEO title</label>
                    <span class="text-xs text-muted tabular-nums mb-1.5" data-count-for="seo_title" data-max="60" data-soft="1"></span>
                </div>
                <input id="seo_title" name="seo_title" maxlength="190" class="field-input" value="<?= $val('seo_title') ?>" placeholder="Defaults to the page title" data-seo-title>
                <?= $err('seo_title') ?>
            </div>
            <div>
                <div class="flex items-end justify-between">
                    <label class="field-label" for="seo_description">Meta description</label>
                    <span class="text-xs text-muted tabular-nums mb-1.5" data-count-for="seo_description" data-max="160" data-soft="1"></span>
                </div>
                <textarea id="seo_description" name="seo_description" rows="2" maxlength="320" class="field-input" placeholder="Defaults to the summary" data-seo-desc><?= esc(old('seo_description', $p['seo_description'] ?? '')) ?></textarea>
                <?= $err('seo_description') ?>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6 lg:sticky lg:top-4">
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">Publish</h2>
            <div>
                <label class="field-label" for="status">Status</label>
                <select id="status" name="status" class="field-input" data-status>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft — hidden from the store</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
            </div>
            <div>
                <label class="field-label" for="published_at">Publish date</label>
                <input id="published_at" name="published_at" type="datetime-local" class="field-input" value="<?= esc($pubAt, 'attr') ?>" aria-describedby="pub-help">
                <p id="pub-help" class="field-help" data-pub-help>Leave blank to publish immediately. A future date schedules it.</p>
            </div>
            <div>
                <label class="field-label" for="type">Type</label>
                <select id="type" name="type" class="field-input" data-type <?= $isSystem ? 'disabled' : '' ?>>
                    <option value="page" <?= $type === 'page' ? 'selected' : '' ?>>Page</option>
                    <option value="journal" <?= $type === 'journal' ? 'selected' : '' ?>>Journal post</option>
                </select>
            </div>
            <div data-author-wrap class="<?= $type === 'journal' ? '' : 'hidden' ?>">
                <label class="field-label" for="author">Author</label>
                <input id="author" name="author" maxlength="120" class="field-input" value="<?= $val('author') ?>">
            </div>
            <button type="submit" class="btn-primary btn-block" data-save>Save</button>
            <?php if (! $isNew): ?>
                <div class="flex items-center justify-between text-sm">
                    <?php if (! ($isFaq)): ?>
                        <a href="<?= base_url('admin/pages/preview/' . $p['id']) ?>" target="_blank" rel="noopener" class="text-wood hover:underline">Preview saved version ↗</a>
                    <?php endif ?>
                    <?php if ($p['status'] === 'published'): ?>
                        <a href="<?= esc($publicUrl, 'attr') ?>" target="_blank" rel="noopener" class="text-muted hover:text-wood">View live ↗</a>
                    <?php endif ?>
                </div>
                <p class="text-xs text-muted">Last updated <?= esc(date('d M Y, H:i', strtotime($p['updated_at'] ?? $p['created_at']))) ?></p>
            <?php endif ?>
        </div>
    </div>
</form>

<?php if (! $isNew && ! $isSystem): ?>
    <form method="post" action="<?= base_url('admin/pages/delete/' . $p['id']) ?>" onsubmit="return confirm('Delete this page permanently? This cannot be undone.')" class="mt-4 flex justify-end">
        <?= csrf_field() ?><button type="submit" class="text-sm text-danger hover:underline">Delete <?= $type === 'journal' ? 'post' : 'page' ?></button>
    </form>
<?php endif ?>

<?php if (! $isFaq): ?>
    <!-- Link dialog -->
    <dialog id="rte-link-dialog" aria-labelledby="rte-link-title" closedby="any" class="rte-dialog">
        <form method="dialog" class="space-y-4" data-link-form>
            <h2 id="rte-link-title" class="font-medium text-lg">Insert link</h2>
            <div>
                <label class="field-label" for="rte-link-url">Web address</label>
                <input id="rte-link-url" type="text" inputmode="url" class="field-input" placeholder="https://… or /shop" autofocus required data-link-url>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30" data-link-blank> Open in a new tab</label>
            <div class="flex justify-end gap-2">
                <button type="submit" value="cancel" formnovalidate class="btn-outline btn-sm">Cancel</button>
                <button type="submit" value="ok" class="btn-primary btn-sm">Insert link</button>
            </div>
        </form>
    </dialog>

    <!-- Image dialog -->
    <dialog id="rte-image-dialog" aria-labelledby="rte-image-title" closedby="any" class="rte-dialog">
        <form method="dialog" class="space-y-4" data-image-form>
            <h2 id="rte-image-title" class="font-medium text-lg">Insert image</h2>
            <div>
                <label class="field-label" for="rte-image-file">Upload an image</label>
                <input id="rte-image-file" type="file" accept="image/jpeg,image/png,image/webp" autofocus data-image-file
                       class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-ink file:text-bg file:text-sm hover:file:bg-wood-dark file:cursor-pointer">
                <p class="field-help">JPG, PNG or WebP, max 6 MB.</p>
            </div>
            <div>
                <label class="field-label" for="rte-image-url">…or paste an image address</label>
                <input id="rte-image-url" type="url" class="field-input" placeholder="https://…" data-image-url>
            </div>
            <div>
                <label class="field-label" for="rte-image-alt">Description (alt text)</label>
                <input id="rte-image-alt" type="text" class="field-input" maxlength="200" placeholder="What does the picture show?" data-image-alt>
                <p class="field-help">Read aloud by screen readers and shown if the image fails to load.</p>
            </div>
            <p class="text-sm text-danger hidden" role="alert" data-image-error></p>
            <div class="flex justify-end gap-2">
                <button type="submit" value="cancel" formnovalidate class="btn-outline btn-sm">Cancel</button>
                <button type="submit" value="ok" class="btn-primary btn-sm" data-image-ok>Insert image</button>
            </div>
        </form>
    </dialog>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
    .rte-btn { min-width: 2rem; height: 2rem; padding: 0 .5rem; border-radius: .375rem; font-size: .8125rem; line-height: 1; white-space: nowrap; color: rgb(var(--color-ink)); }
    .rte-btn:hover { background: rgb(var(--color-sand)); }
    .rte-btn[aria-pressed="true"] { background: rgb(var(--color-ink)); color: rgb(var(--color-bg)); }
    .rte-btn:disabled { opacity: .35; pointer-events: none; }
    .rte-sep { width: 1px; height: 1.25rem; background: rgb(var(--color-line)); margin: 0 .25rem; }
    .rte-select { height: 2rem; border: 1px solid rgb(var(--color-line)); border-radius: .375rem; background: rgb(var(--color-bg)); font-size: .8125rem; padding: 0 .5rem; }
    [data-rte-area]:empty::before { content: 'Start writing…'; color: rgb(var(--color-ink) / .4); pointer-events: none; }
    [data-rte-area] img { cursor: default; }
    .rte-dialog { border: 0; border-radius: .75rem; padding: 1.5rem; width: min(30rem, calc(100vw - 2rem)); background: rgb(var(--color-surface)); color: rgb(var(--color-ink)); box-shadow: 0 20px 50px rgb(0 0 0 / .25); }
    .rte-dialog::backdrop { background: rgb(0 0 0 / .45); overflow: hidden; overscroll-behavior: contain; }
    dialog:modal { overscroll-behavior: contain; }
</style>
<script src="<?= base_url('assets/js/admin_cms.js') ?>?v=1"></script>
<?= $this->endSection() ?>
