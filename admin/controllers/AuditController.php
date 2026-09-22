<?php
namespace Admin\controllers;

use Admin\core\Audit;
use Admin\core\Model;

class AuditController extends BaseController
{
    protected string $section = 'audit';
    protected int $perPage = 40;

    protected array $permissions = [
        'purge'  => 'roles.manage',
        'export' => 'audit.view',
    ];

    public function index($id = 0): void
    {
        $filters = [
            'admin_id' => (int) param('admin_id', 0),
            'action'   => (string) param('action', ''),
            'entity'   => (string) param('entity', ''),
            'q'        => trim((string) param('q', '')),
            'from'     => $this->dateParam('from'),
            'to'       => $this->dateParam('to'),
            'critical' => param('critical') ? 1 : 0,
        ];

        $total = Audit::countAll($filters);
        $pg = paginate($total, $this->page(), $this->perPage);
        $logs = Audit::query($filters, $this->perPage, $pg['offset']);

        $admins = Model::all("SELECT DISTINCT admin_id, admin_name FROM admin_audit_logs
                              WHERE admin_id IS NOT NULL ORDER BY admin_name");
        $actions = Model::all('SELECT action, COUNT(*) c FROM admin_audit_logs GROUP BY action ORDER BY c DESC');

        $stats = [
            'today'    => (int) Model::scalar('SELECT COUNT(*) FROM admin_audit_logs WHERE DATE(created_at) = CURDATE()'),
            'week'     => (int) Model::scalar('SELECT COUNT(*) FROM admin_audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'critical' => (int) Model::scalar('SELECT COUNT(*) FROM admin_audit_logs WHERE action IN ('
                . implode(',', array_fill(0, count(Audit::CRITICAL), '?')) . ') AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)',
                Audit::CRITICAL),
            'blocked'  => (int) Model::scalar("SELECT COUNT(*) FROM admin_audit_logs
                                               WHERE action LIKE 'security.%' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
        ];

        $labels = Audit::LABELS;
        $critical = Audit::CRITICAL;
        $fromJ = param('from', '');
        $toJ = param('to', '');

        $this->view('audit/index',
            compact('logs', 'pg', 'filters', 'admins', 'actions', 'stats', 'labels', 'critical', 'fromJ', 'toJ'),
            'لاگ رویدادهای مدیران', money($total) . ' رویداد ثبت‌شده');
    }

    public function export($id = 0): void
    {
        $filters = [
            'admin_id' => (int) param('admin_id', 0),
            'action'   => (string) param('action', ''),
            'from'     => $this->dateParam('from'),
            'to'       => $this->dateParam('to'),
            'critical' => param('critical') ? 1 : 0,
        ];
        $logs = Audit::query($filters, 5000, 0);

        $data = array_map(fn($l) => [
            $l['id'],
            shamsiTime($l['created_at']),
            $l['admin_name'] ?? '—',
            Audit::label($l['action']),
            $l['entity_type'] ?? '',
            $l['entity_id'] ?? '',
            $l['description'] ?? '',
            $l['old_values'] ?? '',
            $l['new_values'] ?? '',
            $l['ip_address'] ?? '',
        ], $logs);

        $this->streamCsv('audit-log-' . date('Y-m-d') . '.csv',
            ['شناسه', 'تاریخ', 'مدیر', 'رویداد', 'نوع موجودیت', 'شناسه موجودیت', 'توضیح', 'مقدار قبلی', 'مقدار جدید', 'IP'],
            $data);
    }

    /** پاک‌سازی لاگ‌های قدیمی */
    public function purge($id = 0): void
    {
        $days = max(30, (int) post('days', 180));
        $deleted = Model::exec('DELETE FROM admin_audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)', [$days]);

        $this->audit('role.update', 'audit', null, "پاک‌سازی {$deleted} رویداد قدیمی‌تر از {$days} روز");
        flash('success', money($deleted) . ' رویداد قدیمی حذف شد.');
        redirect(admin_url('audit'));
    }
}
