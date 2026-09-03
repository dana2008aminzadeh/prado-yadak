<?php
namespace App\models;

use Core\Database;
use PDO;

class Category
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT slug, name, icon_svg, description, tags FROM categories");
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[$row['slug']] = [
                'name' => $row['name'],
                'icon' => $row['icon_svg'],
                'description' => $row['description'],
                'tags' => !empty($row['tags']) ? explode(',', $row['tags']) : []
            ];
        }
        return $categories;
    }
}