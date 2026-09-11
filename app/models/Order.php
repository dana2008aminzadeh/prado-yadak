<?php
namespace App\models;

use Core\Database;

class Order
{
    public static function findByTrackingCode($code)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT status FROM orders WHERE tracking_code = ? LIMIT 1");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }
}