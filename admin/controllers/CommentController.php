<?php
namespace Admin\controllers;

use Admin\core\Model;

class CommentController extends BaseController
{
    protected string $section = 'comments';

    public function index($id = 0): void
    {
        $status = param('status', '');
        $q = trim((string) param('q', ''));
        $where = ['1']; $params = [];
        if ($status !== '') { $where[] = 'pc.status = ?'; $params[] = $status; }
        if ($q !== '') { $where[] = '(pc.name LIKE ? OR pc.comment_text LIKE ? OR p.name LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM product_comments pc LEFT JOIN products p ON p.id = pc.product_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $comments = Model::all("SELECT pc.*, p.name AS product_name, p.slug AS product_slug, u.phone
                                FROM product_comments pc
                                LEFT JOIN products p ON p.id = pc.product_id
                                LEFT JOIN users u ON u.id = pc.user_id
                                WHERE $w ORDER BY pc.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $counts = [
            'pending'  => Model::count('product_comments', "status='pending'"),
            'approved' => Model::count('product_comments', "status='approved'"),
            'rejected' => Model::count('product_comments', "status='rejected'"),
        ];

        $this->view('comments', compact('comments', 'pg', 'status', 'q', 'counts'), 'دیدگاه محصولات', money($total) . ' دیدگاه');
    }

    public function approve($id = 0): void { $this->setStatus((int) $id, 'approved', 'دیدگاه تأیید شد.'); }
    public function reject($id = 0): void  { $this->setStatus((int) $id, 'rejected', 'دیدگاه رد شد.'); }
    public function pending($id = 0): void { $this->setStatus((int) $id, 'pending', 'دیدگاه به حالت انتظار برگشت.'); }

    private function setStatus(int $id, string $status, string $msg): void
    {
        Model::exec('UPDATE product_comments SET status = ? WHERE id = ?', [$status, $id]);
        flash('success', $msg);
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('comments'));
    }

    public function delete($id = 0): void
    {
        Model::delete('product_comments', (int) $id);
        flash('success', 'دیدگاه حذف شد.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('comments'));
    }

    public function bulk($id = 0): void
    {
        $ids = array_map('intval', (array) post('ids', []));
        $act = (string) post('bulk_action');
        if (!$ids) { flash('error', 'موردی انتخاب نشده.'); redirect(admin_url('comments')); }
        $in = implode(',', array_fill(0, count($ids), '?'));
        if ($act === 'delete') {
            Model::exec("DELETE FROM product_comments WHERE id IN ($in)", $ids);
            flash('success', 'دیدگاه‌های انتخابی حذف شدند.');
        } elseif (in_array($act, ['approved', 'rejected', 'pending'], true)) {
            Model::exec("UPDATE product_comments SET status = ? WHERE id IN ($in)", [$act, ...$ids]);
            flash('success', 'وضعیت دیدگاه‌های انتخابی تغییر کرد.');
        }
        redirect(admin_url('comments'));
    }
}
