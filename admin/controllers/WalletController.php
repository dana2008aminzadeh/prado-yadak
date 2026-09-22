<?php
namespace Admin\controllers;

use Admin\core\Model;
use Admin\core\Sms;

class WalletController extends BaseController
{
    protected string $section = 'wallet';

    protected array $permissions = [
        'approve' => 'wallet.approve',
        'reject'  => 'wallet.approve',
        'delete'  => 'wallet.adjust',
    ];

    public const TYPES = ['charge' => 'شارژ', 'purchase' => 'خرید', 'refund' => 'بازگشت وجه', 'adjust' => 'اصلاح'];
    public const STATUSES = ['pending' => 'در انتظار', 'completed' => 'موفق', 'failed' => 'ناموفق', 'cancelled' => 'لغو شده'];

    public function index($id = 0): void
    {
        $type = param('type', '');
        $status = param('status', '');
        $q = trim((string) param('q', ''));
        $from = $this->dateParam('from');
        $to = $this->dateParam('to');

        $where = ['1'];
        $params = [];
        if ($type !== '')   { $where[] = 'w.type = ?'; $params[] = $type; }
        if ($status !== '') { $where[] = 'w.status = ?'; $params[] = $status; }
        if ($from) { $where[] = 'w.created_at >= ?'; $params[] = $from . ' 00:00:00'; }
        if ($to)   { $where[] = 'w.created_at <= ?'; $params[] = $to . ' 23:59:59'; }
        if ($q !== '') {
            $where[] = '(u.full_name LIKE ? OR u.phone LIKE ? OR w.reference_code LIKE ? OR w.description LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like, $like);
        }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM wallet_transactions w
                                      LEFT JOIN users u ON u.id = w.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $txs = Model::all("SELECT w.*, u.full_name, u.phone FROM wallet_transactions w
                           LEFT JOIN users u ON u.id = w.user_id
                           WHERE $w ORDER BY w.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $sums = [
            'charged'  => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type='charge' AND status='completed'"),
            'spent'    => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type='purchase' AND status='completed'"),
            'pending'  => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE status='pending'"),
            'balances' => (float) Model::scalar('SELECT COALESCE(SUM(wallet_balance),0) FROM users'),
        ];

        $types = self::TYPES;
        $statuses = self::STATUSES;
        $fromJ = param('from', '');
        $toJ = param('to', '');

        $this->view('wallet/index',
            compact('txs', 'pg', 'type', 'status', 'q', 'types', 'statuses', 'sums', 'fromJ', 'toJ'),
            'کیف پول و تراکنش‌ها', money($total) . ' تراکنش');
    }

    /** تأیید تراکنش در انتظار و اعمال اتمیک روی موجودی */
    public function approve($id = 0): void
    {
        $tid = (int) post('tx_id', $id);

        $db = Model::db();
        $db->beginTransaction();
        try {
            $st = $db->prepare('SELECT w.*, u.full_name, u.phone, u.wallet_balance
                                FROM wallet_transactions w LEFT JOIN users u ON u.id = w.user_id
                                WHERE w.id = ? FOR UPDATE');
            $st->execute([$tid]);
            $tx = $st->fetch(\PDO::FETCH_ASSOC);

            if (!$tx) {
                $db->rollBack();
                flash('error', 'تراکنش یافت نشد.');
                back(admin_url('wallet'));
            }
            if ($tx['status'] !== 'pending') {
                $db->rollBack();
                flash('error', 'این تراکنش قبلاً پردازش شده است (وضعیت فعلی: ' . (self::STATUSES[$tx['status']] ?? $tx['status']) . ').');
                back(admin_url('wallet'));
            }

            $amount = abs((float) $tx['amount']);
            $isCredit = in_array($tx['type'], ['charge', 'refund'], true);
            $signed = $isCredit ? $amount : -$amount;
            $balanceBefore = (float) ($tx['wallet_balance'] ?? 0);

            if (!$isCredit && $balanceBefore < $amount) {
                $db->rollBack();
                flash('error', 'موجودی کاربر برای این کسر کافی نیست.');
                back(admin_url('wallet'));
            }

            $db->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?')
                ->execute([$signed, (int) $tx['user_id']]);
            $balance = (float) Model::scalar('SELECT wallet_balance FROM users WHERE id = ?', [(int) $tx['user_id']]);

            $db->prepare("UPDATE wallet_transactions SET status = 'completed', balance_after = ? WHERE id = ?")
                ->execute([$balance, $tid]);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            flash('error', 'خطا در تأیید تراکنش: ' . $e->getMessage());
            back(admin_url('wallet'));
        }

        $this->audit('wallet.approve', 'wallet_tx', $tid,
            'تأیید تراکنش ' . money($amount) . ' تومان برای ' . ($tx['full_name'] ?: $tx['phone'])
            . '. مانده جدید: ' . money($balance),
            ['status' => 'pending', 'wallet_balance' => $balanceBefore],
            ['status' => 'completed', 'wallet_balance' => $balance]);

        if ($isCredit && !empty($tx['phone'])) {
            Sms::sendTemplate('wallet_charged', (string) $tx['phone'], [
                'name' => $tx['full_name'] ?: 'مشتری',
                'amount' => money($amount),
            ], (int) $tx['user_id'], true);
        }

        flash('success', 'تراکنش تأیید شد. موجودی جدید کاربر: ' . money($balance) . ' تومان');
        back(admin_url('wallet'));
    }

    public function reject($id = 0): void
    {
        $tid = (int) post('tx_id', $id);
        $tx = Model::find('wallet_transactions', $tid);
        if (!$tx || $tx['status'] !== 'pending') {
            flash('error', 'تراکنش در انتظاری با این شناسه یافت نشد.');
            back(admin_url('wallet'));
        }

        $reason = trim((string) post('reason'));
        Model::exec("UPDATE wallet_transactions SET status = 'failed', description = CONCAT(COALESCE(description,''), ?) WHERE id = ?",
            [$reason !== '' ? ' | رد شد: ' . mb_substr($reason, 0, 150) : ' | رد شد توسط مدیر', $tid]);

        $this->audit('wallet.reject', 'wallet_tx', $tid,
            'رد تراکنش ' . money($tx['amount']) . ' تومان' . ($reason ? '. علت: ' . $reason : ''));
        flash('success', 'تراکنش رد شد.');
        back(admin_url('wallet'));
    }

    public function delete($id = 0): void
    {
        $tid = (int) post('tx_id', $id);
        $tx = Model::find('wallet_transactions', $tid);
        if (!$tx) {
            flash('error', 'تراکنش یافت نشد.');
            back(admin_url('wallet'));
        }
        if ($tx['status'] === 'completed') {
            flash('error', 'تراکنش‌های تأییدشده قابل حذف نیستند (برای حفظ صحت حسابداری). در صورت نیاز یک تراکنش اصلاحی ثبت کنید.');
            back(admin_url('wallet'));
        }

        Model::delete('wallet_transactions', $tid);
        $this->audit('wallet.delete', 'wallet_tx', $tid, 'حذف تراکنش ناموفق/در انتظار', $tx, null);
        flash('success', 'تراکنش حذف شد.');
        back(admin_url('wallet'));
    }

    public function export($id = 0): void
    {
        $rows = Model::all('SELECT w.id, u.full_name, u.phone, w.type, w.amount, w.balance_after,
                                   w.description, w.reference_code, w.status, w.created_at
                            FROM wallet_transactions w LEFT JOIN users u ON u.id = w.user_id ORDER BY w.id DESC');

        $data = array_map(fn($r) => [
            $r['id'], $r['full_name'] ?? '', $r['phone'] ?? '', self::TYPES[$r['type']] ?? $r['type'],
            $r['amount'], $r['balance_after'], $r['description'] ?? '', $r['reference_code'] ?? '',
            self::STATUSES[$r['status']] ?? $r['status'], shamsiTime($r['created_at']),
        ], $rows);

        $this->streamCsv('wallet-' . date('Y-m-d') . '.csv',
            ['شناسه', 'کاربر', 'موبایل', 'نوع', 'مبلغ', 'مانده', 'توضیح', 'کد پیگیری', 'وضعیت', 'تاریخ'], $data);
    }
}
