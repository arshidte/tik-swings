<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ReviewModel;

class Reviews extends BaseController
{
    public function index(): string
    {
        $db     = db_connect();
        $status = $this->request->getGet('status');
        $builder = $db->table('reviews r')
            ->select('r.*, p.name AS product_name, p.slug AS product_slug')
            ->join('products p', 'p.id = r.product_id', 'left')
            ->orderBy('r.id', 'DESC');
        if ($status) {
            $builder->where('r.status', $status);
        }

        return view('admin/reviews', [
            'title'   => 'Reviews',
            'reviews' => $builder->get(60)->getResultArray(),
            'status'  => $status,
        ]);
    }

    public function moderate(int $id)
    {
        $action = $this->request->getPost('action');
        $model  = new ReviewModel();
        $review = $model->find($id);
        if (! $review) {
            return redirect()->back()->with('error', 'Review not found.');
        }
        $map = ['approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending'];
        if (isset($map[$action])) {
            $model->update($id, ['status' => $map[$action]]);
            $model->recalculateProductRating((int) $review['product_id']);
        }

        return redirect()->to(site_url('admin/reviews'))->with('success', 'Review updated.');
    }
}
