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
        $stmt = $db->prepare("
            SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt 
            FROM login_attempts 
            WHERE (phone = ? OR ip_address = ?) 
              AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$phone, $ip]);
        $row = $stmt->fetch();

        $attempts = (int) ($row['attempts'] ?? 0);
        $lastAttempt = $row['last_attempt'] ? strtotime($row['last_attempt']) : time();

        if ($attempts >= 5) {
            $lockoutDuration = 15 * 60; // 15 دقیقه
            $remaining = ($lastAttempt + $lockoutDuration) - time();
            if ($remaining > 0) {
                return ['locked' => true, 'minutes' => ceil($remaining / 60)];
            }
        }

        return ['locked' => false, 'attempts' => $attempts];
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