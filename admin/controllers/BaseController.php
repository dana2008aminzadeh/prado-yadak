<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Audit;
use Admin\core\Inventory;
use Admin\core\Model;

abstract class BaseController
{
    protected string $section = 'dashboard';
    protected int $perPage = 20;

    /** نگاشت اکشن → دسترسی لازم؛ در کنترلرهای فرزند بازنویسی می‌شود */
    protected array $permissions = [];

    public function permissionFor(string $action): ?string
    {
        return $this->permissions[$action] ?? null;
    }

    /** رندر یک صفحه داخل قالب پنل */
    protected function view(string $viewFile, array $data = [], string $title = 'پنل مدیریت', string $sub = ''): void
    {
        $GLOBALS['admin_badges'] = $this->badges();

        extract($data, EXTR_SKIP);
        $section = $this->section;
        $pageTitle = $title;
        $pageSub = $sub;

        ob_start();
        require ADMIN_PATH . '/views/pages/' . $viewFile . '.php';
        $content = ob_get_clean();

        require ADMIN_PATH . '/views/layout/main.php';
    }

    protected function json($data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    protected function page(): int
    {
        return max(1, (int) param('page', 1));
    }

    /** شمارنده‌های نوتیفیکیشن منو (با توجه به دسترسی) */
    protected function badges(): array
    {
        $b = [];
        try {
            if (Auth::can('orders.view')) {
                $b['orders'] = Model::count('orders', "status IN ('pending_payment','processing')");
            }
            if (Auth::can('tickets.view')) {
                $b['tickets'] = Model::count('support_tickets', "status IN ('open','pending')");
            }
            if (Auth::can('comments.view')) {
                $b['comments'] = Model::count('product_comments', "status = 'pending'");
            }
            if (Auth::can('wallet.view')) {
                $b['wallet'] = Model::count('wallet_transactions', "status = 'pending'");
            }
            if (Auth::can('products.view')) {
                $b['products'] = count(Inventory::lowStock(99));
            }
            if (Auth::can('seo.view') && Model::hasTable('seo_404_logs')) {
                $b['seo'] = Model::count('seo_404_logs', 'resolved = 0');
            }
        } catch (\Throwable $e) {
            // جداول ممکن است هنوز مهاجرت نشده باشند
        }
        return $b;
    }

    protected function csrf(): string
    {
        return Auth::csrf();
    }

    protected function admin(): array
    {
        return Auth::user() ?? [];
    }

    protected function adminId(): int
    {
        return (int) ($this->admin()['id'] ?? 0);
    }

    protected function adminName(): string
    {
        return (string) ($this->admin()['full_name'] ?? 'مدیر');
    }

    /** ثبت رویداد در لاگ ممیزی */
    protected function audit(string $action, ?string $entity = null, $entityId = null, ?string $desc = null, ?array $old = null, ?array $new = null): void
    {
        Audit::log($action, $entity, $entityId, $desc, $old, $new);
    }

    /** اطمینان از وجود دسترسی، وگرنه توقف */
    protected function need(string $permission): void
    {
        Auth::requirePermission($permission);
    }

    /** ساخت بخش WHERE از فیلترهای اختیاری */
    protected function buildWhere(array $conditions): array
    {
        $where = [];
        $params = [];
        foreach ($conditions as [$sql, $value]) {
            if ($value === null || $value === '') continue;
            $where[] = $sql;
            if (is_array($value)) {
                foreach ($value as $v) $params[] = $v;
            } else {
                $params[] = $value;
            }
        }
        return [$where ? implode(' AND ', $where) : '1', $params];
    }

    /** ارسال یک فایل CSV با پشتیبانی UTF-8 برای اکسل */
    protected function streamCsv(string $filename, array $headers, array $rows): void
    {
        $this->audit('export.data', 'csv', $filename, 'خروجی CSV: ' . $filename . ' (' . count($rows) . ' ردیف)');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers);
        foreach ($rows as $r) {
            fputcsv($out, is_array($r) ? array_values($r) : [$r]);
        }
        fclose($out);
        exit;
    }

    /** دریافت تاریخ شمسی از فرم و تبدیل به میلادی */
    protected function dateParam(string $key, ?string $default = null): ?string
    {
        $raw = param($key);
        if ($raw === null || $raw === '') return $default;
        return jalaliToDate((string) $raw) ?? $default;
    }

    protected function datePost(string $key): ?string
    {
        $raw = post($key);
        if ($raw === null || $raw === '') return null;
        return jalaliToDate((string) $raw);
    }
}
