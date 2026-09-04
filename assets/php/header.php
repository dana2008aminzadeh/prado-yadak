<?php

global $settings;

$is_logged_in = isset($_SESSION['user_id']);

$current_page = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// حذف اسلش اضافی در انتهای آدرس (به جز صفحه اصلی)
if ($current_page !== '/' && substr($current_page, -1) === '/') {
    $current_page = rtrim($current_page, '/');
}

// اگر کاربر در ریشه سایت بود، آن را به عنوان صفحه اصلی در نظر بگیر
if ($current_page === '/' || $current_page === '') {
    $current_page = '/index';
}

// تابع استایل‌دهی منوی دسکتاپ
function getDesktopClass($pageName, $currentPage)
{
    $activeClass = 'text-brand-accent font-bold relative after:content-[""] after:absolute after:-bottom-2 after:left-0 after:w-full after:h-0.5 after:bg-brand-accent after:rounded-full';
    $inactiveClass = 'text-gray-300 hover:text-white transition';

    if ($pageName == '/blog' && in_array($currentPage, ['/blog', '/blog-detail']))
        return $activeClass;
    if ($pageName == '/parts' && in_array($currentPage, ['/parts', '/product']))
        return $activeClass;

    return ($pageName == $currentPage) ? $activeClass : $inactiveClass;
}

// تابع استایل‌دهی منوی موبایل
function getMobileClass($pageName, $currentPage)
{
    $activeClass = 'block py-2 px-3 rounded-lg text-brand-accent font-bold bg-brand-accent/10 border-r-2 border-brand-accent';
    $inactiveClass = 'block py-2 px-3 rounded-lg text-gray-300 hover:bg-white/5 transition';

    if ($pageName == '/blog' && in_array($currentPage, ['/blog', '/blog-detail']))
        return $activeClass;
    if ($pageName == '/parts' && in_array($currentPage, ['/parts', '/product']))
        return $activeClass;

    return ($pageName == $currentPage) ? $activeClass : $inactiveClass;
}

// آرایه منوهای اصلی سایت
$menu_items = [
    '/index' => 'صفحه اصلی',
    '/parts' => 'مشاهده قطعات',
    '/blog' => 'وبلاگ فنی',
    '/terms' => 'قوانین و ضمانت'
];

// لیست صفحاتی که دکمه‌های شناور واتساپ و تماس نباید در آن‌ها نمایش داده شوند
$hide_floating_buttons_on = ['/index', '/blog', '/login', '/404'];
?>

<?php
// بررسی شرط: اگر صفحه فعلی در لیست بالا نبود، دکمه‌ها را نمایش بده
if (!in_array($current_page, $hide_floating_buttons_on)):
    ?>
    <!-- دکمه‌های شناور مشاوره -->
    <div class="fixed bottom-6 left-6 z-50 flex flex-col gap-3 anim-fade-up">
        <a href="<?= e($settings['whatsapp_link'] ?? 'https://wa.me/989189998852') ?>" target="_blank"
            class="w-12 h-12 bg-emerald-500 rounded-full flex items-center justify-center text-white shadow-[0_4px_20px_rgba(16,185,129,0.4)] hover:bg-emerald-600 transition-transform hover:scale-110 group relative">
            <i data-lucide="message-circle" style="width:24px;height:24px;"></i>
            <span
                class="absolute left-14 bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none shadow-md hidden sm:block">مشاوره
                در واتساپ</span>
        </a>
        <a href="tel:<?= e($settings['phone_number'] ?? '09189998852') ?>"
            class="w-12 h-12 bg-brand-red rounded-full flex items-center justify-center text-white shadow-[0_4px_20px_rgba(225,6,0,0.4)] hover:bg-red-700 transition-transform hover:scale-110 group relative">
            <i data-lucide="phone" style="width:24px;height:24px;"></i>
            <span
                class="absolute left-14 bg-brand-red text-white text-xs font-bold px-3 py-1.5 rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none shadow-md hidden sm:block">مشاوره
                تلفنی سریع</span>
        </a>
    </div>
<?php endif; ?>

<!-- نوار ناوبری اصلی (Navbar) -->
<nav class="sticky top-0 z-40 bg-brand-dark/95 backdrop-blur-md border-b border-white/10">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">

        <!-- دکمه منو همبرگری -->
        <button class="md:hidden text-brand-accent p-2 hover:bg-white/5 rounded-lg transition order-1"
            onclick="toggleMobileMenu(true)">
            <i data-lucide="menu" style="width:24px;height:24px;"></i>
        </button>

        <!-- لوگو و برند -->
        <a href="/index" class="flex items-center gap-2.5 sm:gap-3 order-2 md:order-1">
            <img src="assets/logo/logo.webp" alt="<?= e($settings['site_title'] ?? 'پرادو یدک') ?>"
                class="h-10 sm:h-12 w-auto object-contain transition-transform duration-300 group-hover:scale-105">
            <div class="text-center md:text-right">
                <h1 class="text-base sm:text-lg font-extrabold leading-tight">
                    <?= e($settings['site_title'] ?? 'پرادو یدک') ?>
                </h1>
                <p class="text-[9px] sm:text-[10px] text-gray-400 tracking-wider">
                    <?= e($settings['site_subtitle'] ?? 'PRADO YADAK') ?>
                </p>
            </div>
        </a>

        <!-- لینک‌های دسترسی دسکتاپ -->
        <div class="hidden md:flex items-center gap-6 text-sm font-medium md:order-2">
            <?php foreach ($menu_items as $url => $title): ?>
                <a href="<?= $url ?>" class="<?= getDesktopClass($url, $current_page) ?>">
                    <?= $title ?>
                </a>
            <?php endforeach; ?>

            <!-- لینک پروفایل در منوی متنی دسکتاپ (فقط در صورت لاگین) -->
            <?php if ($is_logged_in): ?>
                <a href="/profile" class="<?= getDesktopClass('/profile', $current_page) ?>">پنل کاربری</a>
            <?php endif; ?>
        </div>

        <!-- دکمه‌های دسکتاپ (ورود یا پروفایل+سبد خرید) -->
        <div class="hidden md:flex items-center gap-3 md:order-3">
            <?php if ($is_logged_in): ?>
                <!-- حالت لاگین شده -->
                <a href="/profile"
                    class="flex items-center gap-2 border border-white/20 text-white text-sm font-bold px-4 py-2 rounded-lg transition hover:border-brand-accent">
                    <i data-lucide="user-check" style="width:16px;height:16px;"></i> پروفایل
                </a>
                <button onclick="toggleCart(true)"
                    class="flex items-center gap-2 bg-brand-red hover:bg-red-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition relative">
                    <i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> سبد خرید
                    <span id="header-cart-badge"
                        class="absolute -top-2 -left-2 bg-white text-brand-red font-black text-xs w-5 h-5 rounded-full hidden items-center justify-center border-2 border-brand-red animate-bounce">0</span>
                </button>
            <?php else: ?>
                <!-- حالت لاگین نشده -->
                <a href="/login"
                    class="flex items-center gap-2 border border-brand-accent text-brand-accent text-sm font-bold px-4 py-2 rounded-lg transition bg-brand-accent/10 hover:bg-brand-accent hover:text-white">
                    <i data-lucide="log-in" style="width:16px;height:16px;"></i> ورود / ثبت‌نام
                </a>
            <?php endif; ?>
        </div>

        <!-- آیکون موبایل (سمت چپ هدر) -->
        <?php if ($is_logged_in): ?>
            <!-- در صورت لاگین: آیکون پروفایل -->
            <a href="/profile"
                class="md:hidden order-3 flex items-center justify-center w-10 h-10 rounded-full bg-brand-grey border border-brand-accent text-brand-accent hover:bg-brand-accent hover:text-white transition shrink-0 overflow-hidden">
                <i data-lucide="user-check" style="width:20px;height:20px;"></i>
            </a>
        <?php else: ?>
            <!-- در صورت لاگین نبودن: آیکون ورود -->
            <a href="/login"
                class="md:hidden order-3 flex items-center justify-center w-10 h-10 rounded-full bg-brand-grey border border-white/20 text-white hover:border-brand-accent transition shrink-0 overflow-hidden">
                <i data-lucide="log-in" style="width:20px;height:20px;"></i>
            </a>
        <?php endif; ?>

    </div>
</nav>

<!-- لایه تاریک پس‌زمینه منوی موبایل -->
<div id="mobile-menu-overlay"
    class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"
    onclick="toggleMobileMenu(false)"></div>

<!-- منوی کشویی موبایل -->
<div id="mobile-menu"
    class="fixed top-0 right-0 bottom-0 w-72 z-50 p-6 flex flex-col justify-between border-l border-white/10 translate-x-full transition-transform duration-300 ease-in-out hidden shadow-[0_0_50px_rgba(0,0,0,0.8)] bg-brand-grey">
    <div>
        <div class="flex items-center justify-between pb-4 border-b border-white/10 mb-6">
            <span class="font-extrabold text-sm text-gray-400">منوی دسترسی</span>
            <button class="text-brand-accent p-1 hover:bg-white/10 rounded-lg transition"
                onclick="toggleMobileMenu(false)">
                <i data-lucide="x" style="width:24px;height:24px;"></i>
            </button>
        </div>

        <div class="space-y-3 text-base">
            <?php foreach ($menu_items as $url => $title): ?>
                <a href="<?= $url ?>" class="<?= getMobileClass($url, $current_page) ?>" onclick="toggleMobileMenu(false)">
                    <?= $title ?>
                </a>
            <?php endforeach; ?>

            <?php if ($is_logged_in): ?>
                <!-- نمایش منوی پروفایل داخل لیست موبایل فقط در صورت لاگین -->
                <a href="/profile" class="<?= getMobileClass('/profile', $current_page) ?>"
                    onclick="toggleMobileMenu(false)">پنل کاربری من</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- بخش دکمه‌های پایین منوی موبایل -->
    <div class="pt-4 border-t border-white/10 flex flex-col gap-2">
        <?php if ($is_logged_in): ?>
            <!-- در صورت لاگین: دکمه‌های پروفایل و سبد خرید -->
            <a href="/profile"
                class="flex items-center justify-center gap-2 border border-white/20 text-white py-2.5 rounded-lg text-center font-bold hover:border-brand-accent transition">
                <i data-lucide="user-check" style="width:16px;height:16px;"></i> پنل کاربری من
            </a>
            <button
                class="flex items-center justify-center gap-2 text-white py-2.5 rounded-lg font-bold transition hover:bg-red-700 md:hidden shadow-[0_4px_15px_rgba(225,6,0,0.2)] bg-brand-red"
                onclick="toggleMobileMenu(false); toggleCart(true);">
                <i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> سبد خرید
            </button>
        <?php else: ?>
            <!-- در صورت لاگین نبودن: فقط دکمه ورود و ثبت نام -->
            <a href="/login"
                class="flex items-center justify-center gap-2 border border-brand-accent text-brand-accent py-2.5 rounded-lg text-center font-bold bg-brand-accent/10 transition hover:bg-brand-accent hover:text-white">
                <i data-lucide="log-in" style="width:16px;height:16px;"></i> ورود / ثبت‌نام
            </a>
        <?php endif; ?>
    </div>
</div>