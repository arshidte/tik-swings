<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductModel;

class Search extends BaseController
{
    /**
     * Debounced suggestion endpoint (§21). Returns products + categories.
     */
    public function suggest()
    {
        $q = trim((string) $this->request->getGet('q'));
        if (mb_strlen($q) < 2) {
            return $this->jsonSuccess('', ['products' => [], 'categories' => []]);
        }

        $model    = new ProductModel();
        $products = $model->cardColumns()->active()
            ->groupStart()
            ->like('products.name', $q)
            ->orLike('products.short_description', $q)
            ->orLike('products.wood_type', $q)
            ->groupEnd()
            ->orderBy('products.featured', 'DESC')
            ->findAll(6);
        $products = $model->withPrimaryImage($products);

        $categories = (new CategoryModel())->active()->like('name', $q)->findAll(4);

        return $this->jsonSuccess('', [
            'products' => array_map(static fn ($p) => [
                'name'  => $p['name'],
                'price' => (float) $p['price'],
                'image' => product_image($p['primary_image'] ?? null),
                'url'   => site_url('product/' . $p['slug']),
            ], $products),
            'categories' => array_map(static fn ($c) => ['name' => $c['name'], 'slug' => $c['slug']], $categories),
        ]);
    }

    /**
     * Full search results page (server-rendered).
     */
    public function index()
    {
        $q     = trim((string) $this->request->getGet('q'));
        $model = new ProductModel();
        $products = [];
        if ($q !== '') {
            $products = $model->cardColumns()->active()
                ->groupStart()
                ->like('products.name', $q)
                ->orLike('products.short_description', $q)
                ->orLike('products.description', $q)
                ->orLike('products.wood_type', $q)
                ->groupEnd()
                ->findAll(48);
            $products = $model->withPrimaryImage($products);
        }

        return view('frontend/search', [
            'meta'     => ['title' => ($q !== '' ? "Search: {$q}" : 'Search') . ' — ' . store_name()],
            'query'    => $q,
            'products' => $products,
            'wishIds'  => wishlist_ids(),
        ]);
    }
}
