<?php
/**
 * هدر مشترک صفحات — لایه خروجی سئو
 * ---------------------------------------------------------------------------
 * کنترلرها متاها را از Core\Seo می‌گیرند؛ این لایه آخرین اعتبارسنجی canonical،
 * description و robots را پیش از چاپ انجام می‌دهد. عنوان‌های بدون تعریفِ route
 * جدید تا زمان تکمیل محتوا اجازه ایندکس ندارند.
 *
 * متغیرهای قابل تنظیم توسط کنترلر:
 *   $pageTitle, $metaDescription, $canonicalUrl, $robotsMeta,
 *   $pageImage, $pageImageAlt, $pageImageWidth, $pageImageHeight,
 *   $schemaMarkup, $prevUrl, $nextUrl
 */

use Core\Seo;

global $settings;
$site_name = $settings['site_title'] ?? 'پرادو یدک';

$hostUrl = Seo::base();
$uri = Seo::currentPath();

// -----------------------------------------------------------------------------
// توجه: هیچ ریدایرکتی در این فایل انجام نمی‌شود.
// نرمال‌سازی آدرس (حذف اسلش پایانی، /index → /) وظیفه‌ی Front Controller است
// و در index.php پیش از هر خروجی انجام می‌گیرد؛ چون در لایه قالب ممکن است
// بخشی از خروجی ارسال شده باشد و header() دیگر کار نکند.
// -----------------------------------------------------------------------------

// ---------------------------------------------------------------- کانونیکال
// حتی مقدار دستی کنترلر/پنل نیز نباید هاست دیگر، fragment یا query زائد داشته باشد.
$canonicalUrl = Seo::canonical($canonicalUrl ?? null, [
    'article' => $article ?? null,
    'product' => $product ?? null,
    'landing' => $landingPage ?? null,
]);
$canonicalUrl = Seo::normalizeCanonicalHost($canonicalUrl);

// ---------------------------------------------------------------- عنوان
$missingTitle = !isset($pageTitle) || Seo::clean((string) $pageTitle) === '';
if ($missingTitle) {
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
        if (is_string($_GET['category'] ?? null) && isset($GLOBALS['part_categories'][$_GET['category']])) {
            $catData = $GLOBALS['part_categories'][$_GET['category']];
            $catName = is_array($catData) ? ($catData['name'] ?? '') : $catData;
            $partsTitle = 'خرید قطعات ' . $catName . ' تویوتا';
        } elseif (is_string($_GET['model'] ?? null) && isset($GLOBALS['car_models'][$_GET['model']])) {
            $modData = $GLOBALS['car_models'][$_GET['model']];
            $modName = is_array($modData) ? ($modData['name'] ?? '') : $modData;
            $partsTitle = 'خرید قطعات تویوتا ' . $modName;
        }
        $pageTitle = $partsTitle . ' | ' . $site_name;
    } elseif (isset($defaultTitles[$uri])) {
        $pageTitle = $defaultTitles[$uri];
    } else {
        // افزودن route عمومی بدون title اختصاصی نباید چند URL indexable یکسان بسازد.
        $pageTitle = 'محتوای این صفحه | ' . $site_name;
    }
}
$pageTitle = Seo::clean((string) $pageTitle);
$unmappedTitle = $missingTitle && $uri !== '/parts' && !isset($defaultTitles[$uri]);

// ---------------------------------------------------------------- توضیحات
$defaultDesc = 'فروشگاه تخصصی پرادو یدک؛ تامین قطعات اصلی جنیون پارت تویوتا و لکسوس با ضمانت بازگشت وجه در صورت اثبات عدم اصالت، تطابق با شماره شاسی (VIN) و ارسال سریع به سراسر کشور.';
$isProductPage = str_starts_with($uri, '/product') && isset($product) && is_array($product);
$finalMetaDesc = Seo::metaDescription($metaDescription ?? null, $defaultDesc, $isProductPage ? $product : null, $site_name);

// ---------------------------------------------------------------- ربات‌ها
$privatePages = ['/404', '/checkout', '/order/success', '/profile', '/login'];
$isPrivateUri = in_array($uri, $privatePages, true) || str_starts_with($uri, '/order/');
if (http_response_code() >= 400 || $isPrivateUri) {
    $robotsMeta = 'noindex, nofollow';
} elseif ($unmappedTitle) {
    $robotsMeta = 'noindex, follow';
    error_log('Indexable route without SEO title: ' . $uri);
} elseif ($uri === '/parts' && Seo::catalogRobots($_GET) !== 'index, follow') {
    // فیلتر ناشناخته یا کم‌ارزش حتی با robots اشتباه کنترلر index نمی‌شود.
    $robotsMeta = 'noindex, follow';
} else {
    $robotsMeta = $robotsMeta ?? 'index, follow';
}
$robotsMeta = trim((string) $robotsMeta);
if (!in_array($robotsMeta, ['index, follow', 'noindex, follow', 'noindex, nofollow'], true)) {
    $robotsMeta = 'noindex, follow';
}
Seo::emitNoindexHeader($robotsMeta);

// ---------------------------------------------------------------- تصویر اشتراک‌گذاری
// در صفحه محصول بدون عکس، لوگو نباید به‌عنوان تصویر همان محصول به موتور جستجو
// معرفی شود. صفحات عمومی سایت همچنان می‌توانند لوگو را fallback داشته باشند.
$ogImage = !empty($pageImage) ? (string) $pageImage : ($isProductPage ? '' : $hostUrl . '/assets/logo/logo.webp');
if ($ogImage !== '' && !str_starts_with($ogImage, 'http')) {
    $ogImage = $hostUrl . '/' . ltrim($ogImage, '/');
}
$ogImageAlt = Seo::clean($pageImageAlt ?? null) ?: $pageTitle;
// اندازه تصویر فقط وقتی واقعاً معلوم است ارسال شود (نه حدس ۶۰۰×۶۰۰ برای /media/).
$ogImageWidth = filter_var($pageImageWidth ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$ogImageHeight = filter_var($pageImageHeight ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
if ($ogImage === $hostUrl . '/assets/logo/logo.webp') {
    $logoPath = __DIR__ . '/../logo/logo.webp';
    $logoSize = is_file($logoPath) ? @getimagesize($logoPath) : false;
    $ogImageWidth = $logoSize[0] ?? null;
    $ogImageHeight = $logoSize[1] ?? null;
}
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

<?php
// ---------------------------------------------------------------- تایید مالکیت و مانیتورینگ
// اتصال واقعی به Google Search Console / Bing Webmaster Tools از طریق تنظیمات
// پنل مدیریت (بدون نیاز به دست‌کاری کد) و بدون افشای هیچ کلید مخفی؛ فقط رشته
// تایید HTML meta که گوگل/بینگ در ثبت Property درخواست می‌کند.
$gscVerification = trim((string) ($settings['gsc_verification_content'] ?? ''));
$bingVerification = trim((string) ($settings['bing_verification_content'] ?? ''));
$ga4Id = trim((string) ($settings['ga4_measurement_id'] ?? ''));
$gtmId = trim((string) ($settings['gtm_container_id'] ?? ''));
?>
<?php if ($gscVerification !== ''): ?>
<meta name="google-site-verification" content="<?= e($gscVerification); ?>">
<?php endif; ?>
<?php if ($bingVerification !== ''): ?>
<meta name="msvalidate.01" content="<?= e($bingVerification); ?>">
<?php endif; ?>

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
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= e($ogImage); ?>">
<meta property="og:image:alt" content="<?= e($ogImageAlt); ?>">
<?php if ($ogImageWidth !== null && $ogImageHeight !== null): ?>
<meta property="og:image:width" content="<?= (int) $ogImageWidth; ?>">
<meta property="og:image:height" content="<?= (int) $ogImageHeight; ?>">
<?php endif; ?>
<?php endif; ?>
<meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($pageTitle); ?>">
<meta name="twitter:description" content="<?= e($finalMetaDesc); ?>">
<meta name="twitter:url" content="<?= e($canonicalUrl); ?>">
<?php if ($ogImage !== ''): ?>
<meta name="twitter:image" content="<?= e($ogImage); ?>">
<meta name="twitter:image:alt" content="<?= e($ogImageAlt); ?>">
<?php endif; ?>

<?php
// صفحه‌بندی: برای کاتالوگ و لندینگ‌های دسته/مدل/دستی یکسان است.
$catalogBasePath = $catalogBasePath ?? (str_starts_with($uri, '/parts/') ? $uri : null);
$hasCatalogPagination = http_response_code() === 200 && ($uri === '/parts' || $catalogBasePath !== null)
    && isset($page, $totalPages) && $totalPages > 1 && $page >= 1 && $page <= $totalPages;
if ($hasCatalogPagination):
    $buildPageUrl = function ($pageNum) use ($catalogBasePath) {
        // لندینگ دسته/مدل فقط پارامتر page می‌گیرد؛ کاتالوگ عمومی پارامترهای
        // مجاز خود را با ترتیب ثابت نگه می‌دارد.
        if (!empty($catalogBasePath)) {
            return Seo::normalizeCanonicalHost(Seo::absolute($catalogBasePath) . ($pageNum > 1 ? '?page=' . $pageNum : ''));
        }
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

<?php if (!$hasCatalogPagination && !empty($prevUrl) && http_response_code() < 400): ?>
    <?php $safePrev = Seo::normalizeCanonicalHost((string) $prevUrl); ?>
    <?php if ($safePrev !== $canonicalUrl): ?><link rel="prev" href="<?= e($safePrev); ?>"><?php endif; ?>
<?php endif; ?>
<?php if (!$hasCatalogPagination && !empty($nextUrl) && http_response_code() < 400): ?>
    <?php $safeNext = Seo::normalizeCanonicalHost((string) $nextUrl); ?>
    <?php if ($safeNext !== $canonicalUrl): ?><link rel="next" href="<?= e($safeNext); ?>"><?php endif; ?>
<?php endif; ?>

<!-- آیکون‌ها -->
<link rel="icon" type="image/webp" href="/assets/logo/logo.webp">
<link rel="apple-touch-icon" href="/assets/logo/logo.webp">

<!-- پیش‌بارگذاری وزن معمولی فونت اصلی سایت (بیشترین استفاده در متن بدنه)
     برای کاهش FOIT/CLS ناشی از دیرکرد بارگذاری فونت -->
<link rel="preload" href="/assets/font/IRANSans.ttf" as="font" type="font/ttf" crossorigin>

<!-- فایل استایل اصلی کامپایل‌شده -->
<link rel="stylesheet" href="/assets/css/style.css">

<!-- متغیرهای پایه سمت کلاینت برای جاوااسکریپت -->
<script>
    window.isLoggedIn = <?= isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
</script>

<!-- آیکون‌های Lucide، نسخه ثابت روی همین سرور (بدون اتصال به CDN در مسیر رندر) -->
<script src="/assets/js/vendor/lucide-0.468.0.min.js" defer></script>

<!-- داده‌های ساختاریافته یکپارچه (JSON-LD @graph) -->
<?php if (!empty($schemaMarkup)): ?>
    <?= $schemaMarkup; ?>
<?php endif; ?>

<?php
// ---------------------------------------------------------------- مانیتورینگ Core Web Vitals / ترافیک
// GA4 (که فیلد اصلی گزارش Core Web Vitals میدانی و ترافیک واقعی است) فقط با
// شناسه‌ی واقعی ثبت‌شده در تنظیمات فعال می‌شود؛ صفحات noindex (چک‌اوت و admin
// از قبل خارج از این include هستند) بدون تغییر ردیابی می‌شوند.
if (!empty($gtmId) && preg_match('/^GTM-[A-Z0-9]+$/i', $gtmId)):
?>
<script>
(function (w, d, s, l, i) {
    w[l] = w[l] || []; w[l].push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    var f = d.getElementsByTagName(s)[0], j = d.createElement(s), dl = l !== 'dataLayer' ? '&l=' + l : '';
    j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
})(window, document, 'script', 'dataLayer', '<?= e($gtmId); ?>');
</script>
<?php elseif (!empty($ga4Id) && preg_match('/^G-[A-Z0-9]+$/i', $ga4Id)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($ga4Id); ?>"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag() { dataLayer.push(arguments); }
    gtag('js', new Date());
    gtag('config', '<?= e($ga4Id); ?>', { anonymize_ip: true });
</script>
<?php endif; ?>
