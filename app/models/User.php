<?php

namespace App\models;

use Core\Database;
use PDO;

class User
{
    public static function findByPhone($phone)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        return $stmt->fetch();
    }

    public static function create($full_name, $phone, $password_hash)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$full_name, $phone, $password_hash]);
        return $db->lastInsertId();
    }

    public static function checkLoginAttempts($phone, $ip)
    {
        $db = Database::getInstance();

        $stmtPhone = $db->prepare(
            "SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
             FROM login_attempts
             WHERE phone = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmtPhone->execute([$phone]);
        $rowPhone = $stmtPhone->fetch();
        $phoneAttempts = (int) ($rowPhone['attempts'] ?? 0);

        if ($phoneAttempts >= 5) {
            $lastAttempt = $rowPhone['last_attempt'] ? strtotime($rowPhone['last_attempt']) : time();
            $remaining = ($lastAttempt + (15 * 60)) - time();
            if ($remaining > 0) {
                return [
                    'locked' => true,
                    'type' => 'account',
                    'minutes' => ceil($remaining / 60),
                    'attempts' => $phoneAttempts,
                ];
            }
        }

        $stmtIp = $db->prepare(
            "SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
             FROM login_attempts
             WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
        );
        $stmtIp->execute([$ip]);
        $rowIp = $stmtIp->fetch();
        $ipAttempts = (int) ($rowIp['attempts'] ?? 0);

        if ($ipAttempts >= 25) {
            $lastAttempt = $rowIp['last_attempt'] ? strtotime($rowIp['last_attempt']) : time();
            $remaining = ($lastAttempt + (15 * 60)) - time();
            if ($remaining > 0) {
                return [
                    'locked' => true,
                    'type' => 'ip',
                    'minutes' => ceil($remaining / 60),
                    'attempts' => $ipAttempts,
                ];
            }
        }

        return ['locked' => false, 'attempts' => $phoneAttempts];
    }

    public static function recordFailedLogin($phone, $ip)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO login_attempts (phone, ip_address, attempted_at) VALUES (?, ?, NOW())");
        $stmt->execute([$phone, $ip]);
    }

    public static function clearLoginAttempts($phone)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM login_attempts WHERE phone = ?");
        $stmt->execute([$phone]);
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, full_name, phone, email, national_id, role, wallet_balance, city, created_at
             FROM users
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    /**
     * به‌روزرسانی مشخصات پایه حساب (نام، ایمیل، کد ملی، شهر)
     */
    public static function updateProfile(int $userId, array $data): bool
    {
        $db = Database::getInstance();

        $fields = [];
        $params = [];

        if (array_key_exists('full_name', $data)) {
            $fields[] = 'full_name = ?';
            $params[] = mb_substr(trim((string) $data['full_name']), 0, 100, 'UTF-8');
        }

        if (array_key_exists('email', $data)) {
            $fields[] = 'email = ?';
            $email = trim((string) $data['email']);
            $params[] = $email !== '' ? mb_substr($email, 0, 120, 'UTF-8') : null;
        }

        if (array_key_exists('national_id', $data)) {
            $fields[] = 'national_id = ?';
            $nid = preg_replace('/\D/', '', (string) $data['national_id']);
            $params[] = $nid !== '' ? $nid : null;
        }

        if (array_key_exists('city', $data)) {
            $fields[] = 'city = ?';
            $city = trim((string) $data['city']);
            $params[] = $city !== '' ? mb_substr($city, 0, 80, 'UTF-8') : null;
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * تغییر رمز عبور با بررسی رمز فعلی
     */
    public static function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['success' => false, 'message' => 'کاربر یافت نشد.'];
        }

        if (!password_verify($currentPassword, $row['password_hash'])) {
            return ['success' => false, 'message' => 'رمز عبور فعلی اشتباه است.'];
        }

        if (mb_strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'رمز عبور جدید باید حداقل ۶ کاراکتر باشد.'];
        }

        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'رمز جدید نباید با رمز فعلی یکسان باشد.'];
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $upd->execute([$hash, $userId]);

        return ['success' => true, 'message' => 'رمز عبور با موفقیت تغییر کرد.'];
    }

    /**
     * سطح عضویت بر اساس تعداد سفارش‌های تحویل‌شده
     */
    public static function getMembershipLevel(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'delivered'"
        );
        $stmt->execute([$userId]);
        $delivered = (int) $stmt->fetchColumn();

        if ($delivered >= 20) {
            return ['key' => 'diamond', 'label' => 'خریدار الماسی', 'icon' => 'gem'];
        }
        if ($delivered >= 10) {
            return ['key' => 'gold', 'label' => 'خریدار طلایی', 'icon' => 'crown'];
        }
        if ($delivered >= 3) {
            return ['key' => 'silver', 'label' => 'خریدار نقره‌ای', 'icon' => 'award'];
        }

        return ['key' => 'bronze', 'label' => 'عضو عادی', 'icon' => 'user'];
    }

    /**
     * حروف اول نام برای آواتار
     */
    public static function getInitials(?string $fullName): string
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return '؟';
        }

        $parts = preg_split('/\s+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY);
        if (!$parts) {
            return mb_substr($fullName, 0, 1, 'UTF-8');
        }

        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = count($parts) > 1 ? mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8') : '';
        return $first . $last;
    }
}
