<?php
namespace App\models;

use Core\Database;
use PDO;

class Product
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $query = "SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id";
        $stmt = $db->query($query);
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'model' => $row['car_model'] ?? '',
                'category' => $row['category_slug'] ?? '',
                'price' => (int) $row['price'],
                'oem' => $row['oem_code'] ?? '',
                'isGenuine' => (bool) $row['is_genuine'],
                'brand' => $row['brand'] ?? '',
                'inStock' => (bool) $row['in_stock'],
                'imageIcon' => 'disc',
                'images' => !empty($row['telegram_photo_id']) ? [$row['telegram_photo_id']] : ['disc'],
                'desc' => $row['description'] ?? ''
            ];
        }
        return $products;
    }
}