<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NewsletterModel;

class Subscribers extends BaseController
{
    public function index(): string
    {
        return view('admin/subscribers', [
            'title'       => 'Subscribers',
            'subscribers' => (new NewsletterModel())->orderBy('id', 'DESC')->findAll(),
        ]);
    }
}
