<?php
global $settings;
$site_name = $settings['site_title'] ?? 'پرادو یدک';

// اگر متغیر pageTitle از سمت کنترلر (Controller) مقداردهی نشده بود، بر اساس آدرس (URI) آن را تنظیم کن
if (!isset($pageTitle)) {
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($uri !== '/' && substr($uri, -1) === '/') {
        $uri = rtrim($uri, '/');
    }

    $defaultTitles = [
        '/' => $site_name,
        '/index' => $site_name,
        '/parts' => 'جستجو و خرید قطعات تویوتا | ' . $site_name,
        '/blog' => 'وبلاگ و راهنمای فنی تویوتا | ' . $site_name,
        '/login' => 'ورود / ثبت‌نام | ' . $site_name,
        '/profile' => 'پنل کاربری و پروفایل | ' . $site_name,
        '/terms' => 'قوانین، مقررات و ضمانت اصالت | ' . $site_name,
        '/checkout' => 'تسویه حساب | ' . $site_name,
        '/404' => 'صفحه پیدا نشد | ' . $site_name
    ];

    $pageTitle = $defaultTitles[$uri] ?? $site_name;
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
<title><?php echo e($pageTitle); ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<?php
$uri_for_scripts = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri_for_scripts === '/' || $uri_for_scripts === '/index'):
    global $car_models, $part_categories, $parts_database;
    ?>
    <script>
        window.dynamicSettings = <?php echo json_encode($settings ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicCarModels = <?php echo json_encode($car_models ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicPartCategories = <?php echo json_encode($part_categories ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicPartsDatabase = <?php echo json_encode($parts_database ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false' ?>;
    </script>
    <script src="/_sdk/element_sdk.js"></script>
    <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
<?php endif; ?>

<?php if (isset($schemaMarkup)): ?>
    <?= $schemaMarkup ?>
<?php endif; ?>

<link rel="icon" type="image/webp" href="/assets/logo/logo.webp">
<link rel="stylesheet" href="/assets/css/style.css">