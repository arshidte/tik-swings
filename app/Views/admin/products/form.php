<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$p = $product;
$errors = session('errors') ?? [];
$val = fn ($k, $d = '') => esc(old($k, $p[$k] ?? $d));
$action = $p ? base_url('admin/products/update/' . $p['id']) : base_url('admin/products/store');
?>
<a href="<?= base_url('admin/products') ?>" class="text-sm text-muted hover:text-wood">← Back to products</a>

<?php if ($errors): ?><div class="mt-4 rounded-md bg-danger/10 text-danger px-4 py-3 text-sm">Please correct the highlighted fields.</div><?php endif ?>

<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="mt-4 grid lg:grid-cols-3 gap-6 items-start">
    <?= csrf_field() ?>
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <div>
                <label class="field-label" for="name">Name</label>
                <input id="name" name="name" class="field-input" required value="<?= $val('name') ?>">
                <?php if (isset($errors['name'])): ?><p class="field-error"><?= esc($errors['name']) ?></p><?php endif ?>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="field-label" for="slug">Slug (auto if blank)</label><input id="slug" name="slug" class="field-input" value="<?= $val('slug') ?>"></div>
                <div><label class="field-label" for="sku">SKU</label><input id="sku" name="sku" class="field-input" required value="<?= $val('sku') ?>"><?php if (isset($errors['sku'])): ?><p class="field-error"><?= esc($errors['sku']) ?></p><?php endif ?></div>
            </div>
            <div><label class="field-label" for="short_description">Short description</label><input id="short_description" name="short_description" class="field-input" value="<?= $val('short_description') ?>"></div>
            <div><label class="field-label" for="description">Description</label><textarea id="description" name="description" rows="6" class="field-input"><?= $val('description') ?></textarea></div>
        </div>

        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">Attributes</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="field-label" for="wood_type">Wood type</label><input id="wood_type" name="wood_type" class="field-input" value="<?= $val('wood_type') ?>"></div>
                <div><label class="field-label" for="finish">Finish</label><input id="finish" name="finish" class="field-input" value="<?= $val('finish') ?>"></div>
                <div><label class="field-label" for="material">Material</label><input id="material" name="material" class="field-input" value="<?= $val('material') ?>"></div>
                <div><label class="field-label" for="dimensions">Dimensions</label><input id="dimensions" name="dimensions" class="field-input" value="<?= $val('dimensions') ?>"></div>
                <div><label class="field-label" for="weight_capacity">Weight capacity</label><input id="weight_capacity" name="weight_capacity" class="field-input" value="<?= $val('weight_capacity') ?>"></div>
                <div><label class="field-label" for="warranty">Warranty</label><input id="warranty" name="warranty" class="field-input" value="<?= $val('warranty') ?>"></div>
                <div class="sm:col-span-2"><label class="field-label" for="delivery_estimate">Delivery estimate</label><input id="delivery_estimate" name="delivery_estimate" class="field-input" value="<?= $val('delivery_estimate') ?>"></div>
            </div>
        </div>

        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">SEO</h2>
            <div><label class="field-label" for="seo_title">SEO title</label><input id="seo_title" name="seo_title" class="field-input" value="<?= $val('seo_title') ?>"></div>
            <div><label class="field-label" for="seo_description">SEO description</label><textarea id="seo_description" name="seo_description" rows="2" class="field-input"><?= $val('seo_description') ?></textarea></div>
            <div><label class="field-label" for="seo_keywords">SEO keywords</label><input id="seo_keywords" name="seo_keywords" class="field-input" value="<?= $val('seo_keywords') ?>"></div>
        </div>

        <!-- Images (§36/§37) -->
        <div class="bg-surface rounded-lg border border-line p-5">
            <h2 class="font-medium mb-4">Images</h2>
            <?php if (! empty($images)): ?>
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 mb-4">
                    <?php foreach ($images as $img): ?>
                        <div class="relative aspect-square rounded-md overflow-hidden bg-sand border <?= $img['is_primary'] ? 'border-wood ring-1 ring-wood' : 'border-line' ?>">
                            <img src="<?= esc(product_image($img['image']), 'attr') ?>" alt="" class="w-full h-full object-cover">
                            <?php if ($img['is_primary']): ?><span class="absolute top-1 left-1 badge-soft text-[10px]">Primary</span><?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
            <label class="field-label" for="images">Upload images (JPG/PNG/WebP, max 6MB each)</label>
            <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-ink file:text-bg file:text-sm hover:file:bg-wood-dark file:cursor-pointer">
            <p class="field-help">Images are validated, re-encoded to WebP and resized automatically.</p>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <div><label class="field-label" for="status">Status</label>
                <select id="status" name="status" class="field-input">
                    <?php foreach (['active' => 'Active', 'draft' => 'Draft', 'archived' => 'Archived'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($p['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div><label class="field-label" for="category_id">Category</label>
                <select id="category_id" name="category_id" class="field-input">
                    <option value="">— None —</option>
                    <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($p['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach ?>
                </select>
            </div>
            <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="featured" value="1" <?= ! empty($p['featured']) ? 'checked' : '' ?> class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Featured on homepage</label>
            <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="is_customizable" value="1" <?= ! empty($p['is_customizable']) ? 'checked' : '' ?> class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Customizable</label>
            <label class="flex items-center gap-2.5 text-sm"><input type="checkbox" name="installation_available" value="1" <?= ! empty($p['installation_available']) ? 'checked' : '' ?> class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"> Installation available</label>
        </div>

        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">Pricing &amp; Stock</h2>
            <div><label class="field-label" for="price">Price (₹)</label><input id="price" name="price" type="number" step="0.01" class="field-input" required value="<?= $val('price') ?>"><?php if (isset($errors['price'])): ?><p class="field-error"><?= esc($errors['price']) ?></p><?php endif ?></div>
            <div><label class="field-label" for="compare_price">Compare-at price (₹)</label><input id="compare_price" name="compare_price" type="number" step="0.01" class="field-input" value="<?= $val('compare_price') ?>"></div>
            <div><label class="field-label" for="cost_price">Cost price (₹)</label><input id="cost_price" name="cost_price" type="number" step="0.01" class="field-input" value="<?= $val('cost_price') ?>"></div>
            <div><label class="field-label" for="stock">Stock</label><input id="stock" name="stock" type="number" class="field-input" value="<?= $val('stock', '0') ?>"></div>
            <div><label class="field-label" for="stock_status">Stock status</label>
                <select id="stock_status" name="stock_status" class="field-input">
                    <?php foreach (['in_stock' => 'In stock', 'out_of_stock' => 'Out of stock', 'made_to_order' => 'Made to order'] as $k => $lbl): ?>
                        <option value="<?= $k ?>" <?= ($p['stock_status'] ?? 'in_stock') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn-primary btn-block">Save Product</button>
    </div>
</form>

<?php // ============ Customer Gallery (user-uploaded media) ============ ?>
<?php if ($p): $userMedia = $userMedia ?? []; ?>
<section id="customer-gallery" class="mt-6 bg-surface rounded-lg border border-line p-5">
    <div class="flex items-center justify-between gap-3 mb-1">
        <h2 class="font-medium">Customer Gallery</h2>
        <span class="text-xs text-muted"><?= count($userMedia) ?> item<?= count($userMedia) === 1 ? '' : 's' ?></span>
    </div>
    <p class="field-help mb-4">Photos &amp; videos shared by customers. Shown on the product page above the reviews.</p>

    <!-- Add new media -->
    <form method="post" action="<?= base_url('admin/products/media/' . $p['id']) ?>" enctype="multipart/form-data" class="grid sm:grid-cols-2 gap-4 border border-dashed border-line rounded-md p-4 mb-6">
        <?= csrf_field() ?>
        <div class="sm:col-span-2">
            <label class="field-label" for="media">Upload photos / videos</label>
            <input id="media" name="media[]" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" multiple required
                   class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-ink file:text-bg file:text-sm hover:file:bg-wood-dark file:cursor-pointer">
            <p class="field-help">Images (JPG/PNG/WebP, ≤6MB) are re-encoded to WebP. Videos (MP4/WebM/MOV, ≤64MB) are stored as-is. Author &amp; caption below apply to this batch.</p>
        </div>
        <div><label class="field-label" for="author_name">Customer name (optional)</label><input id="author_name" name="author_name" class="field-input" placeholder="e.g. Priya from Pune"></div>
        <div><label class="field-label" for="caption">Caption (optional)</label><input id="caption" name="caption" class="field-input" placeholder="e.g. Perfect in our balcony corner"></div>
        <div class="sm:col-span-2"><button type="submit" class="btn-wood">Add to gallery</button></div>
    </form>

    <!-- Manage existing media -->
    <?php if (! empty($userMedia)): ?>
        <form method="post" action="<?= base_url('admin/products/media/save/' . $p['id']) ?>">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($userMedia as $m): $isVideo = $m['media_type'] === 'video'; ?>
                    <div class="rounded-md border border-line overflow-hidden <?= $m['status'] === 'hidden' ? 'opacity-60' : '' ?>">
                        <div class="relative aspect-square bg-sand">
                            <?php if ($isVideo): ?>
                                <video src="<?= esc(product_image($m['media']), 'attr') ?>#t=0.1" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                                <span class="absolute top-1.5 left-1.5 badge-soft text-[10px]">Video</span>
                            <?php else: ?>
                                <img src="<?= esc(product_image($m['media']), 'attr') ?>" alt="" class="w-full h-full object-cover">
                            <?php endif ?>
                            <button type="submit" formaction="<?= base_url('admin/products/media/delete/' . $p['id'] . '/' . $m['id']) ?>"
                                    onclick="return confirm('Remove this media item?')"
                                    class="absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-ink/70 text-white flex items-center justify-center hover:bg-danger" aria-label="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                        <div class="p-2.5 space-y-2">
                            <input name="items[<?= $m['id'] ?>][author_name]" value="<?= esc($m['author_name'], 'attr') ?>" placeholder="Customer name" class="field-input !py-1.5 !text-xs">
                            <input name="items[<?= $m['id'] ?>][caption]" value="<?= esc($m['caption'], 'attr') ?>" placeholder="Caption" class="field-input !py-1.5 !text-xs">
                            <div class="flex items-center gap-2">
                                <input type="number" name="items[<?= $m['id'] ?>][sort_order]" value="<?= (int) $m['sort_order'] ?>" title="Sort order" class="field-input !py-1.5 !text-xs w-16">
                                <select name="items[<?= $m['id'] ?>][status]" class="field-input !py-1.5 !text-xs flex-1">
                                    <option value="visible" <?= $m['status'] === 'visible' ? 'selected' : '' ?>>Visible</option>
                                    <option value="hidden" <?= $m['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
                                </select>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="mt-4"><button type="submit" class="btn-primary">Save gallery changes</button></div>
        </form>
    <?php endif ?>
</section>
<?php endif ?>
<?= $this->endSection() ?>
