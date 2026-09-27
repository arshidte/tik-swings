<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'parent_id', 'name', 'slug', 'description', 'image', 'icon',
        'sort_order', 'featured', 'status', 'seo_title', 'seo_description',
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'name' => 'required|max_length[150]',
        'slug' => 'required|max_length[170]',
    ];

    public function active()
    {
        return $this->where('status', 'active');
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('status', 'active')->first();
    }

    /**
     * Active categories with a live product count, ordered for display (cached).
     */
    public function forNavigation(): array
    {
        return cache()->remember('categories_nav', 1800, function () {
            return $this->select('categories.*, COUNT(products.id) AS product_count')
                ->join('products', 'products.category_id = categories.id AND products.status = "active" AND products.deleted_at IS NULL', 'left')
                ->where('categories.status', 'active')
                ->where('categories.deleted_at', null)
                ->groupBy('categories.id')
                ->orderBy('categories.sort_order', 'ASC')
                ->orderBy('categories.name', 'ASC')
                ->findAll();
        });
    }

    public function featured(int $limit = 8): array
    {
        return $this->select('categories.*, COUNT(products.id) AS product_count')
            ->join('products', 'products.category_id = categories.id AND products.status = "active" AND products.deleted_at IS NULL', 'left')
            ->where('categories.status', 'active')
            ->where('categories.featured', 1)
            ->groupBy('categories.id')
            ->orderBy('categories.sort_order', 'ASC')
            ->findAll($limit);
    }

    public function clearCache(): void
    {
        cache()->delete('categories_nav');
    }
}
