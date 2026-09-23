<?php
namespace App\models;

use Core\Database;
use PDO;

class Product
{
    public static function search($filters = [], $page = 1, $perPage = 20)
    {
        $db = Database::getInstance();
        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['q'])) {
            $conditions[] = "(p.name LIKE ? OR p.oem_code LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['id'])) {
            $conditions[] = "p.id = ?";
            $params[] = (int) $filters['id'];
        }

        if (!empty($filters['categories']) && is_array($filters['categories'])) {
            $placeholders = implode(',', array_fill(0, count($filters['categories']), '?'));
            $conditions[] = "c.slug IN ($placeholders)";
            foreach ($filters['categories'] as $cat) {
                $params[] = $cat;
            }
        }

        if (!empty($filters['models']) && is_array($filters['models'])) {
            $placeholders = implode(',', array_fill(0, count($filters['models']), '?'));
            $conditions[] = "p.car_model IN ($placeholders)";
            foreach ($filters['models'] as $model) {
                $params[] = $model;
            }
        }

        if (!empty($filters['maxPrice'])) {
            $conditions[] = "p.price <= ?";
            $params[] = (float) $filters['maxPrice'];
        }

        if (!empty($filters['inStock']) && $filters['inStock'] === 'true') {
            $conditions[] = "p.in_stock = 1";
        }

        // ۶. فیلتر اصالت و برندها
        if (!empty($filters['brands']) && is_array($filters['brands'])) {
            $brandConditions = [];
            foreach ($filters['brands'] as $brand) {
                if ($brand === 'genuine') {
                    $brandConditions[] = "p.is_genuine = 1";
                } elseif ($brand === 'oem') {
                    $brandConditions[] = "p.is_genuine = 0";
                } else {
                    $brandConditions[] = "LOWER(p.brand) = ?";
                    $params[] = strtolower(trim($brand));
                }
            }
            if (!empty($brandConditions)) {
                $conditions[] = "(" . implode(" OR ", $brandConditions) . ")";
            }
        }

        $whereClause = implode(" AND ", $conditions);

        // واکشی تعداد کل (برای نمایش "یافت شده: X قطعه")
        $countSql = "SELECT COUNT(p.id) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $totalCount = $stmtCount->fetchColumn();

        $sort = $filters['sort'] ?? 'newest';
        $orderBy = "p.id DESC"; // پیش‌فرض: جدیدترین

        if ($sort === 'price-asc') {
            $orderBy = "p.price ASC";
        } elseif ($sort === 'price-desc') {
            $orderBy = "p.price DESC";
        } elseif ($sort === 'popular') {
            $orderBy = "p.id ASC"; // می‌توانید با فیلد بازدید جایگزین کنید
        }

        // صفحه‌بندی (Pagination)
        $page = max(1, (int) $page);
        $perPage = max(1, (int) $perPage);
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT p.*, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE $whereClause 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET $offset";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();

        // مپ کردن خروجی
        $mapped = [];
        foreach ($results as $r) {
            $mapped[] = self::mapRow($r);
        }

        return [
            'total' => (int) $totalCount,
            'page' => $page,
            'items' => $mapped
        ];
    }
    public static function getDistinctBrands()
    {
        $db = \Core\Database::getInstance();
        $stmt = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC");
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function findById($id)
    {
        $data = self::search(['id' => $id], 1, 1);
        return !empty($data['items']) ? $data['items'][0] : null;
    }

    public static function findBySlug($slug)
    {
        $db = Database::getInstance();
        $sql = "SELECT p.*, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([urldecode($slug)]);
        $r = $stmt->fetch();

        if (!$r)
            return null;

        return self::mapRow($r, true);
    }

    /**
     * تبدیل ردیف خام دیتابیس به آرایه‌ی مورد استفاده‌ی فرانت‌اند.
     * فیلدهای اختصاصی سئو و گالری تصاویر (به همراه alt) نیز اینجا ضمیمه می‌شوند.
     */
    private static function mapRow(array $r, bool $withGallery = false): array
    {
        $rawPhoto = (string) ($r['telegram_photo_id'] ?? '');
        $legacy = $rawPhoto !== '' ? json_decode($rawPhoto, true) : [];
        $legacy = is_array($legacy) ? $legacy : ($rawPhoto !== '' ? [$rawPhoto] : []);

        $gallery = $withGallery ? self::getGallery((int) $r['id']) : [];
        $images = $gallery ? array_column($gallery, 'identifier') : $legacy;

        return [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'slug' => $r['slug'],
            'category' => $r['category_slug'] ?? null,
            'price' => (float) $r['price'],
            'oem' => $r['oem_code'],
            'model' => $r['car_model'],
            'brand' => $r['brand'],
            'isGenuine' => (bool) $r['is_genuine'],
            'inStock' => (bool) $r['in_stock'],
            'stock_qty' => isset($r['stock_qty']) ? (int) $r['stock_qty'] : null,
            'desc' => $r['description'],
            'images' => $images,
            'gallery' => $gallery,
            // ---- فیلدهای اختصاصی سئو (اولویت با مقدار دستی مدیر) ----
            'meta_title' => $r['meta_title'] ?? null,
            'meta_description' => $r['meta_description'] ?? null,
            'focus_keyword' => $r['focus_keyword'] ?? null,
            'robots_directive' => $r['robots_directive'] ?? 'default',
            'canonical_url' => $r['canonical_url'] ?? null,
            'updated_at' => $r['updated_at'] ?? ($r['created_at'] ?? null),
            'created_at' => $r['created_at'] ?? null,
        ];
    }

    /**
     * گالری تصاویر با متن جایگزین و آدرس سئوشده.
     * اگر مدیر alt ننوشته باشد، پیشنهاد خودکار بر اساس نام قطعه، مدل خودرو و کد فنی ساخته می‌شود.
     */
    public static function getGallery(int $productId): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'SELECT pi.*, p.name, p.oem_code, p.car_model
                 FROM product_images pi
                 JOIN products p ON p.id = pi.product_id
                 WHERE pi.product_id = ?
                 ORDER BY pi.is_primary DESC, pi.sort_order ASC, pi.id ASC'
            );
            $stmt->execute([$productId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $modelName = null;
        $out = [];
        foreach ($rows as $i => $row) {
            $identifier = $row['telegram_file_id'] ?: ($row['image_path'] ?? '');
            if ($identifier === '') {
                continue;
            }
            if ($modelName === null) {
                $md = $GLOBALS['car_models'][$row['car_model'] ?? ''] ?? ($row['car_model'] ?? '');
                $modelName = is_array($md) ? ($md['name'] ?? '') : (string) $md;
            }

            $alt = trim((string) ($row['alt_text'] ?? ''));
            if ($alt === '') {
                $alt = \Core\Seo::suggestAlt((string) $row['name'], $modelName ?: null, $row['oem_code'] ?? null, (int) $i);
            }

            $seoName = trim((string) ($row['seo_filename'] ?? ''))
                ?: \Core\Seo::imageSlug((string) $row['name'], $row['oem_code'] ?? null, $modelName ?: null, (int) $i);

            $out[] = [
                'id' => (int) $row['id'],
                'identifier' => $identifier,
                'url' => \Core\Seo::imageUrl($identifier, $seoName),
                'alt' => $alt,
                'is_primary' => (int) ($row['is_primary'] ?? 0) === 1,
            ];
        }
        return $out;
    }

    public static function getComments($productId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM product_comments WHERE product_id = ? AND status = 'approved' ORDER BY created_at DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public static function canUserComment($productId, $userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT 1 FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'delivered' LIMIT 1
        ");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() ? true : false;
    }

    /**
     * تولید داده‌های ساختاریافته محصول به صورت یک گراف یکپارچه (@graph).
     * ---------------------------------------------------------------------
     * به‌جای چند اسکریپت مجزا، فروشگاه، وب‌سایت، صفحه، نان‌ریزه، محصول،
     * پیشنهاد فروش و دیدگاه‌ها همگی در یک بلوک به هم گره می‌خورند.
     * ویژگی‌های تخصصی خودرو (برند سازگار، MPN، شماره فنی) نیز درج می‌شود تا
     * قابلیت‌های Merchant Listings و نتایج خرید گوگل فعال شوند.
     */
    public static function generateSchema($product, $comments = [])
    {
        $settings = $GLOBALS['settings'] ?? [];
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $base = \Core\Seo::base();
        $productUrl = $base . '/product/' . rawurlencode((string) $product['slug']);

        // ---------- تصاویر با آدرس سئوشده ----------
        $images = [];
        if (!empty($product['gallery']) && is_array($product['gallery'])) {
            foreach ($product['gallery'] as $g) {
                $images[] = $base . $g['url'];
            }
        } elseif (!empty($product['images']) && is_array($product['images'])) {
            foreach ($product['images'] as $i => $img) {
                $seoName = \Core\Seo::imageSlug((string) $product['name'], $product['oem'] ?? null, $product['model'] ?? null, (int) $i);
                $images[] = $base . \Core\Seo::imageUrl((string) $img, $seoName);
            }
        }
        if (!$images) {
            $images[] = $base . '/assets/logo/logo.webp';
        }

        // ---------- دیدگاه‌ها ----------
        $commentCount = count($comments ?? []);
        $reviews = [];
        $sum = 0;
        foreach ($comments ?? [] as $c) {
            $rating = (float) ($c['rating'] ?? 5);
            $sum += $rating;
            $reviews[] = [
                '@type' => 'Review',
                'reviewRating' => [
                    '@type' => 'Rating',
                    'ratingValue' => (string) $rating,
                    'bestRating' => '5',
                    'worstRating' => '1',
                ],
                'author' => [
                    '@type' => 'Person',
                    'name' => !empty($c['name']) ? $c['name'] : 'خریدار قطعه',
                ],
                'datePublished' => !empty($c['created_at']) ? date('Y-m-d', strtotime($c['created_at'])) : date('Y-m-d'),
                'reviewBody' => \Core\Seo::clean($c['comment_text'] ?? ''),
            ];
        }

        $metaTitle = trim((string) ($product['meta_title'] ?? '')) ?: \Core\Seo::productTitle($product, $siteName);
        $metaDesc = trim((string) ($product['meta_description'] ?? '')) ?: \Core\Seo::productDescription($product, $siteName);

        // ---------- نان‌ریزه ----------
        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
        ];
        if (!empty($product['category'])) {
            $catData = $GLOBALS['part_categories'][$product['category']] ?? null;
            $catName = is_array($catData) ? ($catData['name'] ?? $product['category']) : ($catData ?: $product['category']);
            $crumbs[] = ['name' => $catName, 'url' => '/parts?category=' . rawurlencode((string) $product['category'])];
        }
        $crumbs[] = ['name' => $product['name'], 'url' => '/product/' . rawurlencode((string) $product['slug'])];

        // ---------- شناسه‌های قطعه ----------
        $oem = trim((string) ($product['oem'] ?? ''));
        $sku = $oem !== '' ? $oem : 'PRD-' . $product['id'];

        $modelData = $GLOBALS['car_models'][$product['model'] ?? ''] ?? ($product['model'] ?? '');
        $modelName = is_array($modelData) ? ($modelData['name'] ?? '') : (string) $modelData;

        $productNode = [
            '@type' => ['Product', 'IndividualProduct'],
            '@id' => $productUrl . '#product',
            'name' => $product['name'],
            'url' => $productUrl,
            'image' => $images,
            'description' => $metaDesc,
            'sku' => (string) $sku,
            'mpn' => (string) $sku,
            'productID' => 'oem:' . $sku,
            'category' => $GLOBALS['part_categories'][$product['category'] ?? '']['name'] ?? 'قطعات یدکی خودرو',
            'brand' => [
                '@type' => 'Brand',
                'name' => !empty($product['brand']) ? $product['brand'] : 'Toyota',
            ],
            'manufacturer' => [
                '@type' => 'Organization',
                'name' => !empty($product['brand']) ? $product['brand'] : 'Toyota',
            ],
            'itemCondition' => 'https://schema.org/NewCondition',
            'mainEntityOfPage' => ['@id' => $productUrl . '#webpage'],
        ];

        // ---- ویژگی‌های تخصصی خودرو: سازگاری قطعه با مدل‌ها ----
        if ($modelName !== '') {
            $productNode['isAccessoryOrSparePartFor'] = [
                '@type' => 'Product',
                'name' => 'تویوتا ' . $modelName,
            ];
            $productNode['audience'] = [
                '@type' => 'Audience',
                'name' => 'مالکان تویوتا ' . $modelName,
            ];
        }

        $additional = [];
        if ($oem !== '') {
            $additional[] = ['@type' => 'PropertyValue', 'name' => 'شماره فنی سازنده (OEM)', 'value' => $oem];
        }
        if ($modelName !== '') {
            $additional[] = ['@type' => 'PropertyValue', 'name' => 'خودرو سازگار', 'value' => 'تویوتا ' . $modelName];
        }
        $additional[] = [
            '@type' => 'PropertyValue',
            'name' => 'اصالت کالا',
            'value' => !empty($product['isGenuine']) ? 'جنیون پارت اصلی' : 'OEM وارداتی معتبر',
        ];
        $productNode['additionalProperty'] = $additional;

        // ---------- پیشنهاد فروش ----------
        // قیمت‌های دیتابیس به تومان‌اند؛ واحد رسمی قابل قبول گوگل برای ایران IRR است.
        $productNode['offers'] = [
            '@type' => 'Offer',
            '@id' => $productUrl . '#offer',
            'url' => $productUrl,
            'priceCurrency' => 'IRR',
            'price' => \Core\Seo::priceIRR((float) ($product['price'] ?? 0)),
            'priceValidUntil' => date('Y-m-d', strtotime('+6 months')),
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => \Core\Seo::availability($product),
            'seller' => ['@id' => $base . '/#organization'],
            'hasMerchantReturnPolicy' => [
                '@type' => 'MerchantReturnPolicy',
                'applicableCountry' => 'IR',
                'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
                'merchantReturnDays' => 7,
                'returnMethod' => 'https://schema.org/ReturnByMail',
                'returnFees' => 'https://schema.org/FreeReturn',
            ],
            'shippingDetails' => [
                '@type' => 'OfferShippingDetails',
                'shippingRate' => [
                    '@type' => 'MonetaryAmount',
                    'value' => '0',
                    'currency' => 'IRR',
                ],
                'shippingDestination' => [
                    ['@type' => 'DefinedRegion', 'addressCountry' => 'IR'],
                ],
                'deliveryTime' => [
                    '@type' => 'ShippingDeliveryTime',
                    'handlingTime' => ['@type' => 'QuantitativeValue', 'minValue' => 0, 'maxValue' => 1, 'unitCode' => 'DAY'],
                    'transitTime' => ['@type' => 'QuantitativeValue', 'minValue' => 1, 'maxValue' => 3, 'unitCode' => 'DAY'],
                ],
            ],
        ];

        if ($commentCount > 0) {
            $productNode['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) round($sum / $commentCount, 1),
                'reviewCount' => (string) $commentCount,
                'bestRating' => '5',
                'worstRating' => '1',
            ];
            $productNode['review'] = $reviews;
        }

        return \Core\Seo::graph([
            \Core\Seo::organizationNode($settings),
            \Core\Seo::websiteNode($settings),
            \Core\Seo::webPageNode($productUrl, $metaTitle, $metaDesc, $images[0] ?? null),
            \Core\Seo::breadcrumbNode($crumbs, $productUrl),
            $productNode,
        ]);
    }

    /**
     * مقالات آموزشی مرتبط با این قطعه — بخش «راهنمای فنی و سرویس» صفحه محصول.
     * ابتدا مقالاتی که مدیر صراحتاً به این محصول متصل کرده، سپس مقالات هم‌موضوع.
     */
    public static function getRelatedArticles(int $productId, int $limit = 3): array
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT a.id, a.title, a.slug, a.summary, a.reading_time, a.icon, a.category_label
                 FROM article_products ap
                 JOIN articles a ON a.id = ap.article_id
                 WHERE ap.product_id = ? AND a.status = 'published'
                 ORDER BY ap.sort_order, a.id DESC
                 LIMIT " . (int) $limit
            );
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** ذخیره امتیاز سئوی محاسبه‌شده */
    public static function saveSeoScore(int $productId, int $score): void
    {
        try {
            Database::getInstance()
                ->prepare('UPDATE products SET seo_score = ? WHERE id = ?')
                ->execute([max(0, min(100, $score)), $productId]);
        } catch (\Throwable $e) {
            // ستون هنوز مهاجرت نشده
        }
    }

    public static function findByOem($oemCode)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT name, is_genuine FROM products WHERE oem_code = ? LIMIT 1");
        $stmt->execute([$oemCode]);
        return $stmt->fetch();
    }

    public static function addComment($productId, $name, $rating, $text)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO product_comments (product_id, name, rating, comment_text, status) VALUES (?, ?, ?, ?, 'pending')");
        return $stmt->execute([$productId, $name, $rating, $text]);
    }
}