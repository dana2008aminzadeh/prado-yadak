<?php

namespace App\models;

use Core\Database;

class CarModel
{
    public static function getAll(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT slug, name FROM car_models ORDER BY name ASC");
        $results = $stmt->fetchAll();
        $models = [];
        foreach ($results as $row) {
            $models[$row['slug']] = $row['name'];
        }
        return $models;
    }

    public static function getList(): array
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT slug, name FROM car_models ORDER BY name ASC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function isValidName(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM car_models WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return (bool) $stmt->fetchColumn();
    }

    public static function isValidSlug(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM car_models WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return (bool) $stmt->fetchColumn();
    }

    public static function nameBySlug(string $slug): ?string
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT name FROM car_models WHERE slug = ? LIMIT 1");
        $stmt->execute([trim($slug)]);
        $name = $stmt->fetchColumn();
        return $name !== false ? (string) $name : null;
    }
}
