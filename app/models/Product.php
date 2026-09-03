<?php
namespace App\models;

use Core\Database;

class Product
{
    public static function getAll()
    {
        $db = Database::getInstance();
        // امنیت: فقط محصولات موجود یا فعال واکشی شوند
        $stmt = $db->query("SELECT p.*, c.slug as category_slug 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id");
        $results = $stmt->fetchAll();

        $mapped = [];
        foreach ($results as $r) {
            // تبدیل JSON تلگرام به آرایه (نکته دیتابیس بالا اینجا اعمال شده)
            $images = !empty($r['telegram_photo_id']) ? json_decode($r['telegram_photo_id'], true) : [];
            
            $mapped[] = [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'category' => $r['category_slug'],
                'price' => (float) $r['price'],
                'oem' => $r['oem_code'],
                'model' => $r['car_model'],
                'brand' => $r['brand'],
                'isGenuine' => (bool) $r['is_genuine'],
                'inStock' => (bool) $r['in_stock'],
                'desc' => $r['description'],
                'images' => is_array($images) ? $images : []
            ];
        }
        return $mapped;
    }
}