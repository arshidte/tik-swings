<?php

namespace App\Controllers\Cart;

use App\Controllers\BaseController;
use App\Services\CartService;

class Cart extends BaseController
{
    private CartService $cart;

    public function __construct()
    {
        $this->cart = new CartService();
    }

    /**
     * Full cart page (server-rendered, §67).
     */
    public function index()
    {
        $snapshot = $this->cart->snapshot();
        $this->cart->syncCount();

        return view('cart/index', [
            'meta'     => ['title' => 'Your Cart — ' . store_name()],
            'snapshot' => $snapshot,
        ]);
    }

    public function mini()
    {
        $snap = $this->cart->snapshot();
        $this->cart->syncCount();

        return $this->jsonSuccess('', $snap);
    }

    public function add()
    {
        $productId = (int) $this->input('product_id');
        $variantId = $this->input('variant_id');
        $variantId = $variantId ? (int) $variantId : null;
        $quantity  = (int) ($this->input('quantity') ?? 1);
        $options   = $this->input('options');
        if (is_string($options)) {
            $options = json_decode($options, true);
        }

        if ($productId <= 0) {
            return $this->jsonError('Please choose a product.');
        }

        try {
            $snap = $this->cart->add($productId, $variantId, $quantity, is_array($options) ? $options : null);
            $this->cart->syncCount();

            return $this->jsonSuccess('Added to your cart.', $snap);
        } catch (\RuntimeException $e) {
            return $this->jsonError($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'Cart add failed: {0}', [$e->getMessage()]);

            return $this->jsonError('Could not add to cart. Please try again.', 500);
        }
    }

    public function update()
    {
        $lineId   = (string) $this->input('item_id');
        $quantity = (int) $this->input('quantity');
        if ($lineId === '') {
            return $this->jsonError('Invalid cart item.');
        }
        $snap = $this->cart->updateQuantity($lineId, $quantity);
        $this->cart->syncCount();

        return $this->jsonSuccess('', $snap);
    }

    public function remove()
    {
        $lineId = (string) $this->input('item_id');
        $snap   = $this->cart->remove($lineId);
        $this->cart->syncCount();

        return $this->jsonSuccess('Item removed.', $snap);
    }

    public function applyCoupon()
    {
        $code = trim((string) $this->input('code'));
        if ($code === '') {
            return $this->jsonError('Please enter a coupon code.');
        }
        try {
            $snap = $this->cart->applyCoupon($code);

            return $this->jsonSuccess('Coupon applied.', $snap);
        } catch (\RuntimeException $e) {
            return $this->jsonError($e->getMessage());
        }
    }

    public function removeCoupon()
    {
        return $this->jsonSuccess('Coupon removed.', $this->cart->removeCoupon());
    }
}
