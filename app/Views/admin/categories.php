<?= $this->extend('admin/layout') ?>

<?= $this->section('content') ?>
<?php
$isEdit = ! empty($editCategory);
$activeId = $isEdit ? (int) $editCategory['id'] : null;
?>
<div class="grid lg:grid-cols-3 gap-6 items-start">
    <!-- Category list -->
    <div class="lg:col-span-2 bg-surface rounded-lg border border-line overflow-hidden shadow-xs">
        <div class="p-4 border-b border-line flex items-center justify-between bg-sand/20">
            <div>
                <h2 class="font-medium text-ink">All Categories</h2>
                <p class="text-xs text-muted mt-0.5"><?= count($categories) ?> categor<?= count($categories) === 1 ? 'y' : 'ies' ?> configured</p>
            </div>
            <?php if ($isEdit): ?>
                <a href="<?= base_url('admin/categories') ?>" class="text-xs font-medium text-wood hover:underline flex items-center gap-1">
                    <span>+</span> Add new category
                </a>
            <?php endif ?>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sand/40 text-left text-muted text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 font-medium">Category</th>
                        <th class="px-4 py-3 font-medium">Slug</th>
                        <th class="px-4 py-3 font-medium text-center">Order</th>
                        <th class="px-4 py-3 font-medium text-center">Products</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <?php foreach ($categories as $c): ?>
                        <?php
                        $isSelected = ($activeId === (int) $c['id']);
                        $n = $counts[(int) $c['id']] ?? 0;
                        ?>
                        <tr class="transition <?= $isSelected ? 'bg-wood/10 border-l-4 border-wood' : 'hover:bg-sand/30' ?>" id="category-row-<?= $c['id'] ?>">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <?php if (! empty($c['image'])): ?>
                                        <img src="<?= esc(product_image($c['image']), 'attr') ?>" alt="" class="w-9 h-9 rounded object-cover bg-sand shrink-0 border border-line">
                                    <?php else: ?>
                                        <div class="w-9 h-9 rounded bg-sand/60 flex items-center justify-center text-muted shrink-0 border border-line">
                                            <svg class="w-4 h-4 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                        </div>
                                    <?php endif ?>
                                    <div class="min-w-0">
                                        <div class="font-medium text-ink flex items-center gap-2">
                                            <span><?= esc($c['name']) ?></span>
                                            <?php if ($c['featured']): ?>
                                                <span class="badge-soft text-[10px] py-0.5 px-1.5">Featured</span>
                                            <?php endif ?>
                                        </div>
                                        <?php if (! empty($c['description'])): ?>
                                            <p class="text-xs text-muted truncate max-w-xs"><?= esc($c['description']) ?></p>
                                        <?php endif ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-muted font-mono"><?= esc($c['slug']) ?></td>
                            <td class="px-4 py-3 text-center tabular-nums text-muted"><?= (int) $c['sort_order'] ?></td>
                            <td class="px-4 py-3 text-center tabular-nums font-medium"><?= $n ?></td>
                            <td class="px-4 py-3">
                                <span class="badge <?= $c['status'] === 'active' ? 'bg-success/10 text-success' : 'bg-sand text-wood-dark' ?>">
                                    <?= esc(ucfirst($c['status'])) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="<?= base_url('admin/categories/edit/' . $c['id']) ?>"
                                   class="text-wood hover:underline font-medium inline-flex items-center gap-1"
                                   data-edit-category='<?= esc(json_encode($c), 'attr') ?>'>
                                    Edit
                                </a>
                                <span class="text-muted/60 mx-1.5">·</span>
                                <form method="post" action="<?= base_url('admin/categories/delete/' . $c['id']) ?>" class="inline"
                                      onsubmit="return confirm('Delete “<?= esc($c['name'], 'js') ?>”?\n\n<?= $n > 0 ? esc($n . ' product' . ($n === 1 ? '' : 's') . ' will be kept and become uncategorised.', 'js') : 'It has no products.' ?>')">
                                    <?= csrf_field() ?>
                                    <button class="text-danger hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="6" class="px-4 py-10 text-center text-muted">No categories yet.</td></tr>
                    <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Category Form (Add / Edit) -->
    <div class="bg-surface rounded-lg border border-line p-5 shadow-xs sticky top-[calc(var(--header-height)+1.5rem)]" id="category-form-card">
        <div class="flex items-center justify-between mb-4 border-b border-line pb-3">
            <div>
                <h2 class="font-medium text-base text-ink" data-form-title>
                    <?= $isEdit ? 'Edit Category' : 'Add Category' ?>
                </h2>
                <p class="text-xs text-muted mt-0.5 truncate max-w-[210px]" data-form-subtitle>
                    <?= $isEdit ? esc($editCategory['name']) : 'Create a new product grouping' ?>
                </p>
            </div>
            <a href="<?= base_url('admin/categories') ?>"
               class="<?= $isEdit ? 'inline-flex' : 'hidden' ?> text-xs text-muted hover:text-ink border border-line rounded px-2.5 py-1 transition"
               data-cancel-btn>
                Cancel
            </a>
        </div>

        <form method="post"
              action="<?= $isEdit ? base_url('admin/categories/update/' . $editCategory['id']) : base_url('admin/categories/store') ?>"
              enctype="multipart/form-data"
              class="space-y-3.5"
              data-category-form>
            <?= csrf_field() ?>

            <div>
                <label class="field-label" for="name">Name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="field-input" required
                       placeholder="e.g. Balcony Swings"
                       value="<?= esc(old('name', $editCategory['name'] ?? '')) ?>">
            </div>

            <div>
                <label class="field-label" for="slug">Slug (URL)</label>
                <input id="slug" name="slug" class="field-input"
                       placeholder="e.g. balcony-swings (auto-generated if empty)"
                       value="<?= esc(old('slug', $editCategory['slug'] ?? '')) ?>">
            </div>

            <div>
                <label class="field-label" for="description">Description</label>
                <textarea id="description" name="description" rows="2" class="field-input"
                          placeholder="Brief description for customers and category banner…"><?= esc(old('description', $editCategory['description'] ?? '')) ?></textarea>
            </div>

            <!-- Image upload with preview -->
            <div>
                <label class="field-label" for="image">Category Image</label>
                <div data-image-preview-container class="<?= (! empty($editCategory['image'])) ? '' : 'hidden' ?> mb-2">
                    <div class="flex items-center gap-3 p-2 bg-sand/30 border border-line rounded-md">
                        <img src="<?= ! empty($editCategory['image']) ? esc(product_image($editCategory['image']), 'attr') : '' ?>"
                             alt="" data-image-preview class="w-12 h-12 rounded object-cover bg-surface border border-line shrink-0">
                        <div class="flex-1 min-w-0 text-xs">
                            <p class="truncate text-muted font-mono text-[11px]" data-image-name>
                                <?= ! empty($editCategory['image']) ? esc(basename($editCategory['image'])) : '' ?>
                            </p>
                            <label class="inline-flex items-center gap-1.5 mt-1 text-danger cursor-pointer hover:underline">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-line text-danger" data-remove-image>
                                <span>Remove current image</span>
                            </label>
                        </div>
                    </div>
                </div>
                <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="field-input text-xs">
                <p class="text-[11px] text-muted mt-1">Recommended: 800×600 WebP, JPG or PNG (up to 6MB)</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="field-label" for="status">Status</label>
                    <select id="status" name="status" class="field-input text-sm">
                        <option value="active" <?= old('status', $editCategory['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Visible)</option>
                        <option value="inactive" <?= old('status', $editCategory['status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="sort_order">Sort Order</label>
                    <input id="sort_order" name="sort_order" type="number" class="field-input text-sm"
                           value="<?= (int) old('sort_order', $editCategory['sort_order'] ?? 0) ?>">
                </div>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm select-none cursor-pointer">
                    <input type="checkbox" name="featured" value="1" class="w-4 h-4 rounded border-line text-wood focus:ring-wood/30"
                           <?= old('featured', $editCategory['featured'] ?? 0) ? 'checked' : '' ?>>
                    <span class="font-medium text-ink">Feature on homepage</span>
                </label>
                <p class="text-[11px] text-muted ml-6">Shows in the featured categories section on the homepage.</p>
            </div>

            <!-- Optional SEO details -->
            <details class="text-xs group border-t border-line pt-2 mt-2">
                <summary class="cursor-pointer text-muted hover:text-ink font-medium select-none py-1 flex items-center justify-between">
                    <span>SEO settings (optional)</span>
                    <span class="text-muted/60 group-open:rotate-180 transition-transform">▾</span>
                </summary>
                <div class="space-y-3 pt-2">
                    <div>
                        <label class="field-label text-xs" for="seo_title">SEO Meta Title</label>
                        <input id="seo_title" name="seo_title" class="field-input text-xs"
                               placeholder="e.g. Handcrafted Balcony Swings | TIK Swings"
                               value="<?= esc(old('seo_title', $editCategory['seo_title'] ?? '')) ?>">
                    </div>
                    <div>
                        <label class="field-label text-xs" for="seo_description">SEO Meta Description</label>
                        <textarea id="seo_description" name="seo_description" rows="2" class="field-input text-xs"
                                  placeholder="Meta description shown in search engine results…"><?= esc(old('seo_description', $editCategory['seo_description'] ?? '')) ?></textarea>
                    </div>
                </div>
            </details>

            <div class="pt-2 flex items-center gap-2">
                <button type="submit" class="btn-primary flex-1 btn-sm" data-submit-btn>
                    <?= $isEdit ? 'Update Category' : 'Add Category' ?>
                </button>
                <a href="<?= base_url('admin/categories') ?>"
                   class="<?= $isEdit ? 'inline-flex' : 'hidden' ?> btn-outline btn-sm"
                   data-cancel-btn-bottom>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-category-form]');
    if (!form) return;

    const formCard = document.getElementById('category-form-card');
    const formTitle = document.querySelector('[data-form-title]');
    const formSubtitle = document.querySelector('[data-form-subtitle]');
    const cancelBtns = document.querySelectorAll('[data-cancel-btn], [data-cancel-btn-bottom]');
    const submitBtn = document.querySelector('[data-submit-btn]');
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    const descInput = document.getElementById('description');
    const sortInput = document.getElementById('sort_order');
    const statusInput = document.getElementById('status');
    const featuredInput = form.querySelector('[name="featured"]');
    const seoTitleInput = document.getElementById('seo_title');
    const seoDescInput = document.getElementById('seo_description');
    const imgPreviewContainer = document.querySelector('[data-image-preview-container]');
    const imgPreview = document.querySelector('[data-image-preview]');
    const imgName = document.querySelector('[data-image-name]');
    const removeImgCheckbox = document.querySelector('[data-remove-image]');
    const fileInput = document.getElementById('image');

    const baseUrl = '<?= rtrim(base_url(), '/') ?>';

    function setEditMode(cat) {
        form.action = `${baseUrl}/admin/categories/update/${cat.id}`;
        formTitle.textContent = 'Edit Category';
        formSubtitle.textContent = cat.name || '';
        submitBtn.textContent = 'Update Category';
        cancelBtns.forEach(b => b.classList.remove('hidden'));

        nameInput.value = cat.name || '';
        slugInput.value = cat.slug || '';
        descInput.value = cat.description || '';
        sortInput.value = cat.sort_order || 0;
        statusInput.value = cat.status || 'active';
        featuredInput.checked = (cat.featured == 1 || cat.featured === true);
        if (seoTitleInput) seoTitleInput.value = cat.seo_title || '';
        if (seoDescInput) seoDescInput.value = cat.seo_description || '';

        // Handle image preview
        if (cat.image) {
            imgPreviewContainer?.classList.remove('hidden');
            const imgUrl = cat.image.startsWith('http') || cat.image.startsWith('/')
                ? cat.image
                : `${baseUrl}/${cat.image}`;
            if (imgPreview) imgPreview.src = imgUrl;
            if (imgName) imgName.textContent = cat.image.split('/').pop();
            if (removeImgCheckbox) removeImgCheckbox.checked = false;
        } else {
            imgPreviewContainer?.classList.add('hidden');
        }
        if (fileInput) fileInput.value = '';

        // Highlight active table row
        document.querySelectorAll('tbody tr').forEach(tr => {
            tr.classList.remove('bg-wood/10', 'border-l-4', 'border-wood');
        });
        const activeRow = document.getElementById(`category-row-${cat.id}`);
        if (activeRow) {
            activeRow.classList.add('bg-wood/10', 'border-l-4', 'border-wood');
        }

        // Smooth scroll on smaller screens
        if (window.innerWidth < 1024) {
            formCard.scrollIntoView({ behavior: 'smooth' });
        }
        nameInput.focus();
    }

    // Intercept clicks on edit buttons for instant client-side responsiveness
    document.querySelectorAll('[data-edit-category]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            // If user holds cmd/ctrl or middle click, allow opening in new tab
            if (e.metaKey || e.ctrlKey || e.which === 2) return;

            e.preventDefault();
            try {
                const cat = JSON.parse(btn.getAttribute('data-edit-category'));
                setEditMode(cat);
                history.pushState(null, '', `${baseUrl}/admin/categories/edit/${cat.id}`);
            } catch (err) {
                // Fallback to normal navigation
                window.location.href = btn.href;
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
