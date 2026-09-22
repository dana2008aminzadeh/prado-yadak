<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Model;
use Admin\core\Sms;

class UserController extends BaseController
{
    protected string $section = 'users';

    protected array $permissions = [
        'update' => 'users.edit',
        'store'  => 'users.edit',
        'delete' => 'users.delete',
        'wallet' => 'wallet.adjust',
        'sms'    => 'sms.send',
    ];

    public function index($id = 0): void
    {
        $q    = trim((string) param('q', ''));
        $role = param('role', '');
        $sort = param('sort', 'new');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.full_name LIKE ? OR u.phone LIKE ? OR u.email LIKE ? OR u.national_code LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like);
        }
        if ($role !== '') { $where[] = 'u.role = ?'; $params[] = $role; }
        $w = implode(' AND ', $where);

        $orderBy = match ($sort) {
            'spent'  => 'spent DESC',
            'orders' => 'orders_count DESC',
            'wallet' => 'u.wallet_balance DESC',
            'name'   => 'u.full_name ASC',
            default  => 'u.created_at DESC',
        };

        $total = (int) Model::scalar("SELECT COUNT(*) FROM users u WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $users = Model::all(
            "SELECT u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                    (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o
                     WHERE o.user_id = u.id AND o.status NOT IN ('cancelled','returned')) AS spent
             FROM users u WHERE $w ORDER BY $orderBy LIMIT {$this->perPage} OFFSET {$pg['offset']}",
            $params
        );

        $this->view('users/index', compact('users', 'pg', 'q', 'role', 'sort'),
            'مدیریت کاربران', money($total) . ' کاربر ثبت‌شده');
    }

    public function show($id = 0): void
    {
        $user = Model::find('users', (int) $id);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }

        $orders    = Model::all('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 15', [(int) $id]);
        $addresses = Model::all('SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC', [(int) $id]);
        $vehicles  = Model::all('SELECT * FROM user_vehicles WHERE user_id = ? ORDER BY is_primary DESC', [(int) $id]);
        $wallet    = Model::all('SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 15', [(int) $id]);
        $tickets   = Model::all('SELECT * FROM support_tickets WHERE user_id = ? ORDER BY updated_at DESC LIMIT 10', [(int) $id]);
        $wishlist  = Model::all('SELECT w.*, p.name, p.price FROM wishlists w
                                 LEFT JOIN products p ON p.id = w.product_id WHERE w.user_id = ? LIMIT 20', [(int) $id]);
        $smsLogs   = Model::all('SELECT * FROM sms_logs WHERE user_id = ? OR phone = ? ORDER BY created_at DESC LIMIT 10',
                                [(int) $id, (string) $user['phone']]);
        $roles     = Model::all('SELECT id, name, slug FROM admin_roles ORDER BY is_system DESC, id');

        $stats = [
            'orders' => Model::count('orders', 'user_id = ?', [(int) $id]),
            'spent'  => (float) Model::scalar("SELECT COALESCE(SUM(total_amount),0) FROM orders
                                               WHERE user_id = ? AND status NOT IN ('cancelled','returned')", [(int) $id]),
            'avg'    => (float) Model::scalar("SELECT COALESCE(AVG(total_amount),0) FROM orders
                                               WHERE user_id = ? AND status NOT IN ('cancelled','returned')", [(int) $id]),
            'last'   => Model::scalar('SELECT MAX(created_at) FROM orders WHERE user_id = ?', [(int) $id]),
        ];

        $this->view('users/show',
            compact('user', 'orders', 'addresses', 'vehicles', 'wallet', 'tickets', 'wishlist', 'stats', 'smsLogs', 'roles'),
            'پروفایل: ' . ($user['full_name'] ?: $user['phone']));
    }

    public function update($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        $old = Model::find('users', $uid);
        if (!$old) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }

        $phone = Auth::normalizePhone((string) post('phone'));
        if (!preg_match('/^09\d{9}$/', $phone)) {
            flash('error', 'شماره موبایل معتبر نیست.');
            back(admin_url('users/show/' . $uid));
        }
        $dup = Model::one('SELECT id FROM users WHERE phone = ? AND id <> ?', [$phone, $uid]);
        if ($dup) {
            flash('error', 'این شماره موبایل قبلاً برای کاربر دیگری ثبت شده است.');
            back(admin_url('users/show/' . $uid));
        }

        $data = [
            'full_name'           => mb_substr(trim((string) post('full_name')), 0, 100),
            'phone'               => $phone,
            'email'               => trim((string) post('email')) ?: null,
            'national_code'       => preg_replace('/\D/', '', (string) post('national_code')) ?: null,
            'national_company_id' => preg_replace('/\D/', '', (string) post('national_company_id')) ?: null,
            'economic_code'       => preg_replace('/\D/', '', (string) post('economic_code')) ?: null,
            'city'                => trim((string) post('city')) ?: null,
        ];

        // تغییر نقش فقط با دسترسی مخصوص
        if (can('users.roles')) {
            $newRole = post('role') === 'admin' ? 'admin' : 'user';
            if ($uid === $this->adminId() && $newRole !== 'admin') {
                flash('info', 'نمی‌توانید نقش مدیریتی خودتان را حذف کنید.');
            } else {
                $data['role'] = $newRole;
                if ($newRole === 'admin') {
                    $roleId = post('admin_role_id') !== '' ? (int) post('admin_role_id') : null;
                    $data['admin_role_id'] = $roleId;
                } else {
                    $data['admin_role_id'] = null;
                }
            }
        }

        $pass = (string) post('password');
        if ($pass !== '') {
            if (!can('users.edit')) {
                flash('error', 'اجازه تغییر رمز عبور را ندارید.');
                back(admin_url('users/show/' . $uid));
            }
            if (mb_strlen($pass) < 8) {
                flash('error', 'رمز عبور باید حداقل ۸ کاراکتر باشد.');
                back(admin_url('users/show/' . $uid));
            }
            $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
        }

        Model::update('users', $uid, $data);

        $this->audit('user.update', 'user', $uid, 'ویرایش کاربر ' . ($old['full_name'] ?: $old['phone']), $old, $data);
        if ($pass !== '') {
            $this->audit('user.password', 'user', $uid, 'تغییر رمز عبور کاربر ' . ($old['full_name'] ?: $old['phone']));
        }
        if (isset($data['role']) && $data['role'] !== $old['role']) {
            $this->audit('user.role', 'user', $uid, 'تغییر نقش از ' . $old['role'] . ' به ' . $data['role']);
        }

        flash('success', 'اطلاعات کاربر به‌روزرسانی شد.');
        back(admin_url('users/show/' . $uid));
    }

    /** شارژ یا کسر دستی کیف پول */
    public function wallet($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        $user = Model::find('users', $uid);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }

        $amount = (float) preg_replace('/[^\d.]/', '', (string) post('amount', 0));
        $type   = in_array(post('type'), ['charge', 'purchase', 'refund', 'adjust'], true) ? (string) post('type') : 'adjust';
        $desc   = trim((string) post('description')) ?: 'تغییر دستی توسط مدیر';
        $notify = (bool) post('send_sms');

        if ($amount <= 0) {
            flash('error', 'مبلغ باید بزرگ‌تر از صفر باشد.');
            back(admin_url('users/show/' . $uid));
        }

        $isCredit = in_array($type, ['charge', 'refund'], true);
        $signed = $isCredit ? $amount : -$amount;
        $balanceBefore = (float) $user['wallet_balance'];

        if (!$isCredit && $balanceBefore < $amount) {
            flash('error', 'موجودی کیف پول کاربر (' . money($balanceBefore) . ' تومان) برای این کسر کافی نیست.');
            back(admin_url('users/show/' . $uid));
        }

        $db = Model::db();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?')->execute([$signed, $uid]);
            $balance = (float) Model::scalar('SELECT wallet_balance FROM users WHERE id = ?', [$uid]);

            Model::insert('wallet_transactions', [
                'user_id'        => $uid,
                'type'           => $type,
                'amount'         => $amount,
                'balance_after'  => $balance,
                'description'    => mb_substr($desc, 0, 255),
                'reference_code' => 'ADM-' . strtoupper(bin2hex(random_bytes(4))),
                'status'         => 'completed',
                'created_at'     => date('Y-m-d H:i:s'),
            ]);
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در ثبت تراکنش: ' . $e->getMessage());
            back(admin_url('users/show/' . $uid));
        }

        $this->audit('wallet.adjust', 'user', $uid,
            ($isCredit ? 'افزایش' : 'کاهش') . ' دستی کیف پول ' . ($user['full_name'] ?: $user['phone'])
            . ' به مبلغ ' . money($amount) . ' تومان. مانده جدید: ' . money($balance) . '. علت: ' . $desc,
            ['wallet_balance' => $balanceBefore], ['wallet_balance' => $balance]);

        if ($notify && $isCredit) {
            Sms::sendTemplate('wallet_charged', (string) $user['phone'], [
                'name'   => $user['full_name'] ?: 'مشتری',
                'amount' => money($amount),
            ], $uid);
        }

        flash('success', 'کیف پول به‌روزرسانی شد. موجودی جدید: ' . money($balance) . ' تومان');
        back(admin_url('users/show/' . $uid));
    }

    public function store($id = 0): void
    {
        $phone = Auth::normalizePhone((string) post('phone'));
        $pass  = (string) post('password');

        if (!preg_match('/^09\d{9}$/', $phone)) {
            flash('error', 'شماره موبایل معتبر نیست.');
            back(admin_url('users'));
        }
        if (mb_strlen($pass) < 8) {
            flash('error', 'رمز عبور باید حداقل ۸ کاراکتر باشد.');
            back(admin_url('users'));
        }
        if (Model::one('SELECT id FROM users WHERE phone = ?', [$phone])) {
            flash('error', 'کاربری با این شماره موبایل قبلاً ثبت شده است.');
            back(admin_url('users'));
        }

        $role = (can('users.roles') && post('role') === 'admin') ? 'admin' : 'user';

        $newId = Model::insert('users', [
            'full_name'     => mb_substr(trim((string) post('full_name')) ?: 'کاربر جدید', 0, 100),
            'phone'         => $phone,
            'email'         => trim((string) post('email')) ?: null,
            'password_hash' => password_hash($pass, PASSWORD_DEFAULT),
            'role'          => $role,
        ]);

        $this->audit('user.create', 'user', $newId, 'ایجاد کاربر جدید ' . $phone . ' با نقش ' . $role);
        flash('success', 'کاربر جدید ایجاد شد.');
        redirect(admin_url('users/show/' . $newId));
    }

    /** ارسال پیامک تکی به کاربر */
    public function sms($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        $user = Model::find('users', $uid);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }
        $message = trim((string) post('message'));
        if ($message === '') {
            flash('error', 'متن پیامک خالی است.');
            back(admin_url('users/show/' . $uid));
        }

        $text = Sms::render($message, ['name' => $user['full_name'] ?: 'مشتری']);
        $res = Sms::send((string) $user['phone'], $text, $uid, 'manual');

        $this->audit('sms.send', 'user', $uid, 'ارسال پیامک دستی به ' . $user['phone']);
        flash($res['success'] ? 'success' : 'error',
            $res['success'] ? 'پیامک ارسال شد.' : ('ارسال ناموفق: ' . $res['message']));
        back(admin_url('users/show/' . $uid));
    }

    public function delete($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        if ($uid === $this->adminId()) {
            flash('error', 'نمی‌توانید حساب خودتان را حذف کنید.');
            redirect(admin_url('users'));
        }

        $user = Model::find('users', $uid);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('users'));
        }
        if (Model::count('orders', 'user_id = ?', [$uid]) > 0) {
            flash('error', 'این کاربر سفارش ثبت‌شده دارد و قابل حذف نیست. می‌توانید حسابش را غیرفعال کنید.');
            back(admin_url('users/show/' . $uid));
        }

        foreach (['user_addresses', 'user_vehicles', 'wishlists', 'cart_items', 'wallet_transactions'] as $t) {
            Model::exec("DELETE FROM `$t` WHERE user_id = ?", [$uid]);
        }
        Model::exec('DELETE FROM ticket_messages WHERE ticket_id IN (SELECT id FROM support_tickets WHERE user_id = ?)', [$uid]);
        Model::exec('DELETE FROM support_tickets WHERE user_id = ?', [$uid]);
        Model::delete('users', $uid);

        $this->audit('user.delete', 'user', $uid,
            'حذف کامل کاربر ' . ($user['full_name'] ?: $user['phone']), $user, null);
        flash('success', 'کاربر حذف شد.');
        redirect(admin_url('users'));
    }

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT u.id, u.full_name, u.phone, u.email, u.national_code, u.city, u.role,
                                   u.wallet_balance, u.created_at,
                                   (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders_count,
                                   (SELECT COALESCE(SUM(o.total_amount),0) FROM orders o
                                    WHERE o.user_id = u.id AND o.status NOT IN ('cancelled','returned')) AS spent
                            FROM users u ORDER BY u.id");

        $data = array_map(fn($r) => [
            $r['id'], $r['full_name'], $r['phone'], $r['email'] ?? '', $r['national_code'] ?? '',
            $r['city'] ?? '', $r['role'] === 'admin' ? 'مدیر' : 'کاربر', $r['wallet_balance'],
            $r['orders_count'], $r['spent'], shamsiTime($r['created_at']),
        ], $rows);

        $this->streamCsv('users-' . date('Y-m-d') . '.csv',
            ['شناسه', 'نام', 'موبایل', 'ایمیل', 'کد ملی', 'شهر', 'نقش', 'کیف پول', 'تعداد سفارش', 'مجموع خرید', 'عضویت'],
            $data);
    }
}
