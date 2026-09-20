<?php

namespace App\models;

use Core\Database;
use PDO;

class ShippingMethod
{
    public static function getActiveMethods(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, title, subtitle FROM shipping_methods WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, title, subtitle FROM shipping_methods WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}