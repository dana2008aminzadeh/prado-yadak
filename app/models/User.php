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
}