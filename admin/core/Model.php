<?php
namespace Admin\core;

use Core\Database;
use PDO;

/**
 * لایه‌ی سبک دسترسی به دیتابیس مخصوص پنل مدیریت
 */
class Model
{
    public static function db(): PDO
    {
        return Database::getInstance();
    }

    public static function all(string $sql, array $params = []): array
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function scalar(string $sql, array $params = [])
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    public static function exec(string $sql, array $params = []): int
    {
        $st = self::db()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $ph = implode(',', array_fill(0, count($cols), '?'));
        $sql = "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES ({$ph})";
        $st = self::db()->prepare($sql);
        $st->execute(array_values($data));
        return (int) self::db()->lastInsertId();
    }

    public static function update(string $table, int $id, array $data): bool
    {
        if (!$data) return false;
        $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $sql = "UPDATE `{$table}` SET {$set} WHERE id = ?";
        $st = self::db()->prepare($sql);
        return $st->execute([...array_values($data), $id]);
    }

    public static function delete(string $table, int $id): bool
    {
        $st = self::db()->prepare("DELETE FROM `{$table}` WHERE id = ?");
        return $st->execute([$id]);
    }

    public static function find(string $table, int $id): ?array
    {
        return self::one("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1", [$id]);
    }

    /** آیا ستون موردنظر در جدول وجود دارد؟ (برای سازگاری پیش/پس از مهاجرت سئو) */
    public static function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        try {
            $cache[$key] = (bool) self::scalar(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );
        } catch (\Throwable $e) {
            $cache[$key] = false;
        }
        return $cache[$key];
    }

    /** آیا جدول موردنظر وجود دارد؟ */
    public static function hasTable(string $table): bool
    {
        static $cache = [];
        if (isset($cache[$table])) {
            return $cache[$table];
        }
        try {
            $cache[$table] = (bool) self::scalar(
                'SELECT COUNT(*) FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                [$table]
            );
        } catch (\Throwable $e) {
            $cache[$table] = false;
        }
        return $cache[$table];
    }

    /** حذف کلیدهایی که ستون متناظرشان هنوز در جدول ساخته نشده است */
    public static function filterColumns(string $table, array $data): array
    {
        foreach (array_keys($data) as $col) {
            if (!self::hasColumn($table, $col)) {
                unset($data[$col]);
            }
        }
        return $data;
    }

    public static function count(string $table, string $where = '1', array $params = []): int
    {
        return (int) self::scalar("SELECT COUNT(*) FROM `{$table}` WHERE {$where}", $params);
    }
}
