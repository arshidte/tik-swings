<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductImageModel;
use App\Models\ProductModel;
use App\Models\ProductUserMediaModel;
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
            'title'      => 'New Product',
            'product'    => null,
            'images'     => [],
            'categories' => (new CategoryModel())->active()->orderBy('name')->findAll(),
        ]);
    }

    public function edit(int $id): string
    {
        $product = $this->products->find($id);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('admin/products/form', [
            'title'      => 'Edit Product',
            'product'    => $product,
            'images'     => (new ProductImageModel())->forProduct($id),
            'userMedia'  => (new ProductUserMediaModel())->forProductAdmin($id),
            'categories' => (new CategoryModel())->active()->orderBy('name')->findAll(),
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
