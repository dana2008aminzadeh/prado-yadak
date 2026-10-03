<?php
namespace App\models;

use Core\Database;
use PDO;
use Throwable;

/**
 * مدل اطلاعیه‌های سایت (جدول site_notices)
 * ---------------------------------------------------------------------------
 * اطلاعیه‌ها از پنل مدیریت ساخته می‌شوند و بر اساس «کلید صفحه» روی فروشگاه
 * نمایش داده می‌شوند. اطلاعیه با کلید 'global' علاوه بر کلید صفحه جاری،
 * روی همه صفحات نمایش داده می‌شود.
 *
 * نمایش فروشگاه از پارشیال مشترک assets/php/notices.php انجام می‌شود که
 * در هدر همه صفحات گنجانده شده است؛ کشوی سبد خرید هم از طریق
 * GET /api/notices?page=cart اطلاعیه‌های خود را می‌گیرد.
 */
class Notice
{
    /**
     * کلیدهای صفحه نمایش + برچسب فارسی.
     * این لیست تنها منبع حقیقت (single source of truth) است و در فرم
     * پنل مدیریت هم برای dropdown صفحه نمایش استفاده می‌شود.
     */
    public const PAGE_LABELS = [
        'global'   => 'همه صفحات',
        'home'     => 'صفحه اصلی',
        'parts'    => 'کاتالوگ قطعات',
        'product'  => 'صفحه جزئیات محصول',
        'blog'     => 'وبلاگ و مقالات',
        'cart'     => 'سبد خرید (کشویی)',
        'checkout' => 'تسویه حساب و پرداخت',
        'order'    => 'نتیجه سفارش',
        'login'    => 'ورود / ثبت‌نام',
        'profile'  => 'پنل کاربری',
        'terms'    => 'قوانین و ضمانت',
    ];

    /** کلیدهای هدف نمایش (بدون global) */
    public const PAGE_KEYS = [
        'home', 'parts', 'product', 'blog', 'cart',
        'checkout', 'order', 'login', 'profile', 'terms',
    ];

    /** کش در سطح درخواست برای جلوگیری از کوئری تکراری در پارشیال‌ها */
    private static array $cache = [];

    /**
     * اطلاعیه‌های فعالِ یک صفحه (اختصاصی همان صفحه + سراسری) را برمی‌گرداند.
     * خروجی بر اساس اولویت (نزولی) و سپس جدیدترین مرتب می‌شود.
     *
     * کلید نامعتبر به 'global' فرو می‌ریزد؛ یعنی فقط اطلاعیه‌های سراسری.
     * خطای دیتابیس (مثل نبود جدول) هرگز صفحه را از کار نمی‌اندازد.
     */
    public static function getForPage(?string $page = 'checkout'): array
    {
        $page = self::normalizePageKey($page);

        if (array_key_exists($page, self::$cache)) {
            return self::$cache[$page];
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, page, type, title, message, icon, priority
                FROM site_notices
                WHERE (page = ? OR page = 'global') AND is_active = 1
                ORDER BY priority DESC, id DESC
            ");
            $stmt->execute([$page]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Notice::getForPage failed: ' . $e->getMessage());
            return self::$cache[$page] = [];
        }

        foreach ($rows as &$row) {
            $row['type'] = self::sanitizeType($row['type'] ?? 'info');
            $row['icon'] = self::sanitizeIcon($row['icon'] ?? 'info');
        }
        unset($row);

        return self::$cache[$page] = $rows;
    }

    /** نرمال‌سازی کلید صفحه ورودی (کاربر/API)؛ نامعتبر → 'global' */
    public static function normalizePageKey(?string $page): string
    {
        $page = strtolower(trim((string) $page));

        return ($page === 'global' || in_array($page, self::PAGE_KEYS, true)) ? $page : 'global';
    }

    /**
     * نگاشت مسیر جاری فروشگاه به کلید صفحه اطلاعیه.
     * مسیرهای ناشناخته (مثل صفحه 404) فقط اطلاعیه سراسری می‌گیرند.
     */
    public static function pageKeyFromUri(?string $uri): string
    {
        $uri = (string) $uri;

        // حذف query string (مثل ?page=2) و fragment پیش از تطبیق مسیر؛
        // در غیر این صورت /parts?page=2 با هیچ کلیدی match نمی‌شد.
        $uri = preg_split('/[?#]/', $uri, 2)[0];

        $path = '/' . trim(rawurldecode($uri), '/');

        if ($path === '/' || $path === '') {
            return 'home';
        }
        if ($path === '/parts' || str_starts_with($path, '/parts/')) {
            return 'parts';
        }
        if ($path === '/product' || str_starts_with($path, '/product/')) {
            return 'product';
        }
        if ($path === '/blog' || $path === '/blog-detail' || str_starts_with($path, '/blog/')) {
            return 'blog';
        }
        if ($path === '/checkout') {
            return 'checkout';
        }
        if ($path === '/order/success' || str_starts_with($path, '/order/')) {
            return 'order';
        }
        if ($path === '/login') {
            return 'login';
        }
        if ($path === '/profile') {
            return 'profile';
        }
        if ($path === '/terms') {
            return 'terms';
        }

        return 'global';
    }

    /** نوع اطلاعیه را به سه حالت مجاز محدود می‌کند */
    public static function sanitizeType(?string $type): string
    {
        $type = strtolower(trim((string) $type));

        return in_array($type, ['info', 'warning', 'danger'], true) ? $type : 'info';
    }

    /** نام آیکون Lucide را به الگوی امن (حروف/رقم/خط تیره) محدود می‌کند */
    public static function sanitizeIcon(?string $icon): string
    {
        $icon = strtolower(trim((string) $icon));

        return preg_match('/^[a-z0-9][a-z0-9-]{0,63}$/', $icon) ? $icon : 'info';
    }
}
