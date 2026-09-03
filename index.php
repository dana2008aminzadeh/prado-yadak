<?php
// Security: پیکربندی امن سشن‌ها قبل از استارت
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

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

$router = new Router();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

$db = \Core\Database::getInstance();

$settings = [];
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
}
$GLOBALS['settings'] = $settings;

// 2. دسته‌بندی‌ها (همراه با آیکون، توضیحات و تگ‌ها)
$part_categories = [];
try {
    $stmt = $db->query("SELECT slug, name, icon_svg, description, tags FROM categories");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $part_categories[$row['slug']] = [
            'name' => $row['name'],
            'icon' => $row['icon_svg'],
            'description' => $row['description'],
            // تبدیل تگ‌های جدا شده با کاما به آرایه
            'tags' => !empty($row['tags']) ? explode(',', $row['tags']) : []
        ];
    }
} catch (Exception $e) {
}
$GLOBALS['part_categories'] = $part_categories;

// 3. مدل‌های ماشین (همراه با لوگو)
$car_models = [];
try {
    $stmt = $db->query("SELECT slug, name, logo_svg FROM car_models");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $car_models[$row['slug']] = [
            'name' => $row['name'],
            'logo' => $row['logo_svg']
        ];
    }
} catch (Exception $e) {
}
$GLOBALS['car_models'] = $car_models;

$parts_database = [];
try {
    $query = "SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id";
    $stmt = $db->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $parts_database[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'model' => $row['car_model'] ?? '',
            'category' => $row['category_slug'] ?? '',
            'price' => (int) $row['price'],
            'oem' => $row['oem_code'] ?? '',
            'isGenuine' => (bool) $row['is_genuine'],
            'brand' => $row['brand'] ?? '',
            'inStock' => (bool) $row['in_stock'],
            'imageIcon' => 'disc',
            'images' => !empty($row['telegram_photo_id']) ? [$row['telegram_photo_id']] : ['disc'],
            'desc' => $row['description'] ?? ''
        ];
    }
} catch (Exception $e) {
}
$GLOBALS['parts_database'] = $parts_database;

$router->dispatch($uri);