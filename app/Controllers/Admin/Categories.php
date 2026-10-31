<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class Categories extends BaseController
{
    public function index(): string
    {
        helper('text');

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
            'title'      => 'Categories',
            'categories' => $categories,
            'counts'     => $counts,
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
            ->with('success', '"' . $category['name'] . '" deleted. Its products are now uncategorised.');
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
        helper('text');
        $model = new CategoryModel();
        $name  = trim((string) $this->request->getPost('name'));
        $slug  = trim((string) $this->request->getPost('slug')) ?: url_title($name, '-', true);

        $data = [
            'name' => $name, 'slug' => $slug,
            'description' => $this->request->getPost('description'),
            'sort_order'  => (int) $this->request->getPost('sort_order'),
            'featured'    => $this->request->getPost('featured') ? 1 : 0,
            'status'      => $this->request->getPost('status') ?: 'active',
        ];
        if (! $this->validate(['name' => 'required'])) {
            return redirect()->back()->withInput()->with('error', 'Category name is required.');
        }

        if ($id) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }
        $model->clearCache();

        return redirect()->to(site_url('admin/categories'))->with('success', 'Category saved.');
    }
}
