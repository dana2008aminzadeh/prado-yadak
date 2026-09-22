<?php
namespace Admin\controllers;

use Admin\core\Model;
use Admin\core\Settings;
use Admin\core\Sms;

class SmsController extends BaseController
{
    protected string $section = 'sms';
    protected int $perPage = 30;

    protected array $permissions = [
        'send'          => 'sms.send',
        'campaign'      => 'sms.send',
        'runCampaign'   => 'sms.send',
        'saveTemplate'  => 'sms.send',
        'deleteTemplate' => 'sms.send',
        'resend'        => 'sms.send',
    ];

    /** مخاطبان قابل انتخاب برای ارسال گروهی */
    public const AUDIENCES = [
        'all'         => 'همه کاربران',
        'buyers'      => 'مشتریانی که حداقل یک خرید داشته‌اند',
        'no_orders'   => 'کاربران بدون خرید',
        'recent'      => 'خریداران ۹۰ روز اخیر',
        'inactive'    => 'مشتریان غیرفعال (بیش از ۶ ماه بدون خرید)',
        'wallet'      => 'دارندگان موجودی کیف پول',
        'custom'      => 'شماره‌های دستی',
    ];

    public function index($id = 0): void
    {
        $status = param('status', '');
        $q = trim((string) param('q', ''));

        $where = ['1'];
        $params = [];
        if ($status !== '') { $where[] = 'l.status = ?'; $params[] = $status; }
        if ($q !== '') {
            $where[] = '(l.phone LIKE ? OR l.message LIKE ? OR u.full_name LIKE ?)';
            $like = "%$q%";
            array_push($params, $like, $like, $like);
        }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM sms_logs l LEFT JOIN users u ON u.id = l.user_id WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $logs = Model::all("SELECT l.*, u.full_name FROM sms_logs l
                            LEFT JOIN users u ON u.id = l.user_id
                            WHERE $w ORDER BY l.created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $stats = [
            'sent'   => Model::count('sms_logs', "status = 'sent'"),
            'failed' => Model::count('sms_logs', "status = 'failed'"),
            'today'  => Model::count('sms_logs', "DATE(created_at) = CURDATE() AND status = 'sent'"),
            'month'  => Model::count('sms_logs', "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'sent'"),
        ];

        $templates = Model::all('SELECT * FROM sms_templates ORDER BY id');
        $campaigns = Model::all('SELECT c.*, u.full_name AS admin_name FROM sms_campaigns c
                                 LEFT JOIN users u ON u.id = c.admin_id
                                 ORDER BY c.created_at DESC LIMIT 10');

        $enabled = Settings::bool('sms_enabled');
        $configured = (bool) (Settings::get('smsir_api_key') && Settings::get('smsir_line_number'));
        $audiences = self::AUDIENCES;

        $this->view('sms/index',
            compact('logs', 'pg', 'stats', 'templates', 'campaigns', 'status', 'q', 'enabled', 'configured', 'audiences'),
            'سامانه پیامک', money($total) . ' پیامک ثبت‌شده');
    }

    /** ارسال تکی */
    public function send($id = 0): void
    {
        $phone = (string) post('phone');
        $message = trim((string) post('message'));

        if ($message === '') {
            flash('error', 'متن پیامک خالی است.');
            back(admin_url('sms'));
        }

        $user = Model::one('SELECT id FROM users WHERE phone = ?', [\Admin\core\Auth::normalizePhone($phone)]);
        $res = Sms::send($phone, $message, $user['id'] ?? null, 'manual');

        $this->audit('sms.send', 'sms', $phone, 'ارسال پیامک دستی به ' . $phone);
        flash($res['success'] ? 'success' : 'error',
            $res['success'] ? 'پیامک ارسال شد.' : ('ارسال ناموفق: ' . $res['message']));
        back(admin_url('sms'));
    }

    /** پیش‌نمایش مخاطبان کمپین */
    public function campaign($id = 0): void
    {
        $audience = (string) param('audience', 'all');
        $recipients = $this->resolveAudience($audience, (string) param('numbers', ''));

        $preview = array_slice($recipients, 0, 20);
        $audiences = self::AUDIENCES;
        $templates = Model::all('SELECT * FROM sms_templates ORDER BY id');
        $total = count($recipients);
        $enabled = Settings::bool('sms_enabled');

        $this->view('sms/campaign', compact('audience', 'audiences', 'preview', 'total', 'templates', 'enabled'),
            'کمپین پیامکی', $total . ' مخاطب در این گروه');
    }

    /** اجرای کمپین (دسته‌ای با سقف ایمن) */
    public function runCampaign($id = 0): void
    {
        $title = trim((string) post('title')) ?: 'کمپین بدون عنوان';
        $message = trim((string) post('message'));
        $audience = (string) post('audience', 'all');
        $numbers = (string) post('numbers', '');
        $limit = min(500, max(1, (int) post('limit', 200)));

        if ($message === '') {
            flash('error', 'متن پیامک الزامی است.');
            back(admin_url('sms/campaign'));
        }
        if (!Settings::bool('sms_enabled')) {
            flash('error', 'سرویس پیامک در تنظیمات غیرفعال است.');
            back(admin_url('sms'));
        }

        $recipients = $this->resolveAudience($audience, $numbers);
        if (!$recipients) {
            flash('error', 'هیچ مخاطبی در این گروه یافت نشد.');
            back(admin_url('sms/campaign'));
        }
        $recipients = array_slice($recipients, 0, $limit);

        $campaignId = Model::insert('sms_campaigns', [
            'title'            => mb_substr($title, 0, 150),
            'message'          => $message,
            'audience'         => $audience,
            'audience_params'  => $audience === 'custom' ? mb_substr($numbers, 0, 2000) : null,
            'total_recipients' => count($recipients),
            'status'           => 'running',
            'admin_id'         => $this->adminId(),
        ]);

        @set_time_limit(0);
        @ignore_user_abort(true);

        $sent = 0;
        $failed = 0;
        foreach ($recipients as $r) {
            $text = Sms::render($message, [
                'name'   => $r['full_name'] ?: 'مشتری',
                'amount' => money($r['wallet_balance'] ?? 0),
                'code'   => '',
            ]);
            $res = Sms::send((string) $r['phone'], $text, $r['id'] ?? null, 'campaign', $campaignId);
            $res['success'] ? $sent++ : $failed++;
            usleep(120000); // جلوگیری از محدودیت نرخ سرویس‌دهنده
        }

        Model::update('sms_campaigns', $campaignId, [
            'sent_count'   => $sent,
            'failed_count' => $failed,
            'status'       => 'completed',
        ]);

        $this->audit('sms.campaign', 'campaign', $campaignId,
            "کمپین «{$title}» — ارسال موفق: {$sent}، ناموفق: {$failed}");

        flash($sent > 0 ? 'success' : 'error',
            "کمپین انجام شد. ارسال موفق: {$sent} — ناموفق: {$failed}");
        redirect(admin_url('sms'));
    }

    public function saveTemplate($id = 0): void
    {
        $key = preg_replace('/[^a-z0-9_]/i', '', (string) post('template_key')) ?: '';
        $title = trim((string) post('title'));
        $body = trim((string) post('body'));

        if ($key === '' || $body === '') {
            flash('error', 'کلید و متن قالب الزامی است.');
            back(admin_url('sms'));
        }

        $data = [
            'template_key' => $key,
            'title'        => $title ?: $key,
            'body'         => $body,
            'is_active'    => post('is_active') ? 1 : 0,
            'auto_send'    => post('auto_send') ? 1 : 0,
        ];

        $existing = Model::one('SELECT id FROM sms_templates WHERE template_key = ?', [$key]);
        if ($existing) {
            Model::update('sms_templates', (int) $existing['id'], $data);
            flash('success', 'قالب پیامک به‌روزرسانی شد.');
        } else {
            Model::insert('sms_templates', $data);
            flash('success', 'قالب پیامک ایجاد شد.');
        }

        $this->audit('sms.send', 'template', $key, 'ذخیره قالب پیامک: ' . $key);
        back(admin_url('sms'));
    }

    public function deleteTemplate($id = 0): void
    {
        $tid = (int) post('template_id', $id);
        Model::delete('sms_templates', $tid);
        $this->audit('sms.send', 'template', $tid, 'حذف قالب پیامک');
        flash('success', 'قالب حذف شد.');
        back(admin_url('sms'));
    }

    public function resend($id = 0): void
    {
        $lid = (int) post('log_id', $id);
        $log = Model::find('sms_logs', $lid);
        if (!$log) {
            flash('error', 'رکورد پیامک یافت نشد.');
            back(admin_url('sms'));
        }
        $res = Sms::send((string) $log['phone'], (string) $log['message'],
            $log['user_id'] ? (int) $log['user_id'] : null, (string) $log['template_key']);
        flash($res['success'] ? 'success' : 'error',
            $res['success'] ? 'پیامک مجدداً ارسال شد.' : ('ارسال مجدد ناموفق: ' . $res['message']));
        back(admin_url('sms'));
    }

    /** استخراج لیست مخاطبان بر اساس گروه انتخابی */
    private function resolveAudience(string $audience, string $customNumbers = ''): array
    {
        switch ($audience) {
            case 'buyers':
                return Model::all("SELECT DISTINCT u.id, u.full_name, u.phone, u.wallet_balance FROM users u
                                   JOIN orders o ON o.user_id = u.id AND o.status NOT IN ('cancelled','returned')
                                   WHERE u.phone <> '' ORDER BY u.id");
            case 'no_orders':
                return Model::all("SELECT u.id, u.full_name, u.phone, u.wallet_balance FROM users u
                                   WHERE u.phone <> '' AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
                                   ORDER BY u.id");
            case 'recent':
                return Model::all("SELECT DISTINCT u.id, u.full_name, u.phone, u.wallet_balance FROM users u
                                   JOIN orders o ON o.user_id = u.id
                                   WHERE u.phone <> '' AND o.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                                   ORDER BY u.id");
            case 'inactive':
                return Model::all("SELECT u.id, u.full_name, u.phone, u.wallet_balance FROM users u
                                   WHERE u.phone <> '' AND EXISTS (SELECT 1 FROM orders o WHERE o.user_id = u.id)
                                     AND NOT EXISTS (SELECT 1 FROM orders o2 WHERE o2.user_id = u.id
                                                     AND o2.created_at >= DATE_SUB(NOW(), INTERVAL 180 DAY))
                                   ORDER BY u.id");
            case 'wallet':
                return Model::all("SELECT id, full_name, phone, wallet_balance FROM users
                                   WHERE phone <> '' AND wallet_balance > 0 ORDER BY wallet_balance DESC");
            case 'custom':
                $out = [];
                foreach (preg_split('/[\s,،;\r\n]+/', $customNumbers) ?: [] as $n) {
                    $p = \Admin\core\Auth::normalizePhone((string) $n);
                    if (preg_match('/^09\d{9}$/', $p)) {
                        $u = Model::one('SELECT id, full_name, wallet_balance FROM users WHERE phone = ?', [$p]);
                        $out[] = ['id' => $u['id'] ?? null, 'full_name' => $u['full_name'] ?? '', 'phone' => $p,
                                  'wallet_balance' => $u['wallet_balance'] ?? 0];
                    }
                }
                return $out;
            case 'all':
            default:
                return Model::all("SELECT id, full_name, phone, wallet_balance FROM users WHERE phone <> '' ORDER BY id");
        }
    }
}
