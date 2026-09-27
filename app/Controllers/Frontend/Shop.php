<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductModel;

class Shop extends BaseController
{
    private int $perPage = 12;

    public function index()
    {
        return $this->render(null);
    }

    public function category(string $slug)
    {
        $category = (new CategoryModel())->findBySlug($slug);
        if (! $category) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->render($category);
    }

    /**
     * AJAX endpoint returning just the results partial (§20).
     */
    public function filter()
    {
        $slug     = $this->request->getGet('category');
        $category = $slug ? (new CategoryModel())->findBySlug($slug) : null;
        $result   = $this->query($category);

        return view('frontend/partials/product_results', [
            'products' => $result['products'],
            'pager'    => $result['pager'],
            'total'    => $result['total'],
            'page'     => $result['page'],
            'perPage'  => $this->perPage,
            'wishIds'  => wishlist_ids(),
        ]);
    }

    private function render(?array $category): string
    {
        $result     = $this->query($category);
        $categories = (new CategoryModel())->forNavigation();

        $title = $category ? $category['name'] : 'All Swings';

        return view('frontend/shop', [
            'meta' => [
                'title'       => ($category ? ($category['seo_title'] ?: $category['name']) : 'Shop All Swings') . ' — ' . store_name(),
                'description' => $category ? ($category['seo_description'] ?: $category['description']) : 'Browse the full collection of handcrafted wooden swings.',
            ],
            'schema'         => $this->breadcrumbSchema($category),
            'category'       => $category,
            'title'          => $title,
            'categories'     => $categories,
            'woodTypes'      => $this->distinct('wood_type'),
            'finishes'       => $this->distinct('finish'),
            'products'       => $result['products'],
            'pager'          => $result['pager'],
            'total'          => $result['total'],
            'page'           => $result['page'],
            'perPage'        => $this->perPage,
            'filters'        => $this->activeFilters(),
            'sort'           => $this->request->getGet('sort') ?: 'featured',
            'priceBounds'    => $this->priceBounds(),
            'wishIds'        => wishlist_ids(),
        ]);
    }

    /**
     * Build and run the filtered product query. Returns products + pager.
     */
    private function query(?array $category): array
    {
        $model = new ProductModel();
        $model->cardColumns()->active();

        if ($category) {
            $model->where('products.category_id', $category['id']);
        } elseif ($slug = $this->request->getGet('category')) {
            if ($cat = (new CategoryModel())->findBySlug($slug)) {
                $model->where('products.category_id', $cat['id']);
            }
        }

        // Filters — all validated/whitelisted (§40).
        $get = $this->request->getGet();

        if (! empty($get['wood_type'])) {
            $model->whereIn('products.wood_type', (array) $get['wood_type']);
        }
        if (! empty($get['finish'])) {
            $model->whereIn('products.finish', (array) $get['finish']);
        }
        if (isset($get['min_price']) && is_numeric($get['min_price'])) {
            $model->where('products.price >=', (float) $get['min_price']);
        }
        if (isset($get['max_price']) && is_numeric($get['max_price'])) {
            $model->where('products.price <=', (float) $get['max_price']);
        }
        if (! empty($get['availability']) && $get['availability'] === 'in_stock') {
            $model->where('products.stock_status', 'in_stock');
        }
        if (! empty($get['customizable'])) {
            $model->where('products.is_customizable', 1);
        }

        // Sort — whitelist.
        switch ($get['sort'] ?? 'featured') {
            case 'price_low':  $model->orderBy('products.price', 'ASC'); break;
            case 'price_high': $model->orderBy('products.price', 'DESC'); break;
            case 'newest':     $model->orderBy('products.created_at', 'DESC'); break;
            case 'rating':     $model->orderBy('products.rating_avg', 'DESC'); break;
            default:           $model->orderBy('products.featured', 'DESC')->orderBy('products.created_at', 'DESC');
        }

        $page     = max(1, (int) ($get['page'] ?? 1));
        $products = $model->paginate($this->perPage, 'default', $page);
        $products = $model->withPrimaryImage($products);

        return [
            'products' => $products,
            'pager'    => $model->pager,
            'total'    => $model->pager->getTotal('default'),
            'page'     => $page,
        ];
    }

    private function distinct(string $column): array
    {
        $rows = (new ProductModel())->select($column)->where('status', 'active')
            ->where("{$column} IS NOT NULL")->groupBy($column)->orderBy($column, 'ASC')->findAll();

        return array_values(array_filter(array_column($rows, $column)));
    }

    private function priceBounds(): array
    {
        $row = (new ProductModel())->select('MIN(price) AS lo, MAX(price) AS hi')->where('status', 'active')->first();

        return ['min' => (int) floor(($row['lo'] ?? 0) / 1000) * 1000, 'max' => (int) ceil(($row['hi'] ?? 100000) / 1000) * 1000];
    }

    private function activeFilters(): array
    {
        $get = $this->request->getGet();

        return [
            'wood_type'    => (array) ($get['wood_type'] ?? []),
            'finish'       => (array) ($get['finish'] ?? []),
            'min_price'    => $get['min_price'] ?? null,
            'max_price'    => $get['max_price'] ?? null,
            'availability' => $get['availability'] ?? null,
            'customizable' => $get['customizable'] ?? null,
        ];
    }

    private function breadcrumbSchema(?array $category): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => base_url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => base_url('shop')],
        ];
        if ($category) {
            $items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $category['name'], 'item' => base_url('category/' . $category['slug'])];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
