<?php
namespace App\models;

use Core\Database;
use PDO;

class Article
{
    public static function getAll($status = 'published', $category = null, $limit = null)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM articles WHERE status = ?";
        $params = [$status];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        $sql .= " ORDER BY created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM articles WHERE id = ? AND status = 'published' LIMIT 1");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findBySlug($slug)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([rawurldecode($slug)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getRelated($categoryId, $excludeId, $limit = 2)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT id, title, slug, summary, reading_time, icon, cover_image, category_label 
            FROM articles 
            WHERE category = ? AND id != ? AND status = 'published' 
            ORDER BY id DESC 
            LIMIT " . (int) $limit
        );
        $stmt->execute([$categoryId, (int) $excludeId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // در صورت نبودن مقالات هم‌دسته، جدیدترین‌ها را بازمی‌گرداند
        if (count($results) < $limit) {
            $remaining = $limit - count($results);
            $stmt = $db->prepare("
                SELECT id, title, slug, summary, reading_time, icon, cover_image, category_label 
                FROM articles 
                WHERE id != ? AND status = 'published' 
                ORDER BY id DESC 
                LIMIT " . (int) $remaining
            );
            $stmt->execute([(int) $excludeId]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return $results;
    }

    public static function incrementViews($id)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        return $stmt->execute([(int) $id]);
    }

    /**
     * محصولات مرتبط با مقاله (ساختار سیلو).
     * کارت خرید این قطعات همراه با قیمت و موجودی داخل بدنه مقاله نمایش داده می‌شود.
     */
    public static function getRelatedProducts($articleId, $limit = 4)
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                "SELECT p.id, p.name, p.slug, p.price, p.oem_code, p.brand, p.in_stock,
                        p.stock_qty, p.is_genuine, p.car_model, p.telegram_photo_id
                 FROM article_products ap
                 JOIN products p ON p.id = ap.product_id
                 WHERE ap.article_id = ?
                 ORDER BY ap.sort_order, ap.id
                 LIMIT " . (int) $limit
            );
            $stmt->execute([(int) $articleId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            // محصول متوقف‌شده نباید در کارت خرید مقاله بازگردد.
            if (!Product::findById((int) $r['id'])) {
                continue;
            }
            $gallery = Product::getGallery((int) $r['id']);
            $primary = $gallery[0] ?? null;

            $legacyPhoto = (string) ($r['telegram_photo_id'] ?? '');
            $legacyImages = $legacyPhoto !== '' ? json_decode($legacyPhoto, true) : [];
            $legacyImages = is_array($legacyImages) ? $legacyImages : ($legacyPhoto !== '' ? [$legacyPhoto] : []);
            $fallbackImage = !empty($legacyImages[0])
                ? \Core\Seo::imageUrl((string) $legacyImages[0], \Core\Seo::imageSlug((string) $r['name'], $r['oem_code'] ?? null, $r['car_model'] ?? null))
                : '';

            $out[] = [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'price' => (float) $r['price'],
                'oem' => $r['oem_code'],
                'brand' => $r['brand'],
                'inStock' => (bool) $r['in_stock'],
                'isGenuine' => (bool) $r['is_genuine'],
                'image' => $primary['url'] ?? $fallbackImage,
                'alt' => $primary['alt'] ?? \Core\Seo::suggestAlt((string) $r['name'], null, $r['oem_code'] ?? null),
            ];
        }
        return $out;
    }

    /** تعداد محصولات متصل (برای موتور سنجش سئو) */
    public static function countRelatedProducts($articleId): int
    {
        try {
            $st = Database::getInstance()->prepare('SELECT COUNT(*) FROM article_products WHERE article_id = ?');
            $st->execute([(int) $articleId]);
            return (int) $st->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public static function getPopular($limit = 3)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, title, slug, views FROM articles WHERE status = 'published' ORDER BY views DESC, id DESC LIMIT " . (int) $limit);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
