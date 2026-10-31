<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NewsletterModel;

class Subscribers extends BaseController
{
    public function index(): string
    {
        $q     = trim((string) $this->request->getGet('q'));
        $model = new NewsletterModel();
        if ($q !== '') {
            $model->like('email', $q);
        }
        $subscribers = $model->orderBy('id', 'DESC')->paginate(50);

        return view('admin/subscribers', [
            'title'       => 'Subscribers',
            'subscribers' => $subscribers,
            'pager'       => $model->pager,
            'total'       => $model->pager->getTotal('default'),
            'q'           => $q,
        ]);
    }
}
