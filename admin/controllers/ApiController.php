<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Model;
use Admin\core\Settings;

/**
 * نقاط پایانی JSON برای نوتیفیکیشن لحظه‌ای و جستجوی سریع
 */
class ApiController extends BaseController
{
    protected string $section = 'dashboard';

    /**
     * Polling سبک برای آگاه‌سازی از رویدادهای جدید.
     * فقط شمارنده‌ها و آخرین شناسه‌ها را برمی‌گرداند.
     */
    public function poll($id = 0): void
    {
        $since = [
            'order'   => (int) param('last_order', 0),
            'ticket'  => (int) param('last_ticket', 0),
            'wallet'  => (int) param('last_wallet', 0),
            'comment' => (int) param('last_comment', 0),
        ];

        $data = [
            'success' => true,
            'time'    => date('H:i'),
            'counts'  => [],
            'latest'  => [],
            'new'     => [],
        ];

        try {
            if (Auth::can('orders.view')) {
                $data['counts']['orders'] = (int) Model::scalar(
                    "SELECT COUNT(*) FROM orders WHERE status IN ('pending_payment','processing')");
                $maxOrder = (int) Model::scalar('SELECT COALESCE(MAX(id),0) FROM orders');
                $data['latest']['order'] = $maxOrder;

                if ($since['order'] > 0 && $maxOrder > $since['order']) {
                    $rows = Model::all('SELECT o.id, o.tracking_code, o.total_amount, u.full_name
                                        FROM orders o LEFT JOIN users u ON u.id = o.user_id
                                        WHERE o.id > ? ORDER BY o.id DESC LIMIT 5', [$since['order']]);
                    foreach ($rows as $r) {
                        $data['new'][] = [
                            'type'  => 'order',
                            'title' => 'سفارش جدید ثبت شد',
                            'body'  => ($r['full_name'] ?: 'مشتری') . ' — ' . money($r['total_amount']) . ' تومان',
                            'url'   => admin_url('orders/show/' . $r['id']),
                            'icon'  => 'shopping-bag',
                        ];
                    }
                }
            }

            if (Auth::can('tickets.view')) {
                $data['counts']['tickets'] = (int) Model::scalar(
                    "SELECT COUNT(*) FROM support_tickets WHERE status IN ('open','pending')");
                $maxTicket = (int) Model::scalar('SELECT COALESCE(MAX(id),0) FROM support_tickets');
                $data['latest']['ticket'] = $maxTicket;

                if ($since['ticket'] > 0 && $maxTicket > $since['ticket']) {
                    $rows = Model::all('SELECT t.id, t.subject, u.full_name FROM support_tickets t
                                        LEFT JOIN users u ON u.id = t.user_id
                                        WHERE t.id > ? ORDER BY t.id DESC LIMIT 5', [$since['ticket']]);
                    foreach ($rows as $r) {
                        $data['new'][] = [
                            'type'  => 'ticket',
                            'title' => 'تیکت پشتیبانی جدید',
                            'body'  => excerpt($r['subject'], 60) . ' — ' . ($r['full_name'] ?: ''),
                            'url'   => admin_url('tickets/show/' . $r['id']),
                            'icon'  => 'headphones',
                        ];
                    }
                }
            }

            if (Auth::can('wallet.view')) {
                $data['counts']['wallet'] = (int) Model::scalar(
                    "SELECT COUNT(*) FROM wallet_transactions WHERE status = 'pending'");
                $maxWallet = (int) Model::scalar("SELECT COALESCE(MAX(id),0) FROM wallet_transactions WHERE status = 'pending'");
                $data['latest']['wallet'] = $maxWallet;

                if ($since['wallet'] > 0 && $maxWallet > $since['wallet']) {
                    $rows = Model::all("SELECT w.id, w.amount, u.full_name FROM wallet_transactions w
                                        LEFT JOIN users u ON u.id = w.user_id
                                        WHERE w.id > ? AND w.status = 'pending' ORDER BY w.id DESC LIMIT 5", [$since['wallet']]);
                    foreach ($rows as $r) {
                        $data['new'][] = [
                            'type'  => 'wallet',
                            'title' => 'درخواست شارژ کیف پول',
                            'body'  => ($r['full_name'] ?: 'کاربر') . ' — ' . money($r['amount']) . ' تومان',
                            'url'   => admin_url('wallet'),
                            'icon'  => 'wallet',
                        ];
                    }
                }
            }

            if (Auth::can('comments.view')) {
                $data['counts']['comments'] = (int) Model::scalar(
                    "SELECT COUNT(*) FROM product_comments WHERE status = 'pending'");
                $maxComment = (int) Model::scalar("SELECT COALESCE(MAX(id),0) FROM product_comments WHERE status = 'pending'");
                $data['latest']['comment'] = $maxComment;

                if ($since['comment'] > 0 && $maxComment > $since['comment']) {
                    $data['new'][] = [
                        'type'  => 'comment',
                        'title' => 'دیدگاه جدید در انتظار تأیید',
                        'body'  => 'یک یا چند دیدگاه جدید ثبت شد.',
                        'url'   => admin_url('comments', ['status' => 'pending']),
                        'icon'  => 'message-square',
                    ];
                }
            }

            if (Auth::can('products.view')) {
                $data['counts']['products'] = (int) Model::scalar(
                    'SELECT COUNT(*) FROM products WHERE track_stock = 1 AND stock_qty <= low_stock_threshold');
            }

            $data['sound'] = Settings::bool('admin_notify_sound', true) && !empty($data['new']);
        } catch (\Throwable $e) {
            $data['success'] = false;
            $data['message'] = 'خطا در دریافت اعلان‌ها';
        }

        $this->json($data);
    }

    /** جستجوی سراسری سریع در پنل */
    public function search($id = 0): void
    {
        $q = trim((string) param('q', ''));
        if (mb_strlen($q) < 2) {
            $this->json(['success' => true, 'results' => []]);
        }
        $like = '%' . $q . '%';
        $results = [];

        if (Auth::can('orders.view')) {
            foreach (Model::all('SELECT o.id, o.tracking_code, o.total_amount, u.full_name FROM orders o
                                 LEFT JOIN users u ON u.id = o.user_id
                                 WHERE o.tracking_code LIKE ? OR o.shipping_tracking_code LIKE ?
                                 ORDER BY o.id DESC LIMIT 5', [$like, $like]) as $r) {
                $results[] = ['group' => 'سفارش', 'title' => $r['tracking_code'],
                    'sub' => ($r['full_name'] ?? '') . ' — ' . money($r['total_amount']) . ' تومان',
                    'url' => admin_url('orders/show/' . $r['id'])];
            }
        }
        if (Auth::can('products.view')) {
            foreach (Model::all('SELECT id, name, oem_code, stock_qty FROM products
                                 WHERE name LIKE ? OR oem_code LIKE ? ORDER BY id DESC LIMIT 5', [$like, $like]) as $r) {
                $results[] = ['group' => 'محصول', 'title' => $r['name'],
                    'sub' => 'کد: ' . ($r['oem_code'] ?: '—') . ' — موجودی: ' . $r['stock_qty'],
                    'url' => admin_url('products/edit/' . $r['id'])];
            }
        }
        if (Auth::can('users.view')) {
            foreach (Model::all('SELECT id, full_name, phone FROM users
                                 WHERE full_name LIKE ? OR phone LIKE ? ORDER BY id DESC LIMIT 5', [$like, $like]) as $r) {
                $results[] = ['group' => 'کاربر', 'title' => $r['full_name'] ?: 'بدون نام',
                    'sub' => $r['phone'], 'url' => admin_url('users/show/' . $r['id'])];
            }
        }
        if (Auth::can('tickets.view')) {
            foreach (Model::all('SELECT id, subject FROM support_tickets WHERE subject LIKE ?
                                 ORDER BY id DESC LIMIT 4', [$like]) as $r) {
                $results[] = ['group' => 'تیکت', 'title' => $r['subject'], 'sub' => 'تیکت #' . $r['id'],
                    'url' => admin_url('tickets/show/' . $r['id'])];
            }
        }

        $this->json(['success' => true, 'results' => $results]);
    }

    /** پیشنهاد محصول برای فرم‌ها */
    public function products($id = 0): void
    {
        $q = trim((string) param('q', ''));
        $rows = $q === '' ? [] : Model::all(
            'SELECT id, name, price, stock_qty, oem_code FROM products
             WHERE name LIKE ? OR oem_code LIKE ? ORDER BY name LIMIT 15',
            ['%' . $q . '%', '%' . $q . '%']);
        $this->json(['success' => true, 'items' => $rows]);
    }
}
