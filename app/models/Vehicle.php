<?php

namespace App\models;

use Core\Database;
use PDO;

class Vehicle
{
    public static function getByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, model_name, model_year, trim_name, vin, engine_code, is_primary, notes, created_at
             FROM user_vehicles
             WHERE user_id = ?
             ORDER BY is_primary DESC, id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM user_vehicles WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, array $data): array
    {
        // مدل باید از لیست car_models انتخاب شود (slug یا name)
        $modelSlug = trim((string) ($data['model_slug'] ?? ''));
        $modelName = mb_substr(trim((string) ($data['model_name'] ?? '')), 0, 80, 'UTF-8');

        if ($modelSlug !== '') {
            $resolved = CarModel::nameBySlug($modelSlug);
            if ($resolved === null) {
                return ['success' => false, 'message' => 'مدل خودرو معتبر نیست. لطفاً از لیست انتخاب کنید.'];
            }
            $modelName = $resolved;
        } elseif ($modelName !== '') {
            if (!CarModel::isValidName($modelName)) {
                return ['success' => false, 'message' => 'مدل خودرو معتبر نیست. لطفاً از لیست انتخاب کنید.'];
            }
        }

        $modelYear = trim((string) ($data['model_year'] ?? ''));
        $trimName = mb_substr(trim((string) ($data['trim_name'] ?? '')), 0, 60, 'UTF-8');
        $vin = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($data['vin'] ?? '')));
        $engineCode = mb_substr(trim((string) ($data['engine_code'] ?? '')), 0, 40, 'UTF-8');
        $notes = mb_substr(trim((string) ($data['notes'] ?? '')), 0, 255, 'UTF-8');
        $setPrimary = !empty($data['is_primary']);

        if ($modelName === '') {
            return ['success' => false, 'message' => 'انتخاب مدل خودرو الزامی است.'];
        }

        if ($modelYear !== '' && !preg_match('/^(13|14|19|20)\d{2}$/', $modelYear)) {
            return ['success' => false, 'message' => 'سال ساخت نامعتبر است.'];
        }

        if ($vin !== '' && (strlen($vin) < 11 || strlen($vin) > 17)) {
            return ['success' => false, 'message' => 'شماره شاسی (VIN) باید بین ۱۱ تا ۱۷ کاراکتر باشد.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $countStmt = $db->prepare("SELECT COUNT(*) FROM user_vehicles WHERE user_id = ?");
            $countStmt->execute([$userId]);
            $count = (int) $countStmt->fetchColumn();

            if ($count >= 8) {
                $db->rollBack();
                return ['success' => false, 'message' => 'حداکثر ۸ خودرو قابل ثبت است.'];
            }

            if ($vin !== '') {
                $dup = $db->prepare(
                    "SELECT id FROM user_vehicles WHERE user_id = ? AND vin = ? LIMIT 1"
                );
                $dup->execute([$userId, $vin]);
                if ($dup->fetch()) {
                    $db->rollBack();
                    return ['success' => false, 'message' => 'این شماره شاسی قبلاً برای شما ثبت شده است.'];
                }
            }

            $isPrimary = ($count === 0 || $setPrimary) ? 1 : 0;

            if ($isPrimary) {
                $db->prepare("UPDATE user_vehicles SET is_primary = 0 WHERE user_id = ?")
                    ->execute([$userId]);
            }

            $stmt = $db->prepare(
                "INSERT INTO user_vehicles
                    (user_id, model_name, model_year, trim_name, vin, engine_code, is_primary, notes, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([
                $userId,
                $modelName,
                $modelYear !== '' ? $modelYear : null,
                $trimName !== '' ? $trimName : null,
                $vin !== '' ? $vin : null,
                $engineCode !== '' ? $engineCode : null,
                $isPrimary,
                $notes !== '' ? $notes : null,
            ]);

            $id = (int) $db->lastInsertId();
            $db->commit();

            return [
                'success' => true,
                'message' => 'خودرو با موفقیت ثبت شد.',
                'vehicle' => self::findById($id, $userId),
            ];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Vehicle create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت خودرو.'];
        }
    }

    public static function update(int $id, int $userId, array $data): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'خودرو یافت نشد.'];
        }

        $modelSlug = trim((string) ($data['model_slug'] ?? ''));
        $modelName = mb_substr(trim((string) ($data['model_name'] ?? $existing['model_name'])), 0, 80, 'UTF-8');

        if ($modelSlug !== '') {
            $resolved = CarModel::nameBySlug($modelSlug);
            if ($resolved === null) {
                return ['success' => false, 'message' => 'مدل خودرو معتبر نیست. لطفاً از لیست انتخاب کنید.'];
            }
            $modelName = $resolved;
        } elseif ($modelName !== '' && !CarModel::isValidName($modelName)) {
            // اگر مدل قدیمی خارج از لیست باشد، فقط وقتی کاربر تغییرش داده سخت‌گیری می‌کنیم
            if ($modelName !== ($existing['model_name'] ?? '')) {
                return ['success' => false, 'message' => 'مدل خودرو معتبر نیست. لطفاً از لیست انتخاب کنید.'];
            }
        }

        $modelYear = trim((string) ($data['model_year'] ?? $existing['model_year'] ?? ''));
        $trimName = mb_substr(trim((string) ($data['trim_name'] ?? $existing['trim_name'] ?? '')), 0, 60, 'UTF-8');
        $vin = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($data['vin'] ?? $existing['vin'] ?? '')));
        $engineCode = mb_substr(trim((string) ($data['engine_code'] ?? $existing['engine_code'] ?? '')), 0, 40, 'UTF-8');
        $notes = mb_substr(trim((string) ($data['notes'] ?? $existing['notes'] ?? '')), 0, 255, 'UTF-8');

        if ($modelName === '') {
            return ['success' => false, 'message' => 'انتخاب مدل خودرو الزامی است.'];
        }

        if ($modelYear !== '' && !preg_match('/^(13|14|19|20)\d{2}$/', $modelYear)) {
            return ['success' => false, 'message' => 'سال ساخت نامعتبر است.'];
        }

        if ($vin !== '' && (strlen($vin) < 11 || strlen($vin) > 17)) {
            return ['success' => false, 'message' => 'شماره شاسی (VIN) باید بین ۱۱ تا ۱۷ کاراکتر باشد.'];
        }

        $db = Database::getInstance();

        if ($vin !== '') {
            $dup = $db->prepare(
                "SELECT id FROM user_vehicles WHERE user_id = ? AND vin = ? AND id != ? LIMIT 1"
            );
            $dup->execute([$userId, $vin, $id]);
            if ($dup->fetch()) {
                return ['success' => false, 'message' => 'این شماره شاسی قبلاً ثبت شده است.'];
            }
        }

        $stmt = $db->prepare(
            "UPDATE user_vehicles
             SET model_name = ?, model_year = ?, trim_name = ?, vin = ?, engine_code = ?, notes = ?
             WHERE id = ? AND user_id = ?"
        );
        $ok = $stmt->execute([
            $modelName,
            $modelYear !== '' ? $modelYear : null,
            $trimName !== '' ? $trimName : null,
            $vin !== '' ? $vin : null,
            $engineCode !== '' ? $engineCode : null,
            $notes !== '' ? $notes : null,
            $id,
            $userId,
        ]);

        return $ok
            ? ['success' => true, 'message' => 'خودرو به‌روزرسانی شد.', 'vehicle' => self::findById($id, $userId)]
            : ['success' => false, 'message' => 'خطا در به‌روزرسانی خودرو.'];
    }

    public static function delete(int $id, int $userId): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'خودرو یافت نشد.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM user_vehicles WHERE id = ? AND user_id = ?")->execute([$id, $userId]);

            if ((int) $existing['is_primary'] === 1) {
                $next = $db->prepare(
                    "SELECT id FROM user_vehicles WHERE user_id = ? ORDER BY id DESC LIMIT 1"
                );
                $next->execute([$userId]);
                $nextId = $next->fetchColumn();
                if ($nextId) {
                    $db->prepare("UPDATE user_vehicles SET is_primary = 1 WHERE id = ? AND user_id = ?")
                        ->execute([$nextId, $userId]);
                }
            }

            $db->commit();
            return ['success' => true, 'message' => 'خودرو حذف شد.'];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در حذف خودرو.'];
        }
    }

    public static function setPrimary(int $id, int $userId): array
    {
        $existing = self::findById($id, $userId);
        if (!$existing) {
            return ['success' => false, 'message' => 'خودرو یافت نشد.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();
            $db->prepare("UPDATE user_vehicles SET is_primary = 0 WHERE user_id = ?")->execute([$userId]);
            $db->prepare("UPDATE user_vehicles SET is_primary = 1 WHERE id = ? AND user_id = ?")
                ->execute([$id, $userId]);
            $db->commit();
            return ['success' => true, 'message' => 'خودروی اصلی تنظیم شد.'];
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در تنظیم خودروی اصلی.'];
        }
    }
}
