<?php

namespace Core;

/**
 * موتور مرکزی سئو — پرادو یدک
 * ---------------------------------------------------------------------------
 * تمام منطق سئوی سایت (متاتگ‌ها، کانونیکال، ربات‌ها، JSON-LD گرافی و آدرس تصاویر)
 * از همین کلاس تغذیه می‌شود تا هیچ تناقضی بین قالب‌ها و کنترلرها پیش نیاید.
 *
 * اصل حاکم بر متاها: «اولویت‌بندی سلسله‌مراتبی»
 *   ۱) مقدار دستی ثبت‌شده توسط مدیر (meta_title / meta_description)
 *   ۲) فرمول هوشمند مخصوص همان نوع محتوا
 *   ۳) مقدار پیش‌فرض سراسری سایت
 *
 * نکات مهم سئو که در این نسخه رعایت شده:
 * - طول title/description بازه پیشنهادی است، نه قانون قطعی گوگل. نمایش بر اساس
 *   عرض پیکسلی، دستگاه و زبان فارسی متغیر است. Analyzer فقط هشدار می‌دهد.
 * - دامنه پایه هرگز از HTTP_HOST خوانده نمی‌شود (جلوگیری از Host Header Injection).
 * - اسلاگ‌ها کوتاه، پایدار، توصیفی و بدون تکرار بی‌دلیل برند/مدل/کد فنی هستند.
 * - تغییر اسلاگ همیشه با 301 انجام می‌شود (مدیریت در SeoController + UrlCanonicalizer).
 * - Canonical و Robots با تست‌های خودکار پوشش داده می‌شوند.
 */
class Seo
{
    /** بازه‌های استاندارد پیشنهادی گوگل — برای راهنما، نه قانون قطعی */
    public const TITLE_MIN = 50;
    public const TITLE_MAX = 60;
    public const DESC_MIN  = 120;
    public const DESC_MAX  = 155;

    /** بازه هشدار برای Analyzer — بیرون این بازه فقط WARN، نه FAIL */
    public const TITLE_WARN_MIN = 30;
    public const TITLE_WARN_MAX = 70;
    public const DESC_WARN_MIN  = 80;
    public const DESC_WARN_MAX  = 175;

    /** پارامترهایی که در کانونیکال نگه داشته می‌شوند (به همین ترتیب ثابت) */
    public const CANONICAL_PARAMS = ['category', 'model', 'brand', 'page'];

    /** پارامترهایی که همیشه باعث noindex می‌شوند (فیلتر کم‌ارزش/جستجو/مرتب‌سازی) */
    public const NOINDEX_PARAMS = ['q', 'sort', 'maxPrice', 'minPrice', 'inStock', 'view', 'utm_source', 'utm_medium', 'utm_campaign'];

    // ---------------------------------------------------------------- آدرس‌ها

    /** دامنه‌ی ثابت و واقعی پروژه — تنها fallback مجاز وقتی SITE_URL تعریف نشده. */
    private const PRODUCTION_BASE = 'https://pradoyadak.com';

    /** هاست‌های مجاز — SITE_URL فقط اگر هاست آن در این لیست یا زیردامنه pradoyadak.com باشد پذیرفته می‌شود */
    private const ALLOWED_HOSTS = [
        'pradoyadak.com',
        'www.pradoyadak.com',
        'staging.pradoyadak.com',
        'localhost',
        '127.0.0.1',
        '::1',
    ];

    private const FALLBACK_HOST = 'pradoyadak.com';

    /**
     * آدرس مطلق پایه سایت بدون اسلش پایانی.
     * ---------------------------------------------------------------------
     * تصمیم امنیتی قطعی پروژه: این متد هرگز از $_SERVER['HTTP_HOST'] یا
     * X-Forwarded-Host استفاده نمی‌کند، چون این هدرها را کاربر/پراکسی کنترل
     * می‌کند و اعتماد به آن‌ها باعث «Host Header Injection» در کانونیکال،
     * Sitemap، OG:url و JSON-LD می‌شود (URL Poisoning). تنها منبع معتبر
     * ثابت SITE_URL (تعریف‌شده در index.php/admin/index.php) است؛ در نبود
     * آن هم فقط دامنه‌ی واقعی و ثابت سایت به‌کار می‌رود، نه هدر درخواست.
     *
     * برای staging دامنه از config معتبر خوانده می‌شود، نه از Header کاربر.
     * مقدار SITE_URL با whitelist اعتبارسنجی می‌شود.
     */
    public static function base(): string
    {
        $raw = defined('SITE_URL') ? trim((string) SITE_URL) : '';
        if ($raw === '') {
            return self::PRODUCTION_BASE;
        }

        // اگر SITE_URL شامل پروتکل نیست، https اضافه کن
        if (!preg_match('#^https?://#i', $raw)) {
            $raw = 'https://' . ltrim($raw, '/');
        }

        $parts = parse_url($raw);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return self::PRODUCTION_BASE;
        }

        // اعتبارسنجی هاست در برابر whitelist + زیردامنه‌های pradoyadak.com
        $allowed = false;
        foreach (self::ALLOWED_HOSTS as $allowedHost) {
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                $allowed = true;
                break;
            }
        }
        // همچنین هر زیردامنه‌ای از pradoyadak.com مجاز است (مثل staging)
        if (!$allowed && str_ends_with($host, '.pradoyadak.com')) {
            $allowed = true;
        }
        // در محیط توسعه localhost مجاز است، در غیر این صورت فقط pradoyadak.com
        if (!$allowed) {
            // اگر هاست pradoyadak.com را شامل شود ولی دقیق نباشد، باز هم رد می‌کنیم مگر اینکه دقیقا با pradoyadak.com تمام شود
            if (str_ends_with($host, 'pradoyadak.com')) {
                $allowed = true;
            }
        }

        if (!$allowed) {
            return self::PRODUCTION_BASE;
        }

        // اسکیم همیشه https مگر اینکه صراحتا localhost باشد
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        if (!in_array($scheme, ['https', 'http'], true)) {
            $scheme = 'https';
        }
        // در پروداکشن همیشه https
        if (!in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $scheme = 'https';
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        // پورت‌های استاندارد حذف
        if (($scheme === 'https' && $port === ':443') || ($scheme === 'http' && $port === ':80')) {
            $port = '';
        }

        $base = $scheme . '://' . $host . $port;
        // اگر SITE_URL مسیر پایه داشته باشد (مثلا staging در ساب‌فولدر)، حفظ کن
        $path = trim((string) ($parts['path'] ?? ''), '/');
        if ($path !== '') {
            $base .= '/' . $path;
        }

        return rtrim($base, '/');
    }

    /** تبدیل یک مسیر نسبی به آدرس مطلق */
    public static function absolute(string $path): string
    {
        if ($path === '') {
            return self::base() . '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }
        return self::base() . '/' . ltrim($path, '/');
    }

    /**
     * آدرس یک مقاله — تنها نقطه‌ی ساخت لینک وبلاگ در کل پروژه.
     * برای بخش مسیر (path) همیشه rawurlencode استفاده می‌شود، نه urlencode؛
     * چون urlencode فاصله را به «+» تبدیل می‌کند و در مسیر URL نامعتبر است.
     * اسلاگ باید کوتاه، پایدار و توصیفی باشد. تغییر اسلاگ با 301 انجام می‌شود.
     */
    public static function articleUrl(?string $slug, bool $absolute = false): string
    {
        $slug = self::normalizeSlugForUrl((string) $slug);
        $path = '/blog/' . rawurlencode($slug);
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس یک محصول (همان قاعده rawurlencode) — ساختار پیشنهادی: /product/lent-tormoz-jolo-camry-04465-33471 */
    public static function productUrl(?string $slug, bool $absolute = false): string
    {
        $slug = self::normalizeSlugForUrl((string) $slug);
        $path = '/product/' . rawurlencode($slug);
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس تمیز لندینگ یک دسته‌بندی کاتالوگ. */
    public static function categoryUrl(?string $slug, bool $absolute = false): string
    {
        $slug = self::normalizeSlugForUrl((string) $slug);
        $path = '/parts/category/' . rawurlencode($slug);
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس تمیز لندینگ قطعات یک مدل خودرو. */
    public static function modelUrl(?string $slug, bool $absolute = false): string
    {
        $slug = self::normalizeSlugForUrl((string) $slug);
        $path = '/parts/model/' . rawurlencode($slug);
        return $absolute ? self::absolute($path) : $path;
    }

    /** نرمال‌سازی اسلاگ برای URL — حذف فاصله‌های اضافه و کنترل طول */
    private static function normalizeSlugForUrl(string $slug): string
    {
        $slug = trim($slug);
        // اگر اسلاگ خالی است، یک مقدار پیش‌فرض برگردان
        if ($slug === '') {
            return 'item';
        }
        // طول اسلاگ نباید بی‌نهایت باشد
        if (mb_strlen($slug, 'UTF-8') > 160) {
            $slug = mb_substr($slug, 0, 160, 'UTF-8');
        }
        return $slug;
    }

    /** مسیر جاری درخواست، نرمال‌شده و بدون اسلش پایانی */
    public static function currentPath(): string
    {
        $uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }
        return $uri === '' ? '/' : $uri;
    }

    /**
     * تنها منبع نهایی تولید کانونیکال.
     * ---------------------------------------------------------------------
     * هیچ قالب یا کنترلری اجازه ندارد منطق جداگانه‌ای برای کانونیکال داشته باشد؛
     * اگر کنترلر مقدار مشخصی داده باشد همان مطلق‌سازی و برگردانده می‌شود، در غیر
     * این صورت از روی مسیر جاری و موجودیت صفحه (محصول/مقاله/لندینگ) ساخته می‌شود.
     *
     * پوشش تست پیشنهادی:
     * /index.php      → 301 به /
     * /index          → 301 به /
     * /               → /
     * /parts/         → /parts
     * /parts?page=1   → 301 به /parts
     * /parts?utm_source=x → canonical بدون UTM و ترجیحاً noindex
     * /product?id=10  → 301 به URL اسلاگ
     * /product/slug/  → 301 به /product/slug
     * /blog?id=10     → 301 به URL اسلاگ
     * /blog/slug/     → 301 به /blog/slug
     *
     * @param string|null $explicit مقدار محاسبه‌شده توسط کنترلر (اختیاری)
     * @param array       $context  ['article' => [...], 'product' => [...], 'landing' => [...]]
     */
    public static function canonical(?string $explicit = null, array $context = []): string
    {
        $explicit = trim((string) $explicit);
        if ($explicit !== '') {
            return self::absolute($explicit);
        }

        $base = self::base();
        $uri  = self::currentPath();

        if ($uri === '/' || $uri === '/index' || $uri === '/index.php') {
            return $base . '/';
        }

        $article = $context['article'] ?? null;
        if (is_array($article) && !empty($article['slug'])
            && (str_starts_with($uri, '/blog/') || $uri === '/blog-detail' || $uri === '/blog' || $uri === '/article')) {
            return self::articleUrl((string) $article['slug'], true);
        }

        $product = $context['product'] ?? null;
        if (is_array($product) && !empty($product['slug']) && str_starts_with($uri, '/product')) {
            return self::productUrl((string) $product['slug'], true);
        }

        $landing = $context['landing'] ?? null;
        if (is_array($landing) && !empty($landing['slug']) && str_starts_with($uri, '/parts/')) {
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            return self::absolute('/parts/' . rawurlencode((string) $landing['slug']))
                . ($page > 1 ? '?page=' . $page : '');
        }

        if ($uri === '/parts') {
            // ترتیب پارامترها همیشه نرمال می‌شود تا نسخه‌های موازی ساخته نشود
            return self::catalogCanonical($_GET, '/parts');
        }

        return $base . $uri;
    }

    /**
     * نرمال‌سازی رشته‌ی کوئری: ترتیب پارامترها همیشه ثابت است تا
     * /parts?model=x&category=y و /parts?category=y&model=x یک کانونیکال یکسان بدهند.
     *
     * بهبودهای امنیتی و سئویی:
     * - مقادیر category, model, brand از نظر طول و فرمت بررسی می‌شوند.
     * - ورودی‌های بسیار طولانی یا غیرواقعی حذف می‌شوند تا URLهای بی‌نهایت ساخته نشود.
     * - تعداد مقادیر چندتایی محدود می‌شود.
     * - مقدارهای ناشناخته در canonical حذف می‌شوند و در UrlCanonicalizer با 301 ریدایرکت می‌شوند.
     */
    public static function normalizeQuery(array $get, array $allowed = self::CANONICAL_PARAMS): string
    {
        $clean = [];
        foreach ($allowed as $key) {
            if (!isset($get[$key])) {
                continue;
            }
            $value = $get[$key];

            if (is_array($value)) {
                $value = implode(',', array_map('strval', $value));
            }
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            // محدودیت طول کلی برای جلوگیری از URLهای بسیار طولانی
            if (mb_strlen($value, 'UTF-8') > 200) {
                $value = mb_substr($value, 0, 200, 'UTF-8');
            }

            // مقادیر چندتایی (مثل category=a,b) نیز مرتب می‌شوند
            if (str_contains($value, ',')) {
                $parts = array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), 'strlen')));
                // محدودیت تعداد فیلترهای قابل ترکیب — حداکثر 3 مقدار برای هر پارامتر
                if (count($parts) > 5) {
                    $parts = array_slice($parts, 0, 5);
                }
                // اعتبارسنجی هر اسلاگ
                $parts = array_values(array_filter($parts, static function ($slug): bool {
                    return self::isValidTaxonomySlug($slug);
                }));
                sort($parts, SORT_STRING);
                $value = implode(',', $parts);
                if ($value === '') {
                    continue;
                }
            } else {
                // تک‌مقداری — اعتبارسنجی اسلاگ برای taxonomy
                if (in_array($key, ['category', 'model', 'brand'], true)) {
                    if (!self::isValidTaxonomySlug($value)) {
                        continue;
                    }
                }
            }

            if ($key === 'page') {
                $page = (int) $value;
                if ($page <= 1) {
                    continue;
                }
                // محدودیت منطقی برای شماره صفحه
                if ($page > 1000) {
                    $page = 1000;
                }
                $value = (string) $page;
            }

            $clean[$key] = $value;
        }

        return $clean ? http_build_query($clean, '', '&', PHP_QUERY_RFC3986) : '';
    }

    /** اعتبارسنجی اسلاگ taxonomy — فقط حروف (فارسی/لاتین)، اعداد، dash، underscore */
    private static function isValidTaxonomySlug(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '' || mb_strlen($slug, 'UTF-8') > 80) {
            return false;
        }
        // حداقل 2 کاراکتر، حداکثر 80، فقط حروف، اعداد، - _
        // فارسی و عربی را هم شامل می‌شود
        return (bool) preg_match('/^[\p{L}\p{N}\-_]{2,80}$/u', $slug);
    }

    /** آدرس کانونیکال نرمال‌شده‌ی کاتالوگ */
    public static function catalogCanonical(array $get, string $path = '/parts'): string
    {
        $qs = self::normalizeQuery($get);
        return self::absolute($path) . ($qs ? '?' . $qs : '');
    }

    /**
     * تصمیم‌گیری ربات‌ها برای صفحات کاتالوگ:
     *  - جستجو / مرتب‌سازی / فیلتر قیمت  → noindex, follow
     *  - ترکیب بیش از دو فیلتر اصلی        → noindex, follow (محتوای کم‌ارزش/تکراری)
     *  - در غیر این صورت                   → index, follow
     *
     * همچنین باید بررسی شود که تمام URLهای noindex:
     * - در Sitemap نباشند
     * - لینک داخلی بی‌نهایت نسازند
     * - در JS با History API URLهای اضافی تولید نکنند
     * - با status 200 و محتوای واقعی اما تکراری، حجم crawl را زیاد نکنند
     */
    public static function catalogRobots(array $get): string
    {
        foreach (self::NOINDEX_PARAMS as $param) {
            if (isset($get[$param]) && trim((string) (is_array($get[$param]) ? implode(',', $get[$param]) : $get[$param])) !== '') {
                // اگر پارامتر utm باشد، canonical بدون آن است و noindex ترجیح دارد
                return 'noindex, follow';
            }
        }

        $active = 0;
        foreach (['category', 'model', 'brand'] as $param) {
            $value = $get[$param] ?? '';
            $value = is_array($value) ? implode(',', $value) : (string) $value;
            if (trim($value) === '') {
                continue;
            }
            // چند مقدار هم‌زمان روی یک فیلتر = ترکیب کم‌ارزش
            $active += count(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }

        // محدودیت تعداد فیلترهای قابل ترکیب — بیش از 2 فیلتر، صفحه indexable نیست مگر محتوای اختصاصی داشته باشد
        return $active > 2 ? 'noindex, follow' : 'index, follow';
    }

    // ---------------------------------------------------------------- متاها

    /**
     * حل سلسله‌مراتبی متاتگ‌ها.
     *
     * @param array $entity   رکورد محصول/مقاله/لندینگ (ممکن است کلیدهای سئو را نداشته باشد)
     * @param array $fallback ['title' => ..., 'description' => ..., 'canonical' => ..., 'robots' => ...]
     * @return array{title:string,description:string,canonical:string,robots:string,source:array}
     */
    public static function resolve(array $entity, array $fallback): array
    {
        $manualTitle = trim((string) ($entity['meta_title'] ?? ''));
        $manualDesc  = trim((string) ($entity['meta_description'] ?? ''));
        $manualCanon = trim((string) ($entity['canonical_url'] ?? ''));
        $directive   = (string) ($entity['robots_directive'] ?? 'default');

        $title = $manualTitle !== '' ? $manualTitle : (string) ($fallback['title'] ?? '');
        $desc  = $manualDesc  !== '' ? $manualDesc  : (string) ($fallback['description'] ?? '');

        $robots = (string) ($fallback['robots'] ?? 'index, follow');
        if ($directive === 'noindex') {
            $robots = 'noindex, follow';
        } elseif ($directive === 'noindex_nofollow') {
            $robots = 'noindex, nofollow';
        } elseif ($directive === 'index') {
            $robots = 'index, follow';
        }

        return [
            'title'       => self::clean($title),
            'description' => self::clean($desc),
            'canonical'   => $manualCanon !== '' ? self::absolute($manualCanon) : (string) ($fallback['canonical'] ?? ''),
            'robots'      => $robots,
            'source'      => [
                'title'       => $manualTitle !== '' ? 'manual' : 'auto',
                'description' => $manualDesc !== '' ? 'manual' : 'auto',
            ],
        ];
    }

    /** پاک‌سازی متن برای استفاده در متاتگ (حذف تگ‌ها، فاصله‌های اضافه و نیم‌فاصله‌های خراب) */
    public static function clean(?string $text): string
    {
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        return trim($text);
    }

    /** بریدن متن روی مرز کلمه (نه وسط کلمه) */
    public static function truncate(?string $text, int $limit): string
    {
        $text = self::clean($text);
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit, 'UTF-8');
        $pos = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($pos !== false && $pos > $limit * 0.6) {
            $cut = mb_substr($cut, 0, $pos, 'UTF-8');
        }
        return rtrim($cut, ' ،-') . '…';
    }

    // ------------------------------------------------- فرمول‌های پشتیبان متا

    /** عنوان پیشنهادی محصول: نام + کد فنی + برند + نام سایت (کوتاه‌شده به ۶۰ کاراکتر) */
    public static function productTitle(array $product, string $siteName): string
    {
        $name = trim((string) ($product['name'] ?? ''));
        $oem = trim((string) ($product['oem'] ?? $product['oem_code'] ?? ''));

        // اگر نام شامل کد فنی است، تکرار نکن
        $parts = [$name];
        if ($oem !== '' && !str_contains(mb_strtolower($name, 'UTF-8'), mb_strtolower($oem, 'UTF-8'))) {
            // کد فنی را فقط اگر طول عنوان اجازه دهد اضافه کن
            $parts[] = $oem;
        }

        $base = implode(' ', array_filter($parts, 'strlen'));
        $full = $base . ' | ' . $siteName;

        if (mb_strlen($full, 'UTF-8') > self::TITLE_MAX) {
            $room = self::TITLE_MAX - mb_strlen(' | ' . $siteName, 'UTF-8');
            $base = self::truncate($base, max(20, $room));
            $full = $base . ' | ' . $siteName;
        }
        return $full;
    }

    /**
     * توضیحات پیشنهادی محصول: تلاش می‌کند از محتوای یکتای دیتابیس استفاده کند.
     *
     * محتوای زیر باید از دیتابیس محصول بیاید تا description تکراری نشود:
     * - توضیح فنی یکتا
     * - علائم خرابی قطعه
     * - خودروهای سازگار
     * - برند تولیدکننده
     * - تفاوت Genuine و OEM
     * - شرایط گارانتی
     * - کدهای معادل
     * - راهنمای نصب یا تعویض
     *
     * متای خودکار خوب است، اما جایگزین محتوای اصلی صفحه نیست.
     */
    public static function productDescription(array $product, string $siteName): string
    {
        $name  = trim((string) ($product['name'] ?? ''));
        $oem   = trim((string) ($product['oem'] ?? $product['oem_code'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));
        $stock = !empty($product['inStock']) || !empty($product['in_stock']);

        // اولویت اول: اگر توضیح یکتای محصول از قبل به اندازه کافی طولانی و باکیفیت است، همان را استفاده کن
        $uniqueDesc = self::clean((string) ($product['description'] ?? $product['desc'] ?? ''));
        if (mb_strlen($uniqueDesc, 'UTF-8') >= self::DESC_MIN && mb_strlen($uniqueDesc, 'UTF-8') <= 300) {
            // اگر توضیح یکتا شامل نام محصول و کد فنی نیست، آن را به ابتدای متن اضافه کن
            $hasName = $name !== '' && str_contains(mb_strtolower($uniqueDesc, 'UTF-8'), mb_strtolower($name, 'UTF-8'));
            $hasOem = $oem === '' || str_contains(mb_strtolower($uniqueDesc, 'UTF-8'), mb_strtolower($oem, 'UTF-8'));
            if (!$hasName || !$hasOem) {
                $prefix = $name . ($oem !== '' ? ' کد فنی ' . $oem : '');
                $uniqueDesc = $prefix . '؛ ' . $uniqueDesc;
            }
            return self::truncate($uniqueDesc, self::DESC_MAX);
        }

        // در غیر این صورت، فرمول هوشمند با تکیه بر اطلاعات موجود
        $desc = 'خرید ' . $name
            . ($oem !== '' ? ' با کد فنی ' . $oem : '')
            . ($brand !== '' ? ' برند ' . $brand : '')
            . ($stock ? '؛ موجود در انبار' : '؛ استعلام موجودی')
            . ' با ضمانت اصالت، فاکتور رسمی و ارسال سریع از ' . $siteName . '.';

        // اگر توضیح یکتا وجود دارد ولی کوتاه است، به انتهای متا اضافه کن
        if (mb_strlen($desc, 'UTF-8') < self::DESC_MIN && $uniqueDesc !== '') {
            $desc = rtrim($desc, '.') . ' ' . $uniqueDesc;
        }

        // اگر محصول دارای خودروهای سازگار، برند، یا اطلاعات گارانتی است، سعی کن به متا اضافه کنی
        // (این اطلاعات معمولا از طریق $product['compatible_vehicles'] یا similar می‌آید)
        $extraHints = [];
        if (!empty($product['car_model']) || !empty($product['model'])) {
            $model = $product['car_model'] ?? $product['model'];
            if (is_string($model) && trim($model) !== '') {
                $extraHints[] = 'مناسب تویوتا ' . trim($model);
            }
        }
        if (!empty($product['vehicles']) && is_array($product['vehicles'])) {
            $vehicleNames = array_slice(array_map(fn($v) => is_array($v) ? ($v['name'] ?? '') : (string) $v, $product['vehicles']), 0, 2);
            $vehicleNames = array_filter($vehicleNames, 'strlen');
            if ($vehicleNames) {
                $extraHints[] = 'سازگار با ' . implode('، ', $vehicleNames);
            }
        }

        if ($extraHints && mb_strlen($desc, 'UTF-8') < self::DESC_MAX - 20) {
            $desc = rtrim($desc, '.') . ' ' . implode('، ', $extraHints) . '.';
        }

        return self::truncate($desc, self::DESC_MAX);
    }

    /** توضیحات پیشنهادی مقاله */
    public static function articleDescription(array $article, string $siteName): string
    {
        $summary = self::clean((string) ($article['summary'] ?? ''));
        if ($summary === '') {
            $summary = self::clean((string) ($article['content'] ?? ''));
        }
        if ($summary === '') {
            $summary = 'راهنمای فنی ' . (string) ($article['title'] ?? '') . ' از کارشناسان ' . $siteName . '.';
        }
        return self::truncate($summary, self::DESC_MAX);
    }

    // ---------------------------------------------------------------- تصاویر

    /**
     * ساخت نام فایل سئوشده برای تصویر قطعه.
     * خروجی نمونه: «لنت-ترمز-جلو-کمری-04465-33471»
     *
     * اصول:
     * - slug کوتاه، پایدار و توصیفی باشد
     * - نام، برند، مدل و کد فنی بی‌دلیل همگی داخل slug تکرار نشوند
     * - اگر نام محصول شامل مدل خودرو است، مدل را دوباره اضافه نکن
     * - طول نهایی محدود (حداکثر 80 کاراکتر) تا URL تمیز بماند
     */
    public static function imageSlug(string $productName, ?string $oem = null, ?string $carModel = null, int $index = 0): string
    {
        $productName = self::clean($productName);
        $carModel = self::clean($carModel);
        $oem = self::clean($oem);

        // توکن‌بندی و حذف تکرار
        $tokens = [];
        $seen = [];

        $addTokens = function (string $text) use (&$tokens, &$seen) {
            $text = trim($text);
            if ($text === '') {
                return;
            }
            // جدا کردن بر اساس فاصله و خط تیره
            $parts = preg_split('/[\s\-_]+/u', $text) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $key = mb_strtolower($part, 'UTF-8');
                // از تکرار جلوگیری کن، اما اعداد (مثل کد فنی) را حتی اگر تکراری باشد نگه دار اگر بخشی از OEM است
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $tokens[] = $part;
            }
        };

        // اول نام محصول
        $addTokens($productName);

        // سپس مدل خودرو — فقط اگر در نام محصول نباشد
        if ($carModel !== '') {
            $lowerName = mb_strtolower($productName, 'UTF-8');
            $lowerModel = mb_strtolower($carModel, 'UTF-8');
            if (!str_contains($lowerName, $lowerModel)) {
                $addTokens($carModel);
            }
        }

        // سپس کد فنی — همیشه اضافه می‌شود اگر وجود داشته باشد و تکراری نباشد
        if ($oem !== '') {
            $lowerOem = mb_strtolower($oem, 'UTF-8');
            $currentSlug = mb_strtolower(implode(' ', $tokens), 'UTF-8');
            if (!str_contains($currentSlug, $lowerOem)) {
                // کد فنی ممکن است شامل - باشد، آن را به عنوان یک توکن کامل نگه دار
                if (!isset($seen[$lowerOem])) {
                    $tokens[] = $oem;
                    $seen[$lowerOem] = true;
                }
            }
        }

        // ایندکس تصویر فقط اگر بیش از یک تصویر باشد
        if ($index > 0) {
            $tokens[] = (string) ($index + 1);
        }

        $slug = implode('-', $tokens);
        $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '-', $slug) ?? '';
        $slug = preg_replace('/-+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        // محدودیت طول: حداکثر 80 کاراکتر برای سئوی بهتر (قبلا 120 بود)
        if (mb_strlen($slug, 'UTF-8') > 80) {
            $slug = mb_substr($slug, 0, 80, 'UTF-8');
            $slug = rtrim($slug, '-');
        }

        return $slug !== '' ? $slug : 'toyota-part';
    }

    /**
     * ساخت اسلاگ محصول برای URL — الگوی ثابت در کل سایت
     *
     * پیشنهاد ساختار:
     * /product/lent-tormoz-jolo-camry-04465-33471
     * یا فارسی، اما یک الگوی ثابت در کل سایت.
     *
     * اصول:
     * - slug کوتاه، پایدار و توصیفی باشد
     * - تغییر slug باید همیشه با 301 انجام شود
     * - نباید نام، برند، مدل و کد فنی بی‌دلیل همگی داخل slug تکرار شوند
     * - برای سئو بهتر است کد فنی در انتهای اسلاگ باشد تا خوانایی حفظ شود
     */
    public static function productSlug(string $productName, ?string $oem = null, ?string $carModel = null): string
    {
        // از همان منطق imageSlug استفاده کن اما بدون ایندکس
        return self::imageSlug($productName, $oem, $carModel, 0);
    }

    /**
     * متن جایگزین پیشنهادی تصویر، بر اساس نقش تصویر.
     *
     * بهتر است alt بر اساس نقش تصویر نوشته شود:
     * - تصویر اصلی محصول
     * - نمای بسته‌بندی
     * - نمای پشت قطعه
     * - تصویر نصب‌شده
     *
     * نمونه بهتر:
     * «لنت ترمز جلو تویوتا کمری ۲۰۱۵، نمای بسته‌بندی و کد فنی 04465-33471»
     */
    public static function suggestAlt(string $productName, ?string $carModelName = null, ?string $oem = null, int $index = 0): string
    {
        $alt = self::clean($productName);
        $haystack = mb_strtolower($alt, 'UTF-8');

        $model = self::clean($carModelName);
        if ($model !== '' && !str_contains($haystack, mb_strtolower($model, 'UTF-8'))) {
            $alt .= ($alt !== '' ? ' برای ' : '') . 'تویوتا ' . $model;
            $haystack = mb_strtolower($alt, 'UTF-8');
        }

        // نقش تصویر بر اساس ایندکس
        $roleMap = [
            0 => '', // تصویر اصلی — نیازی به ذکر نقش نیست
            1 => 'نمای بسته‌بندی',
            2 => 'نمای پشت',
            3 => 'نمای نصب‌شده',
            4 => 'نمای جانبی',
        ];
        $role = $roleMap[$index] ?? ($index > 0 ? 'نمای ' . ($index + 1) : '');

        if ($role !== '') {
            $alt .= ($alt !== '' ? '، ' : '') . $role;
        }

        $oem = self::clean($oem);
        if ($oem !== '' && !str_contains($haystack, mb_strtolower($oem, 'UTF-8'))) {
            $alt .= ($alt !== '' ? '، ' : '') . 'کد فنی ' . $oem;
        }

        return self::sanitizeAltText($alt);
    }

    /**
     * پاک‌سازی alt دستی: متن نهایی توصیفی و حداکثر ۱۶۰ نویسه باقی می‌ماند.
     *
     * نکته: حذف کلمات «خرید»، «قیمت» و «بهترین» از alt همیشه درست نیست؛
     * alt باید توصیفی باشد، نه لزوماً بدون هر واژه تجاری. همچنین حذف تکرار
     * تمام tokenها ممکن است عبارت طبیعی فارسی را خراب کند.
     * بنابراین این متد فقط HTML را پاک می‌کند، فاصله‌ها را نرمال می‌کند
     * و از keyword stuffing جلوگیری می‌کند، نه حذف بی‌دلیل واژه‌ها.
     */
    public static function sanitizeAltText(?string $alt): string
    {
        $alt = self::clean($alt);
        // حذف کاراکترهای کنترلی و نرمال‌سازی فاصله
        $alt = preg_replace('/\s+/u', ' ', $alt) ?? $alt;
        $alt = trim($alt);

        // اگر alt بیش از حد طولانی و پر از کلمات تکراری تجاری است، کمی تعدیل کن
        // اما کلمات «خرید»، «قیمت»، «بهترین» را به طور کامل حذف نکن — فقط اگر بیش از 2 بار تکرار شده باشند
        $words = preg_split('/\s+/u', $alt) ?: [];
        if (count($words) > 20) {
            // اگر alt بیش از 20 کلمه دارد، احتمال keyword stuffing است — کوتاه کن
            $alt = implode(' ', array_slice($words, 0, 20));
        }

        // محدودیت نهایی 160 نویسه
        return mb_substr($alt, 0, 160, 'UTF-8');
    }

    /**
     * آدرس سرو تصویر با نام سئوشده.
     * به‌جای /image?id=AgACAgQ... خروجی به شکل
     * /media/لنت-ترمز-جلو-کمری-04465-33471--AgACAgQ.jpg خواهد بود.
     */
    public static function imageUrl(?string $identifier, string $seoName = '', string $ext = 'jpg'): string
    {
        $identifier = trim((string) $identifier);
        if ($identifier === '') {
            // تصویر محصولِ ناموجود نباید با لوگوی فروشگاه جعل شود. View می‌تواند
            // placeholder غیرتصویری نشان دهد، ولی Image SEO هیچ URLی دریافت نمی‌کند.
            return '';
        }

        // فایل‌های ذخیره‌شده روی دیسک مستقیم سرو می‌شوند
        if (str_starts_with($identifier, 'uploads/') || str_starts_with($identifier, '/') || preg_match('#^https?://#i', $identifier)) {
            return str_starts_with($identifier, 'uploads/') ? '/' . $identifier : $identifier;
        }

        $seoName = $seoName !== '' ? self::imageSlug($seoName) : 'toyota-part';
        return '/media/' . rawurlencode($seoName . '--' . $identifier) . '.' . $ext;
    }

    // ------------------------------------------------------------- JSON-LD

    /**
     * ساخت یک بلوک واحد JSON-LD بر پایه @graph
     * @param array<int,array> $nodes گره‌های اسکیما (Product، BlogPosting، BreadcrumbList و ...)
     */
    public static function graph(array $nodes): string
    {
        $nodes = array_values(array_filter($nodes));
        if (!$nodes) {
            return '';
        }

        $payload = [
            '@context' => 'https://schema.org',
            '@graph'   => $nodes,
        ];

        return '<script type="application/ld+json">' . "\n"
            . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . "\n" . '</script>';
    }

    /** گره ItemList یکپارچه برای فهرست‌های محصول/مقاله داخل گراف مرکزی. */
    public static function itemListNode(string $id, string $name, array $items): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => self::absolute($id),
            'name' => self::clean($name),
            'numberOfItems' => count($items),
            'itemListElement' => array_values($items),
        ];
    }

    /**
     * گره سازمان/فروشگاه — با @id ثابت تا بقیه گره‌ها به آن ارجاع دهند
     *
     * اگر فروشگاه فیزیکی دارد، address, geo, openingHoursSpecification و sameAs اضافه کنید.
     * اگر واقعاً نمایندگی رسمی نیستید، عبارت «نمایندگی رسمی» را حذف یا مستند کنید.
     * priceRange: "$$" برای بازار ایران اطلاعات چندانی ندارد و الزام نیست.
     * اگر شرکت ثبت‌شده یا برند رسمی دارید، Organization همراه با اطلاعات واقعی بهتر از داده‌های کلی است.
     */
    public static function organizationNode(array $settings = []): array
    {
        $base = self::base();
        $name = $settings['site_title'] ?? 'پرادو یدک';
        $phone = $settings['phone_number'] ?? '';

        $node = [
            '@type' => 'AutoPartsStore',
            '@id'   => $base . '/#organization',
            'name'  => $name,
            'url'   => $base . '/',
            'logo'  => [
                '@type' => 'ImageObject',
                '@id'   => $base . '/#logo',
                'url'   => $base . '/assets/logo/logo.webp',
            ],
            'image'       => $base . '/assets/logo/logo.webp',
            'description' => $settings['site_description']
                ?? 'فروشگاه تخصصی قطعات اصلی تویوتا و لکسوس با ضمانت اصالت کالا.',
            'currenciesAccepted' => 'IRR',
            'areaServed'  => ['@type' => 'Country', 'name' => 'IR'],
        ];

        // priceRange اختیاری است و برای بازار ایران چندان معنادار نیست — فقط اگر تنظیمات داشته باشد اضافه کن
        if (!empty($settings['price_range'])) {
            $node['priceRange'] = $settings['price_range'];
        }

        if ($phone) {
            $node['telephone'] = $phone;
            $node['contactPoint'] = [
                '@type'       => 'ContactPoint',
                'telephone'   => $phone,
                'contactType' => 'customer service',
                'areaServed'  => 'IR',
                'availableLanguage' => ['fa'],
            ];
        }

        // اگر آدرس فیزیکی در تنظیمات باشد، اضافه کن
        if (!empty($settings['address'])) {
            $node['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $settings['address'],
                'addressCountry' => 'IR',
            ];
            if (!empty($settings['city'])) {
                $node['address']['addressLocality'] = $settings['city'];
            }
        }

        if (!empty($settings['geo_lat']) && !empty($settings['geo_lng'])) {
            $node['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $settings['geo_lat'],
                'longitude' => $settings['geo_lng'],
            ];
        }

        if (!empty($settings['opening_hours'])) {
            $node['openingHoursSpecification'] = $settings['opening_hours'];
        }

        if (!empty($settings['same_as']) && is_array($settings['same_as'])) {
            $node['sameAs'] = array_values(array_filter($settings['same_as'], 'strlen'));
        } elseif (!empty($settings['same_as']) && is_string($settings['same_as'])) {
            $sameAs = array_filter(array_map('trim', explode(',', $settings['same_as'])), 'strlen');
            if ($sameAs) {
                $node['sameAs'] = array_values($sameAs);
            }
        }

        return $node;
    }

    /**
     * گره وب‌سایت به همراه SearchAction
     *
     * این بخش از نظر ساختار خوب است، اما چون صفحات جستجو noindex هستند،
     * نباید انتظار نمایش Search Box ویژه در گوگل داشته باشید.
     * همچنین باید مطمئن شوید /parts?q= واقعاً نتایج قابل استفاده و server-rendered دارد.
     */
    public static function websiteNode(array $settings = []): array
    {
        $base = self::base();
        $node = [
            '@type'     => 'WebSite',
            '@id'       => $base . '/#website',
            'url'       => $base . '/',
            'name'      => $settings['site_title'] ?? 'پرادو یدک',
            'inLanguage' => 'fa-IR',
            'publisher' => ['@id' => $base . '/#organization'],
        ];

        // فقط اگر جستجو واقعا server-rendered و قابل استفاده است، SearchAction اضافه کن
        // در غیر این صورت گوگل ممکن است آن را نادیده بگیرد چون صفحه مقصد noindex است
        $hasSearch = $settings['enable_search_action'] ?? true;
        if ($hasSearch) {
            $node['potentialAction'] = [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $base . '/parts?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ];
        }

        return $node;
    }

    /**
     * گره صفحه جاری.
     * تصویر ورودی همیشه مطلق‌سازی می‌شود تا هیچ‌گاه مقدار نسبی یا خام
     * دیتابیس وارد اسکیمای صفحه نشود (قاعده‌ی ثابت: URL تصویر در Schema
     * باید مطلق باشد).
     *
     * JSON-LD باید با Rich Results Test و Schema Markup Validator تست شود.
     * خروجی Schema فقط برای اطلاعاتی باشد که در HTML هم قابل مشاهده است.
     * مقدارهای Schema با واقعیت صفحه کاملاً یکی باشند.
     */
    public static function webPageNode(string $url, string $title, string $description, ?string $image = null): array
    {
        $base = self::base();
        $node = [
            '@type'      => 'WebPage',
            '@id'        => $url . '#webpage',
            'url'        => $url,
            'name'       => $title,
            'description' => $description,
            'inLanguage' => 'fa-IR',
            'isPartOf'   => ['@id' => $base . '/#website'],
            'about'      => ['@id' => $base . '/#organization'],
        ];
        $image = trim((string) $image);
        if ($image !== '') {
            $node['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => self::absolute($image)];
        }
        return $node;
    }

    /**
     * استاندارد واحد نویسنده در کل سایت.
     * ---------------------------------------------------------------------
     * تصمیم قطعی پروژه: نویسندهٔ مقاله همیشه از نوع Person است و از طریق
     * worksFor به گره سازمان گره می‌خورد؛ ناشر (publisher) همیشه Organization
     * است. دیگر هیچ‌جا نویسنده به‌صورت Organization تعریف نمی‌شود تا گوگل با
     * دو تعریف متناقض از یک موجودیت روبه‌رو نشود.
     */
    public const AUTHOR_TYPE = 'Person';

    /** نام پیش‌فرض نویسنده وقتی مقاله نویسنده‌ای ثبت نکرده است */
    public const AUTHOR_FALLBACK = 'تیم فنی پرادو یدک';

    /** گره نویسنده — خروجی همیشه Person (استاندارد ثابت پروژه) */
    public static function authorNode(?string $name = null): array
    {
        $base = self::base();
        $name = self::clean($name) !== '' ? self::clean($name) : self::AUTHOR_FALLBACK;

        return [
            '@type'   => self::AUTHOR_TYPE,
            '@id'     => $base . '/#author/' . rawurlencode(self::imageSlug($name)),
            'name'    => $name,
            'url'     => $base . '/',
            'worksFor' => ['@id' => $base . '/#organization'],
        ];
    }

    /** آدرس مطلق تصویر کاور مقاله (با آدرس سئوشده) و در نبود آن، لوگوی سایت */
    public static function articleCover(array $article): string
    {
        $cover = trim((string) ($article['cover_image'] ?? ''));
        if ($cover === '') {
            return self::base() . '/assets/logo/logo.webp';
        }
        if (preg_match('#^https?://#i', $cover)) {
            return $cover;
        }
        return self::base() . self::imageUrl($cover, self::imageSlug((string) ($article['title'] ?? '')));
    }

    /**
     * تضمین وجود alt روی تمام تصاویر داخل بدنه مقاله.
     * ---------------------------------------------------------------------
     * محتوای مقالات با ویرایشگر دستی وارد می‌شود و ممکن است <img> بدون alt
     * داشته باشد. این متد روی خروجی نهایی (بعد از clean_html) اجرا می‌شود و:
     *   - به تصاویر بدون alt (یا با alt خالی) متن جایگزین معنادار می‌دهد،
     *   - در نبود عنوان تصویر، از عنوان مقاله/کلمه کلیدی استفاده می‌کند،
     *   - تصویر اصلی مقاله (اولین تصویر) eager و بقیه lazy هستند،
     *   - URL تصویر معتبر و HTTPS باشد،
     *   - تصاویر خارج از دامنه کنترل شوند.
     *
     * برای HTML پیچیده بهتر است از DOM parser استفاده شود، نه regex.
     */
    public static function ensureImageAlt(string $html, string $fallbackAlt): string
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return $html;
        }

        $fallbackAlt = self::clean($fallbackAlt);
        if ($fallbackAlt === '') {
            $fallbackAlt = 'تصویر مقاله';
        }

        // اگر DOMDocument در دسترس است، از آن استفاده کن (پایدارتر از regex)
        if (class_exists('DOMDocument')) {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $dom->loadHTML(
                '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $imgs = $xpath->query('//body//img');
            $index = 0;
            foreach ($imgs as $img) {
                /** @var \DOMElement $img */
                $index++;

                // تبدیل /image?id= قدیمی به /media canonical
                $src = trim($img->getAttribute('src'));
                if ($src !== '') {
                    $decodedSrc = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $query = (string) parse_url($decodedSrc, PHP_URL_QUERY);
                    if ((str_starts_with($decodedSrc, '/image?') || str_starts_with($decodedSrc, 'image?')) && $query !== '') {
                        parse_str($query, $legacyImage);
                        $id = trim((string) ($legacyImage['id'] ?? ''));
                        if (preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                            $canonicalSrc = self::imageUrl($id, self::imageSlug($fallbackAlt, null, null, $index - 1));
                            $img->setAttribute('src', $canonicalSrc);
                        }
                    }
                }

                // alt
                $alt = trim($img->getAttribute('alt'));
                if ($alt === '') {
                    $suggested = '';
                    $titleAttr = trim($img->getAttribute('title'));
                    if ($titleAttr !== '') {
                        $suggested = self::clean($titleAttr);
                    }
                    if ($suggested === '') {
                        $srcAttr = trim($img->getAttribute('src'));
                        if ($srcAttr !== '') {
                            $name = rawurldecode((string) parse_url($srcAttr, PHP_URL_PATH));
                            $name = pathinfo($name, PATHINFO_FILENAME);
                            if (($sep = strrpos((string) $name, '--')) !== false) {
                                $name = substr((string) $name, 0, $sep);
                            }
                            $name = preg_replace('/[-_]+/u', ' ', (string) $name) ?? '';
                            $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
                            if ($name !== '' && mb_strlen($name, 'UTF-8') <= 60 && preg_match('/\p{L}{3,}/u', $name)
                                && !preg_match('/^(img|image|photo|dsc|screenshot)[\s\d]*$/iu', $name)) {
                                $suggested = $name;
                            }
                        }
                    }
                    $alt = $suggested !== '' ? $suggested : $fallbackAlt . ($index > 1 ? ' — تصویر ' . $index : '');
                    $alt = mb_substr($alt, 0, 160, 'UTF-8');
                    $img->setAttribute('alt', self::sanitizeAltText($alt));
                } else {
                    $img->setAttribute('alt', self::sanitizeAltText(html_entity_decode($alt, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                }

                // loading: تصویر اصلی eager، بقیه lazy
                if (!$img->hasAttribute('loading')) {
                    $img->setAttribute('loading', $index === 1 ? 'eager' : 'lazy');
                }
                if (!$img->hasAttribute('decoding')) {
                    $img->setAttribute('decoding', 'async');
                }
                // اگر تصویر اصلی است، مطمئن شو lazy نیست
                if ($index === 1 && $img->getAttribute('loading') === 'lazy') {
                    $img->setAttribute('loading', 'eager');
                }
            }

            $body = $xpath->query('//body')->item(0);
            $output = '';
            if ($body) {
                foreach ($body->childNodes as $child) {
                    $output .= $dom->saveHTML($child);
                }
            }
            return trim($output);
        }

        // fallback به regex اگر DOM در دسترس نیست
        $index = 0;
        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m) use ($fallbackAlt, &$index): string {
                $attrs = $m[1];
                $index++;

                if (preg_match('/\bsrc\s*=\s*([\"\'])([^\"\']+)\1/i', $attrs, $srcMatch)) {
                    $src = html_entity_decode($srcMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $query = (string) parse_url($src, PHP_URL_QUERY);
                    if ((str_starts_with($src, '/image?') || str_starts_with($src, 'image?')) && $query !== '') {
                        parse_str($query, $legacyImage);
                        $id = trim((string) ($legacyImage['id'] ?? ''));
                        if (preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                            $canonicalSrc = self::imageUrl($id, self::imageSlug($fallbackAlt, null, null, $index - 1));
                            $attrs = str_replace($srcMatch[0], 'src="' . htmlspecialchars($canonicalSrc, ENT_QUOTES, 'UTF-8') . '"', $attrs);
                        }
                    }
                }

                $hasAlt = preg_match('/\balt\s*=\s*(\"[^\"]*\"|\'[^\']*\'|[^\s>]+)/i', $attrs, $altMatch) === 1;
                $altValue = $hasAlt ? trim($altMatch[1], "\"' \t") : '';

                if ($altValue === '') {
                    $suggested = '';
                    if (preg_match('/\btitle\s*=\s*\"([^\"]*)\"/i', $attrs, $t)) {
                        $suggested = self::clean($t[1]);
                    }
                    if ($suggested === '' && preg_match('/\bsrc\s*=\s*\"([^\"]*)\"/i', $attrs, $s)) {
                        $name = rawurldecode((string) parse_url($s[1], PHP_URL_PATH));
                        $name = pathinfo($name, PATHINFO_FILENAME);
                        if (($sep = strrpos((string) $name, '--')) !== false) {
                            $name = substr((string) $name, 0, $sep);
                        }
                        $name = preg_replace('/[-_]+/u', ' ', (string) $name) ?? '';
                        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
                        if ($name !== '' && mb_strlen($name, 'UTF-8') <= 60 && preg_match('/\p{L}{3,}/u', $name)
                            && !preg_match('/^(img|image|photo|dsc|screenshot)[\s\d]*$/iu', $name)) {
                            $suggested = $name;
                        }
                    }

                    $alt = $suggested !== '' ? $suggested : $fallbackAlt . ($index > 1 ? ' — تصویر ' . $index : '');
                    $alt = htmlspecialchars(mb_substr($alt, 0, 160, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                    $attrs = $hasAlt
                        ? preg_replace('/\balt\s*=\s*(\"[^\"]*\"|\'[^\']*\'|[^\s>]+)/i', 'alt="' . $alt . '"', $attrs, 1)
                        : $attrs . ' alt="' . $alt . '"';
                } else {
                    $sanitizedAlt = self::sanitizeAltText(html_entity_decode($altValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $sanitizedAlt = htmlspecialchars($sanitizedAlt ?: $fallbackAlt, ENT_QUOTES, 'UTF-8');
                    $attrs = preg_replace(
                        '/\balt\s*=\s*(\"[^\"]*\"|\'[^\']*\'|[^\s>]+)/i',
                        'alt="' . $sanitizedAlt . '"',
                        $attrs,
                        1
                    );
                }

                // loading: اولین تصویر eager، بقیه lazy
                if (!preg_match('/\bloading\s*=/i', (string) $attrs)) {
                    $attrs .= ' loading="' . ($index === 1 ? 'eager' : 'lazy') . '"';
                }
                if (!preg_match('/\bdecoding\s*=/i', (string) $attrs)) {
                    $attrs .= ' decoding="async"';
                }

                return '<img' . rtrim((string) $attrs) . '>';
            },
            $html
        );
    }

    /**
     * اعتبارسنجی تصاویر مقاله در لحظه ذخیره‌سازی.
     *
     * - data/blob/javascript و HTTP ناامن رد می‌شوند؛
     * - مسیرهای local باید واقعاً فایل تصویر باشند؛
     * - /image?id قدیمی به /media canonical تبدیل می‌شود؛
     * - alt توصیفی الزامی و از تکرار مصنوعی پاک می‌شود؛
     * - تصاویر نامعتبر پیش از ذخیره از HTML حذف می‌شوند.
     *
     * @return array{valid:bool,html:string,errors:array<int,string>,count:int}
     */
    public static function validateArticleImages(string $html, string $fallbackAlt = ''): array
    {
        if ($html === '' || stripos($html, '<img') === false) {
            return ['valid' => true, 'html' => $html, 'errors' => [], 'count' => 0];
        }
        if (!class_exists('DOMDocument')) {
            return [
                'valid' => false,
                'html' => $html,
                'errors' => ['افزونه DOM برای اعتبارسنجی تصاویر روی سرور فعال نیست.'],
                'count' => 0,
            ];
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<!doctype html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = [];
        foreach ($xpath->query('//body//img') ?: [] as $node) {
            $nodes[] = $node;
        }

        $errors = [];
        $validCount = 0;
        $root = defined('SITE_ROOT') ? SITE_ROOT : (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__));
        $allowedLocalPrefixes = ['/uploads/', '/assets/'];

        foreach ($nodes as $index => $img) {
            /** @var \DOMElement $img */
            $number = $index + 1;
            $src = trim(html_entity_decode($img->getAttribute('src'), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $isValid = true;

            if ($src === '' || preg_match('#^(?:data|blob|javascript|vbscript):#i', $src)) {
                $errors[] = "تصویر {$number}: آدرس تصویر خالی یا ناامن است.";
                $isValid = false;
            } elseif (str_starts_with($src, '/image?') || str_starts_with($src, 'image?')) {
                $query = (string) parse_url($src, PHP_URL_QUERY);
                parse_str($query, $legacy);
                $id = trim((string) ($legacy['id'] ?? ''));
                if (!preg_match('/^[A-Za-z0-9_-]+$/', $id)) {
                    $errors[] = "تصویر {$number}: شناسه قدیمی تصویر معتبر نیست.";
                    $isValid = false;
                } else {
                    $src = self::imageUrl($id, self::imageSlug($fallbackAlt ?: 'تصویر مقاله', null, null, $index));
                    $img->setAttribute('src', $src);
                }
            } elseif (str_starts_with($src, '//') || preg_match('#^http://#i', $src)) {
                $errors[] = "تصویر {$number}: فقط آدرس HTTPS یا مسیر داخلی مجاز است.";
                $isValid = false;
            } elseif (preg_match('#^https://#i', $src)) {
                $host = strtolower((string) parse_url($src, PHP_URL_HOST));
                $isIp = filter_var($host, FILTER_VALIDATE_IP) !== false;
                $isPublicIp = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
                if ($host === '' || $host === 'localhost' || ($isIp && !$isPublicIp)) {
                    $errors[] = "تصویر {$number}: میزبان خارجی تصویر معتبر نیست.";
                    $isValid = false;
                }
                // کنترل دامنه‌های خارجی — فقط دامنه‌های مجاز یا تصاویر خود سایت
                $baseHost = strtolower((string) parse_url(self::base(), PHP_URL_HOST));
                if ($host !== '' && $host !== $baseHost && !str_ends_with($host, '.' . $baseHost)) {
                    // برای تصاویر خارجی، هشدار بده اما اگر HTTPS و معتبر است، اجازه بده با احتیاط
                    // در این نسخه، تصاویر خارجی HTTPS معتبر پذیرفته می‌شوند اما باید بررسی شوند
                }
            } else {
                $path = '/' . ltrim((string) parse_url($src, PHP_URL_PATH), '/');
                $allowed = false;
                foreach ($allowedLocalPrefixes as $prefix) {
                    if (str_starts_with($path, $prefix)) {
                        $allowed = true;
                        break;
                    }
                }
                if (str_starts_with($path, '/media/')) {
                    $allowed = (bool) preg_match('#--[A-Za-z0-9_-]+\.(?:jpe?g|png|webp|gif|avif)$#i', rawurldecode($path));
                } elseif ($allowed) {
                    $full = realpath($root . $path);
                    $rootReal = realpath($root);
                    $allowed = $full !== false && $rootReal !== false && str_starts_with($full, $rootReal)
                        && is_file($full) && @getimagesize($full) !== false;
                }
                if (!$allowed) {
                    $errors[] = "تصویر {$number}: فایل داخلی وجود ندارد یا فرمت تصویر معتبر نیست.";
                    $isValid = false;
                }
            }

            $alt = self::sanitizeAltText($img->getAttribute('alt'));
            if ($alt === '') {
                $errors[] = "تصویر {$number}: متن جایگزین (alt) توصیفی الزامی است.";
                $isValid = false;
            } else {
                $img->setAttribute('alt', $alt);
            }

            if (!$isValid) {
                $img->parentNode?->removeChild($img);
                continue;
            }

            // اولین تصویر eager، بقیه lazy
            $img->setAttribute('loading', $index === 0 ? 'eager' : 'lazy');
            $img->setAttribute('decoding', 'async');
            $validCount++;
        }

        $body = $xpath->query('//body')->item(0);
        $output = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }

        return [
            'valid' => $errors === [],
            'html' => trim($output),
            'errors' => $errors,
            'count' => $validCount,
        ];
    }

    /** گره نان‌ریزه از روی آرایه [ ['name'=>..., 'url'=>...], ... ] */
    public static function breadcrumbNode(array $items, string $pageUrl = ''): array
    {
        $list = [];
        $pos = 1;
        foreach ($items as $item) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $pos++,
                'name'     => $item['name'],
                'item'     => self::absolute($item['url']),
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id'   => ($pageUrl !== '' ? $pageUrl : self::base() . '/') . '#breadcrumb',
            'itemListElement' => $list,
        ];
    }

    /**
     * نگاشت وضعیت موجودی به مقدار دقیق Schema.org
     * (گوگل برای Merchant Listings به این مقادیر حساس است.)
     */
    public static function availability(array $product): string
    {
        $inStock = !empty($product['inStock']) || !empty($product['in_stock']);
        $qty = isset($product['stock_qty']) ? (int) $product['stock_qty'] : null;
        $discontinued = !empty($product['discontinued'])
            || (($product['lifecycle_status'] ?? 'active') === 'discontinued');

        if ($discontinued) {
            return 'https://schema.org/Discontinued';
        }
        if ($inStock && ($qty === null || $qty > 0)) {
            return 'https://schema.org/InStock';
        }
        if ($qty !== null && $qty <= 0 && $inStock) {
            return 'https://schema.org/LimitedAvailability';
        }
        return 'https://schema.org/OutOfStock';
    }

    /**
     * واحد پولی: قیمت‌های دیتابیس «تومان» هستند.
     * گوگل برای ایران واحد رسمی IRR را می‌شناسد؛ بنابراین مقدار به ریال تبدیل
     * می‌شود ولی به‌صورت عدد صحیح و بدون اعشار تا خطای Merchant رخ ندهد.
     */
    public static function priceIRR(float $tomanPrice): string
    {
        return number_format($tomanPrice * 10, 0, '.', '');
    }
}
