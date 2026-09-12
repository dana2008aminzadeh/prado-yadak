<?php
global $settings;
$site_name = $settings['site_title'] ?? 'پرادو یدک';

$hostUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

if (!isset($pageTitle)) {
    $defaultTitles = [
        '/' => $site_name,
        '/index' => $site_name,
        '/blog' => 'وبلاگ و راهنمای فنی تویوتا | ' . $site_name,
        '/login' => 'ورود / ثبت‌نام | ' . $site_name,
        '/profile' => 'پنل کاربری و پروفایل | ' . $site_name,
        '/terms' => 'قوانین، مقررات و ضمانت اصالت | ' . $site_name,
        '/checkout' => 'تسویه حساب | ' . $site_name,
        '/404' => 'صفحه پیدا نشد | ' . $site_name
    ];

    if ($uri === '/parts') {
        $partsTitle = 'جستجو و خرید قطعات تویوتا';

        if (!empty($_GET['category']) && isset($GLOBALS['part_categories'][$_GET['category']])) {
            $catData = $GLOBALS['part_categories'][$_GET['category']];
            $catName = is_array($catData) ? ($catData['name'] ?? '') : $catData;
            $partsTitle = 'خرید ' . $catName . ' تویوتا';
        } elseif (!empty($_GET['model']) && isset($GLOBALS['car_models'][$_GET['model']])) {
            $modData = $GLOBALS['car_models'][$_GET['model']];
            $modName = is_array($modData) ? ($modData['name'] ?? '') : $modData;
            $partsTitle = 'خرید قطعات ' . $modName;
        }

        $pageTitle = $partsTitle . ' | ' . $site_name;
    } else {
        $pageTitle = $defaultTitles[$uri] ?? $site_name;
    }
}

$canonicalUrl = $hostUrl . ($uri === '/index' ? '/' : $uri);

if ($uri === '/parts') {
    $canonicalParams = [];
    if (!empty($_GET['category']))
        $canonicalParams['category'] = $_GET['category'];
    if (!empty($_GET['model']))
        $canonicalParams['model'] = $_GET['model'];

    if (!empty($canonicalParams)) {
        $canonicalUrl .= '?' . http_build_query($canonicalParams);
    }
}

$defaultDesc = 'پرادو یدک، تامین‌کننده تخصصی قطعات جنیون و اصلی تویوتا و لکسوس با ضمانت ۱۰۰٪ اصالت کالا و ارسال سریع به سراسر ایران.';
$finalMetaDesc = $metaDescription ?? $defaultDesc;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

<title><?php echo e($pageTitle); ?></title>
<meta name="description" content="<?php echo e($finalMetaDesc); ?>">
<link rel="canonical" href="<?php echo e($canonicalUrl); ?>" />

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.tailwindcss.com"></script>

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