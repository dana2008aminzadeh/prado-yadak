<?php
namespace Admin\core;

use Core\Database;
use PDO;
use Throwable;

/** دسترسی کش‌شده به جدول settings */
class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = Database::getInstance()
                ->query('SELECT setting_key, setting_value FROM settings')
                ->fetchAll(PDO::FETCH_KEY_PAIR);
            return self::$cache = ($rows ?: []);
        } catch (Throwable $e) {
            return self::$cache = [];
        }
    }

    public static function get(string $key, $default = null)
    {
        $all = self::all();
        $v = $all[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $v = self::get($key);
        if ($v === null) return $default;
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key);
        return $v === null ? $default : (int) $v;
    }

    public static function set(string $key, $value): void
    {
        $db = Database::getInstance();
        $st = $db->prepare('SELECT id FROM settings WHERE setting_key = ? LIMIT 1');
        $st->execute([$key]);
        if ($st->fetchColumn()) {
            $db->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = ?')->execute([(string) $value, $key]);
        } else {
            $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)')->execute([$key, (string) $value]);
        }
        self::$cache = null;
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
