<?php

namespace App\controllers;

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

    /** فهرست شاخه‌ای نقشه سایت */
    public function index(): void
    {
        $base = Seo::base();
        $maps = [];

        $maps[] = ['loc' => $base . '/sitemap-static.xml', 'lastmod' => date('c')];

        $productCount = $this->count('products', "COALESCE(robots_directive,'default') <> 'noindex'");
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

        if ($this->tableExists('seo_landing_pages') && $this->count('seo_landing_pages', 'is_active = 1') > 0) {
            $maps[] = ['loc' => $base . '/sitemap-landing.xml', 'lastmod' => $this->latest('seo_landing_pages')];
        }

        $maps[] = ['loc' => $base . '/sitemap-images.xml', 'lastmod' => $this->latest('products')];

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
            ['loc' => '/parts', 'priority' => '0.9', 'changefreq' => 'daily',  'lastmod' => $this->latest('products')],
            ['loc' => '/blog',  'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $this->latest('articles')],
            ['loc' => '/terms', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];
        $this->sendUrlSet($urls);
    }

    /** محصولات */
    public function products(): void
    {
        $page = max(1, (int) ($_GET['p'] ?? 1));
        $offset = ($page - 1) * self::CHUNK;
        $limit = self::CHUNK;

        $rows = $this->query(
            "SELECT p.slug, p.price, p.in_stock, p.stock_qty,
                    GREATEST(
                        COALESCE(p.updated_at, p.created_at),
                        COALESCE((SELECT MAX(sm.created_at) FROM stock_movements sm WHERE sm.product_id = p.id), '1970-01-01'),
                        COALESCE(p.created_at, '1970-01-01')
                    ) AS lastmod
             FROM products p
             WHERE p.slug IS NOT NULL AND p.slug <> ''
               AND COALESCE(p.robots_directive, 'default') <> 'noindex'
             ORDER BY p.id
             LIMIT {$limit} OFFSET {$offset}"
        );

        $urls = [];
        foreach ($rows as $r) {
            $inStock = (int) ($r['in_stock'] ?? 0) === 1 || (int) ($r['stock_qty'] ?? 0) > 0;
            $urls[] = [
                'loc'        => '/product/' . rawurlencode((string) $r['slug']),
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
             LEFT JOIN products p ON p.category_id = c.id
             GROUP BY c.id, c.slug
             HAVING total > 0"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => '/parts?category=' . rawurlencode((string) $r['slug']),
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
             LEFT JOIN products p ON p.car_model = cm.slug
             GROUP BY cm.id, cm.slug
             HAVING total > 0"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => '/parts?model=' . rawurlencode((string) $r['slug']),
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
             WHERE brand IS NOT NULL AND brand <> ''
             GROUP BY brand"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => '/parts?brand=' . rawurlencode((string) $r['brand']),
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
            "SELECT slug, updated_at FROM seo_landing_pages
             WHERE is_active = 1 AND COALESCE(robots_directive,'default') <> 'noindex'"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => '/parts/' . rawurlencode((string) $r['slug']),
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
        $rows = $this->query(
            "SELECT slug, COALESCE(updated_at, created_at) AS lastmod
             FROM articles
             WHERE status = 'published' AND slug IS NOT NULL AND slug <> ''
               AND COALESCE(robots_directive,'default') <> 'noindex'
             ORDER BY id DESC"
        );

        $urls = [];
        foreach ($rows as $r) {
            $urls[] = [
                'loc'        => '/blog/' . rawurlencode((string) $r['slug']),
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

        $rows = $this->query(
            "SELECT p.slug, p.name, p.oem_code, p.car_model,
                    pi.telegram_file_id, pi.image_path, pi.alt_text, pi.sort_order
             FROM products p
             JOIN product_images pi ON pi.product_id = p.id
             WHERE p.slug IS NOT NULL AND p.slug <> ''
             ORDER BY p.id, pi.is_primary DESC, pi.sort_order
             LIMIT 40000"
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
                $url = $base . Seo::imageUrl((string) $identifier, $seoName);
                $alt = trim((string) ($img['alt_text'] ?? ''))
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

    /** آخرین تغییر واقعی یک جدول */
    private function latest(string $table): string
    {
        try {
            $col = $table === 'articles' ? 'COALESCE(updated_at, created_at)' : 'COALESCE(updated_at, created_at)';
            $v = Database::getInstance()->query("SELECT MAX({$col}) FROM `{$table}`")->fetchColumn();
            return $this->iso($v ?: null);
        } catch (Throwable $e) {
            return date('c');
        }
    }

    private function iso(?string $date): string
    {
        if (!$date) {
            return date('c');
        }
        $ts = strtotime($date);
        return $ts ? date('c', $ts) : date('c');
    }
}
