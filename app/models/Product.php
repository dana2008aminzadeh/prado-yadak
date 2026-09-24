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

        // محصولات متوقف‌شده در فهرست‌ها ظاهر نمی‌شوند؛ صفحه مستقیم آن‌ها برای
        // noindex یا انتقال به جایگزین همچنان توسط findBySlug قابل دسترس است.
        if (self::hasColumn('products', 'lifecycle_status')) {
            $conditions[] = "COALESCE(p.lifecycle_status, 'active') <> 'discontinued'";
        }
        if (!empty($filters['excludeIds']) && is_array($filters['excludeIds'])) {
            $excludeIds = array_values(array_unique(array_filter(array_map('intval', $filters['excludeIds']))));
            if ($excludeIds) {
                $conditions[] = 'p.id NOT IN (' . implode(',', array_fill(0, count($excludeIds), '?')) . ')';
                array_push($params, ...$excludeIds);
            }
        }

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

    /** واکشی مستقیم برای URLهای قدیمی/مدیریتی، حتی اگر محصول discontinued باشد. */
    public static function findByIdIncludingDiscontinued($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'SELECT p.*, c.slug AS category_slug
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = ? LIMIT 1'
        );
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::mapRow($row, true) : null;
    }

    public static function findBySlug($slug)
    {
        $db = Database::getInstance();
        $sql = "SELECT p.*, c.slug as category_slug 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([rawurldecode($slug)]);
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
        $images = $gallery ? array_column($gallery, 'identifier') : array_values(array_filter($legacy, 'is_string'));

        $modelData = $GLOBALS['car_models'][$r['car_model'] ?? ''] ?? ($r['car_model'] ?? '');
        $modelName = is_array($modelData) ? ($modelData['name'] ?? '') : (string) $modelData;
        $primaryIdentifier = $gallery[0]['identifier'] ?? ($images[0] ?? '');
        $imageUrl = $gallery[0]['url'] ?? ($primaryIdentifier !== ''
            ? \Core\Seo::imageUrl(
                (string) $primaryIdentifier,
                \Core\Seo::imageSlug((string) $r['name'], $r['oem_code'] ?? null, $modelName ?: null)
            )
            : '');
        $imageAlt = $gallery[0]['alt'] ?? ($primaryIdentifier !== ''
            ? \Core\Seo::suggestAlt((string) $r['name'], $modelName ?: null, $r['oem_code'] ?? null)
            : '');

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
            'image_url' => $imageUrl,
            'image_alt' => $imageAlt,
            'gallery' => $gallery,
            'technicalSpecifications' => $withGallery ? self::getAttributes((int) $r['id']) : [],
            'vehicles' => $withGallery ? self::getCompatibleVehicles((int) $r['id']) : [],
            // چرخه عمر: ناموجودی موقت صفحه را نگه می‌دارد؛ discontinued از
            // Sitemap حذف و در صورت داشتن replacement با 301 منتقل می‌شود.
            'lifecycle_status' => $r['lifecycle_status'] ?? 'active',
            'replacement_product_id' => isset($r['replacement_product_id']) ? (int) $r['replacement_product_id'] : null,
            'sitemap_policy' => $r['sitemap_policy'] ?? 'auto',
            'discontinued' => ($r['lifecycle_status'] ?? 'active') === 'discontinued',
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

            $alt = \Core\Seo::sanitizeAltText((string) ($row['alt_text'] ?? ''));
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

    /** خودروهای واقعی ثبت‌شده در پنل؛ در متا و جدول سازگاری صفحه نمایش داده می‌شوند. */
    public static function getCompatibleVehicles(int $productId): array
    {
        try {
            $stmt = Database::getInstance()->prepare(
                'SELECT cm.name, pv.year_from, pv.year_to, pv.trim_name
                 FROM product_vehicles pv
                 JOIN car_models cm ON cm.id = pv.car_model_id
                 WHERE pv.product_id = ? ORDER BY cm.name, pv.year_from LIMIT 12'
            );
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** مشخصات فنی سفارشی ثبت‌شده در پنل. */
    public static function getAttributes(int $productId): array
    {
        try {
            $stmt = Database::getInstance()->prepare(
                'SELECT attr_key, attr_value FROM product_attributes
                 WHERE product_id = ? AND attr_key <> \'\' AND attr_value <> \'\'
                 ORDER BY sort_order, id'
            );
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * فقط نظرات approved در صفحه قابل مشاهده‌اند. verified_purchase مستقل محاسبه
     * می‌شود تا Review Schema هرگز از نظر دستی/قدیمیِ فاقد خرید تحویل‌شده ساخته نشود.
     */
    public static function getComments($productId)
    {
        $db = Database::getInstance();
        if (self::hasColumn('product_comments', 'user_id')) {
            $stmt = $db->prepare(
                "SELECT pc.*,
                        CASE WHEN pc.user_id IS NOT NULL AND EXISTS (
                            SELECT 1 FROM orders o
                            JOIN order_items oi ON oi.order_id = o.id
                            WHERE o.user_id = pc.user_id AND oi.product_id = pc.product_id
                              AND o.status = 'delivered'
                        ) THEN 1 ELSE 0 END AS verified_purchase
                 FROM product_comments pc
                 WHERE pc.product_id = ? AND pc.status = 'approved'
                 ORDER BY pc.created_at DESC"
            );
        } else {
            // دیدگاه‌های قدیمی قابل نمایش‌اند، اما چون قابل انتساب به خریدار نیستند
            // وارد Review Schema و نشان «خریدار» نمی‌شوند.
            $stmt = $db->prepare(
                "SELECT pc.*, 0 AS verified_purchase
                 FROM product_comments pc
                 WHERE pc.product_id = ? AND pc.status = 'approved'
                 ORDER BY pc.created_at DESC"
            );
        }
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
     * محصولات واقعاً مرتبط با امتیازدهی OEM، خودرو، دسته و برند.
     * محصول جاری حذف می‌شود و رکوردهای تکراریِ OEM+brand نیز یک بار نمایش دارند.
     */
    public static function getSimilar(array $product, int $limit = 4): array
    {
        $id = (int) ($product['id'] ?? 0);
        if ($id <= 0) {
            return [];
        }

        $db = Database::getInstance();
        $conditions = [];
        $scoreParts = [];
        $selectFlags = [];
        $params = [];

        $oem = trim((string) ($product['oem'] ?? ''));
        if ($oem !== '') {
            $conditions[] = 'p.oem_code = ?';
            $params[] = $oem;
            $scoreParts[] = '(CASE WHEN p.oem_code = ' . $db->quote($oem) . ' THEN 12 ELSE 0 END)';
            $selectFlags[] = '(p.oem_code = ' . $db->quote($oem) . ') AS same_oem';
        } else {
            $selectFlags[] = '0 AS same_oem';
        }

        $model = trim((string) ($product['model'] ?? ''));
        if ($model !== '') {
            $conditions[] = 'p.car_model = ?';
            $params[] = $model;
            $scoreParts[] = '(CASE WHEN p.car_model = ' . $db->quote($model) . ' THEN 8 ELSE 0 END)';
            $selectFlags[] = '(p.car_model = ' . $db->quote($model) . ') AS same_model';
        } else {
            $selectFlags[] = '0 AS same_model';
        }

        $category = trim((string) ($product['category'] ?? ''));
        if ($category !== '') {
            $conditions[] = 'c.slug = ?';
            $params[] = $category;
            $scoreParts[] = '(CASE WHEN c.slug = ' . $db->quote($category) . ' THEN 6 ELSE 0 END)';
            $selectFlags[] = '(c.slug = ' . $db->quote($category) . ') AS same_category';
        } else {
            $selectFlags[] = '0 AS same_category';
        }

        $brand = trim((string) ($product['brand'] ?? ''));
        if ($brand !== '') {
            $conditions[] = 'LOWER(p.brand) = LOWER(?)';
            $params[] = $brand;
            $scoreParts[] = '(CASE WHEN LOWER(p.brand) = LOWER(' . $db->quote($brand) . ') THEN 3 ELSE 0 END)';
            $selectFlags[] = '(LOWER(p.brand) = LOWER(' . $db->quote($brand) . ')) AS same_brand';
        } else {
            $selectFlags[] = '0 AS same_brand';
        }

        // روابط چندبه‌چند خودرو دقیق‌تر از ستون legacy car_model هستند.
        try {
            $hasVehicles = (bool) $db->query(
                'SELECT COUNT(*) FROM product_vehicles WHERE product_id = ' . $id
            )->fetchColumn();
        } catch (\Throwable $e) {
            $hasVehicles = false;
        }
        if ($hasVehicles) {
            $vehicleMatch = "EXISTS (
                SELECT 1 FROM product_vehicles current_pv
                JOIN product_vehicles candidate_pv ON candidate_pv.car_model_id = current_pv.car_model_id
                WHERE current_pv.product_id = {$id} AND candidate_pv.product_id = p.id
            )";
            $conditions[] = $vehicleMatch;
            $scoreParts[] = "(CASE WHEN {$vehicleMatch} THEN 10 ELSE 0 END)";
            $selectFlags[] = "({$vehicleMatch}) AS same_vehicle";
        } else {
            $selectFlags[] = '0 AS same_vehicle';
        }

        if (!$conditions) {
            return [];
        }

        $lifecycle = self::hasColumn('products', 'lifecycle_status')
            ? "AND COALESCE(p.lifecycle_status, 'active') <> 'discontinued'" : '';
        $fetchLimit = max($limit * 5, 20);
        $scoreSql = implode(' + ', $scoreParts ?: ['0']);
        $sql = "SELECT p.*, c.slug AS category_slug, {$scoreSql} AS similarity_score,
                       " . implode(', ', $selectFlags) . "
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id <> ? {$lifecycle} AND (" . implode(' OR ', $conditions) . ")
                ORDER BY similarity_score DESC, p.in_stock DESC, p.id DESC
                LIMIT {$fetchLimit}";

        // شناسه مربوط به WHERE پیش از پارامترهای condition قرار دارد.
        $stmt = $db->prepare($sql);
        $stmt->execute([$id, ...$params]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        $seen = [];
        foreach ($rows as $row) {
            $rowOem = trim((string) ($row['oem_code'] ?? ''));
            $dedupeKey = mb_strtolower(
                $rowOem !== ''
                    ? $rowOem . '|' . trim((string) ($row['brand'] ?? ''))
                    : trim((string) ($row['name'] ?? '')) . '|' . trim((string) ($row['car_model'] ?? '')),
                'UTF-8'
            );
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $reasons = [];
            if (!empty($row['same_oem'])) $reasons[] = 'OEM یکسان';
            if (!empty($row['same_vehicle']) || !empty($row['same_model'])) $reasons[] = 'خودروی سازگار';
            if (!empty($row['same_category'])) $reasons[] = 'دسته یکسان';
            if (!empty($row['same_brand'])) $reasons[] = 'برند یکسان';

            $mapped = self::mapRow($row);
            $mapped['similarity_reason'] = implode('، ', array_values(array_unique($reasons)));
            $mapped['similarity_score'] = (int) $row['similarity_score'];
            $items[] = $mapped;
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    /** جدیدترین محصولات بدون تکرار محصول جاری و کارت‌های مشابه. */
    public static function getNewestExcluding(array $ids, int $limit = 4): array
    {
        return self::search(['excludeIds' => $ids, 'sort' => 'newest'], 1, $limit)['items'];
    }

    /**
     * تولید داده‌های ساختاریافته محصول به صورت یک گراف یکپارچه (@graph).
     * ---------------------------------------------------------------------
     * به‌جای چند اسکریپت مجزا، فروشگاه، وب‌سایت، صفحه، نان‌ریزه، محصول،
     * پیشنهاد فروش و دیدگاه‌ها همگی در یک بلوک به هم گره می‌خورند.
     * ویژگی‌های تخصصی خودرو (برند سازگار، MPN، شماره فنی) نیز درج می‌شود تا
     * قابلیت‌های Merchant Listings و نتایج خرید گوگل فعال شوند.
     */
    public static function generateSchema($product, $comments = [], ?string $pageTitle = null, ?string $pageDescription = null)
    {
        $settings = $GLOBALS['settings'] ?? [];
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $base = \Core\Seo::base();
        $productUrl = $base . '/product/' . rawurlencode((string) $product['slug']);

        // ---------- تصاویر با آدرس سئوشده ----------
        $images = [];
        if (!empty($product['gallery']) && is_array($product['gallery'])) {
            foreach ($product['gallery'] as $g) {
                if (!empty($g['url'])) {
                    $images[] = \Core\Seo::absolute((string) $g['url']);
                }
            }
        } elseif (!empty($product['images']) && is_array($product['images'])) {
            foreach ($product['images'] as $i => $img) {
                $seoName = \Core\Seo::imageSlug((string) $product['name'], $product['oem'] ?? null, $product['model'] ?? null, (int) $i);
                $url = \Core\Seo::imageUrl((string) $img, $seoName);
                if ($url !== '') {
                    $images[] = \Core\Seo::absolute($url);
                }
            }
        }
        $images = array_values(array_unique($images));

        // ---------- دیدگاه‌ها ----------
        // تنها نظر approved + قابل مشاهده + منتسب به خرید تحویل‌شده وارد Schema می‌شود.
        $reviews = [];
        $sum = 0.0;
        foreach ($comments ?? [] as $c) {
            $status = (string) ($c['status'] ?? '');
            $body = \Core\Seo::clean($c['comment_text'] ?? '');
            $rating = (float) ($c['rating'] ?? 0);
            if ($status !== 'approved' || empty($c['verified_purchase']) || $body === '' || $rating < 1 || $rating > 5) {
                continue;
            }
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
                'reviewBody' => $body,
            ];
        }
        $commentCount = count($reviews);

        $metaTitle = $pageTitle ?? (\Core\Seo::clean((string) ($product['meta_title'] ?? '')) ?: \Core\Seo::productTitle($product, $siteName));
        $metaDesc = $pageDescription ?? \Core\Seo::metaDescription(
            (string) ($product['meta_description'] ?? ''), \Core\Seo::productDescription($product, $siteName), $product, $siteName
        );

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
        // sku یک شناسه داخلی فروشگاه است و همیشه می‌تواند وجود داشته باشد،
        // اما mpn/productID طبق تعریف Schema.org باید شناسه‌ی واقعیِ سازنده
        // (OEM) باشند؛ ساختن آن‌ها از یک شناسه ساختگی «PRD-{id}» یک ادعای
        // غیرقابل‌اثبات به گوگل مرچنت می‌دهد و ریسک رد Merchant Listing یا
        // حذف Rich Result را افزایش می‌دهد. در نبود OEM واقعی، این دو کلید
        // اصلاً در گراف درج نمی‌شوند.
        $oem = trim((string) ($product['oem'] ?? ''));
        $sku = $oem !== '' ? $oem : 'PRD-' . $product['id'];

        $modelData = $GLOBALS['car_models'][$product['model'] ?? ''] ?? ($product['model'] ?? '');
        $modelName = is_array($modelData) ? ($modelData['name'] ?? '') : (string) $modelData;

        $productNode = [
            '@type' => ['Product', 'IndividualProduct'],
            '@id' => $productUrl . '#product',
            'name' => $product['name'],
            'url' => $productUrl,
            'description' => $metaDesc,
            'sku' => (string) $sku,
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
        if ($oem !== '') {
            $productNode['mpn'] = $oem;
            $productNode['productID'] = 'oem:' . $oem;
        }
        // لوگوی فروشگاه هرگز به‌عنوان تصویر محصول بدون عکس اعلام نمی‌شود.
        if ($images) {
            $productNode['image'] = $images;
        }

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

    public static function addComment($productId, $userId, $name, $rating, $text)
    {
        $db = Database::getInstance();
        $productId = (int) $productId;
        $userId = (int) $userId;
        $rating = max(1, min(5, (int) $rating));
        $name = mb_substr(trim((string) $name), 0, 120, 'UTF-8');
        $text = mb_substr(trim((string) $text), 0, 3000, 'UTF-8');

        if (!self::canUserComment($productId, $userId)) {
            return false;
        }

        if (self::hasColumn('product_comments', 'user_id')) {
            // برای هر خرید یک نظر فعال کافی است؛ از Review spam و شمارش چندباره جلوگیری می‌شود.
            $existing = $db->prepare(
                "SELECT 1 FROM product_comments
                 WHERE product_id = ? AND user_id = ? AND status IN ('pending', 'approved') LIMIT 1"
            );
            $existing->execute([$productId, $userId]);
            if ($existing->fetchColumn()) {
                return false;
            }

            $verifiedColumn = self::hasColumn('product_comments', 'verified_purchase')
                ? ', verified_purchase' : '';
            $verifiedValue = self::hasColumn('product_comments', 'verified_purchase') ? ', 1' : '';
            $stmt = $db->prepare(
                "INSERT INTO product_comments
                    (product_id, user_id, name, rating, comment_text, status{$verifiedColumn})
                 VALUES (?, ?, ?, ?, ?, 'pending'{$verifiedValue})"
            );
            return $stmt->execute([$productId, $userId, $name, $rating, $text]);
        }

        // سازگاری موقت تا اجرای migration؛ این رکورد بدون user_id وارد Schema نمی‌شود.
        $stmt = $db->prepare(
            "INSERT INTO product_comments (product_id, name, rating, comment_text, status)
             VALUES (?, ?, ?, ?, 'pending')"
        );
        return $stmt->execute([$productId, $name, $rating, $text]);
    }

    /** بررسی idempotent وجود ستون برای سازگاری پیش و پس از migration. */
    private static function hasColumn(string $table, string $column): bool
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
        } catch (\Throwable $e) {
            return $cache[$key] = false;
        }
    }
}
