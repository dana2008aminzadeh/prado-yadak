<?php
namespace Admin\controllers;

use Admin\core\Model;

class CommentController extends BaseController
{
    protected string $section = 'comments';

    protected array $permissions = [
        'approve' => 'comments.moderate',
        'reject'  => 'comments.moderate',
        'pending' => 'comments.moderate',
        'delete'  => 'comments.moderate',
        'bulk'    => 'comments.moderate',
    ];

    public function index($id = 0): void
    {
        $status = param('status', '');
        $q = trim((string) param('q', ''));

        $where = ['1'];
        $params = [];
        if ($status !== '') { $where[] = 'pc.status = ?'; $params[] = $status; }
        if ($q !== '') {
            $where[] = '(pc.name LIKE ? OR pc.comment_text LIKE ? OR p.name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM product_comments pc
                                      LEFT JOIN products p ON p.id = pc.product_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $comments = Model::all("SELECT pc.*, p.name AS product_name, p.slug AS product_slug, u.phone
                                FROM product_comments pc
                                LEFT JOIN products p ON p.id = pc.product_id
                                LEFT JOIN users u ON u.id = pc.user_id
                                WHERE $w ORDER BY pc.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $counts = [
            'pending'  => Model::count('product_comments', "status = 'pending'"),
            'approved' => Model::count('product_comments', "status = 'approved'"),
            'rejected' => Model::count('product_comments', "status = 'rejected'"),
        ];

        $this->view('comments', compact('comments', 'pg', 'status', 'q', 'counts'),
            'دیدگاه محصولات', money($total) . ' دیدگاه');
    }

    public function approve($id = 0): void { $this->setStatus((int) post('comment_id', $id), 'approved', 'دیدگاه تأیید شد.'); }
    public function reject($id = 0): void  { $this->setStatus((int) post('comment_id', $id), 'rejected', 'دیدگاه رد شد.'); }
    public function pending($id = 0): void { $this->setStatus((int) post('comment_id', $id), 'pending', 'دیدگاه به حالت انتظار برگشت.'); }

    private function setStatus(int $cid, string $status, string $msg): void
    {
        $c = Model::find('product_comments', $cid);
        if (!$c) {
            flash('error', 'دیدگاه یافت نشد.');
            back(admin_url('comments'));
        }
        Model::exec('UPDATE product_comments SET status = ? WHERE id = ?', [$status, $cid]);
        $this->audit('comment.moderate', 'comment', $cid,
            'تغییر وضعیت دیدگاه به «' . $status . '» — متن: ' . excerpt($c['comment_text'], 60));
        flash('success', $msg);
        back(admin_url('comments'));
    }

    public function delete($id = 0): void
    {
        $cid = (int) post('comment_id', $id);
        $c = Model::find('product_comments', $cid);
        if ($c) {
            Model::delete('product_comments', $cid);
            $this->audit('comment.delete', 'comment', $cid, 'حذف دیدگاه: ' . excerpt($c['comment_text'], 60), $c, null);
            flash('success', 'دیدگاه حذف شد.');
        } else {
            flash('error', 'دیدگاه یافت نشد.');
        }
        back(admin_url('comments'));
    }

    public function bulk($id = 0): void
    {
        $ids = array_values(array_filter(array_map('intval', (array) post('ids', []))));
        $act = (string) post('bulk_action');

        if (!$ids) {
            flash('error', 'موردی انتخاب نشده است.');
            back(admin_url('comments'));
        }
        $in = implode(',', array_fill(0, count($ids), '?'));

        if ($act === 'delete') {
            Model::exec("DELETE FROM product_comments WHERE id IN ($in)", $ids);
            flash('success', count($ids) . ' دیدگاه حذف شد.');
        } elseif (in_array($act, ['approved', 'rejected', 'pending'], true)) {
            Model::exec("UPDATE product_comments SET status = ? WHERE id IN ($in)", [$act, ...$ids]);
            flash('success', 'وضعیت ' . count($ids) . ' دیدگاه تغییر کرد.');
        } else {
            flash('error', 'عملیات نامعتبر است.');
            back(admin_url('comments'));
        }

        $this->audit('comment.moderate', 'comment', implode(',', array_slice($ids, 0, 50)),
            'عملیات گروهی «' . $act . '» روی ' . count($ids) . ' دیدگاه');
        back(admin_url('comments'));
    }
}
