<?php
namespace App\models;

use Core\Database;

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

        // ۱. بررسی قفل بودن همین شماره همراه (محافظت از اکانت خاص)
        $stmtPhone = $db->prepare("
        SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
        FROM login_attempts
        WHERE phone = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
        $stmtPhone->execute([$phone]);
        $rowPhone = $stmtPhone->fetch();
        $phoneAttempts = (int) ($rowPhone['attempts'] ?? 0);

        if ($phoneAttempts >= 5) {
            $lastAttempt = $rowPhone['last_attempt'] ? strtotime($rowPhone['last_attempt']) : time();
            $remaining = ($lastAttempt + (15 * 60)) - time();
            if ($remaining > 0) {
                return ['locked' => true, 'type' => 'account', 'minutes' => ceil($remaining / 60), 'attempts' => $phoneAttempts];
            }
        }

        // ۲. بررسی قفل بودن سراسری این آی‌پی (محافظت در برابر اسپری پسورد / بات‌نت)
        $stmtIp = $db->prepare("
        SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt
        FROM login_attempts
        WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
        $stmtIp->execute([$ip]);
        $rowIp = $stmtIp->fetch();
        $ipAttempts = (int) ($rowIp['attempts'] ?? 0);

        if ($ipAttempts >= 25) {
            $lastAttempt = $rowIp['last_attempt'] ? strtotime($rowIp['last_attempt']) : time();
            $remaining = ($lastAttempt + (15 * 60)) - time();
            if ($remaining > 0) {
                return ['locked' => true, 'type' => 'ip', 'minutes' => ceil($remaining / 60), 'attempts' => $ipAttempts];
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
}