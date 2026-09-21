<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Model;

abstract class BaseController
{
    protected string $section = 'dashboard';
    protected int $perPage = 20;

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

    /** شمارنده‌های نوتیفیکیشن منو */
    protected function badges(): array
    {
        try {
            return [
                'orders'   => Model::count('orders', "status = 'processing'"),
                'tickets'  => Model::count('support_tickets', "status IN ('open','pending')"),
                'comments' => Model::count('product_comments', "status = 'pending'"),
                'wallet'   => Model::count('wallet_transactions', "status = 'pending'"),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function csrf(): string
    {
        return Auth::csrf();
    }

    protected function admin(): array
    {
        return Auth::user() ?? [];
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
}
