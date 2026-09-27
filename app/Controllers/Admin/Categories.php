<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class Categories extends BaseController
{
    public function index(): string
    {
        helper('text');

        return view('admin/categories', [
            'title'      => 'Categories',
            'categories' => (new CategoryModel())->orderBy('sort_order')->orderBy('name')->findAll(),
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
