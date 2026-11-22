<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$p = $product;
$errors = session('errors') ?? [];
$val = fn ($k, $d = '') => esc(old($k, $p[$k] ?? $d));
$action = $p ? base_url('admin/products/update/' . $p['id']) : base_url('admin/products/store');

// Render-ready HTML for a rich-text field's editor. After a failed save the
// posted HTML comes back; never echo it unsanitized. Otherwise, legacy
// plain-text values are escaped and paragraph-wrapped, already-HTML ones
// pass through as-is. The three "optional" fields show blank (not the
// storefront's default wording) when unset — that default is applied at
// render time on the product page, not here.
$htmlFor = fn (string $field) => old($field)
    ? (new \App\Services\HtmlSanitizer())->clean((string) old($field))
    : cms_html($p[$field] ?? '');

/**
 * A rich-text editor field: toolbar + contenteditable area + HTML source
 * textarea + hidden value textarea submitted as $field. Shared by
 * Description and the optional per-product overrides below.
 */
$rteField = function (string $field, string $label, string $html, string $help = '', string $minH = '8rem', string $maxH = '24rem') use ($errors) {
    $areaId  = $field . '-area';
    $labelId = $field . '-label';
    $btn = static fn (string $cmd, string $lbl, string $inner) =>
        '<button type="button" class="rte-btn" data-cmd="' . $cmd . '" title="' . esc($lbl, 'attr') . '" aria-label="' . esc($lbl, 'attr') . '" aria-pressed="false">' . $inner . '</button>';
    ob_start(); ?>
    <div>
        <label class="field-label" id="<?= $labelId ?>"><?= esc($label) ?></label>
        <div class="rounded-md border border-line focus-within:border-ink overflow-hidden bg-bg" data-rte>
            <div role="toolbar" aria-label="Text formatting" aria-controls="<?= $areaId ?>" class="flex flex-wrap items-center gap-1 border-b border-line bg-sand/40 px-2 py-1.5" data-rte-toolbar>
                <?= $btn('undo', 'Undo', '↶') ?>
                <?= $btn('redo', 'Redo', '↷') ?>
                <span class="rte-sep" aria-hidden="true"></span>
                <select class="rte-select" aria-label="Paragraph style" data-rte-block>
                    <option value="p">Paragraph</option>
                    <option value="h3">Heading</option>
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
                <span class="rte-sep" aria-hidden="true"></span>
                <?= $btn('link', 'Insert link (Ctrl+K)', 'Link') ?>
                <?= $btn('unlink', 'Remove link', 'Unlink') ?>
                <span class="rte-sep" aria-hidden="true"></span>
                <?= $btn('clear', 'Clear formatting', 'Tx') ?>
                <?= $btn('source', 'Edit HTML source', '&lt;/&gt;') ?>
            </div>
            <div id="<?= $areaId ?>" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="<?= $labelId ?>"
                 class="product-description text-sm leading-relaxed text-ink px-4 py-3 min-h-[<?= $minH ?>] max-h-[<?= $maxH ?>] overflow-y-auto focus:outline-none"
                 data-rte-area><?= $html ?></div>
            <textarea class="hidden w-full min-h-[<?= $minH ?>] max-h-[<?= $maxH ?>] p-3 font-mono text-sm bg-bg focus:outline-none" spellcheck="false" aria-label="HTML source" data-rte-source></textarea>
            <textarea name="<?= esc($field, 'attr') ?>" hidden data-rte-value></textarea>
        </div>
        <?php if ($help): ?><p class="field-help"><?= $help ?></p><?php endif ?>
        <?php if (isset($errors[$field])): ?><p class="field-error"><?= esc($errors[$field]) ?></p><?php endif ?>
    </div>
    <?php
    return ob_get_clean();
};
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
            <?= $rteField('description', 'Description', $htmlFor('description'), '', '10rem', '28rem') ?>
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
            <h2 class="font-medium">Product page sections</h2>
            <p class="field-help !mt-0.5">These appear as their own expandable sections on the product page, below Description. Leave any of them blank to hide that section on the product page.</p>
            <?= $rteField('craftsmanship', 'Craftsmanship', $htmlFor('craftsmanship'), 'Blank hides this section on the product page.') ?>
            <?= $rteField('returns_policy', 'Returns', $htmlFor('returns_policy'), 'Blank hides this section on the product page.') ?>
            <?= $rteField('care_instructions', 'Care instructions', $htmlFor('care_instructions'), 'Blank hides this section on the product page.') ?>
        </div>

        <div class="bg-surface rounded-lg border border-line p-5 space-y-4">
            <h2 class="font-medium">SEO</h2>
            <div><label class="field-label" for="seo_title">SEO title</label><input id="seo_title" name="seo_title" class="field-input" value="<?= $val('seo_title') ?>"></div>
            <div><label class="field-label" for="seo_description">SEO description</label><textarea id="seo_description" name="seo_description" rows="2" class="field-input"><?= $val('seo_description') ?></textarea></div>
            <div><label class="field-label" for="seo_keywords">SEO keywords</label><input id="seo_keywords" name="seo_keywords" class="field-input" value="<?= $val('seo_keywords') ?>"></div>
        </div>

        <!-- Images (§36/§37) + product-card photo picker -->
        <?php
        $cardImg  = null; $hoverImg = null;
        foreach ($images as $im) {
            if (! empty($im['is_primary']) && ! $cardImg) { $cardImg = $im; }
            if (! empty($im['is_hover']) && empty($im['is_primary']) && ! $hoverImg) { $hoverImg = $im; }
        }
        $cardImg = $cardImg ?: ($images[0] ?? null);
        ?>
        <div class="bg-surface rounded-lg border border-line p-5" data-image-manager>
            <h2 class="font-medium">Images</h2>
            <?php if (! empty($images)): ?>
                <input type="hidden" name="images_present" value="1">
                <p class="field-help !mt-0.5 mb-4">Choose which photo the product card shows by default, and which one it swaps to when a shopper hovers over it. Changes apply when you save.</p>

                <div class="grid sm:grid-cols-[1fr_14rem] gap-5 items-start">
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" role="group" aria-label="Product photos">
                        <?php foreach ($images as $img):
                            $iid   = (int) $img['id'];
                            $isCard  = $cardImg && (int) $cardImg['id'] === $iid;
                            $isHover = $hoverImg && (int) $hoverImg['id'] === $iid;
                        ?>
                            <div class="rounded-md border border-line p-2 space-y-2 transition-opacity" data-img-tile data-id="<?= $iid ?>" data-src="<?= esc(product_image($img['image']), 'attr') ?>">
                                <div class="relative aspect-square rounded overflow-hidden bg-sand">
                                    <img src="<?= esc(product_image($img['image']), 'attr') ?>" alt="<?= esc($img['alt_text'] ?: 'Product photo', 'attr') ?>" class="w-full h-full object-cover" loading="lazy">
                                    <span class="absolute top-1 left-1 flex flex-col gap-1">
                                        <span class="badge-soft text-[10px] <?= $isCard ? '' : 'hidden' ?>" data-badge-card>Card photo</span>
                                        <span class="badge-soft text-[10px] <?= $isHover ? '' : 'hidden' ?>" data-badge-hover>Hover photo</span>
                                    </span>
                                </div>
                                <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input type="radio" name="card_image" value="<?= $iid ?>" <?= $isCard ? 'checked' : '' ?> data-pick="card" class="w-4 h-4 text-wood focus:ring-wood/30"> Card photo
                                </label>
                                <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input type="radio" name="hover_image" value="<?= $iid ?>" <?= $isHover ? 'checked' : '' ?> data-pick="hover" class="w-4 h-4 text-wood focus:ring-wood/30"> Hover photo
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-danger cursor-pointer">
                                    <input type="checkbox" name="delete_images[]" value="<?= $iid ?>" data-remove class="w-4 h-4 rounded border-line text-danger focus:ring-danger/30"> Remove
                                </label>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <!-- Live card preview -->
                    <div class="sm:sticky sm:top-4">
                        <p class="text-xs font-medium mb-2">Card preview <span class="text-muted font-normal">(hover it)</span></p>
                        <div class="group relative aspect-[4/5] rounded-lg overflow-hidden bg-sand border border-line" data-card-preview tabindex="0" aria-label="Product card preview">
                            <img src="<?= esc(product_image($cardImg['image'] ?? null), 'attr') ?>" alt="" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300 group-hover:opacity-0 group-focus:opacity-0" data-prev-card>
                            <img src="<?= esc(product_image($hoverImg['image'] ?? null), 'attr') ?>" alt="" class="absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-focus:opacity-100 <?= $hoverImg ? '' : 'hidden' ?>" data-prev-hover>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs mt-3 cursor-pointer">
                            <input type="radio" name="hover_image" value="0" <?= $hoverImg ? '' : 'checked' ?> data-pick="hover" data-none class="w-4 h-4 text-wood focus:ring-wood/30"> No hover photo
                        </label>
                        <p class="text-xs text-muted mt-1.5" data-prev-note></p>
                    </div>
                </div>
                <hr class="border-line my-5">
            <?php endif ?>
            <label class="field-label" for="images"><?= empty($images) ? 'Upload images' : 'Add more images' ?> (JPG/PNG/WebP, max 6MB each)</label>
            <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple class="block w-full text-sm text-muted file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-ink file:text-bg file:text-sm hover:file:bg-wood-dark file:cursor-pointer">
            <p class="field-help"><?= empty($images)
                ? 'The first photo becomes the card photo and the second its hover photo; you can change both after saving.'
                : 'New photos appear here after you save, where you can make them the card or hover photo.' ?> Images are validated, re-encoded to WebP and resized automatically.</p>
        </div>

        <?php
        // ---- Build the editor's initial state from existing data ----------
        $variants     = $variants ?? [];
        $options      = $options ?? [];        // attribute_id => ['affects_price' => ...]
        $optionAdjust = $optionAdjust ?? [];   // attribute_value_id => price_adjust
        $optionBadges = $optionBadges ?? [];   // attribute_value_id => badge text
        $attrsOut     = []; // attrKey => ['key','name','affectsPrice','values'=>[valKey => [...]]]
        $varsOut      = [];
        foreach ($variants as $v) {
            $combo = [];
            foreach ($v['attributes'] ?? [] as $a) {
                $aid  = (int) $a['attribute_id'];
                $vid  = (int) $a['attribute_value_id'];
                $aKey = 'a' . $aid;
                $vKey = 'v' . $vid;
                if (! isset($attrsOut[$aKey])) {
                    $attrsOut[$aKey] = [
                        'key'          => $aKey,
                        'name'         => $a['attribute'],
                        'affectsPrice' => ! empty($options[$aid]['affects_price']),
                        'values'       => [],
                    ];
                }
                $attrsOut[$aKey]['values'][$vKey] = [
                    'key'    => $vKey,
                    'label'  => $a['value'],
                    'swatch' => $a['swatch'],
                    'adjust' => isset($optionAdjust[$vid]) ? (float) $optionAdjust[$vid] : 0,
                    'badge'  => $optionBadges[$vid] ?? '',
                ];
                $combo[$aKey] = $vKey;
            }
            $varsOut[] = [
                'key'       => 'x' . $v['id'],
                'sku'       => $v['sku'],
                'stock'     => (int) $v['stock'],
                'status'    => $v['status'],
                'isDefault' => (int) $v['is_default'] === 1,
                'price'     => $v['price'] !== null ? (float) $v['price'] : null,
                'combo'     => $combo,
            ];
        }
        $attrsList = [];
        foreach ($attrsOut as $a) {
            $a['values'] = array_values($a['values']);
            $attrsList[] = $a;
        }
        $variantState = [
            'attributes' => $attrsList,
            'variants'   => $varsOut,
        ];
        ?>
        <!-- Options & Variants -->
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4" data-variant-editor>
            <input type="hidden" name="variants_present" value="1">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="font-medium">Options &amp; Variants</h2>
                    <p class="field-help !mt-0.5">Add options like <em>Color</em> or <em>Polish</em>, then generate the purchasable variants. Each variant has its own editable <em>Price</em>. Optionally turn on <em>Affects price</em> for an option to auto-fill prices as base price + per-value adjustments when generating; you can always override any variant's price directly.</p>
                </div>
            </div>

            <!-- Attributes -->
            <div data-attr-list class="space-y-3"></div>
            <button type="button" data-add-attribute class="btn-wood text-sm">+ Add option</button>

            <!-- Variants -->
            <div class="border-t border-line pt-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h3 class="text-sm font-medium">Variants <span class="text-muted" data-variant-count></span></h3>
                    <div class="flex gap-2">
                        <button type="button" data-generate-variants class="btn-wood text-sm">Generate from options</button>
                        <button type="button" data-add-variant class="text-sm px-3 py-2 rounded-md border border-line hover:border-ink">+ Row</button>
                    </div>
                </div>
                <p class="field-help -mt-1 mb-3" data-variant-empty>No variants yet. Add options above and click <strong>Generate from options</strong>.</p>

                <!-- Filter + bulk edit (shown once variants exist). No `name`s: never submitted. -->
                <div class="space-y-2 mb-3" data-variant-tools hidden>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="search" data-vf-search placeholder="Filter by option or SKU…" aria-label="Filter variants"
                               class="field-input !py-1.5 !text-sm flex-1 min-w-[10rem]">
                        <div class="contents" data-vf-attrs></div>
                        <button type="button" data-vf-clear class="text-xs text-wood hover:underline hidden">Clear filters</button>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 rounded-md bg-sand/40 border border-line px-3 py-2">
                        <span class="text-xs font-medium whitespace-nowrap">Bulk edit <span class="text-muted font-normal" data-bulk-scope></span></span>
                        <input type="number" min="0" data-bulk-stock placeholder="Stock" aria-label="Bulk stock" class="field-input !py-1.5 !text-sm w-24">
                        <input type="number" min="0" step="0.01" data-bulk-price placeholder="Price ₹" aria-label="Bulk price" class="field-input !py-1.5 !text-sm w-28">
                        <select data-bulk-status aria-label="Bulk status" class="field-input !py-1.5 !text-sm w-32">
                            <option value="">Status…</option><option value="active">Active</option><option value="inactive">Inactive</option>
                        </select>
                        <button type="button" data-bulk-apply class="text-sm px-3 py-1.5 rounded-md border border-ink hover:bg-ink hover:text-bg transition">Apply</button>
                        <span class="text-xs text-muted">Leave a field blank to keep it unchanged.</span>
                    </div>
                </div>

                <div class="overflow-auto max-h-[65vh] rounded-md border border-line" data-variant-scroll hidden>
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10 bg-surface shadow-[0_1px_0_rgb(var(--color-line))] text-left text-xs text-muted">
                            <tr>
                                <th class="px-2 py-2 font-medium w-12 text-center" title="Default variant">Default</th>
                                <th class="px-2 py-2 font-medium">Variant</th>
                                <th class="px-2 py-2 font-medium">SKU</th>
                                <th class="px-2 py-2 font-medium">Stock</th>
                                <th class="px-2 py-2 font-medium">Price</th>
                                <th class="px-2 py-2 font-medium">Status</th>
                                <th class="px-2 py-2 w-8"><span class="sr-only">Remove</span></th>
                            </tr>
                        </thead>
                        <tbody data-variant-rows class="divide-y divide-line"></tbody>
                    </table>
                    <p class="hidden px-3 py-6 text-center text-sm text-muted" data-vf-none>No variants match these filters.</p>
                </div>
            </div>

            <datalist id="attr-name-suggestions">
                <?php foreach (($attributeNames ?? []) as $an): ?><option value="<?= esc($an, 'attr') ?>"></option><?php endforeach ?>
            </datalist>
            <script type="application/json" data-variant-state><?= json_encode($variantState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
        </div>

        <!-- Add-ons: "Complete your swing" -->
        <?php
        $addonState = array_map(static fn ($a) => [
            'id'           => (int) $a['addon_product_id'],
            'name'         => $a['name'],
            'sku'          => $a['sku'],
            'price'        => price($a['price']),
            'status'       => $a['status'],
            'has_variants' => (int) $a['has_variants'] === 1,
            'image'        => $a['image'],
            'preselected'  => (int) $a['is_preselected'] === 1,
        ], $addons ?? []);
        ?>
        <div class="bg-surface rounded-lg border border-line p-5 space-y-4" data-addon-editor data-exclude="<?= (int) ($p['id'] ?? 0) ?>">
            <input type="hidden" name="addons_present" value="1">
            <div>
                <h2 class="font-medium">Add-ons <span class="text-muted font-normal">(Complete your swing)</span></h2>
                <p class="field-help !mt-0.5">Products offered alongside this one on its product page and in the cart — e.g. a hanging chain or hook kit. Customers choose the add-on's variant themselves; its default variant is pre-selected. Tick <em>Pre-select</em> for essentials the swing can't be used without.</p>
            </div>

            <div class="relative">
                <label class="field-label" for="addon-search">Add a product</label>
                <input id="addon-search" type="search" autocomplete="off" class="field-input" placeholder="Search by name or SKU…" data-addon-search
                       role="combobox" aria-expanded="false" aria-controls="addon-results" aria-autocomplete="list">
                <ul id="addon-results" role="listbox" class="hidden absolute z-20 left-0 right-0 mt-1 max-h-72 overflow-y-auto rounded-md border border-line bg-surface shadow-lift" data-addon-results></ul>
            </div>

            <p class="field-help" data-addon-empty>No add-ons yet.</p>
            <ul class="space-y-2" data-addon-list></ul>

            <script type="application/json" data-addon-state><?= json_encode($addonState, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
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

<?php if ($p): ?>
    <form method="post" action="<?= base_url('admin/products/delete/' . $p['id']) ?>"
          onsubmit="return confirm('Archive this product? It will be hidden from the catalogue but kept in the database.')"
          class="mt-4 flex justify-end">
        <?= csrf_field() ?><button type="submit" class="text-sm text-danger hover:underline">Archive product</button>
    </form>
<?php endif ?>

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

<!-- Description editor: link dialog -->
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
    .rte-dialog { border: 0; border-radius: .75rem; padding: 1.5rem; width: min(30rem, calc(100vw - 2rem)); background: rgb(var(--color-surface)); color: rgb(var(--color-ink)); box-shadow: 0 20px 50px rgb(0 0 0 / .25); }
    .rte-dialog::backdrop { background: rgb(0 0 0 / .45); overflow: hidden; overscroll-behavior: contain; }
    dialog:modal { overscroll-behavior: contain; }
</style>
<script src="<?= base_url('assets/js/admin_product_description.js') ?>?v=2"></script>
<script src="<?= base_url('assets/js/admin_variants.js') ?>?v=6"></script>
<script src="<?= base_url('assets/js/admin_addons.js') ?>?v=1"></script>
<script src="<?= base_url('assets/js/admin_images.js') ?>?v=1"></script>
<?= $this->endSection() ?>
