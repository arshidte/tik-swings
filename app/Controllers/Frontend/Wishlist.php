<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\WishlistModel;

class Wishlist extends BaseController
{
    public function toggle()
    {
        $productId = (int) $this->input('product_id');
        if ($productId <= 0) {
            return $this->jsonError('Invalid product.');
        }

        if (is_logged_in()) {
            $added = (new WishlistModel())->toggle((int) session()->get('user_id'), $productId);
            $count = count((new WishlistModel())->productIdsForUser((int) session()->get('user_id')));
        } else {
            $list  = session()->get('wishlist') ?? [];
            $added = ! in_array($productId, $list, true);
            if ($added) {
                $list[] = $productId;
            } else {
                $list = array_values(array_diff($list, [$productId]));
            }
            session()->set('wishlist', $list);
            $count = count($list);
        }

        return $this->jsonSuccess($added ? 'Saved to wishlist.' : 'Removed from wishlist.', [
            'added' => $added,
            'count' => $count,
        ]);
    }

    public function index()
    {
        $ids = wishlist_ids();
        $products = [];
        if ($ids) {
            $model    = new ProductModel();
            $products = $model->cardColumns()->whereIn('products.id', $ids)->where('products.status', 'active')->findAll();
            $products = $model->withPrimaryImage($products);
        }

        return view('frontend/wishlist', [
            'meta'     => ['title' => 'Your Wishlist — ' . store_name()],
            'products' => $products,
            'wishIds'  => $ids,
        ]);
    }
}
