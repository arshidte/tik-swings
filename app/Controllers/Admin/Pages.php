<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PageModel;

class Pages extends BaseController
{
    public function index(): string
    {
        return view('admin/pages', [
            'title' => 'Pages & Journal',
            'pages' => (new PageModel())->orderBy('type')->orderBy('title')->findAll(),
        ]);
    }
}
