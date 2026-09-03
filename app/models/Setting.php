<?php
namespace App\models;

use Core\Database;
use PDO;

class Setting
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}