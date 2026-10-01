<?php
namespace App\models;

use Core\Database;
use PDO;

/**
 * مدل اطلاعیه‌های سایت
 * ---------------------------------------------------------------------------
 * اطلاعیه‌ها از پنل مدیریت تعریف می‌شوند و لایه‌ی نمایش مشترک
 * (assets/php/notices.php) آن‌ها را در تمام صفحات سایت نشان می‌دهد.
 *
 * هر اطلاعیه به یک «صفحه» متصل است؛ کلید 'global' روی همه صفحات دیده می‌شود.
 * کلید صفحه با URI درخواست در currentPageKey() تطبیق داده می‌شود، پس افزودن
 * صفحه‌ی جدید فقط با افزودن کلید به PAGES و انتخاب آن در پنل ممکن است.
 */
class Notice
{
    /**
     * کلیدهای مجاز «صفحه نمایش» اطلاعیه.
     * این ثابت منبع واحد (single source of truth) هم برای پنل مدیریت و هم
     * برای اعتبارسنجی داده‌های ورودی است؛ ترتیب آرایه ترتیب نمایش در پنل است.
     */
    public const PAGES = [
        'global'       => 'همه صفحات',
        'home'         => 'صفحه اصلی',
        'parts'        => 'کاتالوگ قطعات و لندینگ دسته/مدل',
        'product'      => 'صفحه جزئیات محصول',
        'cart'         => 'سبد خرید (کشوی کنار صفحه)',
        'checkout'     => 'صفحه تسویه حساب و پرداخت',
        'order-success' => 'صفحه ثبت موفق سفارش',
        'blog'         => 'وبلاگ و مقالات',
        'login'        => 'صفحه ورود و ثبت‌نام',
        'profile'      => 'پنل کاربری',
        'terms'        => 'قوانین و ضمانت اصالت',
        '404'          => 'صفحه خطای ۴۰۴',
    ];

    /** کش درخواست جاری؛ هدر و کشوی سبد خرید نباید کوئری تکراری بزنند */
    private static array $cache = [];

    /**
     * اطلاعیه‌های فعال یک صفحه به‌همراه اطلاعیه‌های سراسری (global).
     *
     * اگر جدول site_notices هنوز ساخته نشده باشد (نصب تازه یا اجرای مهاجرت
     * فراموش‌شده) به‌جای متوقف کردن صفحه، آرایه خالی برگردانده می‌شود.
     */
    public static function getForPage(string $page = 'checkout'): array
    {
        $page = trim($page);

        // کلیدهای ناشناخته (مثلاً صفحه‌های بدون اطلاعیه اختصاصی) فقط
        // اطلاعیه سراسری دریافت می‌کنند؛ مقدار دلخواه به SQL راه نمی‌یابد.
        if ($page === '' || !array_key_exists($page, self::PAGES)) {
            $page = '';
        }

        if (array_key_exists($page, self::$cache)) {
            return self::$cache[$page];
        }

        try {
            $db = Database::getInstance();

            if ($page === '') {
                $stmt = $db->prepare(
                    "SELECT * FROM site_notices
                     WHERE page = 'global' AND is_active = 1
                     ORDER BY priority DESC, id DESC"
                );
                $stmt->execute();
            } else {
                $stmt = $db->prepare(
                    "SELECT * FROM site_notices
                     WHERE (page = ? OR page = 'global') AND is_active = 1
                     ORDER BY priority DESC, id DESC"
                );
                $stmt->execute([$page]);
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            // هیچ صفحه‌ای از سایت به‌خاطر نبود جدول اطلاعیه نباید خطا بدهد.
            error_log('Notice::getForPage failed: ' . $e->getMessage());
            $rows = [];
        }

        return self::$cache[$page] = $rows;
    }

    /**
     * تشخیص کلید صفحه‌ی جاری از روی مسیر درخواست.
     *
     * این نگاشت باید با routeهای تعریف‌شده در Core\Router::defineRoutes()
     * هم‌گام بماند؛ URIهایی که به هیچ صفحه‌ی شناخته‌شده‌ای اشاره ندارند
     * رشته خالی برمی‌گردانند (فقط اطلاعیه سراسری نمایش داده می‌شود).
     */
    public static function currentPageKey(): string
    {
        // خطاهای ۴۰۴ پیش از رندر view با کد وضعیت مناسب اجرا می‌شوند؛
        // اطلاعیه‌ی صفحه خطا فقط برای همین حالت معنا دارد.
        if (http_response_code() >= 400) {
            return '404';
        }

        $uri = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

        // نرمال‌سازی اولیه؛ نرمال‌سازی کامل قبلاً توسط UrlCanonicalizer انجام شده.
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        if ($uri === '') {
            $uri = '/';
        }

        if ($uri === '/' || $uri === '/index' || $uri === '/index.php') {
            return 'home';
        }

        if ($uri === '/parts' || str_starts_with($uri, '/parts/')) {
            return 'parts';
        }

        if ($uri === '/product' || str_starts_with($uri, '/product/')) {
            return 'product';
        }

        if ($uri === '/blog' || $uri === '/blog-detail' || str_starts_with($uri, '/blog/')) {
            return 'blog';
        }

        if ($uri === '/checkout') {
            return 'checkout';
        }

        if ($uri === '/order/success' || str_starts_with($uri, '/order/')) {
            return 'order-success';
        }

        if ($uri === '/login') {
            return 'login';
        }

        if ($uri === '/profile' || str_starts_with($uri, '/profile/')) {
            return 'profile';
        }

        if ($uri === '/terms') {
            return 'terms';
        }

        return '';
    }
}
