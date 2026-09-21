<?php
namespace Admin\controllers;

use Admin\core\Model;

class DashboardController extends BaseController
{
    protected string $section = 'dashboard';

    public function index($id = 0): void
    {
        $today = date('Y-m-d');

        $stats = [
            'orders_total'      => Model::count('orders'),
            'orders_today'      => Model::count('orders', 'DATE(created_at) = ?', [$today]),
            'orders_processing' => Model::count('orders', "status = 'processing'"),
            'revenue_total'     => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status <> 'cancelled'"),
            'revenue_month'     => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status <> 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'users_total'       => Model::count('users'),
            'users_month'       => Model::count('users', 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
            'products_total'    => Model::count('products'),
            'products_out'      => Model::count('products', 'in_stock = 0'),
            'tickets_open'      => Model::count('support_tickets', "status IN ('open','pending')"),
            'comments_pending'  => Model::count('product_comments', "status = 'pending'"),
            'wallet_pending'    => Model::count('wallet_transactions', "status = 'pending'"),
            'articles_total'    => Model::count('articles'),
            'coupons_active'    => Model::count('discount_coupons', 'is_active = 1'),
        ];

        // نمودار فروش ۱۴ روز اخیر
        $chart = Model::all("SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s
                             FROM orders
                             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND status <> 'cancelled'
                             GROUP BY DATE(created_at) ORDER BY d ASC");
        $chartMap = [];
        foreach ($chart as $r) {
            $chartMap[$r['d']] = $r;
        }
        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date'  => $d,
                'label' => toShamsi($d),
                'count' => (int) ($chartMap[$d]['c'] ?? 0),
                'sum'   => (float) ($chartMap[$d]['s'] ?? 0),
            ];
        }

        $recentOrders = Model::all("SELECT o.*, u.full_name, u.phone
                                    FROM orders o LEFT JOIN users u ON u.id = o.user_id
                                    ORDER BY o.created_at DESC LIMIT 8");

        $topProducts = Model::all("SELECT p.id, p.name, p.telegram_photo_id,
                                          SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price) AS revenue
                                   FROM order_items oi
                                   JOIN products p ON p.id = oi.product_id
                                   JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled'
                                   GROUP BY p.id ORDER BY qty DESC LIMIT 6");

        $recentUsers = Model::all("SELECT id, full_name, phone, created_at FROM users ORDER BY created_at DESC LIMIT 6");
        $openTickets = Model::all("SELECT t.*, u.full_name FROM support_tickets t
                                   LEFT JOIN users u ON u.id = t.user_id
                                   WHERE t.status IN ('open','pending') ORDER BY t.updated_at DESC LIMIT 6");

        $statusBreakdown = Model::all("SELECT status, COUNT(*) c FROM orders GROUP BY status");

        $this->view('dashboard', compact(
            'stats', 'series', 'recentOrders', 'topProducts', 'recentUsers', 'openTickets', 'statusBreakdown'
        ), 'پیشخوان', 'نمای کلی فروشگاه در یک نگاه');
    }
}
