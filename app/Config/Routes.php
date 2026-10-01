<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ---------------------------------------------------------------------------
// Storefront
// ---------------------------------------------------------------------------
$routes->get('/', 'Frontend\Home::index');
$routes->get('shop', 'Frontend\Shop::index');
$routes->get('shop/filter', 'Frontend\Shop::filter');          // AJAX partial (§20)
$routes->get('category/(:segment)', 'Frontend\Shop::category/$1');
$routes->get('product/(:segment)', 'Frontend\Product::show/$1');
$routes->post('product/variant', 'Frontend\Product::variant'); // AJAX variant resolve (§15)
$routes->get('product/media/(:num)', 'Frontend\Product::media/$1'); // lazy-load customer gallery

// Search (§21)
$routes->get('search', 'Frontend\Search::index');
$routes->get('search/suggest', 'Frontend\Search::suggest');

// Content pages
$routes->get('about', 'Frontend\Pages::about');
$routes->get('craftsmanship', 'Frontend\Pages::craftsmanship');
$routes->get('custom-swings', 'Frontend\Pages::customSwings');
$routes->get('contact', 'Frontend\Pages::contact');
$routes->get('return-and-refunds', 'Frontend\Pages::returnAndRefunds');
$routes->get('help-center', 'Frontend\Pages::helpCenter');
$routes->post('contact', 'Frontend\Pages::submitContact');
$routes->get('faq', 'Frontend\Pages::faq');
$routes->get('journal', 'Frontend\Pages::journal');
$routes->get('journal/(:segment)', 'Frontend\Pages::journalPost/$1');
$routes->post('newsletter/subscribe', 'Frontend\Pages::subscribe');

// ---------------------------------------------------------------------------
// Cart & wishlist (AJAX, §27–§29)
// ---------------------------------------------------------------------------
$routes->get('cart', 'Cart\Cart::index');
$routes->get('cart/mini', 'Cart\Cart::mini');
$routes->post('cart/add', 'Cart\Cart::add');
$routes->post('cart/update', 'Cart\Cart::update');
$routes->post('cart/remove', 'Cart\Cart::remove');
$routes->post('cart/coupon', 'Cart\Cart::applyCoupon');
$routes->post('cart/coupon/remove', 'Cart\Cart::removeCoupon');

$routes->get('wishlist', 'Frontend\Wishlist::index');
$routes->post('wishlist/toggle', 'Frontend\Wishlist::toggle');

// ---------------------------------------------------------------------------
// Checkout & orders (§30–§33)
// ---------------------------------------------------------------------------
$routes->group('checkout', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Checkout\Checkout::index');
    $routes->post('place', 'Checkout\Checkout::place');
    $routes->post('payment/verify', 'Checkout\Checkout::verifyPayment');
    $routes->get('confirmation/(:segment)', 'Checkout\Checkout::confirmation/$1');
});

// ---------------------------------------------------------------------------
// Auth (§68)
// ---------------------------------------------------------------------------
$routes->get('login', 'Auth\Auth::login');
$routes->post('login', 'Auth\Auth::attemptLogin');
$routes->get('register', 'Auth\Auth::register');
$routes->post('register', 'Auth\Auth::attemptRegister');
$routes->get('forgot-password', 'Auth\Auth::forgot');
$routes->post('forgot-password', 'Auth\Auth::sendReset');
$routes->get('logout', 'Auth\Auth::logout');

// ---------------------------------------------------------------------------
// Account (auth-protected)
// ---------------------------------------------------------------------------
$routes->group('account', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Account\Account::index');
    $routes->get('orders', 'Account\Account::orders');
    $routes->get('orders/(:segment)', 'Account\Account::orderDetail/$1');
    $routes->get('addresses', 'Account\Account::addresses');
    $routes->post('addresses/save', 'Account\Account::saveAddress');
    $routes->post('addresses/delete', 'Account\Account::deleteAddress');
    $routes->post('profile', 'Account\Account::updateProfile');
});

// ---------------------------------------------------------------------------
// Admin (admin-protected)
// ---------------------------------------------------------------------------
$routes->group('admin', ['filter' => 'admin'], static function ($routes) {
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('products', 'Admin\Products::index');
    $routes->get('products/create', 'Admin\Products::create');
    $routes->post('products/store', 'Admin\Products::store');
    $routes->get('products/edit/(:num)', 'Admin\Products::edit/$1');
    $routes->post('products/update/(:num)', 'Admin\Products::update/$1');
    $routes->post('products/delete/(:num)', 'Admin\Products::delete/$1');
    $routes->post('products/images/(:num)', 'Admin\Products::uploadImages/$1');
    $routes->post('products/media/(:num)', 'Admin\Products::uploadUserMedia/$1');
    $routes->post('products/media/save/(:num)', 'Admin\Products::saveUserMedia/$1');
    $routes->post('products/media/delete/(:num)/(:num)', 'Admin\Products::deleteUserMedia/$1/$2');
    $routes->get('categories', 'Admin\Categories::index');
    $routes->post('categories/store', 'Admin\Categories::store');
    $routes->post('categories/update/(:num)', 'Admin\Categories::update/$1');
    $routes->get('orders', 'Admin\Orders::index');
    $routes->get('orders/(:num)', 'Admin\Orders::show/$1');
    $routes->post('orders/status/(:num)', 'Admin\Orders::updateStatus/$1');
    $routes->get('customers', 'Admin\Customers::index');
    $routes->get('reviews', 'Admin\Reviews::index');
    $routes->post('reviews/moderate/(:num)', 'Admin\Reviews::moderate/$1');
    $routes->get('coupons', 'Admin\Coupons::index');
    $routes->post('coupons/store', 'Admin\Coupons::store');
    $routes->get('pages', 'Admin\Pages::index');
    $routes->get('messages', 'Admin\Messages::index');
    $routes->get('subscribers', 'Admin\Subscribers::index');
    $routes->get('settings', 'Admin\Settings::index');
    $routes->post('settings/save', 'Admin\Settings::save');
});

// SEO utility routes
$routes->get('sitemap.xml', 'Frontend\Seo::sitemap');
$routes->get('robots.txt', 'Frontend\Seo::robots');
