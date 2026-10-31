<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Customers extends BaseController
{
    public function index(): string
    {
        $db    = db_connect();
        $model = new UserModel();
        $q = trim((string) $this->request->getGet('q'));
        if ($q !== '') {
            $model->groupStart()
                ->like('first_name', $q)->orLike('last_name', $q)->orLike('email', $q)->orLike('phone', $q)
                ->groupEnd();
        }
        $customers = $model->where('role', 'customer')
            ->select('users.*, (SELECT COUNT(*) FROM orders WHERE orders.user_id = users.id) AS order_count, (SELECT COALESCE(SUM(total),0) FROM orders WHERE orders.user_id = users.id) AS spent')
            ->orderBy('id', 'DESC')->paginate(25);

        return view('admin/customers', [
            'title'     => 'Customers',
            'customers' => $customers,
            'pager'     => $model->pager,
            'q'         => $q,
            'total'     => $model->pager->getTotal('default'),
        ]);
    }
}
