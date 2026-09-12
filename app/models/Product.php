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
            $images = !empty($r['telegram_photo_id']) ? json_decode($r['telegram_photo_id'], true) : [];
            $mapped[] = [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'category' => $r['category_slug'],
                'price' => (float) $r['price'],
                'oem' => $r['oem_code'],
                'model' => $r['car_model'],
                'brand' => $r['brand'],
                'isGenuine' => (bool) $r['is_genuine'],
                'inStock' => (bool) $r['in_stock'],
                'desc' => $r['description'],
                'images' => is_array($images) ? $images : []
            ];
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

        $images = !empty($r['telegram_photo_id']) ? json_decode($r['telegram_photo_id'], true) : [];
        return [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'slug' => $r['slug'],
            'category' => $r['category_slug'],
            'price' => (float) $r['price'],
            'oem' => $r['oem_code'],
            'model' => $r['car_model'],
            'brand' => $r['brand'],
            'isGenuine' => (bool) $r['is_genuine'],
            'inStock' => (bool) $r['in_stock'],
            'desc' => $r['description'],
            'images' => is_array($images) ? $images : []
        ];
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

    public static function generateSchema($product, $comments)
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
        $hostUrl = $protocol . "://" . $host;
        $productUrl = $hostUrl . "/product/" . urlencode($product['slug']);

        // اگر محصول تصویری نداشت، آدرس کامل لوگوی پیش‌فرض ست می‌شود تا خطای بحرانی اسکیمای گوگل برطرف شود
        $images = [];
        if (!empty($product['images']) && is_array($product['images'])) {
            foreach ($product['images'] as $img) {
                $images[] = $hostUrl . "/image?id=" . urlencode($img);
            }
        } else {
            $images[] = $hostUrl . "/assets/logo/logo.webp";
        }

        $schemaAvgRating = 5.0;
        $schemaCommentCount = count($comments ?? []);
        if ($schemaCommentCount > 0) {
            $schemaSum = 0;
            foreach ($comments as $c) {
                $schemaSum += (float) ($c['rating'] ?? 5);
            }
            $schemaAvgRating = round($schemaSum / $schemaCommentCount, 1);
        }

        $schemaProduct = [
            "@context" => "https://schema.org",
            "@type" => "Product",
            "name" => $product['name'],
            "image" => $images,
            "description" => strip_tags($product['desc'] ?? ''),
            "sku" => (string) (!empty($product['oem']) ? $product['oem'] : $product['id']),
            "brand" => [
                "@type" => "Brand",
                "name" => !empty($product['brand']) ? $product['brand'] : 'تویوتا'
            ],
            "offers" => [
                "@type" => "Offer",
                "url" => $productUrl,
                "priceCurrency" => "IRR",
                "price" => (float) ($product['price'] ?? 0) * 10,
                "availability" => !empty($product['inStock']) ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
                "itemCondition" => "https://schema.org/NewCondition"
            ]
        ];

        if ($schemaCommentCount > 0) {
            $schemaProduct["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $schemaAvgRating,
                "reviewCount" => $schemaCommentCount,
                "bestRating" => "5",
                "worstRating" => "1"
            ];
        }

        $schemaBreadcrumb = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                ["@type" => "ListItem", "position" => 1, "name" => "صفحه اصلی", "item" => $hostUrl . "/"],
                ["@type" => "ListItem", "position" => 2, "name" => "کاتالوگ قطعات", "item" => $hostUrl . "/parts"],
                ["@type" => "ListItem", "position" => 3, "name" => $product['name'], "item" => $productUrl]
            ]
        ];

        $schemaStr = "<script type=\"application/ld+json\">\n" . json_encode($schemaProduct, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n</script>\n";
        $schemaStr .= "<script type=\"application/ld+json\">\n" . json_encode($schemaBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n</script>";

        return $schemaStr;
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