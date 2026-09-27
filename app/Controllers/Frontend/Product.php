<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductUserMediaModel;
use App\Models\ProductVariantModel;
use App\Models\ReviewModel;

class Product extends BaseController
{
    public function show(string $slug)
    {
        $model   = new ProductModel();
        $product = $model->findDetailBySlug($slug);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $reviews = (new ReviewModel())->approvedForProduct((int) $product['id'], 12);
        $related = $model->relatedTo($product, 4);

        return view('frontend/product', [
            'meta' => [
                'title'       => $product['seo_title'] ?: ($product['name'] . ' — ' . store_name()),
                'description' => $product['seo_description'] ?: $product['short_description'],
                'og_type'     => 'product',
                'og_image'    => product_image($product['images'][0]['image'] ?? null),
            ],
            'schema'   => $this->productSchema($product, $reviews),
            'product'  => $product,
            'reviews'  => $reviews,
            'related'  => $related,
            'wishIds'  => wishlist_ids(),
        ]);
    }

    /**
     * Lazy-load a batch of customer-gallery tiles for the PDP strip.
     * Returns the rendered tile HTML (empty when there is nothing more).
     */
    public function media(int $id)
    {
        $product = (new ProductModel())->find($id);
        if (! $product) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit  = 10;
        $items  = (new ProductUserMediaModel())->visibleBatch($id, $offset, $limit);

        return view('frontend/partials/gallery_tiles', [
            'items'       => $items,
            'productName' => $product['name'],
        ]);
    }

    /**
     * Resolve a variant selection to price / stock / sku / image (§15).
     * Accepts attribute_value_ids[] and returns the matching variant.
     */
    public function variant()
    {
        $productId = (int) $this->input('product_id');
        $valueIds  = (array) ($this->input('values') ?? []);
        $valueIds  = array_map('intval', $valueIds);

        $variants = (new ProductVariantModel())->forProduct($productId);

        // Prefer an exact match on the full set of selected attribute values.
        foreach ($variants as $v) {
            $vIds = array_map('intval', array_column($v['attributes'] ?? [], 'attribute_value_id'));
            if ($vIds && ! array_diff($valueIds, $vIds) && ! array_diff($vIds, $valueIds)) {
                return $this->jsonSuccess('', $this->variantPayload($v));
            }
        }

        // Fall back to a variant that satisfies the values provided so far.
        foreach ($variants as $v) {
            $vIds = array_map('intval', array_column($v['attributes'] ?? [], 'attribute_value_id'));
            if ($valueIds && ! array_diff($valueIds, $vIds)) {
                return $this->jsonSuccess('', $this->variantPayload($v));
            }
        }

        return $this->jsonError('That combination is not available.', 200, ['available' => false]);
    }

    private function variantPayload(array $v): array
    {
        return [
            'available'     => true,
            'variant_id'    => (int) $v['id'],
            'sku'           => $v['sku'],
            'price'         => (float) $v['price'],
            'compare_price' => $v['compare_price'] !== null ? (float) $v['compare_price'] : null,
            'price_display' => price($v['price']),
            'compare_display' => $v['compare_price'] ? price($v['compare_price']) : null,
            'discount'      => discount_percent($v['price'], $v['compare_price'] ?? 0),
            'stock'         => (int) $v['stock'],
            'in_stock'      => (int) $v['stock'] > 0,
            'image'         => $v['image'] ? product_image($v['image']) : null,
        ];
    }

    private function productSchema(array $product, array $reviews): array
    {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => $product['name'],
            'sku'         => $product['sku'],
            'description' => $product['short_description'] ?: strip_tags((string) $product['description']),
            'image'       => array_map(static fn ($i) => product_image($i['image']), $product['images']),
            'brand'       => ['@type' => 'Brand', 'name' => store_name()],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => current_url(),
                'priceCurrency' => 'INR',
                'price'         => (string) $product['price'],
                'availability'  => $product['stock_status'] === 'out_of_stock'
                    ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
            ],
        ];
        // Only expose ratings that are real (§38 — no fake ratings).
        if ((int) $product['rating_count'] > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $product['rating_avg'],
                'reviewCount' => (string) $product['rating_count'],
            ];
        }

        return $schema;
    }
}
