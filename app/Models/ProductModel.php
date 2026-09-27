<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $allowedFields    = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'description',
        'price', 'compare_price', 'cost_price', 'stock', 'stock_status', 'has_variants',
        'material', 'wood_type', 'finish', 'dimensions', 'weight_capacity', 'warranty',
        'delivery_estimate', 'installation_available', 'is_customizable',
        'rating_avg', 'rating_count', 'featured', 'status',
        'seo_title', 'seo_description', 'seo_keywords',
    ];
    protected $useTimestamps = true;
    protected $validationRules = [
        'name'  => 'required|max_length[190]',
        'slug'  => 'required|max_length[210]',
        'sku'   => 'required|max_length[80]',
        'price' => 'required|decimal|greater_than_equal_to[0]',
    ];

    /**
     * Select only the columns product cards/listings need (avoids SELECT *).
     */
    public function cardColumns(): self
    {
        $this->select('products.id, products.name, products.slug, products.short_description,
            products.price, products.compare_price, products.stock_status, products.has_variants,
            products.wood_type, products.finish, products.rating_avg, products.rating_count,
            products.featured, products.category_id, products.is_customizable');

        return $this;
    }

    public function active()
    {
        return $this->where('products.status', 'active');
    }

    /**
     * Attach the primary image path to a set of product rows in a single query
     * (avoids N+1 lookups on listing pages).
     */
    public function withPrimaryImage(array $products): array
    {
        if ($products === []) {
            return $products;
        }
        $ids    = array_column($products, 'id');
        $images = db_connect()->table('product_images')
            ->select('product_id, image, alt_text, is_primary')
            ->whereIn('product_id', $ids)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $primary   = [];
        $secondary = [];
        foreach ($images as $img) {
            $pid = $img['product_id'];
            if (! isset($primary[$pid])) {
                $primary[$pid] = $img;
            } elseif (! isset($secondary[$pid])) {
                $secondary[$pid] = $img;
            }
        }

        foreach ($products as &$product) {
            $pid                       = $product['id'];
            $product['primary_image']  = $primary[$pid]['image'] ?? null;
            $product['primary_alt']    = $primary[$pid]['alt_text'] ?? $product['name'];
            $product['hover_image']    = $secondary[$pid]['image'] ?? null;
        }

        return $products;
    }

    public function featured(int $limit = 8): array
    {
        $rows = $this->cardColumns()->active()
            ->where('featured', 1)
            ->orderBy('products.updated_at', 'DESC')
            ->findAll($limit);

        return $this->withPrimaryImage($rows);
    }

    public function latest(int $limit = 8): array
    {
        $rows = $this->cardColumns()->active()
            ->orderBy('products.created_at', 'DESC')
            ->findAll($limit);

        return $this->withPrimaryImage($rows);
    }

    /**
     * Full product detail with related image, variant and attribute data.
     */
    public function findDetailBySlug(string $slug): ?array
    {
        $product = $this->where('slug', $slug)->where('status', 'active')->first();
        if (! $product) {
            return null;
        }

        $db = db_connect();

        $product['images'] = $db->table('product_images')
            ->where('product_id', $product['id'])
            ->orderBy('is_primary', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $product['category'] = $product['category_id']
            ? $db->table('categories')->select('id, name, slug')->where('id', $product['category_id'])->get()->getRowArray()
            : null;

        if ((int) $product['has_variants'] === 1) {
            $product['variants'] = (new ProductVariantModel())->forProduct((int) $product['id']);
        } else {
            $product['variants'] = [];
        }

        $product['user_media'] = (new ProductUserMediaModel())->forProduct((int) $product['id']);

        return $product;
    }

    public function relatedTo(array $product, int $limit = 4): array
    {
        $rows = $this->cardColumns()->active()
            ->where('category_id', $product['category_id'])
            ->where('products.id !=', $product['id'])
            ->orderBy('RAND()')
            ->findAll($limit);

        return $this->withPrimaryImage($rows);
    }
}
