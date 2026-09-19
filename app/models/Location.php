<?php

namespace App\models;

use Core\Database;
use PDO;

class Location
{
    /**
     * دریافت لیست تمام استان‌های فعال به ترتیب حروف الفبا
     */
    public static function getActiveProvinces(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, name, slug FROM provinces WHERE is_active = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * دریافت شهرهای فعال یک استان با شناسه استان
     */
    public static function getCitiesByProvinceId(int $provinceId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, name, slug FROM cities WHERE province_id = ? AND is_active = 1 ORDER BY name ASC");
        $stmt->execute([$provinceId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * دریافت شهرهای فعال یک استان با نام استان (پشتیبانی از آدرس‌های قبلی ذخیره‌شده)
     */
    public static function getCitiesByProvinceName(string $provinceName): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT c.id, c.name, c.slug 
                              FROM cities c 
                              INNER JOIN provinces p ON c.province_id = p.id 
                              WHERE p.name = ? AND c.is_active = 1 AND p.is_active = 1 
                              ORDER BY c.name ASC");
        $stmt->execute([trim($provinceName)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * اعتبارسنجی امنیتی: بررسی تطابق استان و شهر ارسال شده در دیتابیس
     */
    public static function validateProvinceAndCity(string $provinceName, string $cityName): bool
    {
        $provinceName = trim($provinceName);
        $cityName = trim($cityName);

        if (empty($provinceName) || empty($cityName)) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT c.id 
                              FROM cities c 
                              INNER JOIN provinces p ON c.province_id = p.id 
                              WHERE p.name = ? AND c.name = ? AND c.is_active = 1 AND p.is_active = 1 
                              LIMIT 1");
        $stmt->execute([$provinceName, $cityName]);
        return (bool) $stmt->fetchColumn();
    }
}