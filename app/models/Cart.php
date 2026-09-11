<?php
namespace App\models;

use Core\Database;

class Cart
{
    public static function sync($userId, $items)
    {
        $db = Database::getInstance();

        $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);

        if (empty($items))
            return;

        $stmt = $db->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        foreach ($items as $item) {
            $productId = $item['product']['id'] ?? $item['product_id'];
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $stmt->execute([$userId, $productId, $qty]);
        }
    }

    public static function get($userId)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ci.quantity, p.id, p.name, p.slug, p.oem_code, p.price, p.telegram_photo_id
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}