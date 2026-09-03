<?php
// ۱. تشخیص نام فایلی که در حال اجراست
$current_file = basename($_SERVER['PHP_SELF']);

// ۲. تنظیم خودکار تایتل‌ها بر اساس نام فایل‌ها
$titles = [
    '/index' => 'پرادو یدک',
    '/parts' => 'جستجو و خرید قطعات تویوتا | پرادو یدک',
    '/product' => 'جزئیات قطعه | پرادو یدک',
    '/blog' => 'وبلاگ و راهنمای فنی تویوتا | پرادو یدک',
    '/blog-detail' => 'جزئیات مقاله | وبلاگ فنی پرادو یدک',
    '/login' => 'ورود / ثبت‌نام | پرادو یدک',
    '/profile' => 'پنل کاربری و پروفایل | پرادو یدک',
    '/terms' => 'قوانین، مقررات و ضمانت اصالت | پرادو یدک',
    '/checkout' => 'صفحه پرداخت | پرادو یدک',
    '/404' => 'صفحه مورد نظر پیدا نشد (خطای ۴۰۴) | پرادو یدک'
];

// ۳. گرفتن تایتل مربوطه (اگر فایلی در لیست نبود، تایتل پیش‌فرض قرار می‌گیرد)
$pageTitle = isset($titles[$current_file]) ? $titles[$current_file] : 'پرادو یدک';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $pageTitle; ?></title>

<script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js" defer></script>

<?php
if ($current_file === '/index'):
    global $settings, $car_models, $part_categories, $parts_database;
    ?>
    <script>
        window.dynamicSettings = <?php echo json_encode($settings ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicCarModels = <?php echo json_encode($car_models ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicPartCategories = <?php echo json_encode($part_categories ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.dynamicPartsDatabase = <?php echo json_encode($parts_database ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="/_sdk/element_sdk.js"></script>
    <script src="/_sdk/data_sdk.js" type="text/javascript"></script>
<?php endif; ?>
<link rel="icon" type="image/webp" href="assets/logo/logo.webp">

<link rel="stylesheet" href="assets/css/style.css">