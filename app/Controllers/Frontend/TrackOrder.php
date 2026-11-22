<?php

namespace App\Controllers\Frontend;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\ShipmentModel;

/**
 * Public order tracking: /track-order?order=SW261103A1
 *
 * Query-string based so the link can be whitelisted for DLT SMS templates
 * (the dynamic part must follow a '?', and may not contain '/').
 *
 * Shows only non-sensitive information (status, items, courier tracking).
 * Address, phone, email and payment details stay behind login.
 */
class TrackOrder extends BaseController
{
    public function index()
    {
        $number = strtoupper(trim((string) $this->request->getGet('order')));
        $order  = null;
        $error  = null;

        if ($number !== '') {
            // Throttle lookups so order numbers can't be enumerated.
            $key = 'track_' . md5($this->request->getIPAddress());
            if (! service('throttler')->check($key, 30, MINUTE)) {
                $error = 'Too many lookups. Please wait a minute and try again.';
            } elseif (! preg_match('/^[A-Z0-9]{6,30}$/', $number)) {
                $error = 'Please enter a valid order number.';
            } else {
                $order = (new OrderModel())->findByNumber($number);
                if (! $order) {
                    $error = 'We couldn’t find an order with that number.';
                }
            }
        }

        // The owner gets the full order page directly.
        if ($order) {
            $uid = (int) session()->get('user_id');
            if ($uid > 0 && (int) $order['user_id'] === $uid) {
                return redirect()->to(site_url('account/orders/' . $order['order_number']));
            }
        }

        return view('frontend/track_order', [
            'meta'     => ['title' => 'Track Your Order — ' . store_name(), 'robots' => 'noindex'],
            'number'   => $number,
            'error'    => $error,
            'order'    => $order,
            'items'    => $order ? (new OrderItemModel())->forOrder((int) $order['id']) : [],
            'history'  => $order ? (new OrderStatusHistoryModel())->forOrder((int) $order['id']) : [],
            'shipment' => $order ? (new ShipmentModel())->forOrder((int) $order['id']) : null,
        ]);
    }
}

