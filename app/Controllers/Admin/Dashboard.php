<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $db = db_connect();

        $paidStats = $db->query("SELECT COUNT(*) c, COALESCE(SUM(total),0) s FROM orders WHERE payment_status='paid' OR status IN ('confirmed','processing','shipped','out_for_delivery','delivered')")->getRowArray();

        $data = [
            'title'          => 'Dashboard',
            'totalSales'     => (float) $paidStats['s'],
            'orderCount'     => (int) $db->table('orders')->countAll(),
            'pendingOrders'  => (int) $db->table('orders')->where('status', 'pending')->countAllResults(),
            'customerCount'  => (int) $db->table('users')->where('role', 'customer')->countAllResults(),
            'productCount'   => (int) $db->table('products')->where('deleted_at', null)->countAllResults(),
            'lowStock'       => $db->table('products')->select('id, name, stock, slug')
                ->where('deleted_at', null)->where('has_variants', 0)->where('stock <=', 3)
                ->orderBy('stock', 'ASC')->limit(6)->get()->getResultArray(),
            'recentOrders'   => $db->table('orders')->orderBy('id', 'DESC')->limit(6)->get()->getResultArray(),
            'recentReviews'  => $db->table('reviews')->orderBy('id', 'DESC')->limit(5)->get()->getResultArray(),
            'salesByDay'     => $this->salesByDay($db),
        ];

        return view('admin/dashboard', $data);
    }

    private function salesByDay($db): array
    {
        $rows = $db->query(
            "SELECT DATE(created_at) d, COALESCE(SUM(total),0) s FROM orders
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY DATE(created_at)"
        )->getResultArray();
        $map = array_column($rows, 's', 'd');

        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = ['label' => date('M j', strtotime($day)), 'value' => (float) ($map[$day] ?? 0)];
        }

        return $series;
    }
}
