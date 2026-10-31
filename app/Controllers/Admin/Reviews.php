<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ReviewModel;

class Reviews extends BaseController
{
    public function index(): string
    {
        $status = $this->request->getGet('status');
        $model  = new ReviewModel();
        $model->select('reviews.*, p.name AS product_name, p.slug AS product_slug')
            ->join('products p', 'p.id = reviews.product_id', 'left')
            ->orderBy('reviews.id', 'DESC');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $model->where('reviews.status', $status);
        } else {
            $status = null;
        }
        $reviews = $model->paginate(20);

        return view('admin/reviews', [
            'title'   => 'Reviews',
            'reviews' => $reviews,
            'pager'   => $model->pager,
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
