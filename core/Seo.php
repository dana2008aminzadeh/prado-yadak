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
 */
class Seo
{
    /** بازه‌های استاندارد پیشنهادی گوگل */
    public const TITLE_MIN = 50;
    public const TITLE_MAX = 60;
    public const DESC_MIN  = 120;
    public const DESC_MAX  = 155;

    /** پارامترهایی که در کانونیکال نگه داشته می‌شوند (به همین ترتیب ثابت) */
    public const CANONICAL_PARAMS = ['category', 'model', 'brand', 'page'];

    /** پارامترهایی که همیشه باعث noindex می‌شوند (فیلتر کم‌ارزش/جستجو/مرتب‌سازی) */
    public const NOINDEX_PARAMS = ['q', 'sort', 'maxPrice', 'minPrice', 'inStock', 'view', 'utm_source', 'utm_medium', 'utm_campaign'];

    // ---------------------------------------------------------------- آدرس‌ها

    /** دامنه‌ی ثابت و واقعی پروژه — تنها fallback مجاز وقتی SITE_URL تعریف نشده. */
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
     */
    public static function base(): string
    {
        $raw = defined('SITE_URL') ? trim((string) SITE_URL) : '';
        if ($raw === '') {
            $raw = self::FALLBACK_HOST;
        }

        if (preg_match('#^https?://#i', $raw)) {
            return rtrim($raw, '/');
        }

        return rtrim('https://' . ltrim($raw, '/'), '/');
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
     */
    public static function articleUrl(?string $slug, bool $absolute = false): string
    {
        $path = '/blog/' . rawurlencode(trim((string) $slug));
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس یک محصول (همان قاعده rawurlencode) */
    public static function productUrl(?string $slug, bool $absolute = false): string
    {
        $path = '/product/' . rawurlencode(trim((string) $slug));
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس تمیز لندینگ یک دسته‌بندی کاتالوگ. */
    public static function categoryUrl(?string $slug, bool $absolute = false): string
    {
        $path = '/parts/category/' . rawurlencode(trim((string) $slug));
        return $absolute ? self::absolute($path) : $path;
    }

    /** آدرس تمیز لندینگ قطعات یک مدل خودرو. */
    public static function modelUrl(?string $slug, bool $absolute = false): string
    {
        $path = '/parts/model/' . rawurlencode(trim((string) $slug));
        return $absolute ? self::absolute($path) : $path;
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
            && (str_starts_with($uri, '/blog/') || $uri === '/blog-detail')) {
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

            // مقادیر چندتایی (مثل category=a,b) نیز مرتب می‌شوند
            if (str_contains($value, ',')) {
                $parts = array_values(array_unique(array_filter(array_map('trim', explode(',', $value)), 'strlen')));
                sort($parts, SORT_STRING);
                $value = implode(',', $parts);
            }

            if ($key === 'page') {
                $page = (int) $value;
                if ($page <= 1) {
                    continue;
                }
                $value = (string) $page;
            }

            $clean[$key] = $value;
        }

        return $clean ? http_build_query($clean, '', '&', PHP_QUERY_RFC3986) : '';
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
     */
    public static function catalogRobots(array $get): string
    {
        foreach (self::NOINDEX_PARAMS as $param) {
            if (isset($get[$param]) && trim((string) (is_array($get[$param]) ? implode(',', $get[$param]) : $get[$param])) !== '') {
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
        $parts = [trim((string) ($product['name'] ?? ''))];

        $oem = trim((string) ($product['oem'] ?? $product['oem_code'] ?? ''));
        if ($oem !== '') {
            $parts[] = $oem;
        }

        $base = implode(' ', $parts);
        $full = $base . ' | ' . $siteName;

        if (mb_strlen($full, 'UTF-8') > self::TITLE_MAX) {
            $room = self::TITLE_MAX - mb_strlen(' | ' . $siteName, 'UTF-8');
            $base = self::truncate($base, max(20, $room));
            $full = $base . ' | ' . $siteName;
        }
        return $full;
    }

    /** توضیحات پیشنهادی محصول: پیام فروش + کد فنی + وضعیت موجودی */
    public static function productDescription(array $product, string $siteName): string
    {
        $name  = trim((string) ($product['name'] ?? ''));
        $oem   = trim((string) ($product['oem'] ?? $product['oem_code'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));
        $stock = !empty($product['inStock']) || !empty($product['in_stock']);

        $desc = 'خرید ' . $name
            . ($oem !== '' ? ' با کد فنی ' . $oem : '')
            . ($brand !== '' ? ' برند ' . $brand : '')
            . ($stock ? '؛ موجود در انبار' : '؛ استعلام موجودی')
            . ' با ضمانت بازگشت وجه در صورت اثبات عدم اصالت، فاکتور رسمی و ارسال سریع به سراسر کشور از ' . $siteName . '.';

        if (mb_strlen($desc, 'UTF-8') < self::DESC_MIN) {
            $extra = self::clean((string) ($product['desc'] ?? $product['description'] ?? ''));
            if ($extra !== '') {
                $desc = rtrim($desc, '.') . ' ' . $extra;
            }
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
     */
    public static function imageSlug(string $productName, ?string $oem = null, ?string $carModel = null, int $index = 0): string
    {
        $parts = array_filter([
            trim($productName),
            trim((string) $carModel),
            trim((string) $oem),
            $index > 0 ? (string) ($index + 1) : '',
        ], 'strlen');

        $slug = implode('-', $parts);
        $slug = preg_replace('/[^\p{L}\p{N}\-]+/u', '-', $slug) ?? '';
        $slug = preg_replace('/-+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? mb_substr($slug, 0, 120, 'UTF-8') : 'toyota-part';
    }

    /** متن جایگزین پیشنهادی تصویر، بدون تکرار مصنوعی نام مدل/OEM و کلمات کلیدی. */
    public static function suggestAlt(string $productName, ?string $carModelName = null, ?string $oem = null, int $index = 0): string
    {
        $alt = self::clean($productName);
        $haystack = mb_strtolower($alt, 'UTF-8');

        $model = self::clean($carModelName);
        if ($model !== '' && !str_contains($haystack, mb_strtolower($model, 'UTF-8'))) {
            $alt .= ($alt !== '' ? ' برای ' : '') . 'تویوتا ' . $model;
            $haystack = mb_strtolower($alt, 'UTF-8');
        }

        $oem = self::clean($oem);
        if ($oem !== '' && !str_contains($haystack, mb_strtolower($oem, 'UTF-8'))) {
            $alt .= ($alt !== '' ? '، ' : '') . 'کد فنی ' . $oem;
        }
        if ($index > 0) {
            $alt .= '، نمای ' . ($index + 1);
        }
        return self::sanitizeAltText($alt);
    }

    /**
     * پاک‌سازی alt دستی: حذف HTML، عبارت‌های تبلیغاتی و تکرار واژه‌ها؛ متن نهایی
     * توصیفی و حداکثر ۱۶۰ نویسه باقی می‌ماند.
     */
    public static function sanitizeAltText(?string $alt): string
    {
        $alt = self::clean($alt);
        $alt = preg_replace('/\b(خرید|قیمت|ارزان|بهترین|فروش ویژه)\b/u', '', $alt) ?? $alt;
        $tokens = preg_split('/\s+/u', trim($alt)) ?: [];
        $seen = [];
        $clean = [];
        foreach ($tokens as $token) {
            $key = mb_strtolower(trim($token, "،؛,:|"), 'UTF-8');
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = $token;
        }
        $alt = trim(preg_replace('/\s+/u', ' ', implode(' ', $clean)) ?? '');
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

    /** گره سازمان/فروشگاه — با @id ثابت تا بقیه گره‌ها به آن ارجاع دهند */
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
            'priceRange'  => '$$',
            'currenciesAccepted' => 'IRR',
            'areaServed'  => ['@type' => 'Country', 'name' => 'IR'],
        ];

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

        return $node;
    }

    /** گره وب‌سایت به همراه SearchAction */
    public static function websiteNode(array $settings = []): array
    {
        $base = self::base();
        return [
            '@type'     => 'WebSite',
            '@id'       => $base . '/#website',
            'url'       => $base . '/',
            'name'      => $settings['site_title'] ?? 'پرادو یدک',
            'inLanguage' => 'fa-IR',
            'publisher' => ['@id' => $base . '/#organization'],
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $base . '/parts?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * گره صفحه جاری.
     * تصویر ورودی همیشه مطلق‌سازی می‌شود تا هیچ‌گاه مقدار نسبی یا خام
     * دیتابیس وارد اسکیمای صفحه نشود (قاعده‌ی ثابت: URL تصویر در Schema
     * باید مطلق باشد).
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
     *   - در صورت نبود، loading=lazy و decoding=async اضافه می‌کند.
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
        $index = 0;

        return (string) preg_replace_callback(
            '/<img\b([^>]*)>/i',
            static function (array $m) use ($fallbackAlt, &$index): string {
                $attrs = $m[1];
                $index++;

                // محتوای قدیمی بدون نیاز به ذخیره مجدد نیز از /image به URL یکتای /media منتقل می‌شود.
                if (preg_match('/\bsrc\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $srcMatch)) {
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

                $hasAlt = preg_match('/\balt\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', $attrs, $altMatch) === 1;
                $altValue = $hasAlt ? trim($altMatch[1], "\"' \t") : '';

                if ($altValue === '') {
                    // عنوان یا نام فایل تصویر، معنادارترین جایگزین ممکن است
                    $suggested = '';
                    if (preg_match('/\btitle\s*=\s*"([^"]*)"/i', $attrs, $t)) {
                        $suggested = self::clean($t[1]);
                    }
                    if ($suggested === '' && preg_match('/\bsrc\s*=\s*"([^"]*)"/i', $attrs, $s)) {
                        $name = rawurldecode((string) parse_url($s[1], PHP_URL_PATH));
                        $name = pathinfo($name, PATHINFO_FILENAME);
                        // آدرس‌های سئوشده شکل «نام-قطعه--شناسه» دارند؛ شناسه فنی حذف می‌شود
                        if (($sep = strrpos((string) $name, '--')) !== false) {
                            $name = substr((string) $name, 0, $sep);
                        }
                        $name = preg_replace('/[-_]+/u', ' ', (string) $name) ?? '';
                        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
                        // نام‌های بی‌معنی مثل هش تلگرام یا IMG_1234 کنار گذاشته می‌شوند
                        if ($name !== '' && mb_strlen($name, 'UTF-8') <= 60 && preg_match('/\p{L}{3,}/u', $name)
                            && !preg_match('/^(img|image|photo|dsc|screenshot)[\s\d]*$/iu', $name)) {
                            $suggested = $name;
                        }
                    }

                    $alt = $suggested !== '' ? $suggested : $fallbackAlt . ($index > 1 ? ' — تصویر ' . $index : '');
                    $alt = htmlspecialchars(mb_substr($alt, 0, 160, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                    $attrs = $hasAlt
                        ? preg_replace('/\balt\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', 'alt="' . $alt . '"', $attrs, 1)
                        : $attrs . ' alt="' . $alt . '"';
                } else {
                    $sanitizedAlt = self::sanitizeAltText(html_entity_decode($altValue, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $sanitizedAlt = htmlspecialchars($sanitizedAlt ?: $fallbackAlt, ENT_QUOTES, 'UTF-8');
                    $attrs = preg_replace(
                        '/\balt\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i',
                        'alt="' . $sanitizedAlt . '"',
                        $attrs,
                        1
                    );
                }

                if (!preg_match('/\bloading\s*=/i', (string) $attrs)) {
                    $attrs .= ' loading="lazy"';
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

            $img->setAttribute('loading', $img->getAttribute('loading') === 'eager' ? 'eager' : 'lazy');
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
