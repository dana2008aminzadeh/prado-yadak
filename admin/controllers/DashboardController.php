<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Inventory;
use Admin\core\Model;

class DashboardController extends BaseController
{
    protected string $section = 'dashboard';

    public function index($id = 0): void
    {
        $today = date('Y-m-d');
        $valid = "status NOT IN ('cancelled','returned')";

        $stats = [
            'orders_total'      => Model::count('orders'),
            'orders_today'      => Model::count('orders', 'DATE(created_at) = ?', [$today]),
            'orders_processing' => Model::count('orders', "status IN ('pending_payment','processing')"),
            'orders_packing'    => Model::count('orders', "status = 'packing'"),
            'revenue_total'     => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE $valid"),
            'revenue_month'     => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE $valid AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'revenue_today'     => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE $valid AND DATE(created_at) = ?", [$today]),
            'users_total'       => Model::count('users'),
            'users_month'       => Model::count('users', 'created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)'),
            'products_total'    => Model::count('products'),
            'products_out'      => Model::count('products', 'stock_qty <= 0'),
            'products_low'      => Model::count('products', 'track_stock = 1 AND stock_qty > 0 AND stock_qty <= low_stock_threshold'),
            'tickets_open'      => Model::count('support_tickets', "status IN ('open','pending')"),
            'comments_pending'  => Model::count('product_comments', "status = 'pending'"),
            'wallet_pending'    => Model::count('wallet_transactions', "status = 'pending'"),
            'stock_value'       => (float) Model::scalar('SELECT COALESCE(SUM(stock_qty * price),0) FROM products WHERE track_stock = 1'),
        ];

        // مقایسه با ماه قبل
        $prevMonth = (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders
                                            WHERE $valid AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                                              AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['revenue_growth'] = $prevMonth > 0
            ? round((($stats['revenue_month'] - $prevMonth) / $prevMonth) * 100)
            : null;

        // نمودار ۱۴ روز اخیر
        $chart = Model::all("SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS s
                             FROM orders WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) AND $valid
                             GROUP BY DATE(created_at) ORDER BY d ASC");
        $map = [];
        foreach ($chart as $r) $map[$r['d']] = $r;

        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $series[] = [
                'date'  => $d,
                'label' => toShamsi($d),
                'count' => (int) ($map[$d]['c'] ?? 0),
                'sum'   => (float) ($map[$d]['s'] ?? 0),
            ];
        }

        $recentOrders = Model::all("SELECT o.*, u.full_name, u.phone FROM orders o
                                    LEFT JOIN users u ON u.id = o.user_id
                                    ORDER BY o.created_at DESC LIMIT 8");

        $topProducts = Model::all("SELECT p.id, p.name, p.telegram_photo_id, p.stock_qty,
                                          SUM(oi.quantity) AS qty, SUM(oi.quantity * oi.price) AS revenue
                                   FROM order_items oi
                                   JOIN products p ON p.id = oi.product_id
                                   JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ('cancelled','returned')
                                   GROUP BY p.id ORDER BY qty DESC LIMIT 6");

        $recentUsers = Model::all('SELECT id, full_name, phone, created_at FROM users ORDER BY created_at DESC LIMIT 6');

        $openTickets = Model::all("SELECT t.*, u.full_name FROM support_tickets t
                                   LEFT JOIN users u ON u.id = t.user_id
                                   WHERE t.status IN ('open','pending') ORDER BY
                                   FIELD(t.priority,'high','normal','low'), t.updated_at DESC LIMIT 6");

        $statusBreakdown = Model::all('SELECT status, COUNT(*) c FROM orders GROUP BY status');
        $lowStock = Inventory::lowStock(8);

        // آخرین رویدادهای حساس برای مدیران ارشد
        $recentAudit = Auth::can('audit.view')
            ? Model::all('SELECT * FROM admin_audit_logs ORDER BY id DESC LIMIT 8')
            : [];

        $statuses = OrderController::STATUSES;
        $colors = OrderController::STATUS_COLORS;

        $this->view('dashboard',
            compact('stats', 'series', 'recentOrders', 'topProducts', 'recentUsers', 'openTickets',
                    'statusBreakdown', 'lowStock', 'recentAudit', 'statuses', 'colors'),
            'پیشخوان', 'نمای کلی فروشگاه — ' . shamsiLong(date('Y-m-d')));
    }
}
