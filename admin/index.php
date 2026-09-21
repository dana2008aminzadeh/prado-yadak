<?php
/**
 * پنل مدیریت پرادو یدک — نقطه ورود (Front Controller)
 * مسیر: /admin
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php-error.log');

define('ADMIN_PATH', __DIR__);
define('SITE_ROOT', dirname(__DIR__));
if (!defined('BASE_PATH')) define('BASE_PATH', SITE_ROOT);
if (!defined('APP_PATH')) define('APP_PATH', SITE_ROOT . '/app');
if (!defined('CORE_PATH')) define('CORE_PATH', SITE_ROOT . '/core');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', APP_PATH . '/views');
if (!defined('SITE_URL')) define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost');

spl_autoload_register(function ($class) {
    $map = [
        'App\\'   => APP_PATH . '/',
        'Core\\'  => CORE_PATH . '/',
        'Admin\\' => ADMIN_PATH . '/',
    ];
    foreach ($map as $prefix => $dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $rel = substr($class, strlen($prefix));
            $file = $dir . str_replace('\\', '/', $rel) . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});

require_once ADMIN_PATH . '/core/Helpers.php';

use Admin\core\Auth;
use Admin\core\Model;

// تنظیمات عمومی سایت برای استفاده در قالب پنل
try {
    $GLOBALS['settings'] = \App\models\Setting::getAll();
} catch (\Throwable $e) {
    $GLOBALS['settings'] = [];
}

// ---------- استخراج مسیر ----------
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$route = trim(preg_replace('#^/admin#', '', $uri), '/');
if ($route === '') {
    $route = 'dashboard';
}

// ---------- مسیرهای عمومی (بدون احراز هویت) ----------
if ($route === 'login') {
    Auth::verifyCsrf();
    $error = null;
    if (Auth::check()) {
        redirect(admin_url());
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $res = Auth::attempt((string) post('phone'), (string) post('password'));
        if ($res['success']) {
            $to = $_SESSION['admin_redirect'] ?? admin_url();
            unset($_SESSION['admin_redirect']);
            redirect($to);
        }
        $error = $res['message'];
    }
    require ADMIN_PATH . '/views/login.php';
    exit;
}

if ($route === 'logout') {
    Auth::logout();
    redirect(admin_url('login'));
}

// ---------- از اینجا به بعد نیاز به ورود مدیر ----------
$currentAdmin = Auth::requireLogin();
Auth::verifyCsrf();

$segments = explode('/', $route);
$section = $segments[0];
$action = $segments[1] ?? 'index';
$id = isset($segments[2]) ? (int) $segments[2] : (int) param('id', 0);

$controllers = [
    'dashboard' => \Admin\controllers\DashboardController::class,
    'products'  => \Admin\controllers\ProductController::class,
    'orders'    => \Admin\controllers\OrderController::class,
    'users'     => \Admin\controllers\UserController::class,
    'articles'  => \Admin\controllers\ArticleController::class,
    'comments'  => \Admin\controllers\CommentController::class,
    'tickets'   => \Admin\controllers\TicketController::class,
    'coupons'   => \Admin\controllers\CouponController::class,
    'catalog'   => \Admin\controllers\CatalogController::class,
    'locations' => \Admin\controllers\LocationController::class,
    'notices'   => \Admin\controllers\NoticeController::class,
    'shipping'  => \Admin\controllers\ShippingController::class,
    'wallet'    => \Admin\controllers\WalletController::class,
    'settings'  => \Admin\controllers\SettingController::class,
    'reports'   => \Admin\controllers\ReportController::class,
];

if (!isset($controllers[$section])) {
    http_response_code(404);
    $pageTitle = 'صفحه یافت نشد';
    ob_start();
    echo '<div class="card p-10 text-center"><h2 class="text-xl font-black mb-2">۴۰۴</h2><p class="text-sm text-muted">صفحه‌ای که دنبالش هستید در پنل وجود ندارد.</p></div>';
    $content = ob_get_clean();
    require ADMIN_PATH . '/views/layout/main.php';
    exit;
}

$controllerClass = $controllers[$section];
$controller = new $controllerClass();
$method = preg_replace('/[^a-zA-Z0-9_]/', '', $action) ?: 'index';

if (!method_exists($controller, $method)) {
    http_response_code(404);
    exit('اکشن مورد نظر یافت نشد.');
}

$controller->{$method}($id);
