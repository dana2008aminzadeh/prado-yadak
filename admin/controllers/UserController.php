<?php
namespace Admin\controllers;

use Admin\core\Model;

class UserController extends BaseController
{
    protected string $section = 'users';

    public function index($id = 0): void
    {
        $q    = trim((string) param('q', ''));
        $role = param('role', '');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ? OR u.national_code LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($role !== '') { $where[] = 'u.role = ?'; $params[] = $role; }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM users u WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $users = Model::all(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                    (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o WHERE o.user_id = u.id AND o.status <> 'cancelled') AS spent
             FROM users u WHERE $w ORDER BY u.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}",
            $params
        );

        $this->view('users/index', compact('users', 'pg', 'q', 'role'), 'مدیریت کاربران', money($total) . ' کاربر ثبت‌شده');
    }

    public function show($id = 0): void
    {
        $user = Model::find('users', (int) $id);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }
        $orders   = Model::all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 15', [(int) $id]);
        $addresses = Model::all('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC', [(int) $id]);
        $vehicles = Model::all('SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY is_primary DESC', [(int) $id]);
        $wallet   = Model::all('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 15', [(int) $id]);
        $tickets  = Model::all('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC LIMIT 10', [(int) $id]);
        $wishlist = Model::all('SELECT w.*, p.name, p.price FROM wishlists w LEFT JOIN products p ON p.id = w.product_id WHERE w.user_id = ? LIMIT 20', [(int) $id]);
        $stats = [
            'orders' => Model::count('orders', 'user_id = ?', [(int) $id]),
            'spent'  => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE user_id = ? AND status <> 'cancelled'", [(int) $id]),
        ];

        $this->view('users/show', compact('user', 'orders', 'addresses', 'vehicles', 'wallet', 'tickets', 'wishlist', 'stats'),
            'پروفایل: ' . ($user['full_name'] ?: $user['phone']));
    }

    public function update($id = 0): void
    {
        $data = [
            'full_name'     => trim((string) post('full_name')),
            'phone'         => trim((string) post('phone')),
            'email'         => trim((string) post('email')) ?: null,
            'national_code' => trim((string) post('national_code')) ?: null,
            'city'          => trim((string) post('city')) ?: null,
            'role'          => post('role') === 'admin' ? 'admin' : 'user',
        ];
        $me = $this->admin();
        if ((int) $id === (int) ($me['id'] ?? 0) && $data['role'] !== 'admin') {
            $data['role'] = 'admin';
            flash('info', 'نمی‌توانید نقش مدیریتی خودتان را حذف کنید.');
        }
        $pass = (string) post('password');
        if ($pass !== '') {
            if (mb_strlen($pass) < 6) {
                flash('error', 'رمز عبور باید حداقل ۶ کاراکتر باشد.');
                redirect(admin_url('users/show/' . (int) $id));
            }
            $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        }
        Model::update('users', (int) $id, $data);
        flash('success', 'اطلاعات کاربر به‌روزرسانی شد.');
        redirect(admin_url('users/show/' . (int) $id));
    }

    /** شارژ یا کسر دستی کیف پول */
    public function wallet($id = 0): void
    {
        $amount = (float) str_replace(',', '', (string) post('amount', 0));
        $type   = in_array(post('type'), ['charge', 'purchase', 'refund', 'adjust'], true) ? (string) post('type') : 'adjust';
        $desc   = trim((string) post('description')) ?: 'تغییر دستی توسط مدیر';
        if ($amount == 0) {
            flash('error', 'مبلغ نامعتبر است.');
            redirect(admin_url('users/show/' . (int) $id));
        }
        $signed = in_array($type, ['charge', 'refund'], true) ? abs($amount) : -abs($amount);

        $db = Model::db();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?')->execute([$signed, (int) $id]);
            $balance = (float) Model::scalar('SELECT wallet_balance FROM users WHERE id = ?', [(int) $id]);
            Model::insert('wallet_transactions', [
                'user_id'        => (int) $id,
                'type'           => $type,
                'amount'         => abs($amount),
                'balance_after'  => $balance,
                'description'    => $desc,
                'reference_code' => 'ADM-' . strtoupper(bin2hex(random_bytes(4))),
                'status'         => 'completed',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $db->commit();
            flash('success', 'کیف پول کاربر به‌روزرسانی شد. موجودی جدید: ' . money($balance) . ' تومان');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در ثبت تراکنش: ' . $e->getMessage());
        }
        redirect(admin_url('users/show/' . (int) $id));
    }

    public function delete($id = 0): void
    {
        $me = $this->admin();
        if ((int) $id === (int) ($me['id'] ?? 0)) {
            flash('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
            redirect(admin_url('users'));
        }
        if (Model::count('orders', 'user_id = ?', [(int) $id]) > 0) {
            flash('error', 'این کاربر سفارش ثبت‌شده دارد و قابل حذف نیست.');
            redirect(admin_url('users/show/' . (int) $id));
        }
        foreach (['user_addresses', 'user_vehicles', 'wishlists', 'cart_items', 'wallet_transactions'] as $t) {
            Model::exec("DELETE FROM `$t` WHERE user_id = ?", [(int) $id]);
        }
        Model::delete('users', (int) $id);
        flash('success', 'کاربر حذف شد.');
        redirect(admin_url('users'));
    }

    public function create($id = 0): void
    {
        if (!$this->isPost()) {
            redirect(admin_url('users'));
        }
        $phone = trim((string) post('phone'));
        $pass  = (string) post('password');
        if ($phone === '' || mb_strlen($pass) < 6) {
            flash('error', 'شماره موبایل و رمز (حداقل ۶ کاراکتر) الزامی است.');
            redirect(admin_url('users'));
        }
        if (Model::one('SELECT id FROM users WHERE phone = ?', [$phone])) {
            flash('error', 'کاربری با این شماره موبایل قبلاً ثبت شده است.');
            redirect(admin_url('users'));
        }
        $newId = Model::insert('users', [
            'full_name'     => trim((string) post('full_name')) ?: 'کاربر جدید',
            'phone'         => $phone,
            'email'         => trim((string) post('email')) ?: null,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role'          => post('role') === 'admin' ? 'admin' : 'user',
        ]);
        flash('success', 'کاربر جدید ایجاد شد.');
        redirect(admin_url('users/show/' . $newId));
    }

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT id, full_name, phone, email, national_code, city, role, wallet_balance, created_at FROM users ORDER BY id");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['شناسه', 'نام', 'موبایل', 'ایمیل', 'کد ملی', 'شهر', 'نقش', 'کیف پول', 'تاریخ عضویت']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}
