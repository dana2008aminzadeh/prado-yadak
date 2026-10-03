<?php
namespace App\controllers;

use App\models\Notice;

/**
 * API عمومی اطلاعیه‌های سایت
 * ---------------------------------------------------------------------------
 * کشوی سبد خرید (و هر جزء سمت کلاینت دیگر) اطلاعیه‌های صفحه موردنظر خود را
 * از این اندپوینت می‌گیرد:
 *
 *   GET /api/notices?page=cart
 *
 * کلید صفحه نامعتبر به 'global' فرو می‌ریزد (فقط اطلاعیه‌های سراسری).
 */
class NoticeController extends Controller
{
    public function apiList()
    {
        $pageKey = Notice::normalizePageKey((string) ($_GET['page'] ?? ''));
        $notices = Notice::getForPage($pageKey);

        // فقط فیلدهای عمومی و پالایش‌شده به بیرون فرستاده می‌شود
        $items = array_map(static function (array $notice): array {
            return [
                'id'       => (int) ($notice['id'] ?? 0),
                'type'     => $notice['type'] ?? 'info',
                'title'    => (string) ($notice['title'] ?? ''),
                'message'  => (string) ($notice['message'] ?? ''),
                'icon'     => $notice['icon'] ?? 'info',
                'priority' => (int) ($notice['priority'] ?? 0),
            ];
        }, $notices);

        $this->jsonResponse([
            'success' => true,
            'page'    => $pageKey,
            'notices' => $items,
        ]);
    }
}
