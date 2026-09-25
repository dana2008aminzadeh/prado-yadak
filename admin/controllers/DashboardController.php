<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Inventory;
use Admin\core\Model;
use Admin\core\Settings;

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

        // ===================== هشدارها و یادآورهای مدیریتی =====================
        $alerts = $this->buildAlerts($stats);

        $statuses = OrderController::STATUSES;
        $colors = OrderController::STATUS_COLORS;

        $this->view('dashboard',
            compact('stats', 'series', 'recentOrders', 'topProducts', 'recentUsers', 'openTickets',
                    'statusBreakdown', 'lowStock', 'recentAudit', 'statuses', 'colors', 'alerts'),
            'پیشخوان', 'نمای کلی فروشگاه — ' . shamsiLong(date('Y-m-d')));
    }

    /**
     * ساخت فهرست هشدارها و یادآورهای پیشخوان بر اساس آمار و وضعیت محتوا.
     * سطح‌بندی: danger (بحرانی) > warn (نیازمند توجه) > info (اطلاعی).
     */
    private function buildAlerts(array $stats): array
    {
        $alerts = [];
        $contentDays = max(1, Settings::int('content_alert_days', 14));

        // ---------- ۱) موجودی انبار (ناموجود / رو به اتمام) ----------
        if (can('products.view')) {
            if ($stats['products_out'] > 0) {
                $alerts[] = [
                    'level'  => 'danger',
                    'icon'   => 'package-x',
                    'key'    => 'stock-out',
                    'title'  => money($stats['products_out']) . ' محصول ناموجود است',
                    'desc'   => $stats['products_low'] > 0
                        ? 'علاوه بر آن ' . money($stats['products_low']) . ' محصول هم رو به اتمام است؛ این اقلام اکنون قابل سفارش نیستند.'
                        : 'این اقلام اکنون در فروشگاه قابل سفارش نیستند؛ هرچه سریع‌تر موجودی را تأمین کنید.',
                    'url'    => admin_url('products', ['stock' => 'out']),
                    'action' => 'مشاهده ناموجودها',
                ];
            } elseif ($stats['products_low'] > 0) {
                $alerts[] = [
                    'level'  => 'warn',
                    'icon'   => 'package-minus',
                    'key'    => 'stock-low',
                    'title'  => money($stats['products_low']) . ' محصول رو به اتمام است',
                    'desc'   => 'موجودی این اقلام به حد هشدار رسیده؛ به‌زودی نیاز به تأمین دارند.',
                    'url'    => admin_url('products', ['stock' => 'low']),
                    'action' => 'مشاهده فهرست',
                ];
            }
        }

        // ---------- ۲) سفارش‌ها (ثبت جدید / در انتظار بررسی) ----------
        if (can('orders.view')) {
            if ($stats['orders_today'] > 0) {
                $alerts[] = [
                    'level'  => 'info',
                    'icon'   => 'shopping-bag',
                    'key'    => 'orders-today',
                    'title'  => money($stats['orders_today']) . ' سفارش جدید امروز ثبت شد',
                    'desc'   => 'فروش امروز: ' . money($stats['revenue_today']) . ' تومان — سفارش‌های جدید را بررسی و تأیید کنید.',
                    'url'    => admin_url('orders'),
                    'action' => 'مدیریت سفارش‌ها',
                ];
            }
            if ($stats['orders_processing'] > 0) {
                $alerts[] = [
                    'level'  => 'warn',
                    'icon'   => 'clock',
                    'key'    => 'orders-processing',
                    'title'  => money($stats['orders_processing']) . ' سفارش در انتظار بررسی است',
                    'desc'   => $stats['orders_packing'] > 0
                        ? 'همچنین ' . money($stats['orders_packing']) . ' سفارش در مرحله بسته‌بندی است.'
                        : 'پاسخ‌دادن سریع به سفارش‌ها تجربه خرید بهتری می‌سازد.',
                    'url'    => admin_url('orders', ['status' => 'processing']),
                    'action' => 'بررسی سفارش‌ها',
                ];
            }
        }

        // ---------- ۳) تولید محتوا (وبلاگ) ----------
        if (can('articles.view')) {
            $lastPublish = Model::scalar("SELECT MAX(created_at) FROM articles WHERE status = 'published'");
            if (empty($lastPublish)) {
                $alerts[] = [
                    'level'  => 'danger',
                    'icon'   => 'newspaper',
                    'key'    => 'content-none',
                    'title'  => 'هنوز هیچ مقاله‌ای در وبلاگ منتشر نشده است',
                    'desc'   => 'تولید محتوای منظم برای سئو و جذب مشتری اهمیت زیادی دارد؛ اولین مقاله را منتشر کنید.',
                    'url'    => admin_url('articles/create'),
                    'action' => 'نوشتن مقاله',
                ];
            } else {
                $daysPassed = max(0, (int) floor((time() - strtotime((string) $lastPublish)) / 86400));
                if ($daysPassed >= $contentDays) {
                    $alerts[] = [
                        'level'  => 'warn',
                        'icon'   => 'newspaper',
                        'key'    => 'content-stale',
                        'title'  => money($daysPassed) . ' روز از آخرین تولید محتوا گذشته است',
                        'desc'   => 'آخرین مقاله ' . timeAgo((string) $lastPublish) . ' منتشر شده؛ پیشنهاد می‌شود حداقل هفته‌ای یک مقاله منتشر شود.',
                        'url'    => admin_url('articles/create'),
                        'action' => 'مقاله جدید',
                    ];
                }
            }

            $drafts = (int) Model::count('articles', "status = 'draft'");
            if ($drafts > 0) {
                $alerts[] = [
                    'level'  => 'info',
                    'icon'   => 'file-text',
                    'key'    => 'content-drafts',
                    'title'  => money($drafts) . ' مقاله پیش‌نویس منتشر نشده است',
                    'desc'   => 'پیش‌نویس‌های آماده را کامل و منتشر کنید تا محتوای بلاک‌شده بلااستفاده نماند.',
                    'url'    => admin_url('articles', ['status' => 'draft']),
                    'action' => 'مشاهده پیش‌نویس‌ها',
                ];
            }
        }

        // ---------- ۴) سایر یادآورهای پشتیبانی و فروش ----------
        if (can('tickets.view') && $stats['tickets_open'] > 0) {
            $alerts[] = [
                'level'  => 'warn',
                'icon'   => 'headphones',
                'key'    => 'tickets-open',
                    'title'  => money($stats['tickets_open']) . ' تیکت باز پاسخ‌داده‌نشده دارید',
                'desc'   => 'سرعت پاسخ به تیکت‌ها تأثیر مستقیم روی رضایت مشتری دارد.',
                'url'    => admin_url('tickets'),
                'action' => 'پاسخ به تیکت‌ها',
            ];
        }

        if (can('comments.view') && $stats['comments_pending'] > 0) {
            $alerts[] = [
                'level'  => 'info',
                'icon'   => 'message-square',
                'key'    => 'comments-pending',
                'title'  => money($stats['comments_pending']) . ' دیدگاه در انتظار تأیید است',
                'desc'   => 'دیدگاه‌های تأییدشده اعتماد خریداران بعدی را جلب می‌کنند.',
                'url'    => admin_url('comments', ['status' => 'pending']),
                'action' => 'بررسی دیدگاه‌ها',
            ];
        }

        if (can('wallet.view') && $stats['wallet_pending'] > 0) {
            $alerts[] = [
                'level'  => 'warn',
                'icon'   => 'wallet',
                'key'    => 'wallet-pending',
                'title'  => money($stats['wallet_pending']) . ' درخواست شارژ کیف پول در انتظار رسیدگی است',
                'desc'   => 'درخواست‌های معلق کیف پول را تأیید یا رد کنید.',
                'url'    => admin_url('wallet'),
                'action' => 'رسیدگی کیف پول',
            ];
        }

        // ---------- مرتب‌سازی بر اساس شدت ----------
        $severity = ['danger' => 0, 'warn' => 1, 'info' => 2];
        usort($alerts, fn($a, $b) => ($severity[$a['level']] ?? 3) <=> ($severity[$b['level']] ?? 3));

        return $alerts;
    }
}
