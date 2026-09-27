<?php

namespace App\Controllers\Account;

use App\Controllers\BaseController;
use App\Models\AddressModel;
use App\Models\OrderItemModel;
use App\Models\OrderModel;
use App\Models\OrderStatusHistoryModel;
use App\Models\UserModel;

class Account extends BaseController
{
    private function uid(): int
    {
        return (int) session()->get('user_id');
    }

    public function index()
    {
        $orders = (new OrderModel())->forUser($this->uid());

        return view('account/dashboard', [
            'meta'        => ['title' => 'My Account — ' . store_name()],
            'user'        => (new UserModel())->find($this->uid()),
            'recentOrders' => array_slice($orders, 0, 3),
            'orderCount'  => count($orders),
            'addressCount' => count((new AddressModel())->forUser($this->uid())),
            'active'      => 'dashboard',
        ]);
    }

    public function orders()
    {
        return view('account/orders', [
            'meta'   => ['title' => 'My Orders — ' . store_name()],
            'orders' => (new OrderModel())->forUser($this->uid()),
            'active' => 'orders',
        ]);
    }

    public function orderDetail(string $number)
    {
        $order = (new OrderModel())->where('order_number', $number)->where('user_id', $this->uid())->first();
        if (! $order) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return view('account/order_detail', [
            'meta'    => ['title' => 'Order ' . $order['order_number'] . ' — ' . store_name()],
            'order'   => $order,
            'items'   => (new OrderItemModel())->forOrder((int) $order['id']),
            'history' => (new OrderStatusHistoryModel())->forOrder((int) $order['id']),
            'active'  => 'orders',
        ]);
    }

    public function addresses()
    {
        return view('account/addresses', [
            'meta'      => ['title' => 'My Addresses — ' . store_name()],
            'addresses' => (new AddressModel())->forUser($this->uid()),
            'active'    => 'addresses',
        ]);
    }

    public function saveAddress()
    {
        $model = new AddressModel();
        $id    = (int) $this->request->getPost('id');
        $data  = [
            'user_id'   => $this->uid(),
            'label'     => $this->request->getPost('label'),
            'full_name' => $this->request->getPost('full_name'),
            'phone'     => $this->request->getPost('phone'),
            'line1'     => $this->request->getPost('line1'),
            'line2'     => $this->request->getPost('line2'),
            'city'      => $this->request->getPost('city'),
            'state'     => $this->request->getPost('state'),
            'pincode'   => $this->request->getPost('pincode'),
            'is_default' => $this->request->getPost('is_default') ? 1 : 0,
        ];

        if ($id) {
            $owned = $model->where('user_id', $this->uid())->find($id);
            if (! $owned) {
                return redirect()->to(site_url('account/addresses'))->with('error', 'Address not found.');
            }
            if (! $model->update($id, $data)) {
                return redirect()->back()->withInput()->with('errors', $model->errors());
            }
        } else {
            if (! $model->insert($data)) {
                return redirect()->back()->withInput()->with('errors', $model->errors());
            }
            $id = $model->getInsertID();
        }
        if ($data['is_default']) {
            $model->makeDefault($this->uid(), $id);
        }

        return redirect()->to(site_url('account/addresses'))->with('success', 'Address saved.');
    }

    public function deleteAddress()
    {
        $id    = (int) $this->request->getPost('id');
        $model = new AddressModel();
        if ($model->where('user_id', $this->uid())->find($id)) {
            $model->delete($id);
        }

        return redirect()->to(site_url('account/addresses'))->with('success', 'Address removed.');
    }

    public function updateProfile()
    {
        $users = new UserModel();
        $data  = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
            'phone'      => $this->request->getPost('phone'),
        ];
        $users->update($this->uid(), $data);
        session()->set('user_name', trim($data['first_name'] . ' ' . $data['last_name']));

        return redirect()->to(site_url('account'))->with('success', 'Profile updated.');
    }
}
