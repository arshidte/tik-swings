<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductAttributeModel;
use App\Models\ProductAttributeValueModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductOptionValueModel;
use App\Models\ProductUserMediaModel;
use App\Models\ProductVariantModel;
use App\Services\ImageUploadService;

class Products extends BaseController
{
    private ProductModel $products;

    public function __construct()
    {
        $this->products = new ProductModel();
        helper('text');
    }

    public function index(): string
    {
        $q     = trim((string) $this->request->getGet('q'));
        $model = $this->products->orderBy('id', 'DESC');
        if ($q !== '') {
            $model->groupStart()->like('name', $q)->orLike('sku', $q)->groupEnd();
        }
        $rows = $model->paginate(15);

        return view('admin/products/index', [
            'title'    => 'Products',
            'products' => $this->products->withPrimaryImage($rows),
            'pager'    => $this->products->pager,
            'q'        => $q,
        ]);
    }

    public function create(): string
    {
        return view('admin/products/form', [
            'title'          => 'New Product',
            'product'        => null,
            'images'         => [],
            'categories'     => (new CategoryModel())->active()->orderBy('name')->findAll(),
            'variants'       => [],
            'options'        => [],
            'optionAdjust'   => [],
            'optionBadges'   => [],
            'attributeNames' => (new ProductAttributeModel())->names(),
        ]);
    }

    public function edit(int $id): string
    {
        $product = $this->products->find($id);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('admin/products/form', [
            'title'          => 'Edit Product',
            'product'        => $product,
            'images'         => (new ProductImageModel())->forProduct($id),
            'userMedia'      => (new ProductUserMediaModel())->forProductAdmin($id),
            'categories'     => (new CategoryModel())->active()->orderBy('name')->findAll(),
            'variants'       => (new ProductVariantModel())->forProductAdmin($id),
            'options'        => (new ProductOptionModel())->forProduct($id),
            'optionAdjust'   => (new ProductOptionValueModel())->forProduct($id),
            'optionBadges'   => (new ProductOptionValueModel())->badgesForProduct($id),
            'attributeNames' => (new ProductAttributeModel())->names(),
        ]);
    }

    public function store()
    {
        return $this->save(null);
    }

    public function update(int $id)
    {
        return $this->save($id);
    }

    private function save(?int $id)
    {
        $data = $this->collect($id);

        $rules = [
            'name'  => 'required|max_length[190]',
            'sku'   => "required|max_length[80]|is_unique[products.sku,id,{$id}]",
            'price' => 'required|numeric|greater_than_equal_to[0]',
            'category_id' => 'permit_empty|is_natural_no_zero',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        if ($id) {
            $this->products->update($id, $data);
        } else {
            $id = $this->products->insert($data, true);
        }

        // Handle any uploaded images on save.
        $this->handleUploads($id);

        // Persist attributes / values / variants from the editor.
        $this->saveVariants($id);

        cache()->delete('categories_nav');
        (new CategoryModel())->clearCache();

        return redirect()->to(site_url('admin/products/edit/' . $id))->with('success', 'Product saved.');
    }

    private function collect(?int $id): array
    {
        $name = trim((string) $this->request->getPost('name'));
        $slug = trim((string) $this->request->getPost('slug'));
        if ($slug === '') {
            $slug = url_title($name, '-', true);
        }
        // ensure unique slug
        $exists = $this->products->where('slug', $slug);
        if ($id) {
            $exists->where('id !=', $id);
        }
        if ($exists->first()) {
            $slug .= '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        return [
            'name'              => $name,
            'slug'              => $slug,
            'sku'               => trim((string) $this->request->getPost('sku')),
            'category_id'       => $this->request->getPost('category_id') ?: null,
            'short_description' => $this->request->getPost('short_description'),
            'description'       => $this->request->getPost('description'),
            'price'             => (float) $this->request->getPost('price'),
            'compare_price'     => $this->request->getPost('compare_price') ?: null,
            'cost_price'        => $this->request->getPost('cost_price') ?: null,
            'stock'             => (int) $this->request->getPost('stock'),
            'stock_status'      => $this->request->getPost('stock_status') ?: 'in_stock',
            'wood_type'         => $this->request->getPost('wood_type'),
            'finish'            => $this->request->getPost('finish'),
            'material'          => $this->request->getPost('material'),
            'dimensions'        => $this->request->getPost('dimensions'),
            'weight_capacity'   => $this->request->getPost('weight_capacity'),
            'warranty'          => $this->request->getPost('warranty'),
            'delivery_estimate' => $this->request->getPost('delivery_estimate'),
            'installation_available' => $this->request->getPost('installation_available') ? 1 : 0,
            'is_customizable'   => $this->request->getPost('is_customizable') ? 1 : 0,
            'featured'          => $this->request->getPost('featured') ? 1 : 0,
            'status'            => $this->request->getPost('status') ?: 'active',
            'seo_title'         => $this->request->getPost('seo_title'),
            'seo_description'   => $this->request->getPost('seo_description'),
            'seo_keywords'      => $this->request->getPost('seo_keywords'),
        ];
    }

    /**
     * Persist the admin variant editor: find-or-create global attributes and
     * values (reused by name), store this product's per-option price settings
     * and per-value adjustments, then rebuild its variants. Each variant's
     * price is derived from the base price plus the adjustments of its selected
     * price-affecting values. No-op unless the form rendered the section.
     */
    private function saveVariants(int $productId): void
    {
        if (! $this->request->getPost('variants_present')) {
            return;
        }

        $attrModel    = new ProductAttributeModel();
        $valueModel   = new ProductAttributeValueModel();
        $variantModel = new ProductVariantModel();
        $db           = db_connect();

        $basePrice   = (float) $this->request->getPost('price');
        $baseCompare = ($c = $this->request->getPost('compare_price')) !== '' && $c !== null ? (float) $c : null;

        // 1. Resolve attributes + values; capture price behaviour -----------
        $attrIdByTemp     = [];   // attribute temp key => attribute_id
        $valueIdByTemp    = [];   // value temp key     => attribute_value_id
        $valueLabel       = [];   // value temp key     => label
        $valueAdjustByTmp = [];   // value temp key     => price adjustment (0 unless its option affects price)
        $optionRows       = [];   // product_options rows
        $optionValueRows  = [];   // product_option_values rows

        $aSort = 0;
        foreach ((array) $this->request->getPost('attributes') as $aTemp => $attr) {
            $name = trim((string) ($attr['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $attrId  = $attrModel->findOrCreate($name, $aSort);
            $affects = ! empty($attr['affects_price']);
            $attrIdByTemp[$aTemp] = $attrId;
            $optionRows[] = [
                'product_id' => $productId, 'attribute_id' => $attrId,
                'affects_price' => $affects ? 1 : 0, 'sort_order' => $aSort,
            ];
            $aSort++;

            $vSort = 0;
            foreach ((array) ($attr['values'] ?? []) as $vTemp => $val) {
                $label = trim((string) ($val['label'] ?? ''));
                if ($label === '') {
                    continue;
                }
                $swatch  = trim((string) ($val['swatch'] ?? '')) ?: null;
                $valueId = $valueModel->findOrCreate($attrId, $label, $swatch, $vSort++);
                $adjust  = $affects ? (float) ($val['adjust'] ?? 0) : 0.0;
                $badge   = mb_substr(trim((string) ($val['badge'] ?? '')), 0, 40);

                $valueIdByTemp[$vTemp]    = $valueId;
                $valueLabel[$vTemp]       = $label;
                $valueAdjustByTmp[$vTemp] = $adjust;
                // Persist a per-product value row when it carries a price
                // adjustment and/or a display badge (e.g. "Most Popular").
                if ($adjust != 0.0 || $badge !== '') {
                    $optionValueRows[] = [
                        'product_id'         => $productId,
                        'attribute_value_id' => $valueId,
                        'price_adjust'       => $adjust,
                        'badge'              => $badge !== '' ? $badge : null,
                    ];
                }
            }
        }

        // 2. Build variant payloads; derive price from base + adjustments ----
        $payloads    = [];   // variant temp key => ['variant' => [...], 'combo' => [attrId => valueId]]
        $skus        = [];
        $defaultTemp = (string) $this->request->getPost('variant_default');

        foreach ((array) $this->request->getPost('variants') as $vTemp => $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $combo   = [];
            $labels  = [];
            $adjSum  = 0.0;
            foreach ((array) ($row['combo'] ?? []) as $aTemp => $valTemp) {
                if (! isset($attrIdByTemp[$aTemp], $valueIdByTemp[$valTemp])) {
                    continue;
                }
                $combo[$attrIdByTemp[$aTemp]] = $valueIdByTemp[$valTemp];
                $labels[] = $valueLabel[$valTemp];
                $adjSum  += $valueAdjustByTmp[$valTemp] ?? 0.0;
            }
            if ($combo === []) {
                continue;
            }

            // Use the row's explicit price when provided (supports non-additive
            // pricing); otherwise derive it from base price + adjustments.
            $postedPrice = $row['price'] ?? '';
            $price       = ($postedPrice !== '' && $postedPrice !== null)
                ? max(0, (float) $postedPrice)
                : max(0, $basePrice + $adjSum);

            $skus[]           = $sku;
            $payloads[$vTemp] = [
                'variant' => [
                    'product_id'    => $productId,
                    'sku'           => $sku,
                    'name'          => implode(' / ', $labels),
                    'price'         => $price,
                    'compare_price' => $baseCompare !== null ? max(0, $baseCompare + $adjSum) : null,
                    'stock'         => (int) ($row['stock'] ?? 0),
                    'is_default'    => 0,
                    'status'        => ($row['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
                ],
                'combo' => $combo,
            ];
        }

        // 3. Guard against SKU clashes BEFORE destroying existing data ------
        $dupes = array_unique(array_diff_assoc($skus, array_unique($skus)));
        if ($dupes) {
            session()->setFlashdata('error', 'Variant SKUs must be unique: ' . implode(', ', $dupes));

            return;
        }
        if ($skus) {
            $clash = $variantModel->whereIn('sku', $skus)->where('product_id !=', $productId)->findColumn('sku');
            if ($clash) {
                session()->setFlashdata('error', 'These variant SKUs already belong to other products: ' . implode(', ', $clash));

                return;
            }
        }

        // Ensure exactly one default variant.
        if ($payloads && ! isset($payloads[$defaultTemp])) {
            $defaultTemp = (string) array_key_first($payloads);
        }

        // 4. Persist options + adjustments, then rebuild variants -----------
        (new ProductOptionModel())->replaceForProduct($productId, $optionRows);
        (new ProductOptionValueModel())->replaceForProduct($productId, $optionValueRows);

        $oldIds = array_column(
            $db->table('product_variants')->select('id')->where('product_id', $productId)->get()->getResultArray(),
            'id'
        );
        if ($oldIds) {
            $db->table('product_variant_attributes')->whereIn('variant_id', $oldIds)->delete();
            $db->table('product_variants')->where('product_id', $productId)->delete();
        }

        foreach ($payloads as $vTemp => $data) {
            $variant               = $data['variant'];
            $variant['is_default'] = $vTemp === $defaultTemp ? 1 : 0;
            $variantId             = (int) $variantModel->insert($variant, true);
            foreach ($data['combo'] as $attrId => $valueId) {
                $db->table('product_variant_attributes')->insert([
                    'variant_id'         => $variantId,
                    'attribute_id'       => $attrId,
                    'attribute_value_id' => $valueId,
                ]);
            }
        }

        // 5. Flag whether the product carries variants ----------------------
        $this->products->update($productId, ['has_variants' => $payloads ? 1 : 0]);
    }

    private function handleUploads(int $productId): void
    {
        $files = $this->request->getFiles();
        if (empty($files['images'])) {
            return;
        }
        $uploader = new ImageUploadService();
        $imgModel = new ProductImageModel();
        $hasPrimary = $imgModel->where('product_id', $productId)->where('is_primary', 1)->countAllResults() > 0;
        $order = (int) $imgModel->where('product_id', $productId)->selectMax('sort_order')->get()->getRow('sort_order');

        foreach ($files['images'] as $file) {
            if (! $file->isValid()) {
                continue;
            }
            try {
                $path = $uploader->storeProductImage($file);
                $imgModel->insert([
                    'product_id' => $productId, 'image' => $path, 'image_type' => 'product',
                    'alt_text' => null, 'sort_order' => ++$order, 'is_primary' => $hasPrimary ? 0 : 1,
                ]);
                $hasPrimary = true;
            } catch (\RuntimeException $e) {
                session()->setFlashdata('error', $e->getMessage());
            }
        }
    }

    public function uploadImages(int $id)
    {
        if (! $this->products->find($id)) {
            return $this->jsonError('Product not found.', 404);
        }
        $this->handleUploads($id);

        return redirect()->to(site_url('admin/products/edit/' . $id))->with('success', 'Images uploaded.');
    }

    public function delete(int $id)
    {
        $this->products->delete($id); // soft delete
        (new CategoryModel())->clearCache();

        return redirect()->to(site_url('admin/products'))->with('success', 'Product archived.');
    }

    // ---------------------------------------------------------------------
    // Customer Gallery — user-uploaded photos & videos (product_user_media)
    // ---------------------------------------------------------------------

    /** Upload one or more customer photos/videos for a product. */
    public function uploadUserMedia(int $id)
    {
        if (! $this->products->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $media   = new ProductUserMediaModel();
        $author  = trim((string) $this->request->getPost('author_name')) ?: null;
        $caption = trim((string) $this->request->getPost('caption')) ?: null;
        $order   = $media->nextSortOrder($id);
        $files   = $this->request->getFiles();
        $saved   = 0;

        foreach ($files['media'] ?? [] as $file) {
            if (! $file->isValid()) {
                continue;
            }
            $mime = $file->getMimeType();
            try {
                if (str_starts_with($mime, 'video/')) {
                    $path = $this->storeUserVideo($file);
                    $type = 'video';
                } else {
                    $path = (new ImageUploadService())->storeProductImage($file);
                    $type = 'image';
                }
                $media->insert([
                    'product_id'  => $id,
                    'media_type'  => $type,
                    'media'       => $path,
                    'author_name' => $author,
                    'caption'     => $caption,
                    'sort_order'  => $order++,
                    'status'      => 'visible',
                ]);
                $saved++;
            } catch (\RuntimeException $e) {
                session()->setFlashdata('error', $e->getMessage());
            }
        }

        return redirect()->to(site_url('admin/products/edit/' . $id) . '#customer-gallery')
            ->with('success', $saved ? "Added {$saved} customer media item(s)." : 'No media added.');
    }

    /** Save captions, author, order and visibility for existing media. */
    public function saveUserMedia(int $id)
    {
        if (! $this->products->find($id)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $media = new ProductUserMediaModel();
        $rows  = (array) $this->request->getPost('items');
        foreach ($rows as $mediaId => $row) {
            $item = $media->where('product_id', $id)->find((int) $mediaId);
            if (! $item) {
                continue;
            }
            $media->update((int) $mediaId, [
                'author_name' => trim((string) ($row['author_name'] ?? '')) ?: null,
                'caption'     => trim((string) ($row['caption'] ?? '')) ?: null,
                'sort_order'  => (int) ($row['sort_order'] ?? 0),
                'status'      => ($row['status'] ?? 'visible') === 'hidden' ? 'hidden' : 'visible',
            ]);
        }

        return redirect()->to(site_url('admin/products/edit/' . $id) . '#customer-gallery')
            ->with('success', 'Customer gallery updated.');
    }

    /** Delete a single customer media item (and its uploaded file). */
    public function deleteUserMedia(int $id, int $mediaId)
    {
        $media = new ProductUserMediaModel();
        $item  = $media->where('product_id', $id)->find($mediaId);
        if ($item) {
            // Remove the file only if it lives in our uploads directory.
            if (str_starts_with((string) $item['media'], 'uploads/')) {
                $abs = FCPATH . $item['media'];
                if (is_file($abs)) {
                    @unlink($abs);
                }
            }
            $media->delete($mediaId);
        }

        return redirect()->to(site_url('admin/products/edit/' . $id) . '#customer-gallery')
            ->with('success', 'Media removed.');
    }

    /** Validate and store an uploaded customer video, returning its public path. */
    private function storeUserVideo(\CodeIgniter\HTTP\Files\UploadedFile $file): string
    {
        $allowedMime = ['video/mp4', 'video/webm', 'video/quicktime'];
        $allowedExt  = ['mp4', 'webm', 'mov', 'm4v'];
        $maxBytes    = 64_000_000; // 64 MB

        if ($file->getSize() > $maxBytes) {
            throw new \RuntimeException('Video is too large (max 64 MB).');
        }
        if (! in_array($file->getMimeType(), $allowedMime, true)) {
            throw new \RuntimeException('Only MP4, WebM or MOV videos are allowed.');
        }
        if (! in_array(strtolower($file->getExtension()), $allowedExt, true)) {
            throw new \RuntimeException('Unsupported video extension.');
        }

        $dir = FCPATH . 'uploads/products';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(8)) . '-' . time() . '.' . strtolower($file->getExtension());
        $file->move($dir, $name);

        return 'uploads/products/' . $name;
    }
}
