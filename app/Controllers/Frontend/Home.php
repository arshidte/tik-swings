<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\ReviewModel;

class Home extends BaseController
{
    public function index(): string
    {
        $products   = new ProductModel();
        $categories = new CategoryModel();
        $reviews    = new ReviewModel();

        $data = [
            'meta' => [
                'title'       => setting('seo_title', store_name()),
                'description' => setting('seo_description'),
                'og_type'     => 'website',
            ],
            'schema'     => $this->organizationSchema(),
            'categories' => $categories->featured(8),
            'featured'   => $products->featured(8),
            'newest'     => $products->latest(4),
            'reviews'    => $reviews->featured(6),
            'wishIds'    => wishlist_ids(),
        ];

        return view('frontend/home', $data);
    }

    /**
     * Organization + WebSite JSON-LD for the homepage (§38).
     */
    private function organizationSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type' => 'Organization',
                    'name'  => store_name(),
                    'url'   => base_url('/'),
                    'logo'  => product_image(setting('og_image'), 'hero.svg'),
                    'contactPoint' => [
                        '@type'       => 'ContactPoint',
                        'telephone'   => setting('store_phone'),
                        'contactType' => 'customer service',
                    ],
                ],
                [
                    '@type'           => 'WebSite',
                    'name'            => store_name(),
                    'url'             => base_url('/'),
                    'potentialAction' => [
                        '@type'       => 'SearchAction',
                        'target'      => base_url('search') . '?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];
    }
}
