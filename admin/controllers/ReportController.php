<?php
namespace Admin\controllers;

use Admin\core\Model;

class ReportController extends BaseController
{
    protected string $section = 'reports';

    protected array $permissions = ['export' => 'reports.export'];

    public function index($id = 0): void
    {
        $from = $this->dateParam('from', date('Y-m-d', strtotime('-30 days')));
        $to   = $this->dateParam('to', date('Y-m-d'));
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];
        $valid = "status NOT IN ('cancelled','returned')";

        $summary = Model::one("SELECT COUNT(*) AS orders, COALESCE(SUM(total_amount),0) AS revenue,
                                      COALESCE(AVG(total_amount),0) AS avg_order,
                                      COALESCE(SUM(discount_amount),0) AS discounts,
                                      COALESCE(SUM(shipping_cost),0) AS shipping
                               FROM orders WHERE created_at BETWEEN ? AND ? AND $valid", $range) ?? [];

        $daily = Model::all("SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(total_amount),0) s
                             FROM orders WHERE created_at BETWEEN ? AND ? AND $valid
                             GROUP BY DATE(created_at) ORDER BY d", $range);

        $byStatus = Model::all('SELECT status, COUNT(*) c, COALESCE(SUM(total_amount),0) s
                                FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY status', $range);

        $byCarrier = Model::all("SELECT COALESCE(shipping_carrier,'نامشخص') carrier, COUNT(*) c,
                                        COALESCE(SUM(shipping_cost),0) cost
                                 FROM orders WHERE created_at BETWEEN ? AND ? AND $valid
                                 GROUP BY shipping_carrier ORDER BY c DESC", $range);

        $topProducts = Model::all("SELECT p.id, p.name, p.stock_qty, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
                                   FROM order_items oi
                                   JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ('cancelled','returned')
                                        AND o.created_at BETWEEN ? AND ?
                                   JOIN products p ON p.id = oi.product_id
                                   GROUP BY p.id ORDER BY revenue DESC LIMIT 15", $range);

        $topCategories = Model::all("SELECT COALESCE(c.name,'بدون دسته') name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
                                     FROM order_items oi
                                     JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ('cancelled','returned')
                                          AND o.created_at BETWEEN ? AND ?
                                     JOIN products p ON p.id = oi.product_id
                                     LEFT JOIN categories c ON c.id = p.category_id
                                     GROUP BY c.id ORDER BY revenue DESC LIMIT 10", $range);

        $topVehicles = Model::all("SELECT cm.name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
                                   FROM order_items oi
                                   JOIN orders o ON o.id = oi.order_id AND o.status NOT IN ('cancelled','returned')
                                        AND o.created_at BETWEEN ? AND ?
                                   JOIN product_vehicles pv ON pv.product_id = oi.product_id
                                   JOIN car_models cm ON cm.id = pv.car_model_id
                                   GROUP BY cm.id ORDER BY revenue DESC LIMIT 10", $range);

        $topCustomers = Model::all("SELECT u.id, u.full_name, u.phone, COUNT(o.id) orders, SUM(o.total_amount) spent
                                    FROM orders o JOIN users u ON u.id = o.user_id
                                    WHERE o.status NOT IN ('cancelled','returned') AND o.created_at BETWEEN ? AND ?
                                    GROUP BY u.id ORDER BY spent DESC LIMIT 10", $range);

        $newUsers = (int) Model::scalar('SELECT COUNT(*) FROM users WHERE created_at BETWEEN ? AND ?', $range);

        $coupons = Model::all("SELECT applied_coupon code, COUNT(*) uses, SUM(discount_amount) total
                               FROM orders WHERE applied_coupon IS NOT NULL AND applied_coupon <> ''
                                 AND created_at BETWEEN ? AND ? GROUP BY applied_coupon ORDER BY uses DESC LIMIT 10", $range);

        $lowStock = Model::all('SELECT id, name, price, stock_qty, low_stock_threshold FROM products
                                WHERE track_stock = 1 AND stock_qty <= low_stock_threshold
                                ORDER BY stock_qty ASC LIMIT 15');

        $smsStats = Model::one("SELECT COUNT(*) total, SUM(status='sent') sent, SUM(status='failed') failed
                                FROM sms_logs WHERE created_at BETWEEN ? AND ?", $range) ?? [];

        $fromJ = dateToJalali($from);
        $toJ = dateToJalali($to);

        $this->view('reports',
            compact('from', 'to', 'fromJ', 'toJ', 'summary', 'daily', 'byStatus', 'byCarrier', 'topProducts',
                    'topCategories', 'topVehicles', 'topCustomers', 'newUsers', 'coupons', 'lowStock', 'smsStats'),
            'گزارش‌ها و آمار', 'از ' . $fromJ . ' تا ' . $toJ);
    }

    /** خروجی اکسل گزارش فروش بازه */
    public function export($id = 0): void
    {
        $from = $this->dateParam('from', date('Y-m-d', strtotime('-30 days')));
        $to   = $this->dateParam('to', date('Y-m-d'));
        $range = [$from . ' 00:00:00', $to . ' 23:59:59'];

        $rows = Model::all("SELECT DATE(o.created_at) d, COUNT(*) orders,
                                   COALESCE(SUM(o.subtotal),0) subtotal,
                                   COALESCE(SUM(o.discount_amount),0) discount,
                                   COALESCE(SUM(o.shipping_cost),0) shipping,
                                   COALESCE(SUM(o.total_amount),0) total
                            FROM orders o
                            WHERE o.created_at BETWEEN ? AND ? AND o.status NOT IN ('cancelled','returned')
                            GROUP BY DATE(o.created_at) ORDER BY d", $range);

        $data = array_map(fn($r) => [
            toShamsi($r['d']), $r['d'], $r['orders'], $r['subtotal'], $r['discount'], $r['shipping'], $r['total'],
        ], $rows);

        $this->streamCsv('sales-report-' . $from . '_' . $to . '.csv',
            ['تاریخ شمسی', 'تاریخ میلادی', 'تعداد سفارش', 'جمع اقلام', 'تخفیف', 'هزینه ارسال', 'درآمد کل'], $data);
    }
}
