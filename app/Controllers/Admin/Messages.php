<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ContactMessageModel;

class Messages extends BaseController
{
    public function index(): string
    {
        helper('text');

        return view('admin/messages', [
            'title'    => 'Messages',
            'messages' => (new ContactMessageModel())->orderBy('id', 'DESC')->findAll(),
        ]);
    }
}
