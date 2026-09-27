<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
use App\Services\OrderService;

class Orders extends BaseController
{
    public function index(): string
    {
        $model  = new OrderModel();
        $status = $this->request->getGet('status');
        if ($status) {
            $model->where('status', $status);
        }
        $orders = $model->orderBy('id', 'DESC')->paginate(20);

        return view('admin/orders/index', [
            'title'  => 'Orders',
            'orders' => $orders,
            'pager'  => $model->pager,
            'status' => $status,
        ]);
    }

    public function show(int $id): string
    {
        $order = (new OrderModel())->find($id);
        if (! $order) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('admin/orders/show', [
            'title'    => 'Order ' . $order['order_number'],
            'order'    => $order,
            'items'    => (new OrderItemModel())->forOrder($id),
            'history'  => (new OrderStatusHistoryModel())->forOrder($id),
            'statuses' => ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refunded'],
        ]);
    }

    public function updateStatus(int $id)
    {
        $status = $this->request->getPost('status');
        $note   = $this->request->getPost('note');
        $valid  = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'refunded'];
        if (! in_array($status, $valid, true)) {
            return redirect()->back()->with('error', 'Invalid status.');
        }
        (new OrderService())->updateStatus($id, $status, $note ?: null, (int) session()->get('user_id'));

        return redirect()->to(site_url('admin/orders/' . $id))->with('success', 'Order status updated.');
    }
}
