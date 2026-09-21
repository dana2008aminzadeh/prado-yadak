<?php
/**
 * ابزار خط فرمان برای ساخت/ارتقای حساب مدیر
 *
 * اجرا:
 *   php admin/make-admin.php 09189998852 "کامیار امین‌زاده" MyStrongPass
 *
 * پس از ساخت مدیر، این فایل را از روی سرور حذف کنید.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('این اسکریپت فقط از خط فرمان قابل اجراست.');
}

$root = dirname(__DIR__);
define('BASE_PATH', $root);
define('APP_PATH', $root . '/app');
define('CORE_PATH', $root . '/core');

spl_autoload_register(function ($class) {
    foreach (['App\\' => APP_PATH . '/', 'Core\\' => CORE_PATH . '/'] as $p => $d) {
        if (strncmp($p, $class, strlen($p)) === 0) {
            $f = $d . str_replace('\\', '/', substr($class, strlen($p))) . '.php';
            if (is_file($f)) require_once $f;
        }
    }
});

[$script, $phone, $name, $password] = array_pad($argv, 4, null);

if (!$phone || !$password) {
    fwrite(STDERR, "استفاده: php admin/make-admin.php <موبایل> <نام> <رمز عبور>\n");
    exit(1);
}
if (mb_strlen($password) < 6) {
    fwrite(STDERR, "رمز عبور باید حداقل ۶ کاراکتر باشد.\n");
    exit(1);
}

$db = \Core\Database::getInstance();
$hash = password_hash($password, PASSWORD_DEFAULT);

$st = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
$st->execute([$phone]);
$existing = $st->fetchColumn();

if ($existing) {
    $db->prepare("UPDATE users SET role = 'admin', password_hash = ?, full_name = COALESCE(NULLIF(?, ''), full_name) WHERE id = ?")
        ->execute([$hash, (string) $name, $existing]);
    echo "کاربر موجود (#{$existing}) به مدیر ارتقا یافت و رمز عبور تغییر کرد.\n";
} else {
    $db->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, 'admin')")
        ->execute([$name ?: 'مدیر سیستم', $phone, $hash]);
    echo "مدیر جدید با شناسه #" . $db->lastInsertId() . " ساخته شد.\n";
}

echo "اکنون می‌توانید از مسیر /admin/login وارد شوید.\n";
echo "⚠ پس از ورود موفق، فایل admin/make-admin.php را حذف کنید.\n";
