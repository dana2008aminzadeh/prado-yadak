<?php
global $settings;
$site_name = $settings['site_title'] ?? 'پرادو یدک';

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
$hostUrl = $protocol . "://" . $host;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}

if ($uri === '/' || $uri === '/index') {
    $canonicalUrl = $hostUrl . '/';
} elseif (str_starts_with($uri, '/product/') && isset($product['slug'])) {
    $canonicalUrl = $hostUrl . "/product/" . urlencode($product['slug']);
} elseif ($uri === '/parts') {
    $canonicalParams = [];
    if (!empty($_GET['category']))
        $canonicalParams['category'] = $_GET['category'];
    if (!empty($_GET['model']))
        $canonicalParams['model'] = $_GET['model'];
    if (!empty($_GET['brand']))
        $canonicalParams['brand'] = $_GET['brand'];
    if (!empty($_GET['page']) && (int) $_GET['page'] > 1)
        $canonicalParams['page'] = (int) $_GET['page'];

    $canonicalUrl = $hostUrl . '/parts';
    if (!empty($canonicalParams)) {
        $canonicalUrl .= '?' . http_build_query($canonicalParams);
    }
} else {
    $canonicalUrl = $hostUrl . $uri;
}

// ۱. مدیریت عنوان صفحات (Title)
if (!isset($pageTitle)) {
    $defaultTitles = [
        '/' => $site_name . ' | مرجع تخصصی قطعات اصلی تویوتا و لکسوس',
        '/index' => $site_name . ' | مرجع تخصصی قطعات اصلی تویوتا و لکسوس',
        '/blog' => 'وبلاگ و دانشنامه فنی تویوتا | ' . $site_name,
        '/login' => 'ورود و ثبت‌نام | ' . $site_name,
        '/profile' => 'پنل کاربری | ' . $site_name,
        '/terms' => 'قوانین و ضمانت اصالت کالا | ' . $site_name,
        '/checkout' => 'تسویه حساب و پرداخت | ' . $site_name,
        '/404' => 'صفحه مورد نظر یافت نشد | ' . $site_name
    ];

    if ($uri === '/parts') {
        $partsTitle = 'کاتالوگ و قیمت قطعات یدکی تویوتا';

        if (!empty($_GET['category']) && isset($GLOBALS['part_categories'][$_GET['category']])) {
            $catData = $GLOBALS['part_categories'][$_GET['category']];
            $catName = is_array($catData) ? ($catData['name'] ?? '') : $catData;
            $partsTitle = 'خرید قطعات ' . $catName . ' تویوتا';
        } elseif (!empty($_GET['model']) && isset($GLOBALS['car_models'][$_GET['model']])) {
            $modData = $GLOBALS['car_models'][$_GET['model']];
            $modName = is_array($modData) ? ($modData['name'] ?? '') : $modData;
            $partsTitle = 'خرید قطعات تویوتا ' . $modName;
        }

        $pageTitle = $partsTitle . ' | ' . $site_name;
    } else {
        $pageTitle = $defaultTitles[$uri] ?? ($site_name . ' | قطعات یدکی تویوتا');
    }
}

// ۲. متای توضیحات (Meta Description)
$defaultDesc = 'فروشگاه تخصصی پرادو یدک؛ تامین قطعات اصلی جنیون پارت تویوتا و لکسوس با ضمانت ۱۰۰٪ اصالت، تطابق با شماره شاسی (VIN) و ارسال سریع به سراسر کشور.';
$finalMetaDesc = $metaDescription ?? $defaultDesc;

// ۳. تولید آدرس کانونیکال (حفظ دسته‌بندی، مدل و صفحه برای پیجینیشن)
$canonicalUrl = $hostUrl . ($uri === '/index' ? '/' : $uri);
if ($uri === '/parts') {
    $canonicalParams = [];
    if (!empty($_GET['category']))
        $canonicalParams['category'] = $_GET['category'];
    if (!empty($_GET['model']))
        $canonicalParams['model'] = $_GET['model'];
    if (!empty($_GET['page']) && (int) $_GET['page'] > 1)
        $canonicalParams['page'] = (int) $_GET['page'];

    if (!empty($canonicalParams)) {
        $canonicalUrl .= '?' . http_build_query($canonicalParams);
    }
}

// ۴. مدیریت ربات‌ها (جلوگیری از ایندکس فیلترهای تکراری و صفحات خصوصی)
$noindexParams = ['sort', 'maxPrice', 'q', 'inStock'];
$shouldNoIndex = false;

foreach ($noindexParams as $param) {
    if (isset($_GET[$param]) && trim((string) $_GET[$param]) !== '') {
        $shouldNoIndex = true;
        break;
    }
}

if (http_response_code() === 404 || in_array($uri, ['/404', '/checkout', '/profile', '/login'])) {
    $shouldNoIndex = true;
}

$robotsMeta = $shouldNoIndex ? 'noindex, follow' : 'index, follow';

// ۵. تصویر و نوع صفحه برای شبکه‌های اجتماعی (Open Graph)
$ogImage = $pageImage ?? ($hostUrl . '/assets/logo/logo.webp');
$ogType = (str_starts_with($uri, '/product') || $uri === '/product') ? 'product' : 'website';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="theme-color" content="#251E1B">

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? ''; ?>">
<meta name="robots" content="<?= $robotsMeta; ?>">

<title><?= e($pageTitle); ?></title>
<meta name="description" content="<?= e($finalMetaDesc); ?>">
<link rel="canonical" href="<?= e($canonicalUrl); ?>">

<!-- متاتگ‌های شبکه‌های اجتماعی و پیام‌رسان‌ها (Open Graph & Twitter Cards) -->
<meta property="og:site_name" content="<?= e($site_name); ?>">
<meta property="og:title" content="<?= e($pageTitle); ?>">
<meta property="og:description" content="<?= e($finalMetaDesc); ?>">
<meta property="og:url" content="<?= e($canonicalUrl); ?>">
<meta property="og:type" content="<?= $ogType; ?>">
<meta property="og:locale" content="fa_IR">
<meta property="og:image" content="<?= e($ogImage); ?>">
<meta property="og:image:alt" content="<?= e($pageTitle); ?>">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle); ?>">
<meta name="twitter:description" content="<?= e($finalMetaDesc); ?>">
<meta name="twitter:image" content="<?= e($ogImage); ?>">

<!-- آیکون‌ها -->
<link rel="icon" type="image/webp" href="/assets/logo/logo.webp">
<link rel="apple-touch-icon" href="/assets/logo/logo.webp">

<!-- فایل استایل اصلی کامپایل‌شده -->
<link rel="stylesheet" href="/assets/css/style.css">

<!-- متغیرهای پایه سمت کلاینت برای جاوااسکریپت -->
<script>
    window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>

<!-- آیکون‌های Lucide -->
<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js" defer></script>

<!-- کدهای اسکیما (در صورت وجود در کنترلر) -->
<?php if (isset($schemaMarkup)): ?>
    <?= $schemaMarkup; ?>
<?php endif; ?>