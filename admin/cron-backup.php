<?php
/**
 * پشتیبان‌گیری خودکار روزانه — برای اجرا در کرون‌جاب
 *
 * نمونه تنظیم در cPanel / crontab:
 *   0 3 * * * cd /home/user/public_html && php admin/cron-backup.php >> backups/cron.log 2>&1
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('این اسکریپت فقط از خط فرمان قابل اجراست.');
}

$root = dirname(__DIR__);
define('ADMIN_PATH', __DIR__);
define('SITE_ROOT', $root);
define('BASE_PATH', $root);
define('APP_PATH', $root . '/app');
define('CORE_PATH', $root . '/core');

spl_autoload_register(function ($class) {
    foreach (['App\\' => APP_PATH . '/', 'Core\\' => CORE_PATH . '/', 'Admin\\' => ADMIN_PATH . '/'] as $p => $d) {
        if (strncmp($p, $class, strlen($p)) === 0) {
            $f = $d . str_replace('\\', '/', substr($class, strlen($p))) . '.php';
            if (is_file($f)) require_once $f;
        }
    }
});

$keepDays = 14;
$dir = $root . '/backups';

if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
    fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] خطا: پوشه backups ساخته نشد.\n");
    exit(1);
}
if (!is_file($dir . '/.htaccess')) {
    @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
}

try {
    $db = \Core\Database::getInstance();
    $dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();

    $sql = "-- پشتیبان خودکار {$dbName}\n-- " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";

    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];

    foreach ($tables as $table) {
        $safe = str_replace('`', '', (string) $table);
        $create = $db->query("SHOW CREATE TABLE `{$safe}`")->fetch(PDO::FETCH_NUM);
        $sql .= "\nDROP TABLE IF EXISTS `{$safe}`;\n" . ($create[1] ?? '') . ";\n";

        $count = (int) $db->query("SELECT COUNT(*) FROM `{$safe}`")->fetchColumn();
        if ($count === 0) continue;

        for ($offset = 0; $offset < $count; $offset += 500) {
            $rows = $db->query("SELECT * FROM `{$safe}` LIMIT 500 OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) break;
            $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
            $values = [];
            foreach ($rows as $row) {
                $vals = array_map(function ($v) use ($db) {
                    if ($v === null) return 'NULL';
                    if (is_numeric($v) && !preg_match('/^0\d/', (string) $v)) return (string) $v;
                    return $db->quote((string) $v);
                }, array_values($row));
                $values[] = '(' . implode(',', $vals) . ')';
            }
            $sql .= "INSERT INTO `{$safe}` ({$cols}) VALUES\n" . implode(",\n", $values) . ";\n";
        }
    }
    $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

    $name = 'backup-' . date('Y-m-d_His') . '.sql';
    $path = $dir . '/' . $name;
    if (function_exists('gzencode')) {
        $name .= '.gz';
        $path .= '.gz';
        file_put_contents($path, gzencode($sql, 6));
    } else {
        file_put_contents($path, $sql);
    }
    @chmod($path, 0600);

    $size = round((@filesize($path) ?: 0) / 1048576, 2);
    echo '[' . date('Y-m-d H:i:s') . "] پشتیبان ساخته شد: {$name} ({$size} MB)\n";

    // حذف نسخه‌های قدیمی‌تر از بازه نگهداری
    $removed = 0;
    foreach (glob($dir . '/backup-*.sql*') ?: [] as $f) {
        if (filemtime($f) < strtotime("-{$keepDays} days")) {
            @unlink($f);
            $removed++;
        }
    }
    if ($removed) {
        echo '[' . date('Y-m-d H:i:s') . "] {$removed} نسخه قدیمی حذف شد.\n";
    }

    // ثبت در لاگ ممیزی
    try {
        $db->prepare('INSERT INTO admin_audit_logs (admin_name, action, entity_type, entity_id, description, ip_address, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, NOW())')
            ->execute(['سیستم (کرون)', 'backup.create', 'backup', $name,
                       "پشتیبان‌گیری خودکار — {$size} MB", 'cron']);
    } catch (Throwable $e) {
    }

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] خطا در پشتیبان‌گیری: ' . $e->getMessage() . "\n");
    exit(1);
}
