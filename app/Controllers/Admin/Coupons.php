<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CouponModel;

class Coupons extends BaseController
{
    public function index(): string
    {
        return view('admin/coupons', [
            'title'   => 'Coupons',
            'coupons' => (new CouponModel())->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function store()
    {
        $model = new CouponModel();
        $data  = [
            'code'         => strtoupper(trim((string) $this->request->getPost('code'))),
            'description'  => $this->request->getPost('description'),
            'type'         => $this->request->getPost('type') === 'fixed' ? 'fixed' : 'percent',
            'value'        => (float) $this->request->getPost('value'),
            'min_order'    => (float) $this->request->getPost('min_order'),
            'max_discount' => $this->request->getPost('max_discount') ?: null,
            'usage_limit'  => $this->request->getPost('usage_limit') ?: null,
            'expires_at'   => $this->request->getPost('expires_at') ?: null,
            'status'       => $this->request->getPost('status') ?: 'active',
        ];
        if (! $this->validate(['code' => 'required', 'value' => 'required|numeric'])) {
            return redirect()->back()->withInput()->with('error', 'Code and value are required.');
        }
        $model->insert($data);

        return redirect()->to(site_url('admin/coupons'))->with('success', 'Coupon created.');
    }
}
