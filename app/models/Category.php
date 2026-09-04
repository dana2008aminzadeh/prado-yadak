<?php
namespace App\models;

use Core\Database;

class Category
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM categories");
        $results = $stmt->fetchAll();

        $categories = [];
        foreach ($results as $row) {
            $categories[$row['slug']] = [
                'name' => $row['name'],
                'description' => $row['description'] ?? '',
                'tags' => $row['tags'] ?? '',
                'icon_svg' => $row['icon_svg'] ?? ''
            ];
        }
        return $categories;
    }
}