<?php
namespace Admin\controllers;

use Admin\core\Model;
use Admin\core\Sms;
use Admin\core\Uploader;

class TicketController extends BaseController
{
    protected string $section = 'tickets';

    protected array $permissions = [
        'reply'  => 'tickets.reply',
        'update' => 'tickets.reply',
        'delete' => 'tickets.delete',
    ];

    public const STATUSES = [
        'open'     => 'باز',
        'answered' => 'پاسخ داده شده',
        'pending'  => 'در انتظار کاربر',
        'closed'   => 'بسته شده',
    ];

    public const PRIORITIES = ['low' => 'کم', 'normal' => 'عادی', 'high' => 'فوری'];

    public function index($id = 0): void
    {
        $status   = param('status', '');
        $priority = param('priority', '');
        $q        = trim((string) param('q', ''));

        $where = ['1'];
        $params = [];
        if ($status !== '')   { $where[] = 't.status = ?'; $params[] = $status; }
        if ($priority !== '') { $where[] = 't.priority = ?'; $params[] = $priority; }
        if ($q !== '') {
            $where[] = '(t.subject LIKE ? OR u.full_name LIKE ? OR u.phone LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM support_tickets t LEFT JOIN users u ON u.id = t.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $tickets = Model::all(
            "SELECT t.*, u.full_name, u.phone,
                    (SELECT COUNT(*) FROM ticket_messages m WHERE m.ticket_id = t.id) AS msg_count,
                    (SELECT COUNT(*) FROM ticket_attachments a WHERE a.ticket_id = t.id) AS files_count
             FROM support_tickets t LEFT JOIN users u ON u.id = t.user_id
             WHERE $w ORDER BY FIELD(t.priority,'high','normal','low'), t.updated_at DESC
             LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $counts = [
            'open'     => Model::count('support_tickets', "status = 'open'"),
            'pending'  => Model::count('support_tickets', "status = 'pending'"),
            'high'     => Model::count('support_tickets', "priority = 'high' AND status <> 'closed'"),
            'answered' => Model::count('support_tickets', "status = 'answered'"),
        ];

        $statuses = self::STATUSES;
        $priorities = self::PRIORITIES;

        $this->view('tickets/index',
            compact('tickets', 'pg', 'status', 'priority', 'q', 'statuses', 'priorities', 'counts'),
            'تیکت‌های پشتیبانی', money($total) . ' تیکت');
    }

    public function show($id = 0): void
    {
        $ticket = Model::one('SELECT t.*, u.full_name, u.phone, u.email FROM support_tickets t
                              LEFT JOIN users u ON u.id = t.user_id WHERE t.id = ?', [(int) $id]);
        if (!$ticket) {
            flash('error', 'تیکت یافت نشد.');
            redirect(admin_url('tickets'));
        }

        $messages = Model::all('SELECT * FROM ticket_messages WHERE ticket_id = ? ORDER BY created_at ASC, id ASC', [(int) $id]);

        // پیوست‌ها را به پیام‌هایشان می‌چسبانیم
        $attachments = Model::all('SELECT * FROM ticket_attachments WHERE ticket_id = ? ORDER BY id', [(int) $id]);
        $byMessage = [];
        foreach ($attachments as $a) {
            $byMessage[(int) $a['message_id']][] = $a;
        }

        $userOrders = Model::all('SELECT id, tracking_code, total_amount, status, created_at FROM orders
                                  WHERE user_id = ? ORDER BY created_at DESC LIMIT 5', [(int) $ticket['user_id']]);

        $statuses = self::STATUSES;
        $priorities = self::PRIORITIES;

        $this->view('tickets/show',
            compact('ticket', 'messages', 'byMessage', 'userOrders', 'statuses', 'priorities'),
            'تیکت: ' . $ticket['subject'], 'شماره #' . $ticket['id']);
    }

    public function reply($id = 0): void
    {
        $tid = (int) post('ticket_id', $id);
        $ticket = Model::one('SELECT t.*, u.full_name, u.phone FROM support_tickets t
                              LEFT JOIN users u ON u.id = t.user_id WHERE t.id = ?', [$tid]);
        if (!$ticket) {
            flash('error', 'تیکت یافت نشد.');
            redirect(admin_url('tickets'));
        }

        $msg = trim((string) post('message'));
        $hasFiles = !empty($_FILES['attachments']['name'][0] ?? '');

        if ($msg === '' && !$hasFiles) {
            flash('error', 'متن پاسخ یا حداقل یک پیوست لازم است.');
            back(admin_url('tickets/show/' . $tid));
        }

        $db = Model::db();
        $db->beginTransaction();
        try {
            $messageId = Model::insert('ticket_messages', [
                'ticket_id'   => $tid,
                'sender_type' => 'admin',
                'sender_name' => $this->adminName(),
                'message'     => $msg !== '' ? $msg : '(فایل پیوست ارسال شد)',
                'created_at'  => date('Y-m-d H:i:s'),
            ]);

            $uploaded = $this->saveAttachments($tid, $messageId);

            $status = isset(self::STATUSES[post('status')]) ? (string) post('status') : 'answered';
            Model::exec('UPDATE support_tickets SET status = ?, last_reply_by = ?, updated_at = NOW() WHERE id = ?',
                [$status, 'admin', $tid]);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در ثبت پاسخ: ' . $e->getMessage());
            back(admin_url('tickets/show/' . $tid));
        }

        $this->audit('ticket.reply', 'ticket', $tid,
            'پاسخ به تیکت «' . $ticket['subject'] . '»' . ($uploaded ? " با {$uploaded} پیوست" : ''));

        if (post('send_sms') && $ticket['phone']) {
            Sms::sendTemplate('ticket_answered', (string) $ticket['phone'], [
                'name' => $ticket['full_name'] ?: 'مشتری',
            ], (int) $ticket['user_id']);
        }

        flash('success', 'پاسخ ثبت شد.' . ($uploaded ? " ({$uploaded} فایل پیوست شد)" : ''));
        back(admin_url('tickets/show/' . $tid));
    }

    /** ذخیره پیوست‌های ارسالی */
    private function saveAttachments(int $ticketId, int $messageId): int
    {
        $files = $_FILES['attachments'] ?? null;
        if (!$files || empty($files['name'])) {
            return 0;
        }

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $saved = 0;
        $errors = [];

        for ($i = 0, $n = count($names); $i < $n; $i++) {
            $one = is_array($files['name'])
                ? ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                   'error' => $files['error'][$i], 'size' => $files['size'][$i]]
                : $files;

            if ((int) $one['error'] === UPLOAD_ERR_NO_FILE) continue;

            $res = Uploader::attachment($one, 'tickets');
            if (!$res['success']) {
                $errors[] = $res['message'];
                continue;
            }

            Model::insert('ticket_attachments', [
                'message_id'       => $messageId,
                'ticket_id'        => $ticketId,
                'file_path'        => $res['path'] ?? null,
                'telegram_file_id' => $res['telegram_file_id'] ?? null,
                'original_name'    => $res['original_name'] ?? null,
                'mime_type'        => $res['mime_type'] ?? null,
                'file_size'        => $res['file_size'] ?? null,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            $saved++;
        }

        if ($errors) {
            flash('error', 'برخی پیوست‌ها آپلود نشدند: ' . implode(' | ', array_slice($errors, 0, 3)));
        }
        return $saved;
    }

    public function update($id = 0): void
    {
        $tid = (int) post('ticket_id', $id);
        $old = Model::find('support_tickets', $tid);
        if (!$old) {
            flash('error', 'تیکت یافت نشد.');
            redirect(admin_url('tickets'));
        }

        $data = [];
        if (isset(self::STATUSES[post('status')]))     $data['status'] = (string) post('status');
        if (isset(self::PRIORITIES[post('priority')])) $data['priority'] = (string) post('priority');

        if ($data) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            Model::update('support_tickets', $tid, $data);
            $this->audit('ticket.update', 'ticket', $tid, 'به‌روزرسانی تیکت «' . $old['subject'] . '»', $old, $data);
            flash('success', 'تیکت به‌روزرسانی شد.');
        }
        back(admin_url('tickets/show/' . $tid));
    }

    public function delete($id = 0): void
    {
        $tid = (int) post('ticket_id', $id);
        $ticket = Model::find('support_tickets', $tid);
        if (!$ticket) {
            flash('error', 'تیکت یافت نشد.');
            redirect(admin_url('tickets'));
        }

        foreach (Model::all('SELECT file_path FROM ticket_attachments WHERE ticket_id = ?', [$tid]) as $a) {
            Uploader::deleteFile($a['file_path'] ?? null);
        }
        Model::exec('DELETE FROM ticket_attachments WHERE ticket_id = ?', [$tid]);
        Model::exec('DELETE FROM ticket_messages WHERE ticket_id = ?', [$tid]);
        Model::delete('support_tickets', $tid);

        $this->audit('ticket.delete', 'ticket', $tid, 'حذف تیکت «' . $ticket['subject'] . '»', $ticket, null);
        flash('success', 'تیکت حذف شد.');
        redirect(admin_url('tickets'));
    }
}
