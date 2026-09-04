<?php
// ۱. امنیت پایه‌ای Session برای کل سایت
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === سیستم لاگ و نمایش ارور (مخصوص دیباگ) ===
ini_set('display_errors', 1);           // نمایش ارور روی صفحه
ini_set('display_startup_errors', 1);   // نمایش خطاهای استارت‌آپ
error_reporting(E_ALL);                 // گزارش‌گیری از تمام خطاها، هشدارها و نوتیس‌ها
ini_set('log_errors', 1);               // روشن کردن لاگ در فایل
ini_set('error_log', __DIR__ . '/php-error.log'); // مسیر ذخیره فایل ارور لاگ

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');
define('VIEWS_PATH', APP_PATH . '/views');
define('BASE_URL', '/');

spl_autoload_register(function ($class) {
    $prefix_app = 'App\\';
    $prefix_core = 'Core\\';
    $file = '';

    if (strncmp($prefix_app, $class, strlen($prefix_app)) === 0) {
        $relative_class = substr($class, strlen($prefix_app));
        $file = APP_PATH . '/' . str_replace('\\', '/', $relative_class) . '.php';
    } elseif (strncmp($prefix_core, $class, strlen($prefix_core)) === 0) {
        $relative_class = substr($class, strlen($prefix_core));
        $file = CORE_PATH . '/' . str_replace('\\', '/', $relative_class) . '.php';
    }

    if (file_exists($file)) {
        require_once $file;
    }
});

use Core\Router;

try {
    $GLOBALS['settings'] = \App\models\Setting::getAll();
    $GLOBALS['part_categories'] = \App\models\Category::getAll();
    $GLOBALS['car_models'] = \App\models\CarModel::getAll();
} catch (Exception $e) {
    // در صورت قطعی دیتابیس، می‌توان اینجا کاربر را به صفحه خطای 500 ارجاع داد
}

$router = new Router();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

$router->dispatch($uri);