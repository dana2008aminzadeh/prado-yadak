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

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);   // نمایش خطاهای استارت‌آپ
error_reporting(E_ALL);                 // گزارش‌گیری از تمام خطاها، هشدارها و نوتیس‌ها
ini_set('log_errors', 1);               // روشن کردن لاگ در فایل
ini_set('error_log', __DIR__ . '/php-error.log'); // مسیر ذخیره فایل ارور لاگ

function e($string)
{
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function clean_html($html)
{
    $allowed_tags = '<div><span><p><br><hr><h1><h2><h3><h4><h5><h6><strong><b><i><em><u><a><ul><ol><li><blockquote><code><pre>';
    return strip_tags($html ?? '', $allowed_tags);
}

function toShamsi($dateString)
{
    // بررسی اینکه آیا ورودی خودش تایم‌استمپ است یا رشته متنی
    $timestamp = is_numeric($dateString) ? $dateString : strtotime($dateString);
    if (!$timestamp)
        return 'نامشخص';

    $gy = (int) date('Y', $timestamp);
    $gm = (int) date('m', $timestamp);
    $gd = (int) date('d', $timestamp);

    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + (int) (($gy2 + 3) / 4) - (int) (($gy2 + 99) / 100) + (int) (($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];

    $jy = -1595 + 33 * (int) ($days / 12053);
    $days %= 12053;
    $jy += 4 * (int) ($days / 1461);
    $days %= 1461;

    if ($days > 365) {
        $jy += (int) (($days - 1) / 365);
        $days = ($days - 1) % 365;
    }

    $jm = ($days < 186) ? 1 + (int) ($days / 31) : 7 + (int) (($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));

    return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
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
}

$router = new Router();
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

$router->dispatch($uri);