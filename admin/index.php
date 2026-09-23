<?php
/**
 * پنل مدیریت پرادو یدک — نقطه ورود (Front Controller)
 * مسیر: /admin
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

if (session_status() === PHP_SESSION_NONE) {
    session_name('PRADO_ADMIN');
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/php-error.log');

define('ADMIN_PATH', __DIR__);
define('SITE_ROOT', dirname(__DIR__));
if (!defined('BASE_PATH')) define('BASE_PATH', SITE_ROOT);
if (!defined('APP_PATH'))   define('APP_PATH', SITE_ROOT . '/app');
if (!defined('CORE_PATH'))  define('CORE_PATH', SITE_ROOT . '/core');
if (!defined('VIEWS_PATH')) define('VIEWS_PATH', APP_PATH . '/views');
if (!defined('SITE_URL'))   define('SITE_URL', $_SERVER['HTTP_HOST'] ?? 'localhost');

spl_autoload_register(function ($class) {
    $map = [
        'App\\'   => APP_PATH . '/',
        'Core\\'  => CORE_PATH . '/',
        'Admin\\' => ADMIN_PATH . '/',
    ];
    foreach ($map as $prefix => $dir) {
        if (strncmp($prefix, $class, strlen($prefix)) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
});

require_once ADMIN_PATH . '/core/Helpers.php';

use Admin\core\Auth;
use Admin\core\Audit;
use Admin\core\Settings;

// هدرهای امنیتی
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

$GLOBALS['settings'] = Settings::all();

$adminPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

if (!preg_match('#^/admin/?#', $adminPath)) {
    \Core\UrlCanonicalizer::handleRequest();
}

// ---------- استخراج مسیر ----------
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$route = trim(preg_replace('#^/admin#', '', $uri) ?? '', '/');
if ($route === '') {
    $route = 'dashboard';
}

// ---------- مسیرهای عمومی ----------
if ($route === 'login') {
    $error = null;
    if (Auth::check()) {
        redirect(admin_url());
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        if (!Auth::validCsrf($_POST['_csrf'] ?? '')) {
            $error = 'نشست شما منقضی شده است. دوباره تلاش کنید.';
        } else {
            Auth::verifyOrigin();
            $res = Auth::attempt((string) post('phone'), (string) post('password'));
            if ($res['success']) {
                $to = $_SESSION['admin_redirect'] ?? admin_url();
                unset($_SESSION['admin_redirect']);
                redirect($to);
            }
            $error = $res['message'];
        }
    }
    require ADMIN_PATH . '/views/login.php';
    exit;
}

if ($route === 'logout') {
    // خروج نیز باید POST + CSRF باشد تا با لینک جعلی اجرا نشود
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && Auth::validCsrf($_POST['_csrf'] ?? '')) {
        Auth::logout();
    }
    redirect(admin_url('login'));
}

// ---------- نیازمند ورود ----------
$currentAdmin = Auth::requireLogin();

$segments = array_values(array_filter(explode('/', $route), fn($s) => $s !== ''));
$section  = $segments[0] ?? 'dashboard';
$action   = $segments[1] ?? 'index';
$id       = isset($segments[2]) ? (int) $segments[2] : (int) param('id', 0);

$controllers = [
    'dashboard' => [\Admin\controllers\DashboardController::class, 'dashboard.view'],
    'products'  => [\Admin\controllers\ProductController::class,   'products.view'],
    'orders'    => [\Admin\controllers\OrderController::class,     'orders.view'],
    'users'     => [\Admin\controllers\UserController::class,      'users.view'],
    'articles'  => [\Admin\controllers\ArticleController::class,   'articles.view'],
    'comments'  => [\Admin\controllers\CommentController::class,   'comments.view'],
    'tickets'   => [\Admin\controllers\TicketController::class,    'tickets.view'],
    'coupons'   => [\Admin\controllers\CouponController::class,    'coupons.view'],
    'catalog'   => [\Admin\controllers\CatalogController::class,   'catalog.view'],
    'locations' => [\Admin\controllers\LocationController::class,  'locations.view'],
    'notices'   => [\Admin\controllers\NoticeController::class,    'notices.view'],
    'shipping'  => [\Admin\controllers\ShippingController::class,  'shipping.view'],
    'wallet'    => [\Admin\controllers\WalletController::class,    'wallet.view'],
    'settings'  => [\Admin\controllers\SettingController::class,   'settings.view'],
    'reports'   => [\Admin\controllers\ReportController::class,    'reports.view'],
    'sms'       => [\Admin\controllers\SmsController::class,       'sms.view'],
    'roles'     => [\Admin\controllers\RoleController::class,      'roles.manage'],
    'audit'     => [\Admin\controllers\AuditController::class,     'audit.view'],
    'tools'     => [\Admin\controllers\ToolController::class,      'tools.backup'],
    'seo'       => [\Admin\controllers\SeoController::class,       'seo.view'],
    'api'       => [\Admin\controllers\ApiController::class,       'dashboard.view'],
];

if (!isset($controllers[$section])) {
    http_response_code(404);
    $pageTitle = 'صفحه یافت نشد';
    ob_start();
    echo '<div class="card p-10 text-center"><h2 style="font-size:28px;margin:0 0 8px">۴۰۴</h2>'
        . '<p class="text-muted">صفحه‌ای که دنبالش هستید در پنل وجود ندارد.</p>'
        . '<a class="btn btn-primary mt" href="' . admin_url() . '">بازگشت به پیشخوان</a></div>';
    $content = ob_get_clean();
    require ADMIN_PATH . '/views/layout/main.php';
    exit;
}

[$controllerClass, $viewPermission] = $controllers[$section];

$controller = new $controllerClass();
$method = preg_replace('/[^a-zA-Z0-9_]/', '', $action) ?: 'index';

if ($method === '' || str_starts_with($method, '_') || !method_exists($controller, $method)) {
    http_response_code(404);
    $pageTitle = 'اکشن نامعتبر';
    ob_start();
    echo '<div class="card p-10 text-center"><h2>عملیات یافت نشد</h2>'
        . '<a class="btn mt" href="' . admin_url($section) . '">بازگشت</a></div>';
    $content = ob_get_clean();
    require ADMIN_PATH . '/views/layout/main.php';
    exit;
}

// فقط متدهای عمومیِ خودِ کنترلر قابل فراخوانی‌اند (نه متدهای BaseController)
try {
    $ref = new ReflectionMethod($controller, $method);
    if (!$ref->isPublic() || $ref->isStatic()
        || in_array(strtolower($method), ['__construct', '__destruct', '__call'], true)) {
        throw new ReflectionException('not callable');
    }
} catch (ReflectionException $e) {
    http_response_code(404);
    exit('عملیات نامعتبر است.');
}

/**
 * ---------- حفاظت CSRF ----------
 * هر اکشنی که وضعیت سیستم را تغییر می‌دهد باید POST باشد.
 * فهرست زیر اکشن‌های «فقط خواندنی» است؛ هر چیز دیگری نیازمند POST + توکن است.
 */
$readOnlyActions = [
    'index', 'show', 'create', 'edit', 'form', 'view', 'list',
    'export', 'invoice', 'preview', 'search', 'poll', 'notifications',
    'download', 'stock', 'movements', 'log', 'campaign', 'compose', 'products',
    'issues', 'redirects', 'notfound', 'landing', 'searchproducts', 'suggestseo',
];

$isReadOnly = in_array(strtolower($method), $readOnlyActions, true);
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (!$isReadOnly) {
    if ($requestMethod !== 'POST') {
        // جلوگیری از اجرای عملیات حساس با GET (باگ امنیتی CSRF)
        Audit::log('security.denied', 'request', null,
            'تلاش برای اجرای عملیات «' . $section . '/' . $method . '» با متد ' . $requestMethod);
        http_response_code(405);
        header('Allow: POST');
        $pageTitle = 'روش درخواست نامعتبر';
        ob_start();
        echo '<div class="card p-10 text-center"><h2 style="color:#be123c">درخواست مسدود شد</h2>'
            . '<p class="text-muted">این عملیات فقط از طریق فرم‌های داخل پنل و با متد POST قابل اجراست.</p>'
            . '<a class="btn btn-primary mt" href="' . admin_url($section) . '">بازگشت</a></div>';
        $content = ob_get_clean();
        require ADMIN_PATH . '/views/layout/main.php';
        exit;
    }
    Auth::verifyCsrf();
}

// ---------- بررسی دسترسی ----------
// اجازه‌ی سطح بخش (مشاهده) + اجازه‌ی اختصاصی اکشن اگر کنترلر تعریف کرده باشد
Auth::requirePermission($viewPermission);

if (method_exists($controller, 'permissionFor')) {
    $specific = $controller->permissionFor($method);
    if ($specific) {
        Auth::requirePermission($specific);
    }
}

$controller->{$method}($id);
