<?php
namespace Admin\controllers;

use Admin\core\Model;

class ReportController extends BaseController
{
    protected string $section = 'reports';

    public function index($id = 0): void
    {
        $from = param('from', date('Y-m-d', strtotime('-30 days')));
        $to   = param('to', date('Y-m-d'));
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $summary = Model::one("SELECT COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS revenue,
                                      COALESCE(AVG(total_amount),0) AS avg_order,
                                      COALESCE(SUM(discount_amount),0) AS discounts
                               FROM orders WHERE created_at BETWEEN ? AND ? AND status <> 'cancelled'", $range) ?? [];

        $daily = Model::all("SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(total_amount),0) s
                             FROM orders WHERE created_at BETWEEN ? AND ? AND status <> 'cancelled'
                             GROUP BY DATE(created_at) ORDER BY d", $range);

        $byStatus = Model::all("SELECT status, COUNT(*) c, COALESCE(SUM(total_amount),0) s
                                FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status", $range);

        $topProducts = Model::all("SELECT p.id, p.name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
                                   FROM order_items oi
                                   JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
                                   JOIN products p ON p.id = oi.product_id
                                   GROUP BY p.id ORDER BY revenue DESC LIMIT 15", $range);

        $topCategories = Model::all("SELECT COALESCE(c.name,'بدون دسته') name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
                                     FROM order_items oi
                                     JOIN orders o ON o.id = oi.order_id AND o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
                                     JOIN products p ON p.id = oi.product_id
                                     LEFT JOIN categories c ON c.id = p.category_id
                                     GROUP BY c.id ORDER BY revenue DESC LIMIT 10", $range);

        $topCustomers = Model::all("SELECT u.id, u.full_name, u.phone, COUNT(o.id) orders, SUM(o.total_amount) spent
                                    FROM orders o JOIN users u ON u.id = o.user_id
                                    WHERE o.status <> 'cancelled' AND o.created_at BETWEEN ? AND ?
                                    GROUP BY u.id ORDER BY spent DESC LIMIT 10", $range);

        $newUsers = (int) Model::scalar('SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?', $range);
        $coupons = Model::all("SELECT applied_coupon code, COUNT(*) uses, SUM(discount_amount) total
                               FROM orders WHERE applied_coupon IS NOT NULL AND applied_coupon <> '' AND created_at BETWEEN ? AND ?
                               GROUP BY applied_coupon ORDER BY uses DESC LIMIT 10", $range);
        $lowStock = Model::all('SELECT id, name, price FROM products WHERE in_stock = 0 ORDER BY id DESC LIMIT 15');

        $this->view('reports', compact('from', 'to', 'summary', 'daily', 'byStatus', 'topProducts', 'topCategories', 'topCustomers', 'newUsers', 'coupons', 'lowStock'),
            'گزارش‌ها و آمار', 'از ' . toShamsi($from) . ' تا ' . toShamsi($to));
    }
}
