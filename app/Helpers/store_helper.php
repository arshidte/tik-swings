<?php

use App\Models\SettingModel;

/**
 * Store-wide helpers — settings access, auth state, cart/wishlist context.
 */

if (! function_exists('setting')) {
    /**
     * Read a store setting by key with a sensible default.
     */
    function setting(string $key, $default = null)
    {
        static $map = null;
        if ($map === null) {
            $map = (new SettingModel())->allAsMap();
        }

        return $map[$key] ?? $default;
    }
}

if (! function_exists('store_name')) {
    function store_name(): string
    {
        return setting('store_name', 'Swing & Grain');
    }
}

if (! function_exists('whatsapp_link')) {
    /**
     * Build a wa.me link with a prefilled enquiry message (§50).
     */
    function whatsapp_link(?string $message = null): string
    {
        $number  = preg_replace('/\D+/', '', (string) setting('whatsapp_number', '919000000000'));
        $message ??= 'Hi, I need help choosing a wooden swing.';

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }
}

if (! function_exists('nav_categories')) {
    /**
     * Cached active categories for navigation menus.
     */
    function nav_categories(): array
    {
        return (new \App\Models\CategoryModel())->forNavigation();
    }
}

if (! function_exists('current_user')) {
    function current_user(): ?array
    {
        $session = session();
        if (! $session->get('user_id')) {
            return null;
        }

        return [
            'id'    => $session->get('user_id'),
            'name'  => $session->get('user_name'),
            'email' => $session->get('user_email'),
            'role'  => $session->get('user_role'),
        ];
    }
}

if (! function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return (bool) session()->get('user_id');
    }
}

if (! function_exists('is_admin')) {
    function is_admin(): bool
    {
        return session()->get('user_role') === 'admin';
    }
}

if (! function_exists('cart_count')) {
    /**
     * Total item quantity in the current cart (session mirror kept fresh
     * by CartService). Cheap read for the header badge.
     */
    function cart_count(): int
    {
        return (int) session()->get('cart_count');
    }
}

if (! function_exists('wishlist_ids')) {
    /**
     * Product IDs on the current visitor's wishlist (DB for auth users,
     * session for guests).
     */
    function wishlist_ids(): array
    {
        if (is_logged_in()) {
            return (new \App\Models\WishlistModel())->productIdsForUser((int) session()->get('user_id'));
        }

        return session()->get('wishlist') ?? [];
    }
}
