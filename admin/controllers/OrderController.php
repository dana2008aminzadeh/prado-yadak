<?php
namespace Admin\controllers;

use Admin\core\Inventory;
use Admin\core\Model;
use Admin\core\Settings;
use Admin\core\Sms;

class OrderController extends BaseController
{
    protected string $section = 'orders';

    protected array $permissions = [
        'status'   => 'orders.status',
        'shipping' => 'orders.status',
        'update'   => 'orders.edit',
        'note'     => 'orders.status',
        'delete'   => 'orders.delete',
        'invoice'  => 'orders.invoice',
        'restock'  => 'orders.status',
        'notify'   => 'orders.status',
    ];

    public const STATUSES = [
        'pending_payment' => 'در انتظار پرداخت',
        'processing'      => 'در حال پردازش',
        'packing'         => 'در حال بسته‌بندی',
        'shipped'         => 'ارسال شده',
        'delivered'       => 'تحویل شده',
        'cancelled'       => 'لغو شده',
        'returned'        => 'مرجوع شده',
    ];

    public const STATUS_COLORS = [
        'pending_payment' => 'b-gray', 'processing' => 'b-amber', 'packing' => 'b-blue',
        'shipped' => 'b-blue', 'delivered' => 'b-green', 'cancelled' => 'b-red', 'returned' => 'b-red',
    ];

    /** قالب پیامک متناظر با هر وضعیت */
    public const STATUS_SMS = [
        'processing' => 'order_processing',
        'packing'    => 'order_packing',
        'shipped'    => 'order_shipped',
        'delivered'  => 'order_delivered',
        'cancelled'  => 'order_cancelled',
    ];

    public const CARRIERS = [
        'post'   => 'پست جمهوری اسلامی',
        'tipax'  => 'تیپاکس',
        'chapar' => 'چاپار',
        'mahex'  => 'ماهکس',
        'barani' => 'باربری',
        'inperson' => 'تحویل حضوری',
        'peyk'   => 'پیک شهری',
    ];

    // ---------------------------------------------------------------- لیست

    public function index($id = 0): void
    {
        $q       = trim((string) param('q', ''));
        $status  = param('status', '');
        $carrier = param('carrier', '');
        $from    = $this->dateParam('from');
        $to      = $this->dateParam('to');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(o.tracking_code LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ? OR o.recipient_phone LIKE ?
                         OR o.bank_reference LIKE ? OR o.shipping_tracking_code LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like, $like, $like);
        }
        if ($status !== '' && isset(self::STATUSES[$status])) { $where[] = 'o.status = ?'; $params[] = $status; }
        if ($carrier !== '') { $where[] = 'o.shipping_carrier = ?'; $params[] = $carrier; }
        if ($from) { $where[] = 'o.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to)   { $where[] = 'o.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
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

        $sumFiltered = (float) Model::scalar(
            "SELECT COALESCE(SUM(o.total_amount),0) FROM orders o LEFT JOIN users u ON u.id = o.user_id
             WHERE $w AND o.status NOT IN ('cancelled','returned')", $params);

        $statuses = self::STATUSES;
        $colors = self::STATUS_COLORS;
        $carriers = self::CARRIERS;
        $fromJ = param('from', '');
        $toJ = param('to', '');

        $this->view('orders/index',
            compact('orders', 'pg', 'q', 'status', 'carrier', 'fromJ', 'toJ', 'statuses', 'colors', 'carriers', 'sumFiltered'),
            'مدیریت سفارش‌ها', money($total) . ' سفارش — مجموع ' . money($sumFiltered) . ' تومان');
    }

    // ---------------------------------------------------------------- جزئیات

    public function show($id = 0): void
    {
        $order = Model::one('SELECT o.*, u.full_name, u.phone, u.email, u.wallet_balance, u.national_code
                             FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?', [(int) $id]);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }

        $items = Model::all('SELECT oi.*, p.name, p.slug, p.telegram_photo_id, p.oem_code, p.stock_qty
                             FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
                             WHERE oi.order_id = ?', [(int) $id]);

        $history = Model::all('SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at DESC, id DESC', [(int) $id]);

        $otherOrders = Model::all('SELECT id, tracking_code, total_amount, status, created_at FROM orders
                                   WHERE user_id = ? AND id <> ? ORDER BY created_at DESC LIMIT 5',
                                   [(int) $order['user_id'], (int) $id]);

        $smsLogs = Model::all('SELECT * FROM sms_logs WHERE phone = ? ORDER BY created_at DESC LIMIT 5',
                              [(string) ($order['recipient_phone'] ?: $order['phone'])]);

        $shippingMethods = Model::all('SELECT * FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order');

        $statuses = self::STATUSES;
        $colors = self::STATUS_COLORS;
        $carriers = self::CARRIERS;
        $smsEnabled = Settings::bool('sms_enabled');

        $this->view('orders/show',
            compact('order', 'items', 'history', 'otherOrders', 'smsLogs', 'shippingMethods',
                    'statuses', 'colors', 'carriers', 'smsEnabled'),
            'سفارش ' . $order['tracking_code'], 'ثبت‌شده در ' . shamsiTime($order['created_at']));
    }

    // ---------------------------------------------------------------- تغییر وضعیت

    public function status($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $new = (string) post('status');
        $note = trim((string) post('note'));
        $isPublic = post('is_public') ? 1 : 0;
        $sendSms = (bool) post('send_sms');

        $order = Model::find('orders', $oid);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }
        if (!isset(self::STATUSES[$new])) {
            flash('error', 'وضعیت انتخابی نامعتبر است.');
            back(admin_url('orders/show/' . $oid));
        }

        $oldStatus = (string) $order['status'];
        if ($oldStatus === $new && $note === '') {
            flash('info', 'وضعیت سفارش تغییری نکرد.');
            back(admin_url('orders/show/' . $oid));
        }

        $db = Model::db();
        $db->beginTransaction();
        try {
            $update = ['status' => $new];

            // مهر زمانی مراحل
            if ($new === 'shipped' && empty($order['shipped_at'])) {
                $update['shipped_at'] = date('Y-m-d H:i:s');
            }
            if ($new === 'delivered' && empty($order['delivered_at'])) {
                $update['delivered_at'] = date('Y-m-d H:i:s');
            }

            Model::update('orders', $oid, $update);

            // --- همگام‌سازی انبار ---
            $deductStates = ['processing', 'packing', 'shipped', 'delivered'];
            $restoreStates = ['cancelled', 'returned'];

            if (in_array($new, $deductStates, true) && (int) $order['stock_deducted'] === 0) {
                $r = Inventory::deductOrder($oid);
                if (!empty($r['warnings'])) {
                    flash('info', 'هشدار موجودی: ' . implode('، ', $r['warnings']));
                }
            } elseif (in_array($new, $restoreStates, true) && (int) $order['stock_deducted'] === 1) {
                Inventory::restoreOrder($oid);
                flash('info', 'موجودی اقلام این سفارش به انبار بازگردانده شد.');
            }

            // --- ثبت تاریخچه ---
            Model::insert('order_status_history', [
                'order_id'    => $oid,
                'from_status' => $oldStatus,
                'to_status'   => $new,
                'note'        => $note !== '' ? mb_substr($note, 0, 500) : null,
                'is_public'   => $isPublic,
                'admin_id'    => $this->adminId(),
                'admin_name'  => $this->adminName(),
            ]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در تغییر وضعیت: ' . $e->getMessage());
            back(admin_url('orders/show/' . $oid));
        }

        $this->audit('order.status', 'order', $oid,
            'وضعیت سفارش ' . $order['tracking_code'] . ' از «' . (self::STATUSES[$oldStatus] ?? $oldStatus)
            . '» به «' . self::STATUSES[$new] . '» تغییر کرد.' . ($note ? ' یادداشت: ' . $note : ''));

        // --- اطلاع‌رسانی پیامکی ---
        if ($sendSms) {
            $res = $this->sendStatusSms($oid, $new);
            flash($res['success'] ? 'success' : 'error', $res['message']);
        }

        flash('success', 'وضعیت سفارش به «' . self::STATUSES[$new] . '» تغییر کرد.');
        back(admin_url('orders/show/' . $oid));
    }

    /** ثبت بارنامه/کد رهگیری پستی و اطلاع‌رسانی */
    public function shipping($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $order = Model::find('orders', $oid);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }

        $carrier = (string) post('shipping_carrier');
        $code = \Admin\core\Jalali::toEnglishDigits(trim((string) post('shipping_tracking_code')));
        $code = preg_replace('/[^A-Za-z0-9\-]/', '', $code) ?? '';
        $cost = (float) preg_replace('/[^\d.]/', '', (string) post('shipping_cost', 0));
        $markShipped = (bool) post('mark_shipped');
        $sendSms = (bool) post('send_sms');
        $note = trim((string) post('note'));

        if ($carrier !== '' && !isset(self::CARRIERS[$carrier])) {
            flash('error', 'شرکت حمل انتخابی نامعتبر است.');
            back(admin_url('orders/show/' . $oid));
        }
        // کد رهگیری پست ایران ۲۴ رقمی است؛ بقیه شرکت‌ها آزادترند
        if ($carrier === 'post' && $code !== '' && !preg_match('/^\d{20,26}$/', $code)) {
            flash('error', 'کد رهگیری پست باید عددی و حدود ۲۴ رقم باشد.');
            back(admin_url('orders/show/' . $oid));
        }

        $old = ['shipping_carrier' => $order['shipping_carrier'], 'shipping_tracking_code' => $order['shipping_tracking_code']];

        $data = [
            'shipping_carrier'       => $carrier ?: null,
            'shipping_tracking_code' => $code ?: null,
            'shipping_cost'          => $cost,
        ];
        if (post('shipping_method_id') !== '') {
            $data['shipping_method_id'] = (int) post('shipping_method_id');
        }

        $db = Model::db();
        $db->beginTransaction();
        try {
            if ($markShipped && $order['status'] !== 'shipped') {
                $data['status'] = 'shipped';
                $data['shipped_at'] = date('Y-m-d H:i:s');

                if ((int) $order['stock_deducted'] === 0) {
                    Inventory::deductOrder($oid);
                }
                Model::insert('order_status_history', [
                    'order_id' => $oid, 'from_status' => $order['status'], 'to_status' => 'shipped',
                    'note' => $note !== '' ? $note : ('مرسوله با ' . (self::CARRIERS[$carrier] ?? $carrier) . ' ارسال شد.'),
                    'is_public' => 1, 'admin_id' => $this->adminId(), 'admin_name' => $this->adminName(),
                ]);
            } elseif ($note !== '') {
                Model::insert('order_status_history', [
                    'order_id' => $oid, 'from_status' => $order['status'], 'to_status' => $order['status'],
                    'note' => $note, 'is_public' => post('is_public') ? 1 : 0,
                    'admin_id' => $this->adminId(), 'admin_name' => $this->adminName(),
                ]);
            }

            Model::update('orders', $oid, $data);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در ثبت بارنامه: ' . $e->getMessage());
            back(admin_url('orders/show/' . $oid));
        }

        $this->audit('order.shipping', 'order', $oid,
            'ثبت بارنامه برای ' . $order['tracking_code'] . ' — ' . (self::CARRIERS[$carrier] ?? '—') . ' / ' . ($code ?: 'بدون کد'),
            $old, $data);

        if ($sendSms) {
            $res = $this->sendStatusSms($oid, 'shipped');
            flash($res['success'] ? 'success' : 'error', $res['message']);
        }

        flash('success', 'اطلاعات ارسال ثبت شد.');
        back(admin_url('orders/show/' . $oid));
    }

    /** افزودن یادداشت به تاریخچه بدون تغییر وضعیت */
    public function note($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $order = Model::find('orders', $oid);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }
        $note = trim((string) post('note'));
        if ($note === '') {
            flash('error', 'متن یادداشت خالی است.');
            back(admin_url('orders/show/' . $oid));
        }

        Model::insert('order_status_history', [
            'order_id' => $oid, 'from_status' => $order['status'], 'to_status' => $order['status'],
            'note' => mb_substr($note, 0, 500), 'is_public' => post('is_public') ? 1 : 0,
            'admin_id' => $this->adminId(), 'admin_name' => $this->adminName(),
        ]);

        $this->audit('order.update', 'order', $oid, 'افزودن یادداشت به سفارش ' . $order['tracking_code']);
        flash('success', 'یادداشت ثبت شد.');
        back(admin_url('orders/show/' . $oid));
    }

    /** ارسال دستی پیامک وضعیت */
    public function notify($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $status = (string) post('status');
        if (!isset(self::STATUS_SMS[$status])) {
            flash('error', 'برای این وضعیت قالب پیامکی تعریف نشده است.');
            back(admin_url('orders/show/' . $oid));
        }
        $res = $this->sendStatusSms($oid, $status);
        flash($res['success'] ? 'success' : 'error', $res['message']);
        back(admin_url('orders/show/' . $oid));
    }

    /** ارسال پیامک متناظر با وضعیت سفارش */
    private function sendStatusSms(int $oid, string $status): array
    {
        $key = self::STATUS_SMS[$status] ?? null;
        if (!$key) {
            return ['success' => false, 'message' => 'قالب پیامکی برای این وضعیت وجود ندارد.'];
        }

        $order = Model::one('SELECT o.*, u.full_name, u.phone FROM orders o
                             LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?', [$oid]);
        if (!$order) {
            return ['success' => false, 'message' => 'سفارش یافت نشد.'];
        }

        $phone = (string) ($order['recipient_phone'] ?: $order['phone']);
        if (!$phone) {
            return ['success' => false, 'message' => 'شماره موبایلی برای اطلاع‌رسانی ثبت نشده است.'];
        }

        $res = Sms::sendTemplate($key, $phone, [
            'name'     => $order['recipient_name'] ?: ($order['full_name'] ?: 'مشتری'),
            'order'    => $order['tracking_code'],
            'tracking' => $order['shipping_tracking_code'] ?: '—',
            'carrier'  => self::CARRIERS[$order['shipping_carrier']] ?? ($order['shipping_carrier'] ?: '—'),
            'amount'   => money($order['total_amount']),
        ], (int) $order['user_id']);

        if (!empty($res['success'])) {
            Model::exec('UPDATE order_status_history SET notified_sms = 1
                         WHERE order_id = ? ORDER BY id DESC LIMIT 1', [$oid]);
            $this->audit('sms.send', 'order', $oid, 'ارسال پیامک «' . $key . '» به ' . $phone);
            return ['success' => true, 'message' => 'پیامک اطلاع‌رسانی به ' . $phone . ' ارسال شد.'];
        }

        return ['success' => false, 'message' => 'ارسال پیامک ناموفق بود: ' . ($res['message'] ?? '')];
    }

    // ---------------------------------------------------------------- ویرایش

    public function update($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $old = Model::find('orders', $oid);
        if (!$old) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }

        $data = [
            'recipient_name'   => trim((string) post('recipient_name')) ?: null,
            'recipient_phone'  => \Admin\core\Auth::normalizePhone((string) post('recipient_phone')) ?: null,
            'shipping_address' => trim((string) post('shipping_address')) ?: null,
            'postal_code'      => preg_replace('/\D/', '', (string) post('postal_code')) ?: null,
            'payer_name'       => trim((string) post('payer_name')) ?: null,
            'bank_reference'   => trim((string) post('bank_reference')) ?: null,
            'user_notes'       => trim((string) post('user_notes')) ?: null,
            'admin_notes'      => trim((string) post('admin_notes')) ?: null,
        ];

        Model::update('orders', $oid, $data);
        $this->audit('order.update', 'order', $oid, 'ویرایش اطلاعات سفارش ' . $old['tracking_code'], $old, $data);

        flash('success', 'اطلاعات سفارش به‌روزرسانی شد.');
        back(admin_url('orders/show/' . $oid));
    }

    public function restock($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $order = Model::find('orders', $oid);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }

        $mode = (string) post('mode', 'restore');
        $res = $mode === 'deduct' ? Inventory::deductOrder($oid) : Inventory::restoreOrder($oid);

        if (!empty($res['success'])) {
            $this->audit('product.stock', 'order', $oid,
                ($mode === 'deduct' ? 'کسر' : 'بازگرداندن') . ' موجودی اقلام سفارش ' . $order['tracking_code']);
            flash('success', $mode === 'deduct' ? 'موجودی اقلام کسر شد.' : 'موجودی اقلام به انبار بازگشت.');
        } else {
            flash('error', $res['message'] ?? 'عملیات ناموفق بود.');
        }
        back(admin_url('orders/show/' . $oid));
    }

    public function delete($id = 0): void
    {
        $oid = (int) post('order_id', $id);
        $order = Model::find('orders', $oid);
        if (!$order) {
            flash('error', 'سفارش یافت نشد.');
            redirect(admin_url('orders'));
        }

        if ((int) $order['stock_deducted'] === 1) {
            Inventory::restoreOrder($oid);
        }
        Model::exec('DELETE FROM order_items WHERE order_id = ?', [$oid]);
        Model::exec('DELETE FROM order_status_history WHERE order_id = ?', [$oid]);
        Model::delete('orders', $oid);

        $this->audit('order.delete', 'order', $oid,
            'حذف سفارش ' . $order['tracking_code'] . ' به مبلغ ' . money($order['total_amount']) . ' تومان', $order, null);
        flash('success', 'سفارش حذف شد و موجودی اقلام بازگردانده شد.');
        redirect(admin_url('orders'));
    }

    // ---------------------------------------------------------------- فاکتور

    public function invoice($id = 0): void
    {
        $order = Model::one('SELECT o.*, u.full_name, u.phone, u.national_code, u.national_company_id, u.economic_code, u.email
                             FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.id = ?', [(int) $id]);
        if (!$order) {
            http_response_code(404);
            exit('سفارش یافت نشد.');
        }

        $items = Model::all('SELECT oi.*, p.name, p.oem_code, p.brand FROM order_items oi
                             LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [(int) $id]);

        $settings = $GLOBALS['settings'] ?? [];
        $carriers = self::CARRIERS;
        $statuses = self::STATUSES;
        $vatPercent = (float) Settings::get('invoice_vat_percent', 0);

        $this->audit('order.invoice', 'order', (int) $id, 'صدور فاکتور رسمی برای ' . $order['tracking_code']);

        require ADMIN_PATH . '/views/pages/orders/invoice.php';
        exit;
    }

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT o.tracking_code, u.full_name, u.phone, o.recipient_name, o.recipient_phone,
                                   o.shipping_address, o.postal_code, o.subtotal, o.discount_amount, o.shipping_cost,
                                   o.total_amount, o.applied_coupon, o.shipping_carrier, o.shipping_tracking_code,
                                   o.status, o.created_at
                            FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC");

        $data = array_map(fn($r) => [
            $r['tracking_code'], $r['full_name'] ?? '', $r['phone'] ?? '', $r['recipient_name'] ?? '',
            $r['recipient_phone'] ?? '', $r['shipping_address'] ?? '', $r['postal_code'] ?? '',
            $r['subtotal'], $r['discount_amount'], $r['shipping_cost'], $r['total_amount'],
            $r['applied_coupon'] ?? '', self::CARRIERS[$r['shipping_carrier']] ?? '',
            $r['shipping_tracking_code'] ?? '', self::STATUSES[$r['status']] ?? $r['status'],
            shamsiTime($r['created_at']),
        ], $rows);

        $this->streamCsv('orders-' . date('Y-m-d') . '.csv',
            ['کد رهگیری', 'مشتری', 'موبایل', 'گیرنده', 'موبایل گیرنده', 'آدرس', 'کدپستی', 'جمع اقلام',
             'تخفیف', 'هزینه ارسال', 'قابل پرداخت', 'کوپن', 'شرکت حمل', 'بارنامه', 'وضعیت', 'تاریخ'],
            $data);
    }
}
