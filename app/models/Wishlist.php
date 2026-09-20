<?php

namespace App\models;

use Core\Database;
use PDO;

class Wishlist
{
    public static function getByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT w.id AS wishlist_id, w.created_at AS wishlisted_at,
                    p.id, p.name, p.slug, p.oem_code, p.price, p.brand,
                    p.is_genuine, p.in_stock, p.telegram_photo_id, p.car_model
             FROM wishlists w
             INNER JOIN products p ON w.product_id = p.id
             WHERE w.user_id = ?
             ORDER BY w.id DESC"
        );
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        foreach ($rows as $r) {
            $images = !empty($r['telegram_photo_id']) ? json_decode($r['telegram_photo_id'], true) : [];
            $items[] = [
                'wishlist_id' => (int) $r['wishlist_id'],
                'wishlisted_at' => $r['wishlisted_at'],
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'oem' => $r['oem_code'],
                'price' => (float) $r['price'],
                'brand' => $r['brand'],
                'isGenuine' => (bool) $r['is_genuine'],
                'inStock' => (bool) $r['in_stock'],
                'model' => $r['car_model'],
                'images' => is_array($images) ? $images : [],
            ];
        }

        return $items;
    }

    public static function count(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function exists(int $userId, int $productId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ? LIMIT 1"
        );
        $stmt->execute([$userId, $productId]);
        return (bool) $stmt->fetch();
    }

    public static function add(int $userId, int $productId): array
    {
        if ($productId <= 0) {
            return ['success' => false, 'message' => 'شناسه محصول نامعتبر است.'];
        }

        $product = Product::findById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'محصول یافت نشد.'];
        }

        if (self::exists($userId, $productId)) {
            return ['success' => true, 'message' => 'این قطعه قبلاً در لیست نشان‌شده‌هاست.', 'already' => true];
        }

        $db = Database::getInstance();

        $count = self::count($userId);
        if ($count >= 50) {
            return ['success' => false, 'message' => 'حداکثر ۵۰ قطعه در نشان‌شده‌ها مجاز است.'];
        }

        $stmt = $db->prepare(
            "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->execute([$userId, $productId]);

        return ['success' => true, 'message' => 'قطعه به نشان‌شده‌ها اضافه شد.', 'count' => $count + 1];
    }

    public static function remove(int $userId, int $productId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);

        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'آیتم در نشان‌شده‌ها یافت نشد.'];
        }

        return ['success' => true, 'message' => 'از نشان‌شده‌ها حذف شد.', 'count' => self::count($userId)];
    }

    public static function toggle(int $userId, int $productId): array
    {
        if (self::exists($userId, $productId)) {
            $result = self::remove($userId, $productId);
            $result['in_wishlist'] = false;
            return $result;
        }

        $result = self::add($userId, $productId);
        $result['in_wishlist'] = !empty($result['success']);
        return $result;
    }
}
