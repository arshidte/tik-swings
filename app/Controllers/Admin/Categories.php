<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Services\ImageUploadService;

class Categories extends BaseController
{
    public function index(): string
    {
        return $this->renderView();
    }

    public function edit(int $id): string
    {
        $model    = new CategoryModel();
        $category = $model->find($id);
        if (! $category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Category not found');
        }

        return $this->renderView($category);
    }

    private function renderView(?array $editCategory = null): string
    {
        helper(['text', 'format']);

        $categories = (new CategoryModel())->orderBy('sort_order')->orderBy('name')->findAll();

        // Product counts per category (archived products included, so the
        // number matches what deleting the category would uncategorise).
        $counts = [];
        $rows   = db_connect()->table('products')->select('category_id, COUNT(*) AS c')
            ->where('category_id IS NOT NULL', null, false)->groupBy('category_id')->get()->getResultArray();
        foreach ($rows as $r) {
            $counts[(int) $r['category_id']] = (int) $r['c'];
        }

        return view('admin/categories', [
            'title'        => $editCategory ? 'Edit Category: ' . $editCategory['name'] : 'Categories',
            'categories'   => $categories,
            'counts'       => $counts,
            'editCategory' => $editCategory,
        ]);
    }

    /**
     * Delete a category. Its products are kept and become uncategorised;
     * sub-categories (if any) move to the top level.
     */
    public function delete(int $id)
    {
        $model    = new CategoryModel();
        $category = $model->find($id);
        if (! $category) {
            return redirect()->to(site_url('admin/categories'))->with('error', 'Category not found.');
        }

        $db = db_connect();
        $db->transStart();
        // Includes archived (soft-deleted) products, so none keep a dangling category.
        $db->table('products')->where('category_id', $id)->update(['category_id' => null]);
        $db->table('categories')->where('parent_id', $id)->update(['parent_id' => null]);
        // Permanent delete: a soft-deleted row would keep its unique slug reserved.
        $model->delete($id, true);
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to(site_url('admin/categories'))->with('error', 'Could not delete the category. Please try again.');
        }

        $model->clearCache();
        cache()->delete('categories_nav');

        return redirect()->to(site_url('admin/categories'))
            ->with('success', '“' . $category['name'] . '” deleted. Its products are now uncategorised.');
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
        helper(['text', 'form']);
        $model = new CategoryModel();

        if ($id) {
            $existing = $model->find($id);
            if (! $existing) {
                return redirect()->to(site_url('admin/categories'))->with('error', 'Category not found.');
            }
        }

        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Category name is required.');
        }

        $slug = trim((string) $this->request->getPost('slug')) ?: url_title($name, '-', true);

        // Ensure unique slug (excluding the current category when updating)
        $slugCheck = $model->where('slug', $slug);
        if ($id) {
            $slugCheck->where('id !=', $id);
        }
        if ($slugCheck->first()) {
            return redirect()->back()->withInput()->with('error', 'A category with the slug “' . esc($slug) . '” already exists.');
        }

        $data = [
            'name'            => $name,
            'slug'            => $slug,
            'description'     => trim((string) $this->request->getPost('description')) ?: null,
            'sort_order'      => (int) $this->request->getPost('sort_order'),
            'featured'        => $this->request->getPost('featured') ? 1 : 0,
            'status'          => $this->request->getPost('status') === 'inactive' ? 'inactive' : 'active',
            'seo_title'       => trim((string) $this->request->getPost('seo_title')) ?: null,
            'seo_description' => trim((string) $this->request->getPost('seo_description')) ?: null,
        ];

        // Handle image removal
        if ($this->request->getPost('remove_image')) {
            $data['image'] = null;
        }

        // Handle category image file upload
        $imageFile = $this->request->getFile('image');
        if ($imageFile && $imageFile->isValid() && ! $imageFile->hasMoved()) {
            try {
                $uploadService = new ImageUploadService();
                $data['image'] = $uploadService->storeProductImage($imageFile);
            } catch (\Throwable $e) {
                return redirect()->back()->withInput()->with('error', $e->getMessage());
            }
        }

        if ($id) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }

        $model->clearCache();
        cache()->delete('categories_nav');

        return redirect()->to(site_url('admin/categories'))
            ->with('success', $id ? 'Category “' . $name . '” updated.' : 'Category “' . $name . '” created.');
    }
}
