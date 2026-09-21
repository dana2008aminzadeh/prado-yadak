<?php
namespace Admin\controllers;

use Admin\core\Model;

class TicketController extends BaseController
{
    protected string $section = 'tickets';

    public const STATUSES = ['open' => 'باز', 'answered' => 'پاسخ داده شده', 'pending' => 'در انتظار کاربر', 'closed' => 'بسته شده'];
    public const PRIORITIES = ['low' => 'کم', 'normal' => 'عادی', 'high' => 'فوری'];

    public function index($id = 0): void
    {
        $status = param('status', '');
        $priority = param('priority', '');
        $q = trim((string) param('q', ''));

        $where = ['1']; $params = [];
        if ($status !== '') { $where[] = 't.status = ?'; $params[] = $status; }
        if ($priority !== '') { $where[] = 't.priority = ?'; $params[] = $priority; }
        if ($q !== '') { $where[] = '(t.subject LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM support_tickets t LEFT JOIN users u ON u.id = t.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $tickets = Model::all("SELECT t.*, u.full_name, u.phone,
                                      (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS msg_count
                               FROM support_tickets t LEFT JOIN users u ON u.id = t.user_id
                               WHERE $w ORDER BY FIELD(t.priority,'high','normal','low'), t.updated_at DESC
                               LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $statuses = self::STATUSES;
        $priorities = self::PRIORITIES;
        $this->view('tickets/index', compact('tickets', 'pg', 'status', 'priority', 'q', 'statuses', 'priorities'),
            'تیکت‌های پشتیبانی', money($total) . ' تیکت');
    }

    public function show($id = 0): void
    {
        $ticket = Model::one('SELECT t.*, u.full_name, u.phone, u.email FROM support_tickets t
                              LEFT JOIN users u ON u.id = t.user_id WHERE t.id = ?', [(int) $id]);
        if (!$ticket) { flash('error', 'تیکت یافت نشد.'); redirect(admin_url('tickets')); }
        $messages = Model::all('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC', [(int) $id]);
        $statuses = self::STATUSES;
        $priorities = self::PRIORITIES;
        $this->view('tickets/show', compact('ticket', 'messages', 'statuses', 'priorities'), 'تیکت: ' . $ticket['subject']);
    }

    public function reply($id = 0): void
    {
        $msg = trim((string) post('message'));
        if ($msg === '') { flash('error', 'متن پاسخ خالی است.'); redirect(admin_url('tickets/show/' . (int) $id)); }
        $admin = $this->admin();
        Model::insert('ticket_messages', [
            'ticket_id'   => (int) $id,
            'sender_type' => 'admin',
            'sender_name' => $admin['full_name'] ?? 'پشتیبانی',
            'message'     => $msg,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
        $status = isset(self::STATUSES[post('status')]) ? (string) post('status') : 'answered';
        Model::exec('UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?', [$status, (int) $id]);
        flash('success', 'پاسخ ثبت شد.');
        redirect(admin_url('tickets/show/' . (int) $id));
    }

    public function update($id = 0): void
    {
        $data = [];
        if (isset(self::STATUSES[post('status')])) $data['status'] = (string) post('status');
        if (isset(self::PRIORITIES[post('priority')])) $data['priority'] = (string) post('priority');
        if ($data) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            Model::update('support_tickets', (int) $id, $data);
            flash('success', 'تیکت به‌روزرسانی شد.');
        }
        redirect(admin_url('tickets/show/' . (int) $id));
    }

    public function delete($id = 0): void
    {
        Model::exec('DELETE FROM ticket_messages WHERE ticket_id = ?', [(int) $id]);
        Model::delete('support_tickets', (int) $id);
        flash('success', 'تیکت حذف شد.');
        redirect(admin_url('tickets'));
    }
}
