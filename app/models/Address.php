<?php

namespace App\models;

use Core\Database;
use PDO;

class Address
{
    public static function getByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, province_city, address_detail, postal_code, recipient_name, recipient_phone, is_default, created_at
             FROM user_addresses
             WHERE user_id = ?
             ORDER BY is_default DESC, id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function saveIfNotExists(
        int $userId,
        string $provinceCity,
        string $addressDetail,
        string $postalCode = '',
        string $recipientName = '',
        string $recipientPhone = ''
    ): void {
        $db = Database::getInstance();
        $chk = $db->prepare(
            "SELECT id FROM user_addresses WHERE user_id = ? AND address_detail = ? LIMIT 1"
        );
        $chk->execute([$userId, $addressDetail]);

        if (!$chk->fetch()) {
            $countStmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $isFirst = ((int) $countStmt->fetchColumn() === 0) ? 1 : 0;

            $stmt = $db->prepare(
                "INSERT INTO user_addresses
                    (user_id, province_city, address_detail, postal_code, recipient_name, recipient_phone, is_default, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $provinceCity,
                $addressDetail,
                $postalCode,
                $recipientName !== '' ? $recipientName : null,
                $recipientPhone !== '' ? $recipientPhone : null,
                $isFirst,
            ]);
        }
    }

    public static function create(int $userId, array $data): array
    {
        $provinceCity = mb_substr(trim((string) ($data['province_city'] ?? '')), 0, 120, 'UTF-8');
        $addressDetail = mb_substr(trim((string) ($data['address_detail'] ?? '')), 0, 300, 'UTF-8');
        $postalCode = preg_replace('/\D/', '', (string) ($data['postal_code'] ?? ''));
        $recipientName = mb_substr(trim((string) ($data['recipient_name'] ?? '')), 0, 100, 'UTF-8');
        $recipientPhone = trim((string) ($data['recipient_phone'] ?? ''));
        $setDefault = !empty($data['is_default']);

        if ($provinceCity === '' || mb_strlen($addressDetail, 'UTF-8') < 6) {
            return ['success' => false, 'message' => 'استان/شهر و نشانی دقیق الزامی است.'];
        }

        if ($postalCode !== '' && !preg_match('/^[0-9]{10}$/', $postalCode)) {
            return ['success' => false, 'message' => 'کد پستی باید دقیقاً ۱۰ رقم باشد.'];
        }

        if ($recipientPhone !== '' && !preg_match('/^09[0-9]{9}$/', $recipientPhone)) {
            return ['success' => false, 'message' => 'شماره موبایل نامعتبر است.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $countStmt = $db->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $count = (int) $countStmt->fetchColumn();

            if ($count >= 10) {
                $db->rollBack();
                return ['success' => false, 'message' => 'حداکثر ۱۰ آدرس قابل ثبت است.'];
            }

            $isDefault = ($count === 0 || $setDefault) ? 1 : 0;

            if ($isDefault) {
                $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")
                    ->execute([$userId]);
            }

            $stmt = $db->prepare(
                "INSERT INTO user_addresses
                    (user_id, province_city, address_detail, postal_code, recipient_name, recipient_phone, is_default, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $provinceCity,
                $addressDetail,
                $postalCode !== '' ? $postalCode : null,
                $recipientName !== '' ? $recipientName : null,
                $recipientPhone !== '' ? $recipientPhone : null,
                $isDefault,
            ]);

            $id = (int) $db->lastInsertId();
            $db->commit();

            return [
                'success' => true,
                'message' => 'آدرس با موفقیت ثبت شد.',
                'address' => self::findById($id, $userId),
            ];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Address create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت آدرس.'];
        }
    }

    public static function update(int $id, int $userId, array $data): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'آدرس یافت نشد.'];
        }

        $provinceCity = mb_substr(trim((string) ($data['province_city'] ?? $existing['province_city'])), 0, 120, 'UTF-8');
        $addressDetail = mb_substr(trim((string) ($data['address_detail'] ?? $existing['address_detail'])), 0, 300, 'UTF-8');
        $postalCode = preg_replace('/\D/', '', (string) ($data['postal_code'] ?? $existing['postal_code'] ?? ''));
        $recipientName = mb_substr(trim((string) ($data['recipient_name'] ?? $existing['recipient_name'] ?? '')), 0, 100, 'UTF-8');
        $recipientPhone = trim((string) ($data['recipient_phone'] ?? $existing['recipient_phone'] ?? ''));

        if ($provinceCity === '' || mb_strlen($addressDetail, 'UTF-8') < 6) {
            return ['success' => false, 'message' => 'استان/شهر و نشانی دقیق الزامی است.'];
        }

        if ($postalCode !== '' && !preg_match('/^[0-9]{10}$/', $postalCode)) {
            return ['success' => false, 'message' => 'کد پستی باید دقیقاً ۱۰ رقم باشد.'];
        }

        if ($recipientPhone !== '' && !preg_match('/^09[0-9]{9}$/', $recipientPhone)) {
            return ['success' => false, 'message' => 'شماره موبایل نامعتبر است.'];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "UPDATE user_addresses
             SET province_city = ?, address_detail = ?, postal_code = ?, recipient_name = ?, recipient_phone = ?
             WHERE id = ? AND user_id = ?"
        );
        $ok = $stmt->execute([
            $provinceCity,
            $addressDetail,
            $postalCode !== '' ? $postalCode : null,
            $recipientName !== '' ? $recipientName : null,
            $recipientPhone !== '' ? $recipientPhone : null,
            $id,
            $userId,
        ]);

        return $ok
            ? ['success' => true, 'message' => 'آدرس به‌روزرسانی شد.', 'address' => self::findById($id, $userId)]
            : ['success' => false, 'message' => 'خطا در به‌روزرسانی آدرس.'];
    }

    public static function delete(int $id, int $userId): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'آدرس یافت نشد.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);

            if ((int) $existing['is_default'] === 1) {
                $next = $db->prepare(
                    "SELECT id FROM user_addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1"
                );
                $next->execute([$userId]);
                $nextId = $next->fetchColumn();
                if ($nextId) {
                    $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")
                        ->execute([$nextId, $userId]);
                }
            }

            $db->commit();
            return ['success' => true, 'message' => 'آدرس حذف شد.'];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Address delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در حذف آدرس.'];
        }
    }

    public static function setDefault(int $id, int $userId): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'آدرس یافت نشد.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();
            $db->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?")
                ->execute([$id, $userId]);
            $db->commit();
            return ['success' => true, 'message' => 'آدرس پیش‌فرض تنظیم شد.'];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در تنظیم آدرس پیش‌فرض.'];
        }
    }
}
