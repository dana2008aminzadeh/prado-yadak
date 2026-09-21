<?php
namespace Admin\controllers;

use Admin\core\Model;

class OrderController extends BaseController
{
    protected string $section = 'orders';

    public const STATUSES = [
        'processing' => 'در حال پردازش',
        'shipped'    => 'ارسال شده',
        'delivered'  => 'تحویل شده',
        'cancelled'  => 'لغو شده',
    ];

    public function index($id = 0): void
    {
        $q      = trim((string) param('q', ''));
        $status = param('status', '');
        $from   = param('from', '');
        $to     = param('to', '');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(o.tracking_code LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ? OR o.recipient_phone LIKE ? OR o.bank_reference LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($status !== '' && isset(self::STATUSES[$status])) { $where[] = 'o.status = ?'; $params[] = $status; }
        if ($from !== '') { $where[] = 'o.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to !== '')   { $where[] = 'o.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $orders = Model::all(
            "SELECT o.*, u.full_name, u.phone,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
             FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE $w ORDER BY o.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}",
            $params
        );

        $sumFiltered = (float) Model::scalar("SELECT COALESCE(SUM(o.total_amount),0) FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE $w AND o.status <> 'cancelled'", $params);
        $statuses = self::STATUSES;

        $this->view('orders/index', compact('orders', 'pg', 'q', 'status', 'from', 'to', 'statuses', 'sumFiltered'),
            'مدیریت سفارش‌ها', money($total) . ' سفارش — مجموع ' . money($sumFiltered) . ' تومان');
    }

    public function show($id = 0): void
    {
        $order = Model::one('SELECT o.*, u.full_name, u.phone, u.email, u.wallet_balance
                             FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?', [(int) $id]);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }
        $items = Model::all('SELECT oi.*, p.name, p.slug, p.telegram_photo_id, p.oem_code
                             FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
                             WHERE oi.order_id = ?', [(int) $id]);
        $history = Model::all('SELECT id, tracking_code, total_amount, status, created_at FROM orders
                               WHERE user_id = ? AND id <> ? ORDER BY created_at DESC LIMIT 5', [(int) $order['user_id'], (int) $id]);
        $statuses = self::STATUSES;

        $this->view('orders/show', compact('order', 'items', 'history', 'statuses'),
            'سفارش ' . $order['tracking_code']);
    }

    public function status($id = 0): void
    {
        $new = (string) post('status');
        if (!isset(self::STATUSES[$new])) {
            flash('error', 'وضعیت نامعتبر است.');
            redirect(admin_url('orders/show/' . (int) $id));
        }
        Model::exec('UPDATE orders SET status = ? WHERE id = ?', [$new, (int) $id]);
        flash('success', 'وضعیت سفارش به «' . self::STATUSES[$new] . '» تغییر کرد.');
        redirect(admin_url('orders/show/' . (int) $id));
    }

    public function update($id = 0): void
    {
        $data = [
            'recipient_name'   => trim((string) post('recipient_name')) ?: null,
            'recipient_phone'  => trim((string) post('recipient_phone')) ?: null,
            'shipping_address' => trim((string) post('shipping_address')) ?: null,
            'postal_code'      => trim((string) post('postal_code')) ?: null,
            'payer_name'       => trim((string) post('payer_name')) ?: null,
            'bank_reference'   => trim((string) post('bank_reference')) ?: null,
            'user_notes'       => trim((string) post('user_notes')) ?: null,
        ];
        Model::update('orders', (int) $id, $data);
        flash('success', 'اطلاعات سفارش به‌روزرسانی شد.');
        redirect(admin_url('orders/show/' . (int) $id));
    }

    public function delete($id = 0): void
    {
        Model::exec('DELETE FROM order_items WHERE order_id = ?', [(int) $id]);
        Model::delete('orders', (int) $id);
        flash('success', 'سفارش حذف شد.');
        redirect(admin_url('orders'));
    }

    public function invoice($id = 0): void
    {
        $order = Model::one('SELECT o.*, u.full_name, u.phone FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?', [(int) $id]);
        if (!$order) {
            http_response_code(404);
            exit('سفارش یافت نشد.');
        }
        $items = Model::all('SELECT oi.*, p.name, p.oem_code FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [(int) $id]);
        $settings = $GLOBALS['settings'] ?? [];
        require ADMIN_PATH . '/views/pages/orders/invoice.php';
        exit;
    }

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT o.tracking_code, u.full_name, u.phone, o.recipient_name, o.recipient_phone,
                                   o.shipping_address, o.postal_code, o.subtotal, o.discount_amount, o.total_amount,
                                   o.applied_coupon, o.status, o.created_at
                            FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['کد رهگیری', 'مشتری', 'موبایل', 'گیرنده', 'موبایل گیرنده', 'آدرس', 'کدپستی', 'جمع', 'تخفیف', 'قابل پرداخت', 'کوپن', 'وضعیت', 'تاریخ']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}
