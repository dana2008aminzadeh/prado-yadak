<?php
namespace Admin\controllers;

use Admin\core\Model;

class WalletController extends BaseController
{
    protected string $section = 'wallet';

    public const TYPES = ['charge' => 'شارژ', 'purchase' => 'خرید', 'refund' => 'بازگشت وجه', 'adjust' => 'اصلاح'];
    public const STATUSES = ['pending' => 'در انتظار', 'completed' => 'موفق', 'failed' => 'ناموفق', 'cancelled' => 'لغو شده'];

    public function index($id = 0): void
    {
        $type = param('type', '');
        $status = param('status', '');
        $q = trim((string) param('q', ''));

        $where = ['1']; $params = [];
        if ($type !== '') { $where[] = 'w.type = ?'; $params[] = $type; }
        if ($status !== '') { $where[] = 'w.status = ?'; $params[] = $status; }
        if ($q !== '') { $where[] = '(u.full_name LIKE ? OR u.phone LIKE ? OR w.reference_code LIKE ? OR w.description LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%", "%$q%"); }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM wallet_transactions w LEFT JOIN users u ON u.id = w.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $txs = Model::all("SELECT w.*, u.full_name, u.phone FROM wallet_transactions w
                           LEFT JOIN users u ON u.id = w.user_id
                           WHERE $w ORDER BY w.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $sums = [
            'charged'  => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type='charge' AND status='completed'"),
            'spent'    => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE type='purchase' AND status='completed'"),
            'pending'  => (float) Model::scalar("SELECT COALESCE(SUM(amount),0) FROM wallet_transactions WHERE status='pending'"),
            'balances' => (float) Model::scalar("SELECT COALESCE(SUM(wallet_balance),0) FROM users"),
        ];

        $types = self::TYPES;
        $statuses = self::STATUSES;
        $this->view('wallet/index', compact('txs', 'pg', 'type', 'status', 'q', 'types', 'statuses', 'sums'),
            'کیف پول و تراکنش‌ها', money($total) . ' تراکنش');
    }

    /** تأیید تراکنش در انتظار + اعمال روی موجودی */
    public function approve($id = 0): void
    {
        $tx = Model::find('wallet_transactions', (int) $id);
        if (!$tx || $tx['status'] !== 'pending') {
            flash('error', 'تراکنش معتبر در انتظار یافت نشد.');
            redirect(admin_url('wallet'));
        }
        $signed = in_array($tx['type'], ['charge', 'refund'], true) ? abs((float) $tx['amount']) : -abs((float) $tx['amount']);
        $db = Model::db();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?')->execute([$signed, (int) $tx['user_id']]);
            $balance = (float) Model::scalar('SELECT wallet_balance FROM users WHERE id = ?', [(int) $tx['user_id']]);
            Model::update('wallet_transactions', (int) $id, ['status' => 'completed', 'balance_after' => $balance]);
            $db->commit();
            flash('success', 'تراکنش تأیید و موجودی کاربر به‌روزرسانی شد.');
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا: ' . $e->getMessage());
        }
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('wallet'));
    }

    public function reject($id = 0): void
    {
        Model::exec("UPDATE wallet_transactions SET status = 'failed' WHERE id = ? AND status = 'pending'", [(int) $id]);
        flash('success', 'تراکنش رد شد.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('wallet'));
    }

    public function delete($id = 0): void
    {
        Model::delete('wallet_transactions', (int) $id);
        flash('success', 'تراکنش حذف شد (موجودی تغییر نکرد).');
        redirect(admin_url('wallet'));
    }

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT w.id, u.full_name, u.phone, w.type, w.amount, w.balance_after, w.description, w.reference_code, w.status, w.created_at
                            FROM wallet_transactions w LEFT JOIN users u ON u.id = w.user_id ORDER BY w.id DESC");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="wallet-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['شناسه', 'کاربر', 'موبایل', 'نوع', 'مبلغ', 'مانده', 'توضیح', 'کد پیگیری', 'وضعیت', 'تاریخ']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}
