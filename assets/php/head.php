<?php
/**
 * هدر مشترک صفحات — لایه خروجی سئو
 * ---------------------------------------------------------------------------
 * منطق سئو دیگر اینجا «حدس زده» نمی‌شود؛ این فایل فقط مقادیری را چاپ می‌کند که
 * کنترلرها از طریق Core\Seo محاسبه و تحویل داده‌اند. اولویت همیشه با مقادیر
 * دستی ثبت‌شده در پنل مدیریت است و در نبود آن‌ها فرمول خودکار عمل می‌کند.
 *
 * متغیرهای قابل تنظیم توسط کنترلر:
 *   $pageTitle, $metaDescription, $canonicalUrl, $robotsMeta,
 *   $pageImage, $pageImageAlt, $schemaMarkup, $prevUrl, $nextUrl
 */

use Core\Seo;

global $settings;
$site_name = $settings['site_title'] ?? 'پرادو یدک';

$hostUrl = Seo::base();
$uri = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');

// حذف اسلش پایانی (یک نسخه واحد از هر آدرس)
if ($uri !== '/' && substr($uri, -1) === '/') {
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . rtrim($uri, '/') . ($qs ? '?' . $qs : ''), true, 301);
    exit;
}

// ---------------------------------------------------------------- کانونیکال
if (!isset($canonicalUrl)) {
    if ($uri === '/' || $uri === '/index' || $uri === '') {
        $canonicalUrl = $hostUrl . '/';
    } elseif (isset($product['slug']) && (str_starts_with($uri, '/product'))) {
        $canonicalUrl = $hostUrl . '/product/' . rawurlencode((string) $product['slug']);
    } elseif (isset($article['slug']) && (str_starts_with($uri, '/blog') || $uri === '/blog-detail')) {
        $canonicalUrl = $hostUrl . '/blog/' . rawurlencode((string) $article['slug']);
    } elseif ($uri === '/parts') {
        // ترتیب پارامترها همیشه نرمال می‌شود تا نسخه‌های موازی ساخته نشود
        $canonicalUrl = Seo::catalogCanonical($_GET, '/parts');
    } else {
        $canonicalUrl = $hostUrl . $uri;
    }
}

// ---------------------------------------------------------------- عنوان
if (!isset($pageTitle)) {
    $defaultTitles = [
        '/' => $site_name . ' | مرجع تخصصی قطعات اصلی تویوتا و لکسوس',
        '/blog' => 'وبلاگ و دانشنامه فنی تویوتا | ' . $site_name,
        '/login' => 'ورود و ثبت‌نام | ' . $site_name,
        '/profile' => 'پنل کاربری | ' . $site_name,
        '/terms' => 'قوانین و ضمانت اصالت کالا | ' . $site_name,
        '/checkout' => 'تسویه حساب و پرداخت | ' . $site_name,
        '/order/success' => 'سفارش با موفقیت ثبت شد | ' . $site_name,
        '/404' => 'صفحه مورد نظر یافت نشد | ' . $site_name,
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

// ---------------------------------------------------------------- توضیحات
$defaultDesc = 'فروشگاه تخصصی پرادو یدک؛ تامین قطعات اصلی جنیون پارت تویوتا و لکسوس با ضمانت ۱۰۰٪ اصالت، تطابق با شماره شاسی (VIN) و ارسال سریع به سراسر کشور.';
$finalMetaDesc = $metaDescription ?? $defaultDesc;

// ---------------------------------------------------------------- ربات‌ها
if (!isset($robotsMeta)) {
    $privatePages = ['/404', '/checkout', '/order/success', '/profile', '/login'];
    $isPrivateUri = in_array($uri, $privatePages, true) || str_starts_with($uri, '/order/');

    if (http_response_code() === 404 || $isPrivateUri) {
        $robotsMeta = 'noindex, nofollow';
    } elseif ($uri === '/parts') {
        $robotsMeta = Seo::catalogRobots($_GET);
    } else {
        $robotsMeta = 'index, follow';
    }
}

// ---------------------------------------------------------------- تصویر اشتراک‌گذاری
$ogImage = $pageImage ?? ($hostUrl . '/assets/logo/logo.webp');
if (!str_starts_with($ogImage, 'http')) {
    $ogImage = $hostUrl . '/' . ltrim($ogImage, '/');
}
$ogImageAlt = $pageImageAlt ?? $pageTitle;
$ogType = (str_starts_with($uri, '/product')) ? 'product'
    : ((str_starts_with($uri, '/blog') && isset($article)) ? 'article' : 'website');

// اگر کنترلر گراف اسکیما نساخته بود، دست‌کم گره سازمان و وب‌سایت ارسال شود
if (!isset($schemaMarkup)) {
    $schemaMarkup = Seo::graph([
        Seo::organizationNode($settings ?? []),
        Seo::websiteNode($settings ?? []),
        Seo::webPageNode($canonicalUrl, $pageTitle, $finalMetaDesc, $ogImage),
    ]);
}
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="theme-color" content="#251E1B">

<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? ''; ?>">
<meta name="robots" content="<?= e($robotsMeta); ?>">
<meta name="googlebot" content="<?= e($robotsMeta); ?>, max-image-preview:large, max-snippet:-1, max-video-preview:-1">

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
<meta property="og:image:alt" content="<?= e($ogImageAlt); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle); ?>">
<meta name="twitter:description" content="<?= e($finalMetaDesc); ?>">
<meta name="twitter:image" content="<?= e($ogImage); ?>">

<?php
// صفحه‌بندی: prev/next روی آدرس نرمال‌شده ساخته می‌شود
if ($uri === '/parts' && isset($page, $totalPages) && $totalPages > 1):
    $buildPageUrl = function ($pageNum) {
        $params = $_GET;
        if ($pageNum > 1) {
            $params['page'] = $pageNum;
        } else {
            unset($params['page']);
        }
        return Seo::catalogCanonical($params, '/parts');
    };
    ?>
    <?php if ($page > 1): ?>
        <link rel="prev" href="<?= e($buildPageUrl($page - 1)); ?>">
    <?php endif; ?>
    <?php if ($page < $totalPages): ?>
        <link rel="next" href="<?= e($buildPageUrl($page + 1)); ?>">
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($prevUrl)): ?><link rel="prev" href="<?= e($prevUrl); ?>"><?php endif; ?>
<?php if (!empty($nextUrl)): ?><link rel="next" href="<?= e($nextUrl); ?>"><?php endif; ?>

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

<!-- داده‌های ساختاریافته یکپارچه (JSON-LD @graph) -->
<?php if (!empty($schemaMarkup)): ?>
    <?= $schemaMarkup; ?>
<?php endif; ?>
