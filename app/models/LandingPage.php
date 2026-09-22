<?php

namespace App\models;

use Core\Database;
use PDO;
use Throwable;

/**
 * لندینگ‌پیج‌های اختصاصی سئو
 * ---------------------------------------------------------------------------
 * به‌جای هدایت کاربر و گوگل به آدرس‌های پارامتردار (/parts?category=x&model=y)
 * برای ترکیب‌های پرجستجو یک آدرس تمیز ساخته می‌شود:
 *     /parts/لوازم-یدکی-کمری-لنت-ترمز
 * که متن اختصاصی، H1 و متاتگ دستی خودش را دارد و محتوای تکراری تولید نمی‌کند.
 */
class LandingPage
{
    public static function findBySlug(string $slug): ?array
    {
        try {
            $db = Database::getInstance();
            $st = $db->prepare('SELECT * FROM seo_landing_pages WHERE slug = ? AND is_active = 1 LIMIT 1');
            $st->execute([urldecode(trim($slug))]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function all(bool $onlyActive = false): array
    {
        try {
            $sql = 'SELECT * FROM seo_landing_pages' . ($onlyActive ? ' WHERE is_active = 1' : '') . ' ORDER BY id DESC';
            return Database::getInstance()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function incrementViews(int $id): void
    {
        try {
            Database::getInstance()->prepare('UPDATE seo_landing_pages SET views = views + 1 WHERE id = ?')->execute([$id]);
        } catch (Throwable $e) {
            // بی‌اهمیت
        }
    }

    /** تبدیل رکورد لندینگ به فیلترهای کاتالوگ */
    public static function toFilters(array $page): array
    {
        $split = static fn($v) => array_values(array_filter(array_map('trim', explode(',', (string) $v)), 'strlen'));

        return [
            'categories' => $split($page['filter_category'] ?? ''),
            'models'     => $split($page['filter_model'] ?? ''),
            'brands'     => $split($page['filter_brand'] ?? ''),
            'q'          => '',
            'maxPrice'   => null,
            'inStock'    => '',
            'sort'       => 'newest',
        ];
    }
}
