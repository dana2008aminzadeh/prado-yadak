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

        // ۱. جستجوی متنی (نام یا کد قطعه)
        if (!empty($filters['q'])) {
            $conditions[] = "(p.name LIKE ? OR p.oem_code LIKE ?)";
            $params[] = '%' . $filters['q'] . '%';
            $params[] = '%' . $filters['q'] . '%';
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
            $params[] = (float)$filters['maxPrice'];
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
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
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
            'total' => (int)$totalCount,
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
}