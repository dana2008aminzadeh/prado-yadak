<?php
namespace App\models;

use Core\Database;

class Otp
{
    public static function create($phone, $code)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO otp_codes (phone, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
        return $stmt->execute([$phone, $code]);
    }

    public static function verify($phone, $code)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM otp_codes WHERE phone = ? AND code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone, $code]);
        return $stmt->fetch() ? true : false;
    }

    public static function deleteByPhone($phone)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM otp_codes WHERE phone = ?");
        return $stmt->execute([$phone]);
    }
}