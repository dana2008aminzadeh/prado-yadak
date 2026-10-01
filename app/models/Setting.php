<?php
namespace App\models;

use Core\Database;

class Setting
{
    public static function getAll()
    {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        $results = $stmt->fetchAll();

        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * خواندن یک تنظیم از کش سراسری ($GLOBALS['settings'] که در index.php
     * پر می‌شود) و در صورت نبودش، مستقیم از پایگاه داده.
     */
    public static function get(string $key, $default = null)
    {
        $all = $GLOBALS['settings'] ?? [];
        $v = is_array($all) ? ($all[$key] ?? null) : null;
        if ($v === null || $v === '') {
            try {
                $stmt = Database::getInstance()->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
                $stmt->execute([$key]);
                $v = $stmt->fetchColumn();
            } catch (\Throwable $e) {
                $v = null;
            }
        }
        return ($v === null || $v === '' || $v === false) ? $default : $v;
    }

    /** تفسیر مقدار تنظیم به صورت بولین (سازگار با Admin\core\Settings::bool) */
    public static function boolish(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) {
            return $default;
        }
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }
}