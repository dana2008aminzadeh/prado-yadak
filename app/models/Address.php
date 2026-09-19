<?php

namespace App\models;

use Core\Database;
use PDO;

class Address
{
    /**
     * دریافت لیست آدرس‌های یک کاربر به ترتیب آدرس پیش‌فرض
     */
    public static function getByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, province_city, address_detail, postal_code, is_default 
                              FROM user_addresses 
                              WHERE user_id = ? 
                              ORDER BY is_default DESC, id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * بررسی وجود و ذخیره هوشمند آدرس جدید کاربر
     */
    public static function saveIfNotExists(int $userId, string $provinceCity, string $addressDetail, string $postalCode = ''): void
    {
        $db = Database::getInstance();
        $chk = $db->prepare("SELECT id FROM user_addresses WHERE user_id = ? AND address_detail = ? LIMIT 1");
        $chk->execute([$userId, $addressDetail]);

        if (!$chk->fetch()) {
            $countStmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $isFirst = ((int) $countStmt->fetchColumn() === 0) ? 1 : 0;

            $stmt = $db->prepare("INSERT INTO user_addresses (user_id, province_city, address_detail, postal_code, is_default, created_at) 
                                  VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $provinceCity, $addressDetail, $postalCode, $isFirst]);
        }
    }
}