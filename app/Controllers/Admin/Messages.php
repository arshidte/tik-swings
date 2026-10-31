<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContactMessageModel;

class Messages extends BaseController
{
    public function index(): string
    {
        helper('text');

        $model    = new ContactMessageModel();
        $messages = $model->orderBy('id', 'DESC')->paginate(15);

        return view('admin/messages', [
            'title'    => 'Messages',
            'messages' => $messages,
            'pager'    => $model->pager,
        ]);
    }
}
