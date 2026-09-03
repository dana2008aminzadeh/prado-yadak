<?php
namespace App\models;

use Core\Database;

class CarModel
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT slug, name FROM car_models");
        $results = $stmt->fetchAll();

        $models = [];
        foreach ($results as $row) {
            $models[$row['slug']] = $row['name'];
        }
        return $models;
    }
}