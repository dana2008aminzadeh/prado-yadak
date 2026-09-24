<?php

namespace App\controllers;

use App\models\LandingPage;
use App\models\Product;
use Core\Database;
use Core\Seo;
use PDO;
use Throwable;

/**
 * نقشه سایت پویا و شاخه‌ای (Sitemap Index)
 * ---------------------------------------------------------------------------
 *   /sitemap.xml            → فهرست شاخه‌ها
 *   /sitemap-static.xml     → صفحات ثابت
 *   /sitemap-products.xml   → محصولات (تکه‌تکه با ?p=2)
 *   /sitemap-categories.xml → دسته‌بندی‌ها
 *   /sitemap-brands.xml     → برندها
 *   /sitemap-models.xml     → مدل‌های خودرو
 *   /sitemap-landing.xml    → لندینگ‌پیج‌های سئو
 *   /sitemap-articles.xml   → مقالات
 *   /sitemap-images.xml     → نقشه تصاویر محصولات (Google Images)
 *
 * مقدار lastmod از تغییرات واقعی دیتابیس (updated_at محصول، آخرین حرکت انبار و
 * آخرین تغییر قیمت) گرفته می‌شود تا بودجه خزش هدر نرود.
 */
class SitemapController
{
    private const CHUNK = 5000;
    private const IMAGE_CHUNK = 20000;

    /** فهرست شاخه‌ای نقشه سایت */
    public function index(): void
    {
        $base = Seo::base();
        $maps = [];

        // lastmod این شاخه نباید هر بار برابر «همین لحظه» باشد؛ گوگل چنین
        // مقداری را به‌عنوان سیگنال کاذب تازگی تفسیر می‌کند و بودجه خزش را
        // هدر می‌دهد. مقدار واقعی از آخرین تغییر محصولات/مقالات (که صفحات
        // ثابت مثل /parts و /blog به آن‌ها وابسته‌اند) محاسبه می‌شود.
        $maps[] = ['loc' => $base . '/sitemap-static.xml', 'lastmod' => $this->latestOf(['products', 'articles'])];

        $productCount = $this->count('products', $this->productEligibility());
        $chunks = max(1, (int) ceil($productCount / self::CHUNK));
        for ($i = 1; $i <= $chunks; $i++) {
            $maps[] = [
                'loc'     => $base . '/sitemap-products.xml' . ($i > 1 ? '?p=' . $i : ''),
                'lastmod' => $this->latest('products'),
            ];
        }

        $maps[] = ['loc' => $base . '/sitemap-categories.xml', 'lastmod' => $this->latest('products')];
        $maps[] = ['loc' => $base . '/sitemap-models.xml',     'lastmod' => $this->latest('products')];
        $maps[] = ['loc' => $base . '/sitemap-brands.xml',     'lastmod' => $this->latest('products')];
        $maps[] = ['loc' => $base . '/sitemap-articles.xml',   'lastmod' => $this->latest('articles')];

        if ($this->tableExists('seo_landing_pages') && $this->count('seo_landing_pages',
                "is_active = 1 AND COALESCE(robots_directive, 'default') NOT IN ('noindex', 'noindex_nofollow')") > 0) {
            $maps[] = ['loc' => $base . '/sitemap-landing.xml', 'lastmod' => $this->latest('seo_landing_pages')];
        }

        $imageCount = (int) ($this->query(
            'SELECT COUNT(*) AS total FROM product_images pi JOIN products p ON p.id = pi.product_id'
            . ' WHERE ' . $this->productEligibility('p')
            . " AND (NULLIF(pi.telegram_file_id, '') IS NOT NULL OR NULLIF(pi.image_path, '') IS NOT NULL)"
        )[0]['total'] ?? 0);
        $imageChunks = max(1, (int) ceil($imageCount / self::IMAGE_CHUNK));
        for ($i = 1; $i <= $imageChunks; $i++) {
            $maps[] = [
                'loc' => $base . '/sitemap-images.xml' . ($i > 1 ? '?p=' . $i : ''),
                'lastmod' => $this->latest('products'),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($maps as $m) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . htmlspecialchars($m['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($m['lastmod'])) {
                $xml .= '    <lastmod>' . $m['lastmod'] . "</lastmod>\n";
            }
            $xml .= "  </sitemap>\n";
        }
        $xml .= '</sitemapindex>';

        $this->send($xml);
    }

    /** صفحات ثابت */
    public function statics(): void
    {
        $urls = [
            ['loc' => '/',      'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];
        if ($this->count('products', $this->catalogEligibility()) > 0) {
            $urls[] = ['loc' => '/parts', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => $this->latest('products')];
        }
        if ($this->count('articles', "status = 'published'") > 0) {
            $urls[] = ['loc' => '/blog', 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $this->latest('articles')];
        }
        $this->sendUrlSet($urls);
    }

    /** محصولات */
    public function products(): void
    {
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $offset = ($page - 1) * self::CHUNK;
        $limit = self::CHUNK;
        $canonicalColumn = $this->columnExists('products', 'canonical_url') ? 'p.canonical_url' : 'NULL AS canonical_url';

        $rows = $this->query(
            "SELECT p.slug, p.price, p.in_stock, p.stock_qty, {$canonicalColumn},
                    GREATEST(
                        COALESCE(p.updated_at, p.created_at),
                        COALESCE((SELECT MAX(sm.created_at) FROM stock_movements sm WHERE sm.product_id = p.id), '1970-01-01'),
                        COALESCE(p.created_at, '1970-01-01')
                    ) AS lastmod
             FROM products p
             WHERE p.slug IS NOT NULL AND p.slug <> ''
               AND " . $this->productEligibility('p') . "
             ORDER BY p.id
             LIMIT {$limit} OFFSET {$offset}"
        );

        $urls = [];
        foreach ($rows as $r) {
            $loc = Seo::productUrl((string) $r['slug'], true);
            if (Seo::normalizeCanonicalHost((string) (($r['canonical_url'] ?? '') ?: $loc), $loc) !== $loc) {
                continue;
            }
            $inStock = (int) ($r['in_stock'] ?? 0) === 1 || (int) ($r['stock_qty'] ?? 0) > 0;
            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => $this->iso($r['lastmod'] ?? null),
                'changefreq' => 'weekly',
                // قطعات موجود اولویت بالاتری برای خزش می‌گیرند
                'priority'   => $inStock ? '0.8' : '0.5',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** دسته‌بندی‌ها (با lastmod = آخرین تغییر محصولات همان دسته) */
    public function categories(): void
    {
        $rows = $this->query(
            "SELECT c.slug,
                    MAX(COALESCE(p.updated_at, p.created_at)) AS lastmod,
                    COUNT(p.id) AS total
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND " . $this->catalogEligibility('p') . "
             WHERE c.slug IS NOT NULL AND c.slug <> ''
             GROUP BY c.id, c.slug
             HAVING total > 0"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => Seo::categoryUrl((string) $r['slug']),
                'lastmod'    => $this->iso($r['lastmod'] ?? null),
                'changefreq' => 'daily',
                'priority'   => '0.8',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** مدل‌های خودرو */
    public function models(): void
    {
        $rows = $this->query(
            "SELECT cm.slug, MAX(COALESCE(p.updated_at, p.created_at)) AS lastmod, COUNT(p.id) AS total
             FROM car_models cm
             LEFT JOIN products p ON p.car_model = cm.slug AND " . $this->catalogEligibility('p') . "
             WHERE cm.slug IS NOT NULL AND cm.slug <> ''
             GROUP BY cm.id, cm.slug
             HAVING total > 0"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => Seo::modelUrl((string) $r['slug']),
                'lastmod'    => $this->iso($r['lastmod'] ?? null),
                'changefreq' => 'daily',
                'priority'   => '0.8',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** برندها */
    public function brands(): void
    {
        $rows = $this->query(
            "SELECT brand, MAX(COALESCE(updated_at, created_at)) AS lastmod
             FROM products
             WHERE brand IS NOT NULL AND brand <> '' AND " . $this->catalogEligibility() . "
             GROUP BY brand"
        );

        $urls = [];
        foreach ($rows as $r) {
            $brand = (string) $r['brand'];
            $params = ['brand' => $brand];
            // برند حاوی فاصله/کاراکتر نامعتبر در /parts به صفحه مادر redirect
            // می‌شود؛ چنین URL غیرکانونیکال نباید در Sitemap باشد.
            if (Seo::normalizeQuery($params, ['brand']) !== 'brand=' . rawurlencode($brand)
                || Seo::catalogRobots($params) !== 'index, follow') {
                continue;
            }
            $urls[] = [
                'loc'        => Seo::catalogCanonical($params),
                'lastmod'    => $this->iso($r['lastmod'] ?? null),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** لندینگ‌پیج‌های سئو */
    public function landing(): void
    {
        if (!$this->tableExists('seo_landing_pages')) {
            $this->sendUrlSet([]);
        }

        $rows = $this->query(
            "SELECT * FROM seo_landing_pages
             WHERE is_active = 1 AND COALESCE(robots_directive,'default') NOT IN ('noindex', 'noindex_nofollow')"
        );

        $urls = [];
        foreach ($rows as $r) {
            $slug = (string) ($r['slug'] ?? '');
            if ($slug === '') {
                continue;
            }
            $loc = Seo::absolute('/parts/' . rawurlencode($slug));
            if (Seo::normalizeCanonicalHost((string) (($r['canonical_url'] ?? '') ?: $loc), $loc) !== $loc
                || Product::search(LandingPage::toFilters($r), 1, 1)['total'] <= 0) {
                continue;
            }
            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => $this->iso($r['updated_at'] ?? null),
                'changefreq' => 'weekly',
                'priority'   => '0.9',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** مقالات */
    public function articles(): void
    {
        $canonicalColumn = $this->columnExists('articles', 'canonical_url') ? 'canonical_url' : 'NULL AS canonical_url';
        $rows = $this->query(
            "SELECT slug, {$canonicalColumn}, COALESCE(updated_at, created_at) AS lastmod
             FROM articles
             WHERE status = 'published' AND slug IS NOT NULL AND slug <> ''
               AND COALESCE(robots_directive,'default') NOT IN ('noindex', 'noindex_nofollow')
             ORDER BY id DESC"
        );

        $urls = [];
        foreach ($rows as $r) {
            $loc = Seo::articleUrl((string) $r['slug'], true);
            if (Seo::normalizeCanonicalHost((string) (($r['canonical_url'] ?? '') ?: $loc), $loc) !== $loc) {
                continue;
            }
            $urls[] = [
                'loc'        => $loc,
                'lastmod'    => $this->iso($r['lastmod'] ?? null),
                'changefreq' => 'monthly',
                'priority'   => '0.7',
            ];
        }
        $this->sendUrlSet($urls);
    }

    /** نقشه تصاویر — کلید ورود به ترافیک Google Images */
    public function images(): void
    {
        $base = Seo::base();
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $offset = ($page - 1) * self::IMAGE_CHUNK;
        $limit = self::IMAGE_CHUNK;

        $rows = $this->query(
            "SELECT p.slug, p.name, p.oem_code, p.car_model,
                    pi.telegram_file_id, pi.image_path, pi.alt_text, pi.sort_order
             FROM products p
             JOIN product_images pi ON pi.product_id = p.id
             WHERE p.slug IS NOT NULL AND p.slug <> ''
               AND " . $this->productEligibility('p') . "
               AND (NULLIF(pi.telegram_file_id, '') IS NOT NULL OR NULLIF(pi.image_path, '') IS NOT NULL)
             ORDER BY p.id, pi.is_primary DESC, pi.sort_order, pi.id
             LIMIT {$limit} OFFSET {$offset}"
        );

        $grouped = [];
        foreach ($rows as $r) {
            $grouped[(string) $r['slug']][] = $r;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($grouped as $slug => $imgs) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($base . '/product/' . rawurlencode($slug), ENT_XML1) . "</loc>\n";
            foreach ($imgs as $i => $img) {
                $identifier = $img['telegram_file_id'] ?: $img['image_path'];
                if (!$identifier) {
                    continue;
                }
                $seoName = Seo::imageSlug((string) $img['name'], $img['oem_code'] ?? null, $img['car_model'] ?? null, (int) $i);
                $imagePath = Seo::imageUrl((string) $identifier, $seoName);
                if ($imagePath === '') {
                    continue;
                }
                $url = Seo::absolute($imagePath);
                $alt = Seo::sanitizeAltText((string) ($img['alt_text'] ?? ''))
                    ?: Seo::suggestAlt((string) $img['name'], $img['car_model'] ?? null, $img['oem_code'] ?? null, (int) $i);

                $xml .= "    <image:image>\n";
                $xml .= '      <image:loc>' . htmlspecialchars($url, ENT_XML1) . "</image:loc>\n";
                $xml .= '      <image:title>' . htmlspecialchars(mb_substr($alt, 0, 120, 'UTF-8'), ENT_XML1) . "</image:title>\n";
                $xml .= "    </image:image>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        $this->send($xml);
    }

    // ---------------------------------------------------------------- داخلی

    /** همان محصولاتی که Product::search در کاتالوگ نشان می‌دهد. */
    private function catalogEligibility(string $alias = ''): string
    {
        if (!$this->columnExists('products', 'lifecycle_status')) {
            return '1=1';
        }
        $prefix = $alias !== '' ? $alias . '.' : '';
        return "COALESCE({$prefix}lifecycle_status, 'active') <> 'discontinued'";
    }

    /**
     * سیاست Sitemap محصول:
     * - ناموجودی موقت همچنان در Sitemap می‌ماند و Schema آن OutOfStock است؛
     * - discontinued و sitemap_policy=exclude حذف می‌شوند؛
     * - robots=noindex همیشه حذف می‌شود.
     */
    private function productEligibility(string $alias = ''): string
    {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $conditions = ['1=1'];
        if ($this->columnExists('products', 'robots_directive')) {
            $conditions[] = "COALESCE({$prefix}robots_directive, 'default') NOT IN ('noindex', 'noindex_nofollow')";
        }
        if ($this->columnExists('products', 'lifecycle_status')) {
            $conditions[] = "COALESCE({$prefix}lifecycle_status, 'active') <> 'discontinued'";
        }
        if ($this->columnExists('products', 'sitemap_policy')) {
            $conditions[] = "COALESCE({$prefix}sitemap_policy, 'auto') <> 'exclude'";
        }
        return implode(' AND ', $conditions);
    }

    private function columnExists(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }
        try {
            $st = Database::getInstance()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$table, $column]);
            return $cache[$key] = (bool) $st->fetchColumn();
        } catch (Throwable $e) {
            return $cache[$key] = false;
        }
    }

    private function sendUrlSet(array $urls): void
    {
        $base = Seo::base();
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $loc = str_starts_with($u['loc'], 'http') ? $u['loc'] : $base . $u['loc'];
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }
            if (!empty($u['changefreq'])) {
                $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            }
            if (!empty($u['priority'])) {
                $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';
        $this->send($xml);
    }

    private function send(string $xml): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex');           // خود فایل نقشه ایندکس نمی‌شود
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }

    private function query(string $sql, array $params = []): array
    {
        try {
            $st = Database::getInstance()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Sitemap query failed: ' . $e->getMessage());
            return [];
        }
    }

    private function count(string $table, string $where = '1'): int
    {
        try {
            return (int) Database::getInstance()->query("SELECT COUNT(*) FROM `{$table}` WHERE {$where}")->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $st = Database::getInstance()->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
            );
            $st->execute([$table]);
            return (bool) $st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * آخرین تغییر واقعی یک جدول.
     * ---------------------------------------------------------------------
     * تصمیم قطعی پروژه: اگر تاریخ واقعی در دسترس نباشد، رشته خالی برگردانده
     * می‌شود (نه date('c') لحظه درخواست). lastmod برابر «همین الان» با هر
     * بار خزش گوگل عوض می‌شود و سیگنال تازگی را جعلی و بی‌اعتبار می‌کند؛
     * نبود lastmod (حذف کامل تگ در sendUrlSet/index) همیشه بهتر از یک مقدار
     * نادرست و متغیر است.
     */
    private function latest(string $table): string
    {
        try {
            $v = Database::getInstance()
                ->query("SELECT MAX(COALESCE(updated_at, created_at)) FROM `{$table}`")
                ->fetchColumn();
            return $this->iso($v ?: null);
        } catch (Throwable $e) {
            return '';
        }
    }

    /** جدیدترین lastmod در میان چند جدول — برای صفحاتی که به بیش از یک منبع وابسته‌اند */
    private function latestOf(array $tables): string
    {
        $best = null;
        foreach ($tables as $table) {
            try {
                $v = Database::getInstance()
                    ->query("SELECT MAX(COALESCE(updated_at, created_at)) FROM `{$table}`")
                    ->fetchColumn();
                $ts = $v ? strtotime((string) $v) : false;
                if ($ts !== false && ($best === null || $ts > $best)) {
                    $best = $ts;
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return $best !== null ? date('c', $best) : '';
    }

    private function iso(?string $date): string
    {
        if (!$date) {
            return '';
        }
        $ts = strtotime($date);
        return $ts ? date('c', $ts) : '';
    }
}
