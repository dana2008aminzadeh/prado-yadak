<?php

namespace Core;

use App\models\Article;
use App\models\Product;
use Throwable;

/**
 * مرجع واحد نرمال‌سازی URL و اجرای ریدایرکت‌های سئویی.
 *
 * همه مسیرها به UTF-8/NFC تبدیل و هر segment با rawurlencode کد می‌شود. کوئری‌ها
 * نیز بدون کلید تکراری، با ترتیب ثابت و RFC3986 بازسازی می‌شوند. مسیرهای قدیمی
 * شناسه‌محور قبل از رسیدن درخواست به Router به URL اسلاگ‌محور منتقل می‌شوند.
 */
final class UrlCanonicalizer
{
    private const TRACKING_PARAMS = [
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'gclid', 'fbclid', 'yclid', 'msclkid',
    ];

    /** segmentهای شناخته‌شده‌ای که بزرگی/کوچکی حروف در آن‌ها معنا ندارد. */
    private const ROUTE_SEGMENTS = [
        'index', 'index.php', 'parts', 'product', 'product-detail', 'blog',
        'blog-detail', 'article', 'media', 'image', 'terms', 'login', 'profile', 'admin',
        'checkout', 'order', 'api', 'cart', 'sitemap.xml', 'sitemap-static.xml',
        'sitemap-products.xml', 'sitemap-categories.xml', 'sitemap-models.xml',
        'sitemap-brands.xml', 'sitemap-landing.xml', 'sitemap-articles.xml',
        'sitemap-images.xml',
    ];

    /**
     * درخواست GET/HEAD جاری را بررسی می‌کند، در صورت نیاز 301 می‌دهد و در غیر
     * این صورت مسیر canonical (encode شده) مناسب Router را برمی‌گرداند.
     */
    public static function handleRequest(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $rawPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
        $path = self::normalizePath($rawPath);
        $decodedPath = self::decodePath($path);
        $query = self::parseQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));

        $legacyTarget = in_array($method, ['GET', 'HEAD'], true)
            ? self::legacyTarget($decodedPath, $query)
            : null;
        if ($legacyTarget !== null) {
            self::redirect($legacyTarget, 301, 'legacy');
        }

        // /index و /index.php تنها یک نسخه دارند.
        if ($decodedPath === '/index' || $decodedPath === '/index.php') {
            $path = '/';
            $decodedPath = '/';
        }

        // صفحات جزئیات هیچ query معناداری ندارند؛ پارامترهای ردیابی یا زائد حذف شوند.
        if (preg_match('#^/(product|blog)/[^/]+$#u', $decodedPath)) {
            $query = [];
        }

        $canonicalQuery = self::buildQuery($query, $decodedPath);
        $target = $path . ($canonicalQuery !== '' ? '?' . $canonicalQuery : '');

        $currentPath = $rawPath === '' ? '/' : '/' . ltrim($rawPath, '/');
        $currentQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $current = $currentPath . ($currentQuery !== '' ? '?' . $currentQuery : '');

        if ($current !== $target) {
            // برای درخواست‌های تغییردهنده، 308 متد و body را حفظ می‌کند.
            self::redirect($target, in_array($method, ['GET', 'HEAD'], true) ? 301 : 308, 'canonical');
        }

        return $path;
    }

    /** مسیر را بدون query و با encoding واحد برمی‌گرداند. */
    public static function normalizePath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '/';
        }

        if (preg_match('#^https?://#i', $path)) {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?? '/');
        } else {
            $path = (string) (parse_url($path, PHP_URL_PATH) ?? $path);
        }

        // percentهای خراب encode می‌شوند تا ambiguity بین وب‌سرور و PHP نماند.
        $path = preg_replace('/%(?![0-9A-Fa-f]{2})/', '%25', $path) ?? $path;
        $decoded = rawurldecode($path);
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            $decoded = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
        }
        if (class_exists('Normalizer')) {
            $decoded = \Normalizer::normalize($decoded, \Normalizer::FORM_C) ?: $decoded;
        }

        $decoded = preg_replace('#/+#', '/', '/' . ltrim($decoded, '/')) ?? '/';
        if ($decoded !== '/') {
            $decoded = rtrim($decoded, '/');
        }

        $segments = explode('/', trim($decoded, '/'));
        if ($segments && $segments[0] !== '') {
            $firstLower = strtolower($segments[0]);
            if (in_array($firstLower, self::ROUTE_SEGMENTS, true)) {
                $segments[0] = $firstLower;
            }
            // زیربخش‌های سیستمی slug محتوایی ندارند و case آن‌ها نیز canonical است.
            if (in_array($firstLower, ['api', 'order', 'cart', 'admin'], true)) {
                foreach ($segments as &$segment) {
                    if (preg_match('/^[A-Z0-9._-]+$/i', $segment)) {
                        $segment = strtolower($segment);
                    }
                }
                unset($segment);
            }
        }

        if ($segments === [''] || !$segments) {
            return '/';
        }

        return '/' . implode('/', array_map(static fn(string $segment): string => rawurlencode($segment), $segments));
    }

    /** نسخه decode شده مسیر canonical؛ مناسب مقایسه منطقی و گزارش. */
    public static function decodePath(string $path): string
    {
        $decoded = rawurldecode((string) (parse_url($path, PHP_URL_PATH) ?? $path));
        if (class_exists('Normalizer')) {
            $decoded = \Normalizer::normalize($decoded, \Normalizer::FORM_C) ?: $decoded;
        }
        return $decoded === '' ? '/' : $decoded;
    }

    /**
     * اجرای امن redirect داخلی. استفاده مستقیم از header(Location) در لایه‌های
     * دیگر پروژه لازم نیست و همه redirectها از این متد عبور می‌کنند.
     */
    public static function redirect(string $target, int $status = 301, string $source = 'application'): never
    {
        $status = in_array($status, [301, 302, 307, 308], true) ? $status : 301;
        $parts = parse_url($target);

        // Open Redirect ممنوع: مقصد خارجی به مسیر داخلی همان URL تقلیل می‌یابد.
        $path = (string) ($parts['path'] ?? '/');
        $path = self::normalizePath($path);
        $query = isset($parts['query']) ? self::buildQuery(self::parseQuery((string) $parts['query']), self::decodePath($path)) : '';
        $location = $path . ($query !== '' ? '?' . $query : '');

        if (!headers_sent()) {
            header('Location: ' . $location, true, $status);
            header('X-Redirect-By: PradoYadak-' . preg_replace('/[^A-Za-z0-9_-]/', '', $source));
        }
        exit;
    }

    /** اگر URL جاری با مقصد یکسان نیست، مستقیم به مقصد canonical می‌رود. */
    public static function redirectIfDifferent(string $target, int $status = 301): void
    {
        $targetPath = self::normalizePath((string) (parse_url($target, PHP_URL_PATH) ?? '/'));
        $targetQuery = (string) (parse_url($target, PHP_URL_QUERY) ?? '');
        $targetUrl = $targetPath . ($targetQuery !== '' ? '?' . self::buildQuery(self::parseQuery($targetQuery), self::decodePath($targetPath)) : '');

        $currentPath = self::normalizePath((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
        $currentQuery = self::buildQuery(self::parseQuery((string) ($_SERVER['QUERY_STRING'] ?? '')), self::decodePath($currentPath));
        $currentUrl = $currentPath . ($currentQuery !== '' ? '?' . $currentQuery : '');

        if ($currentUrl !== $targetUrl) {
            self::redirect($targetUrl, $status, 'entity');
        }
    }

    /** @return array<string,string|array<int,string>> */
    public static function parseQuery(string $query): array
    {
        $result = [];
        foreach (explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$rawKey, $rawValue] = array_pad(explode('=', $pair, 2), 2, '');
            $key = trim(urldecode($rawKey));
            $value = trim(urldecode($rawValue));
            if ($key === '' || in_array(strtolower($key), self::TRACKING_PARAMS, true)) {
                continue;
            }

            if (str_ends_with($key, '[]')) {
                $key = substr($key, 0, -2);
                $values = isset($result[$key]) ? (array) $result[$key] : [];
                if (!in_array($value, $values, true)) {
                    $values[] = $value;
                }
                $result[$key] = $values;
                continue;
            }

            // برای کلید scalar تکراری فقط آخرین مقدار معتبر نگه داشته می‌شود.
            $result[$key] = $value;
        }
        return $result;
    }

    /** @param array<string,string|array<int,string>> $params */
    public static function buildQuery(array $params, string $path = '/'): string
    {
        // حذف پارامترهای ردیابی (UTM و کلیک)
        foreach ($params as $key => $value) {
            if (in_array(strtolower((string) $key), self::TRACKING_PARAMS, true)) {
                unset($params[$key]);
                continue;
            }
            if (is_array($value)) {
                $value = array_values(array_unique(array_filter(array_map(static fn($v): string => trim((string) $v), $value), 'strlen')));
                sort($value, SORT_STRING);
                if (!$value) {
                    unset($params[$key]);
                } else {
                    $params[$key] = $value;
                }
            } elseif (trim((string) $value) === '') {
                unset($params[$key]);
            } else {
                $params[$key] = trim((string) $value);
            }
        }

        if ($path === '/parts') {
            // فقط پارامترهای شناخته‌شده برای کاتالوگ مجاز هستند — بقیه با 301 حذف می‌شوند
            // تا URLهای موازی و بی‌نهایت ساخته نشود
            $allowedForParts = ['q', 'category', 'model', 'brand', 'minPrice', 'maxPrice', 'inStock', 'sort', 'page', 'view'];
            foreach (array_keys($params) as $k) {
                if (!in_array($k, $allowedForParts, true)) {
                    unset($params[$k]);
                }
            }

            foreach (['category', 'model', 'brand'] as $key) {
                if (!isset($params[$key])) {
                    continue;
                }
                $values = is_array($params[$key]) ? $params[$key] : explode(',', (string) $params[$key]);
                $values = array_values(array_unique(array_filter(array_map('trim', $values), 'strlen')));
                // اعتبارسنجی اسلاگ — فقط حروف، اعداد، dash، underscore، فارسی
                $values = array_values(array_filter($values, static function ($slug): bool {
                    $slug = trim($slug);
                    if ($slug === '' || mb_strlen($slug, 'UTF-8') > 80) {
                        return false;
                    }
                    return (bool) preg_match('/^[\p{L}\p{N}\-_]{2,80}$/u', $slug);
                }));
                // محدودیت تعداد فیلترهای قابل ترکیب — حداکثر 3 مقدار برای هر کلید
                if (count($values) > 3) {
                    $values = array_slice($values, 0, 3);
                }
                sort($values, SORT_STRING);
                if (!$values) {
                    unset($params[$key]);
                } else {
                    $params[$key] = implode(',', $values);
                }
            }

            // q: جستجو — محدودیت طول و محتوای معقول
            if (isset($params['q'])) {
                $q = trim((string) $params['q']);
                if ($q === '' || mb_strlen($q, 'UTF-8') > 100) {
                    if (mb_strlen($q, 'UTF-8') > 100) {
                        $q = mb_substr($q, 0, 100, 'UTF-8');
                    }
                    if (trim($q) === '') {
                        unset($params['q']);
                    } else {
                        $params['q'] = $q;
                    }
                }
            }

            // اعتبارسنجی قیمت
            foreach (['minPrice', 'maxPrice'] as $priceKey) {
                if (isset($params[$priceKey])) {
                    $v = $params[$priceKey];
                    if (!is_numeric($v) || (float) $v < 0 || (float) $v > 10000000000) {
                        unset($params[$priceKey]);
                    }
                }
            }

            if (isset($params['page']) && (int) $params['page'] <= 1) {
                unset($params['page']);
            }
            if (isset($params['page']) && (int) $params['page'] > 1000) {
                $params['page'] = '1000';
            }
            if (($params['sort'] ?? null) === 'newest') {
                unset($params['sort']);
            }
            // view فقط مقادیر مجاز
            if (isset($params['view']) && !in_array($params['view'], ['grid', 'list'], true)) {
                unset($params['view']);
            }
        }

        // برای صفحات ثابت، هیچ query نباید بماند (به جز /parts و /blog که صفحه‌بندی دارند)
        if (!in_array($path, ['/parts', '/blog'], true) && !str_starts_with($path, '/parts/') && !str_starts_with($path, '/sitemap')) {
            // صفحات محصول و مقاله هیچ query معناداری ندارند
            if (preg_match('#^/(product|blog)/[^/]+$#u', $path)) {
                $params = [];
            } elseif ($path === '/' || $path === '/terms' || $path === '/login' || $path === '/profile') {
                // صفحات ثابت — تمام queryها حذف (به جز موارد خاص)
                $params = [];
            }
        }

        $order = ['q', 'category', 'model', 'brand', 'minPrice', 'maxPrice', 'inStock', 'sort', 'page', 'p'];
        uksort($params, static function ($a, $b) use ($order): int {
            $ai = array_search($a, $order, true);
            $bi = array_search($b, $order, true);
            $ai = $ai === false ? PHP_INT_MAX : $ai;
            $bi = $bi === false ? PHP_INT_MAX : $bi;
            return $ai === $bi ? strcmp((string) $a, (string) $b) : $ai <=> $bi;
        });

        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    /** @param array<string,string|array<int,string>> $query */
    private static function legacyTarget(string $path, array $query): ?string
    {
        $pathLower = strtolower($path);

        // فرم‌های قدیمی شناسه‌محور محصولات.
        if (in_array($pathLower, ['/product', '/product-detail'], true)) {
            try {
                if (!empty($query['id'])) {
                    $product = Product::findByIdIncludingDiscontinued((int) $query['id']);
                    return $product ? Seo::productUrl((string) $product['slug']) : null;
                }
                if (!empty($query['slug'])) {
                    $product = Product::findBySlug((string) $query['slug']);
                    return $product ? Seo::productUrl((string) $product['slug']) : null;
                }
            } catch (Throwable $e) {
                return null;
            }
        }
        if (preg_match('#^/product-detail/(.+)$#u', $path, $m)) {
            return Seo::productUrl($m[1]);
        }

        // فرم‌های قدیمی مقالات: /blog-detail?id=، /blog?id= و /article?id=.
        if (in_array($pathLower, ['/blog-detail', '/article'], true)
            || ($pathLower === '/blog' && !empty($query['id']))) {
            try {
                if (!empty($query['id'])) {
                    $article = Article::findById((int) $query['id']);
                    return $article ? Seo::articleUrl((string) $article['slug']) : null;
                }
                if (!empty($query['slug'])) {
                    $article = Article::findBySlug((string) $query['slug']);
                    return $article ? Seo::articleUrl((string) $article['slug']) : null;
                }
                return $pathLower === '/blog-detail' ? '/blog' : null;
            } catch (Throwable $e) {
                return null;
            }
        }
        if (preg_match('#^/(?:blog-detail|article)/(.+)$#ui', $path, $m)) {
            return Seo::articleUrl($m[1]);
        }

        return null;
    }
}
