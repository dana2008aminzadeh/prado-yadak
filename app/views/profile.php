<?php
/**
 * پنل کاربری — رندر سمت سرور با داده واقعی
 * متغیرها از UserController@profile می‌آیند.
 */
$user = $user ?? [];
$orderStats = $orderStats ?? ['total' => 0, 'processing' => 0, 'shipped' => 0, 'delivered' => 0, 'cancelled' => 0];
$orders = $orders ?? [];
$recentOrders = $recentOrders ?? [];
$addresses = $addresses ?? [];
$vehicles = $vehicles ?? [];
$wishlist = $wishlist ?? [];
$walletBalance = $walletBalance ?? 0;
$walletTransactions = $walletTransactions ?? [];
$tickets = $tickets ?? [];
$membership = $membership ?? ['key' => 'bronze', 'label' => 'عضو عادی', 'icon' => 'user'];
$userInitials = $userInitials ?? '؟';
$primaryVehicle = $primaryVehicle ?? null;
$openTicketsCount = $openTicketsCount ?? 0;
$wishlistCount = $wishlistCount ?? 0;
$vehiclesCount = $vehiclesCount ?? 0;
$addressesCount = $addressesCount ?? 0;
$activeTab = $activeTab ?? 'dashboard';
$carModelsList = $carModelsList ?? [];
$shopInfo = $shopInfo ?? [];

$fullName = $user['full_name'] ?? 'کاربر';
$phone = $user['phone'] ?? '';
$email = $user['email'] ?? '';
$nationalId = $user['national_id'] ?? '';
$city = $user['city'] ?? '';
$memberSince = !empty($user['created_at']) && function_exists('toShamsi')
    ? toShamsi($user['created_at'])
    : ($user['created_at'] ?? '—');

$statusBadgeClass = [
    'processing' => 'bg-amber-500/10 border-amber-500/20 text-amber-500',
    'shipped' => 'bg-blue-500/10 border-blue-500/20 text-blue-400',
    'delivered' => 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
    'cancelled' => 'bg-rose-500/10 border-rose-500/20 text-rose-400',
];
$statusIcon = [
    'processing' => 'package-search',
    'shipped' => 'truck',
    'delivered' => 'package-check',
    'cancelled' => 'package-x',
];
$ticketStatusClass = [
    'open' => 'bg-amber-500/10 border-amber-500/20 text-amber-400',
    'answered' => 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400',
    'pending' => 'bg-blue-500/10 border-blue-500/20 text-blue-400',
    'closed' => 'bg-gray-500/10 border-gray-500/20 text-gray-400',
];

function profileTabClass(string $tab, string $active): string
{
    if ($tab === $active) {
        return 'tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-brand-red bg-brand-red/10 border border-brand-red/30 font-bold text-sm';
    }
    return 'tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent';
}

function profilePageClass(string $tab, string $active): string
{
    return $tab === $active ? 'tab-page space-y-6 lg:space-y-8 block' : 'tab-page hidden space-y-6';
}
?>
<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
    <style>
        /* بج شمارنده منوی پروفایل — خوانا و یکدست */
        .profile-nav-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.45rem;
            border-radius: 9999px;
            background: #EB0A1E;
            color: #fff !important;
            font-size: 11px;
            font-weight: 800;
            line-height: 1;
            font-variant-numeric: tabular-nums;
            box-shadow: 0 2px 8px rgba(235, 10, 30, 0.25);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
        }

        .profile-nav-badge--green {
            background: #059669;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
        }

        /* چاپ فاکتور: فقط یک برگه A4 بدون هدر/فوتر/مودال تکراری */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            html,
            body {
                background: #fff !important;
                color: #000 !important;
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body.printing-invoice>*:not(#invoice-print-root) {
                display: none !important;
            }

            body.printing-invoice #invoice-print-root {
                display: block !important;
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #111 !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }

            .no-print {
                display: none !important;
            }

            a[href]::after {
                content: none !important;
            }
        }
    </style>
    <script>
        window.__PROFILE_SHOP__ = <?= json_encode($shopInfo ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        window.__PROFILE_USER__ = <?= json_encode([
            'full_name' => $fullName ?? '',
            'phone' => $phone ?? '',
            'national_id' => $nationalId ?? '',
            'city' => $city ?? '',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">
    <?php include 'assets/php/header.php'; ?>

    <main class="flex-1 max-w-[1400px] w-full mx-auto px-4 py-8 sm:py-12 relative z-10">

        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-gray-400 whitespace-nowrap mb-6">
            <a href="/" class="hover:text-white transition">صفحه اصلی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <span class="text-brand-red font-bold">پنل مدیریت حساب کاربری</span>
        </div>

        <!-- ناوبری موبایل تب‌ها -->
        <div class="lg:hidden mb-4">
            <button type="button" onclick="document.getElementById('profile-mobile-nav').classList.toggle('hidden')"
                class="w-full flex items-center justify-between bg-brand-grey border border-white/10 rounded-2xl px-4 py-3 text-sm font-bold">
                <span class="flex items-center gap-2"><i data-lucide="menu" class="w-4 h-4 text-brand-red"></i> منوی پنل
                    کاربری</span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400"></i>
            </button>
            <nav id="profile-mobile-nav"
                class="hidden mt-2 bg-brand-grey border border-white/10 rounded-2xl p-2 space-y-1 shadow-lg">
                <button onclick="switchProfileTab('dashboard');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">پیشخوان</button>
                <button onclick="switchProfileTab('orders');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">سفارش‌های
                    من</button>
                <button onclick="switchProfileTab('vehicles');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">خودروهای
                    من</button>
                <button onclick="switchProfileTab('addresses');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">آدرس‌ها</button>
                <button onclick="switchProfileTab('wishlist');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">نشان‌شده‌ها</button>
                <button onclick="switchProfileTab('wallet');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">کیف
                    پول</button>
                <button onclick="switchProfileTab('tickets');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">تیکت‌ها</button>
                <button onclick="switchProfileTab('settings');"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-gray-300 hover:bg-brand-red/10 hover:text-brand-red transition">تنظیمات</button>
                <button onclick="openLogoutModal()"
                    class="w-full text-right px-4 py-3 rounded-xl text-sm font-bold text-rose-500 hover:bg-rose-500/10 transition">خروج</button>
            </nav>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start w-full">

            <!-- ================= سایدبار ================= -->
            <aside class="w-full lg:w-1/4 xl:w-1/5 flex-col gap-6 lg:sticky lg:top-24 hidden lg:flex">

                <!-- کارت پروفایل -->
                <div
                    class="bg-brand-grey border border-white/10 rounded-3xl p-6 text-center shadow-lg relative overflow-hidden">
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="relative mb-4">
                            <div
                                class="w-24 h-24 bg-brand-grey border-2 border-brand-red rounded-full p-1 shadow-lg shadow-brand-red/20">
                                <div id="sidebar-user-initials"
                                    class="w-full h-full bg-brand-dark rounded-full flex items-center justify-center text-white font-black text-2xl">
                                    <?= e($userInitials) ?>
                                </div>
                            </div>
                            <span
                                class="absolute bottom-1 right-1 bg-emerald-500 text-white p-1.5 rounded-full border-4 border-brand-dark"
                                title="حساب تایید شده">
                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                            </span>
                        </div>

                        <h2 id="sidebar-user-name" class="text-xl font-black text-white mb-2"><?= e($fullName) ?></h2>

                        <span
                            class="inline-flex items-center gap-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[11px] font-bold px-3 py-1 rounded-full mb-4 shadow-sm">
                            <i data-lucide="<?= e($membership['icon']) ?>" style="width:12px;height:12px;"></i>
                            <?= e($membership['label']) ?>
                        </span>

                        <div class="w-full border-t border-white/10 pt-4 space-y-3 text-xs text-right">
                            <div class="flex items-center gap-2.5 text-gray-400">
                                <i data-lucide="smartphone" class="text-brand-red shrink-0"
                                    style="width:16px;height:16px;"></i>
                                <span class="font-mono text-white" style="direction:ltr;"><?= e($phone) ?></span>
                            </div>
                            <div class="flex items-center gap-2.5 text-gray-400">
                                <i data-lucide="calendar" class="text-brand-red shrink-0"
                                    style="width:16px;height:16px;"></i>
                                <span class="text-white">عضویت: <?= e($memberSince) ?></span>
                            </div>
                            <?php if ($city !== ''): ?>
                                <div class="flex items-center gap-2.5 text-gray-400">
                                    <i data-lucide="map-pin" class="text-brand-red shrink-0"
                                        style="width:16px;height:16px;"></i>
                                    <span class="text-white"><?= e($city) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- منوی ناوبری -->
                <nav class="bg-brand-grey border border-white/10 rounded-3xl p-3 space-y-1.5 shadow-lg">
                    <button onclick="switchProfileTab('dashboard')" id="nav-dashboard"
                        class="<?= profileTabClass('dashboard', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="layout-dashboard" style="width:18px;height:18px;"></i> پیشخوان
                        </div>
                        <i data-lucide="chevron-left" style="width:16px;height:16px;"></i>
                    </button>

                    <button onclick="switchProfileTab('orders')" id="nav-orders"
                        class="<?= profileTabClass('orders', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i> سفارش‌های من
                        </div>
                        <?php if ($orderStats['total'] > 0): ?>
                            <span class="profile-nav-badge"><?= number_format((int) $orderStats['total']) ?></span>
                        <?php endif; ?>
                    </button>

                    <button onclick="switchProfileTab('vehicles')" id="nav-vehicles"
                        class="<?= profileTabClass('vehicles', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="car" style="width:18px;height:18px;"></i> خودروهای من
                        </div>
                        <?php if ($vehiclesCount > 0): ?>
                            <span id="badge-vehicles-count"
                                class="profile-nav-badge"><?= number_format((int) $vehiclesCount) ?></span>
                        <?php endif; ?>
                    </button>

                    <button onclick="switchProfileTab('addresses')" id="nav-addresses"
                        class="<?= profileTabClass('addresses', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="map-pin" style="width:18px;height:18px;"></i> آدرس‌ها
                        </div>
                    </button>

                    <button onclick="switchProfileTab('wishlist')" id="nav-wishlist"
                        class="<?= profileTabClass('wishlist', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="heart" style="width:18px;height:18px;"></i> نشان‌شده‌ها
                        </div>
                        <?php if ($wishlistCount > 0): ?>
                            <span id="badge-wishlist-count"
                                class="profile-nav-badge"><?= number_format((int) $wishlistCount) ?></span>
                        <?php endif; ?>
                    </button>

                    <button onclick="switchProfileTab('wallet')" id="nav-wallet"
                        class="<?= profileTabClass('wallet', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="wallet" style="width:18px;height:18px;"></i> کیف پول
                        </div>
                    </button>

                    <button onclick="switchProfileTab('tickets')" id="nav-tickets"
                        class="<?= profileTabClass('tickets', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="headphones" style="width:18px;height:18px;"></i> تیکت‌ها
                        </div>
                        <?php if ($openTicketsCount > 0): ?>
                            <span
                                class="profile-nav-badge profile-nav-badge--green"><?= number_format((int) $openTicketsCount) ?></span>
                        <?php endif; ?>
                    </button>

                    <button onclick="switchProfileTab('settings')" id="nav-settings"
                        class="<?= profileTabClass('settings', $activeTab) ?>">
                        <div class="flex items-center gap-3">
                            <i data-lucide="settings" style="width:18px;height:18px;"></i> تنظیمات
                        </div>
                    </button>

                    <div class="pt-3 mt-3 border-t border-white/10">
                        <button onclick="openLogoutModal()"
                            class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition text-rose-500 font-bold text-sm hover:bg-rose-500/10 border border-transparent">
                            <i data-lucide="log-out" style="width:18px;height:18px;"></i> خروج از حساب
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- ================= محتوای اصلی ================= -->
            <div class="w-full lg:w-3/4 xl:w-4/5">

                <!-- ========== TAB: پیشخوان ========== -->
                <div id="tab-content-dashboard" class="<?= profilePageClass('dashboard', $activeTab) ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                        <!-- کیف پول -->
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-6 relative flex items-center justify-between shadow-sm">
                            <div class="relative z-10 space-y-1">
                                <span class="text-gray-400 text-xs font-bold">موجودی کیف پول شما</span>
                                <div class="text-emerald-400 font-black text-2xl sm:text-3xl">
                                    <?= number_format((float) $walletBalance) ?>
                                    <span class="text-sm font-medium text-gray-500">تومان</span>
                                </div>
                            </div>
                            <button onclick="switchProfileTab('wallet')"
                                class="relative z-10 bg-brand-dark border border-emerald-500/30 text-emerald-400 p-3 rounded-2xl hover:border-emerald-500 transition shadow-sm"
                                title="شارژ کیف پول">
                                <i data-lucide="plus" style="width:24px;height:24px;"></i>
                            </button>
                        </div>

                        <!-- خودروی اصلی -->
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-6 relative flex items-center justify-between shadow-sm">
                            <div class="relative z-10 space-y-1 min-w-0">
                                <span class="text-gray-400 text-xs font-bold">خودروی اصلی شما</span>
                                <?php if ($primaryVehicle): ?>
                                    <div class="text-white font-black text-lg sm:text-xl truncate">
                                        <?= e($primaryVehicle['model_name']) ?>
                                        <?= !empty($primaryVehicle['model_year']) ? '(' . e($primaryVehicle['model_year']) . ')' : '' ?>
                                    </div>
                                    <?php if (!empty($primaryVehicle['vin'])): ?>
                                        <div class="text-xs text-gray-400 font-mono truncate" dir="ltr">VIN:
                                            <?= e($primaryVehicle['vin']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-white font-bold text-sm">هنوز خودرویی ثبت نشده</div>
                                    <button onclick="switchProfileTab('vehicles'); openAddVehicleModal();"
                                        class="text-xs text-brand-red font-bold hover:underline mt-1">+ افزودن
                                        خودرو</button>
                                <?php endif; ?>
                            </div>
                            <div
                                class="relative z-10 w-12 h-12 bg-brand-dark text-brand-red rounded-2xl flex items-center justify-center border border-white/10 shrink-0">
                                <i data-lucide="car" style="width:24px;height:24px;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- آمار -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-brand-red transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-brand-red rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="shopping-bag" style="width:20px;height:20px;"></i>
                                </div>
                                <span class="text-xs text-gray-400 font-medium">کل سفارشات</span>
                            </div>
                            <div class="text-2xl font-black text-white"><?= (int) $orderStats['total'] ?></div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-amber-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-amber-500 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="clock" style="width:20px;height:20px;"></i>
                                </div>
                                <span class="text-xs text-gray-400 font-medium">در حال پردازش</span>
                            </div>
                            <div class="text-2xl font-black text-white"><?= (int) $orderStats['processing'] ?></div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-emerald-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-emerald-400 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                                </div>
                                <span class="text-xs text-gray-400 font-medium">تحویل شده</span>
                            </div>
                            <div class="text-2xl font-black text-white"><?= (int) $orderStats['delivered'] ?></div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-blue-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-blue-400 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="heart" style="width:20px;height:20px;"></i>
                                </div>
                                <span class="text-xs text-gray-400 font-medium">علاقه‌مندی‌ها</span>
                            </div>
                            <div class="text-2xl font-black text-white"><?= (int) $wishlistCount ?></div>
                        </div>
                    </div>

                    <!-- استعلام VIN -->
                    <div
                        class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                        <div class="relative z-10 flex-1 space-y-2 text-center md:text-right">
                            <h3 class="font-bold text-lg text-white">استعلام دقیق با شماره شاسی (VIN)</h3>
                            <p class="text-sm text-gray-400">قطعات سازگار با خودروی شما را بیابید.</p>
                        </div>
                        <div class="relative z-10 w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <?php if (!empty($vehicles)): ?>
                                <div class="relative w-full sm:w-56">
                                    <select id="quick-vin-select"
                                        class="w-full bg-brand-dark border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-gray-300 appearance-none focus:outline-none focus:border-brand-red transition cursor-pointer">
                                        <?php foreach ($vehicles as $v): ?>
                                            <option value="<?= e($v['vin'] ?? '') ?>">
                                                <?= e($v['model_name']) ?>
                                                <?= !empty($v['model_year']) ? e($v['model_year']) : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i data-lucide="chevron-down"
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none w-4 h-4"></i>
                                </div>
                            <?php endif; ?>
                            <a href="/parts"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-sm px-8 py-3 rounded-2xl transition flex items-center justify-center gap-2 whitespace-nowrap shadow-lg">
                                جستجوی قطعات
                            </a>
                        </div>
                    </div>

                    <!-- آخرین سفارش‌ها -->
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-4 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center">
                            <h3 class="font-black text-lg text-white">آخرین سفارش‌ها</h3>
                            <button onclick="switchProfileTab('orders')"
                                class="text-brand-red text-sm font-bold flex items-center gap-1 hover:underline">
                                همه سفارش‌ها <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
                            </button>
                        </div>

                        <?php if (empty($recentOrders)): ?>
                            <div class="text-center py-10 text-gray-500 text-sm">
                                <i data-lucide="shopping-bag" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
                                <p>هنوز سفارشی ثبت نکرده‌اید.</p>
                                <a href="/parts"
                                    class="inline-block mt-4 text-brand-red font-bold text-xs hover:underline">مشاهده
                                    کاتالوگ قطعات</a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentOrders as $o):
                                    $st = $o['status'] ?? 'processing';
                                    $badge = $statusBadgeClass[$st] ?? $statusBadgeClass['processing'];
                                    $icon = $statusIcon[$st] ?? 'package';
                                    $label = \App\models\Order::statusLabel($st);
                                    $date = function_exists('toShamsi') ? toShamsi($o['created_at'] ?? '') : ($o['created_at'] ?? '');
                                    $total = (float) ($o['total_amount'] ?? $o['total_price'] ?? 0);
                                    ?>
                                    <div
                                        class="bg-brand-dark border border-white/10 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-brand-red transition">
                                        <div class="flex items-center gap-3 sm:gap-5 w-full md:w-auto min-w-0">
                                            <div
                                                class="w-12 h-12 bg-brand-grey text-brand-red rounded-2xl flex items-center justify-center shrink-0 border border-white/10">
                                                <i data-lucide="<?= e($icon) ?>" class="w-6 h-6"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                                    <span class="font-bold text-white truncate">سفارش
                                                        <span class="font-mono text-brand-red"
                                                            dir="ltr"><?= e($o['tracking_code'] ?? '#' . $o['id']) ?></span>
                                                    </span>
                                                    <span
                                                        class="<?= e($badge) ?> border text-[10px] font-bold px-2 py-0.5 rounded-lg whitespace-nowrap shrink-0"><?= e($label) ?></span>
                                                </div>
                                                <p class="text-xs text-gray-400 truncate">ثبت شده در: <?= e($date) ?></p>
                                            </div>
                                        </div>
                                        <div
                                            class="flex items-center w-full md:w-auto justify-between md:justify-end gap-6 border-t border-white/10 md:border-none pt-4 md:pt-0 mt-2 md:mt-0">
                                            <div class="text-right md:text-left">
                                                <div class="text-xs text-gray-400 mb-0.5">مبلغ کل</div>
                                                <div class="font-black text-white"><?= number_format($total) ?> <span
                                                        class="text-[10px] text-gray-500">تومان</span></div>
                                            </div>
                                            <button onclick="openOrderDetailModal(<?= (int) $o['id'] ?>)"
                                                class="bg-transparent text-brand-red border border-brand-red hover:bg-brand-red hover:!text-white px-5 py-2 sm:py-2.5 rounded-xl font-bold transition text-xs shrink-0">
                                                جزئیات
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: سفارش‌ها ========== -->
                <div id="tab-content-orders" class="<?= profilePageClass('orders', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">سوابق سفارشات</h3>
                            <div class="flex flex-wrap gap-2 bg-brand-dark p-1.5 rounded-xl border border-white/10">
                                <button onclick="filterOrders('all', event)"
                                    class="order-filter-btn bg-brand-red text-white px-4 py-2.5 rounded-lg font-bold text-xs transition">همه</button>
                                <button onclick="filterOrders('processing', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-4 py-2.5 rounded-lg font-bold text-xs transition">جاری</button>
                                <button onclick="filterOrders('shipped', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-4 py-2.5 rounded-lg font-bold text-xs transition">ارسال‌شده</button>
                                <button onclick="filterOrders('delivered', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-4 py-2.5 rounded-lg font-bold text-xs transition">تحویل‌شده</button>
                                <button onclick="filterOrders('cancelled', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-4 py-2.5 rounded-lg font-bold text-xs transition">لغو‌شده</button>
                            </div>
                        </div>

                        <?php if (empty($orders)): ?>
                            <div class="text-center py-16 text-gray-500 text-sm">
                                <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                                <p>سفارشی برای نمایش وجود ندارد.</p>
                                <a href="/parts"
                                    class="inline-flex items-center gap-2 mt-5 bg-brand-red text-white font-bold text-xs px-6 py-3 rounded-xl hover:bg-red-700 transition">
                                    <i data-lucide="shopping-cart" class="w-4 h-4"></i> شروع خرید
                                </a>
                            </div>
                        <?php else: ?>
                            <div id="orders-list" class="space-y-4">
                                <?php foreach ($orders as $o):
                                    $st = $o['status'] ?? 'processing';
                                    $badge = $statusBadgeClass[$st] ?? $statusBadgeClass['processing'];
                                    $label = \App\models\Order::statusLabel($st);
                                    $date = function_exists('toShamsi') ? toShamsi($o['created_at'] ?? '') : ($o['created_at'] ?? '');
                                    $total = (float) ($o['total_amount'] ?? $o['total_price'] ?? 0);
                                    $itemsCount = (int) ($o['items_count'] ?? 0);
                                    ?>
                                    <div class="order-card bg-brand-dark border border-white/10 rounded-3xl p-5 sm:p-6 space-y-4"
                                        data-status="<?= e($st) ?>">
                                        <div
                                            class="flex flex-wrap justify-between items-center gap-3 border-b border-white/10 pb-4">
                                            <div class="space-y-1">
                                                <span class="text-sm font-bold text-gray-400">سفارش:
                                                    <strong class="text-white font-mono"
                                                        dir="ltr"><?= e($o['tracking_code'] ?? '#' . $o['id']) ?></strong>
                                                </span>
                                                <span class="text-xs text-gray-500 block"><?= e($date) ?> — <?= $itemsCount ?>
                                                    قلم</span>
                                            </div>
                                            <span
                                                class="<?= e($badge) ?> text-xs font-bold px-4 py-2 rounded-xl border"><?= e($label) ?></span>
                                        </div>
                                        <div class="flex flex-wrap justify-between items-center gap-4">
                                            <div class="text-xs text-gray-400">
                                                تحویل‌گیرنده: <strong
                                                    class="text-white"><?= e($o['recipient_name'] ?? '—') ?></strong>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                <span class="text-sm text-gray-400">جمع کل:
                                                    <strong
                                                        class="text-emerald-400 text-lg font-black"><?= number_format($total) ?>
                                                        تومان</strong>
                                                </span>
                                                <button onclick="openOrderDetailModal(<?= (int) $o['id'] ?>)"
                                                    class="bg-transparent text-brand-red border border-brand-red hover:bg-brand-red hover:!text-white px-6 py-2.5 rounded-xl font-bold transition text-xs">
                                                    فاکتور
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="orders-empty-filter" class="hidden text-center py-10 text-gray-500 text-sm">
                                سفارشی با این وضعیت یافت نشد.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: خودروها ========== -->
                <div id="tab-content-vehicles" class="<?= profilePageClass('vehicles', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6 gap-3">
                            <h3 class="font-black text-xl text-white">خودروهای من</h3>
                            <button onclick="openAddVehicleModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2 shadow-lg shrink-0">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> افزودن خودرو
                            </button>
                        </div>

                        <?php if (empty($vehicles)): ?>
                            <div class="text-center py-14 text-gray-500 text-sm">
                                <i data-lucide="car" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                                <p>هنوز خودرویی ثبت نکرده‌اید.</p>
                                <button onclick="openAddVehicleModal()"
                                    class="mt-4 text-brand-red font-bold text-xs hover:underline">+ ثبت اولین خودرو</button>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($vehicles as $v):
                                    $isPrimary = (int) ($v['is_primary'] ?? 0) === 1;
                                    $vJson = e(json_encode([
                                        'id' => (int) $v['id'],
                                        'model_name' => $v['model_name'] ?? '',
                                        'model_year' => $v['model_year'] ?? '',
                                        'trim_name' => $v['trim_name'] ?? '',
                                        'vin' => $v['vin'] ?? '',
                                        'engine_code' => $v['engine_code'] ?? '',
                                        'notes' => $v['notes'] ?? '',
                                    ], JSON_UNESCAPED_UNICODE));
                                    ?>
                                    <div
                                        class="bg-brand-dark border <?= $isPrimary ? 'border-brand-red/30' : 'border-white/10' ?> rounded-3xl p-6 relative">
                                        <?php if ($isPrimary): ?>
                                            <span
                                                class="bg-brand-red/10 text-brand-red text-[10px] font-bold px-3 py-1 rounded-lg absolute top-6 left-6 border border-brand-red/20">خودروی
                                                اصلی</span>
                                        <?php endif; ?>

                                        <div class="flex items-center gap-4 mb-6">
                                            <div
                                                class="w-16 h-16 bg-brand-grey border border-white/10 rounded-2xl flex items-center justify-center text-brand-red shrink-0">
                                                <i data-lucide="car" style="width:32px;height:32px;"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h4 class="font-black text-lg text-white truncate">
                                                    <?= e($v['model_name']) ?>        <?= !empty($v['model_year']) ? ' ' . e($v['model_year']) : '' ?>
                                                </h4>
                                                <?php if (!empty($v['trim_name'])): ?>
                                                    <span class="text-xs text-gray-400">تیپ <?= e($v['trim_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div
                                            class="bg-brand-grey border border-white/10 rounded-2xl p-4 space-y-3 text-xs mb-4">
                                            <?php if (!empty($v['vin'])): ?>
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-gray-400 shrink-0">شاسی (VIN)</span>
                                                    <span class="font-mono text-white text-left break-all"
                                                        dir="ltr"><?= e($v['vin']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($v['engine_code'])): ?>
                                                <div class="flex justify-between gap-2">
                                                    <span class="text-gray-400">موتور</span>
                                                    <span class="text-white font-mono" dir="ltr"><?= e($v['engine_code']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($v['notes'])): ?>
                                                <div class="text-gray-400 leading-relaxed pt-1 border-t border-white/5">
                                                    <?= e($v['notes']) ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <a href="/parts<?= !empty($v['model_name']) ? '?q=' . rawurlencode($v['model_name']) : '' ?>"
                                                class="flex-1 text-center bg-brand-grey hover:border-brand-red text-white text-xs py-2.5 rounded-xl transition font-bold border border-white/10 min-w-[120px]">
                                                قطعات این خودرو
                                            </a>
                                            <?php if (!$isPrimary): ?>
                                                <button onclick="setPrimaryVehicle(<?= (int) $v['id'] ?>)"
                                                    class="px-3 py-2.5 rounded-xl border border-white/10 text-xs text-gray-400 hover:text-amber-400 hover:border-amber-500/40 transition"
                                                    title="اصلی کردن">
                                                    <i data-lucide="star" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button data-vehicle='<?= $vJson ?>' onclick="editVehicle(this)"
                                                class="px-3 py-2.5 rounded-xl border border-white/10 text-xs text-gray-400 hover:text-white transition"
                                                title="ویرایش">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="deleteVehicle(<?= (int) $v['id'] ?>)"
                                                class="px-3 py-2.5 rounded-xl border border-white/10 text-xs text-gray-400 hover:text-rose-400 hover:border-rose-500/40 transition"
                                                title="حذف">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: آدرس‌ها ========== -->
                <div id="tab-content-addresses" class="<?= profilePageClass('addresses', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6 gap-3">
                            <h3 class="font-black text-xl text-white">آدرس‌های پستی</h3>
                            <button onclick="openAddAddressModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2 shrink-0">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> ثبت آدرس
                            </button>
                        </div>

                        <?php if (empty($addresses)): ?>
                            <div class="text-center py-14 text-gray-500 text-sm">
                                <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                                <p>آدرسی ثبت نشده است.</p>
                                <button onclick="openAddAddressModal()"
                                    class="mt-4 text-brand-red font-bold text-xs hover:underline">+ ثبت اولین آدرس</button>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($addresses as $addr):
                                    $isDef = (int) ($addr['is_default'] ?? 0) === 1;
                                    $aJson = e(json_encode([
                                        'id' => (int) $addr['id'],
                                        'province_city' => $addr['province_city'] ?? '',
                                        'address_detail' => $addr['address_detail'] ?? '',
                                        'postal_code' => $addr['postal_code'] ?? '',
                                        'recipient_name' => $addr['recipient_name'] ?? '',
                                        'recipient_phone' => $addr['recipient_phone'] ?? '',
                                    ], JSON_UNESCAPED_UNICODE));
                                    ?>
                                    <div
                                        class="bg-brand-dark border <?= $isDef ? 'border-emerald-500/30' : 'border-white/10' ?> rounded-3xl p-6 relative">
                                        <?php if ($isDef): ?>
                                            <span
                                                class="bg-emerald-500/10 text-emerald-400 text-[10px] font-bold px-3 py-1 rounded-lg absolute top-6 left-6 border border-emerald-500/20">پیش‌فرض</span>
                                        <?php endif; ?>

                                        <h4 class="font-bold text-base text-white mb-1 pr-0 pl-16">
                                            <?= e($addr['recipient_name'] ?: $fullName) ?>
                                        </h4>
                                        <p class="text-xs text-gray-500 mb-2"><?= e($addr['province_city'] ?? '') ?></p>
                                        <p class="text-sm text-gray-400 leading-relaxed mb-4">
                                            <?= e($addr['address_detail'] ?? '') ?></p>

                                        <div
                                            class="bg-brand-grey border border-white/10 rounded-2xl p-4 flex flex-wrap justify-between gap-2 text-xs mb-4">
                                            <?php if (!empty($addr['postal_code'])): ?>
                                                <span class="text-gray-400">کد پستی: <strong class="text-white font-mono"
                                                        dir="ltr"><?= e($addr['postal_code']) ?></strong></span>
                                            <?php endif; ?>
                                            <?php if (!empty($addr['recipient_phone'])): ?>
                                                <span class="text-gray-400">تماس: <strong class="text-white font-mono"
                                                        dir="ltr"><?= e($addr['recipient_phone']) ?></strong></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <?php if (!$isDef): ?>
                                                <button onclick="setDefaultAddress(<?= (int) $addr['id'] ?>)"
                                                    class="flex-1 text-xs font-bold py-2.5 rounded-xl border border-white/10 text-gray-400 hover:text-emerald-400 hover:border-emerald-500/40 transition">
                                                    پیش‌فرض
                                                </button>
                                            <?php endif; ?>
                                            <button data-address='<?= $aJson ?>' onclick="editAddress(this)"
                                                class="px-4 py-2.5 rounded-xl border border-white/10 text-xs text-gray-400 hover:text-white transition">
                                                <i data-lucide="pencil" class="w-4 h-4"></i>
                                            </button>
                                            <button onclick="deleteAddress(<?= (int) $addr['id'] ?>)"
                                                class="px-4 py-2.5 rounded-xl border border-white/10 text-xs text-gray-400 hover:text-rose-400 transition">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: نشان‌شده‌ها ========== -->
                <div id="tab-content-wishlist" class="<?= profilePageClass('wishlist', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">قطعات نشان‌شده</h3>
                            <p class="text-xs text-gray-500 mt-1"><?= (int) $wishlistCount ?> قطعه در لیست شما</p>
                        </div>

                        <?php if (empty($wishlist)): ?>
                            <div class="text-center py-14 text-gray-500 text-sm">
                                <i data-lucide="heart" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                                <p>هنوز قطعه‌ای نشان نکرده‌اید.</p>
                                <a href="/parts"
                                    class="inline-block mt-4 text-brand-red font-bold text-xs hover:underline">مشاهده
                                    کاتالوگ</a>
                            </div>
                        <?php else: ?>
                            <div id="wishlist-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <?php foreach ($wishlist as $item):
                                    $img = (string) ($item['image_url'] ?? '');
                                    $slug = rawurlencode((string) ($item['slug'] ?? ''));
                                    ?>
                                    <div id="wishlist-item-<?= (int) $item['id'] ?>"
                                        class="bg-brand-dark border border-white/10 p-5 rounded-3xl space-y-4 hover:border-brand-red transition relative group">
                                        <button onclick="removeFromWishlist(<?= (int) $item['id'] ?>, this)"
                                            class="absolute top-4 left-4 z-10 p-2 rounded-lg text-gray-500 hover:text-rose-400 hover:bg-rose-500/10 transition"
                                            title="حذف از نشان‌شده‌ها">
                                            <i data-lucide="heart-off" class="w-4 h-4"></i>
                                        </button>

                                        <a href="/product/<?= $slug ?>"
                                            class="w-full h-40 bg-brand-grey border border-white/10 rounded-2xl flex items-center justify-center p-3 block">
                                            <?php if ($img !== ''): ?>
                                                <img src="<?= e($img) ?>" alt="<?= e($item['image_alt'] ?? $item['name']) ?>" loading="lazy" decoding="async"
                                                    class="max-w-full max-h-full object-contain">
                                            <?php else: ?>
                                                <span class="text-gray-600" role="img" aria-label="تصویر محصول ثبت نشده است">
                                                    <i data-lucide="image-off" class="w-10 h-10" aria-hidden="true"></i>
                                                </span>
                                            <?php endif; ?>
                                        </a>

                                        <a href="/product/<?= $slug ?>">
                                            <h4
                                                class="font-bold text-sm text-white line-clamp-2 hover:text-brand-red transition">
                                                <?= e($item['name']) ?></h4>
                                        </a>
                                        <?php if (!empty($item['oem'])): ?>
                                            <p class="text-[10px] text-gray-500 font-mono" dir="ltr">OEM: <?= e($item['oem']) ?></p>
                                        <?php endif; ?>

                                        <div class="flex items-center justify-between border-t border-white/10 pt-4">
                                            <span
                                                class="font-black text-sm text-brand-red"><?= number_format((float) $item['price']) ?>
                                                ت</span>
                                            <?php if (!empty($item['inStock'])): ?>
                                                <button onclick="addWishlistToCart(<?= (int) $item['id'] ?>)"
                                                    class="bg-brand-grey hover:border-brand-red border border-white/10 text-white p-3 rounded-xl transition"
                                                    title="افزودن به سبد">
                                                    <i data-lucide="shopping-cart" style="width:18px;height:18px;"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-[10px] text-gray-500 font-bold">ناموجود</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: کیف پول ========== -->
                <div id="tab-content-wallet" class="<?= profilePageClass('wallet', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <h3 class="font-black text-xl text-white border-b border-white/10 pb-6">کیف پول و تراکنش‌ها</h3>

                        <div class="bg-brand-dark border border-white/10 rounded-3xl p-6 lg:p-8 space-y-2 mb-2">
                            <span class="text-xs text-gray-400 font-bold">موجودی فعلی</span>
                            <div class="text-3xl font-black text-emerald-400">
                                <?= number_format((float) $walletBalance) ?>
                                <span class="text-sm font-medium text-gray-500">تومان</span>
                            </div>
                        </div>

                        <div class="bg-brand-dark border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6">
                            <h4 class="font-bold text-sm text-white">شارژ سریع اعتبار</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <?php foreach ([500000, 1000000, 2000000, 5000000] as $amt): ?>
                                    <button type="button" onclick="setChargeAmount(<?= $amt ?>)"
                                        class="wallet-amount-btn bg-brand-grey hover:border-brand-red border border-white/10 py-4 rounded-2xl text-xs font-bold text-gray-400 hover:text-white transition">
                                        <?= number_format($amt) ?> ت
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <input type="number" id="custom-wallet-amount" min="50000" step="1000"
                                    placeholder="مبلغ دلخواه به تومان (حداقل ۵۰٬۰۰۰)..."
                                    class="flex-1 bg-brand-grey border border-white/10 rounded-2xl px-6 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                <button type="button" id="btn-wallet-charge" onclick="submitWalletCharge()"
                                    class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-10 py-4 rounded-2xl text-sm transition shadow-lg shrink-0">
                                    ثبت درخواست شارژ
                                </button>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-relaxed">
                                در نسخه فعلی، شارژ کیف پول پس از ثبت درخواست و تایید پشتیبانی انجام می‌شود.
                                برای شارژ فوری با واتساپ فروشگاه تماس بگیرید.
                            </p>
                        </div>

                        <div class="space-y-3 pt-4">
                            <h4 class="font-bold text-sm text-white mb-4">تراکنش‌های اخیر</h4>
                            <?php if (empty($walletTransactions)): ?>
                                <div class="text-center py-8 text-gray-500 text-xs">تراکنشی ثبت نشده است.</div>
                            <?php else: ?>
                                <?php foreach ($walletTransactions as $tx):
                                    $isPlus = in_array($tx['type'], ['charge', 'refund'], true) || (float) $tx['amount'] > 0;
                                    $amount = abs((float) $tx['amount']);
                                    $txDate = function_exists('toShamsi') ? toShamsi($tx['created_at'] ?? '') : ($tx['created_at'] ?? '');
                                    $typeLabels = [
                                        'charge' => 'شارژ کیف پول',
                                        'purchase' => 'پرداخت سفارش',
                                        'refund' => 'عودت وجه',
                                        'adjust' => 'تعدیل موجودی',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'در انتظار تایید',
                                        'completed' => 'انجام‌شده',
                                        'failed' => 'ناموفق',
                                        'cancelled' => 'لغو شده',
                                    ];
                                    ?>
                                    <div
                                        class="bg-brand-dark p-5 rounded-2xl border border-white/10 flex flex-wrap justify-between items-center gap-3">
                                        <div>
                                            <span class="font-bold text-sm text-white block">
                                                <?= e($tx['description'] ?: ($typeLabels[$tx['type']] ?? $tx['type'])) ?>
                                            </span>
                                            <span class="text-xs text-gray-400">
                                                <?= e($txDate) ?>
                                                <?php if (!empty($tx['reference_code'])): ?>
                                                    — <span class="font-mono" dir="ltr"><?= e($tx['reference_code']) ?></span>
                                                <?php endif; ?>
                                                — <?= e($statusLabels[$tx['status']] ?? $tx['status']) ?>
                                            </span>
                                        </div>
                                        <span class="font-black <?= $isPlus ? 'text-emerald-400' : 'text-rose-500' ?>">
                                            <?= $isPlus ? '+' : '−' ?>         <?= number_format($amount) ?> تومان
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========== TAB: تیکت‌ها ========== -->
                <div id="tab-content-tickets" class="<?= profilePageClass('tickets', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6 gap-3">
                            <h3 class="font-black text-xl text-white">تیکت‌های پشتیبانی</h3>
                            <button onclick="openNewTicketModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2 shrink-0">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> تیکت جدید
                            </button>
                        </div>

                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-14 text-gray-500 text-sm">
                                <i data-lucide="headphones" class="w-12 h-12 mx-auto mb-3 opacity-40"></i>
                                <p>تیکتی ثبت نشده است.</p>
                                <button onclick="openNewTicketModal()"
                                    class="mt-4 text-brand-red font-bold text-xs hover:underline">+ ثبت تیکت
                                    پشتیبانی</button>
                                <p class="text-[11px] text-gray-600 mt-4">یا مستقیم با پشتیبانی واتساپ در تماس باشید.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($tickets as $t):
                                    $ts = $t['status'] ?? 'open';
                                    $tBadge = $ticketStatusClass[$ts] ?? $ticketStatusClass['open'];
                                    $tLabel = \App\models\Ticket::statusLabel($ts);
                                    $tDate = function_exists('toShamsi') ? toShamsi($t['updated_at'] ?? $t['created_at'] ?? '') : '';
                                    ?>
                                    <div class="bg-brand-dark border border-white/10 p-5 sm:p-6 rounded-3xl space-y-3 hover:border-white/20 transition cursor-pointer"
                                        onclick="openTicketDetailModal(<?= (int) $t['id'] ?>)">
                                        <div class="flex flex-wrap justify-between items-center gap-3">
                                            <span class="font-bold text-sm text-white"><?= e($t['subject']) ?></span>
                                            <span
                                                class="<?= e($tBadge) ?> border text-xs font-bold px-3 py-1 rounded-lg"><?= e($tLabel) ?></span>
                                        </div>
                                        <?php if (!empty($t['last_message'])): ?>
                                            <div
                                                class="bg-brand-grey p-3 rounded-2xl border border-white/10 text-xs text-gray-400 leading-relaxed line-clamp-2">
                                                <?= e($t['last_message']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex justify-between text-[10px] text-gray-500">
                                            <span>#<?= (int) $t['id'] ?> — <?= (int) ($t['message_count'] ?? 0) ?> پیام</span>
                                            <span><?= e($tDate) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ========== TAB: تنظیمات ========== -->
                <div id="tab-content-settings" class="<?= profilePageClass('settings', $activeTab) ?>">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <h3 class="font-black text-xl text-white border-b border-white/10 pb-6">تنظیمات حساب</h3>

                        <form onsubmit="handleSaveSettings(event)" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1" for="settings-fullname">نام و نام
                                        خانوادگی</label>
                                    <input type="text" id="settings-fullname" required minlength="3"
                                        value="<?= e($fullName) ?>"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1">موبایل (غیرقابل تغییر)</label>
                                    <input type="tel" value="<?= e($phone) ?>" disabled
                                        class="w-full bg-brand-dark opacity-60 border border-white/10 rounded-2xl px-5 py-4 text-sm text-gray-500 cursor-not-allowed font-mono text-left"
                                        dir="ltr">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1" for="settings-email">ایمیل</label>
                                    <input type="email" id="settings-email" value="<?= e($email) ?>"
                                        placeholder="optional@email.com"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition text-left"
                                        dir="ltr">
                                </div>
                                <div class="space-y-2 sm:col-span-2">
                                    <label class="text-xs text-gray-400 ml-1 flex flex-wrap items-center gap-2"
                                        for="settings-national-id">
                                        <span>کد ملی</span>
                                        <span
                                            class="text-[10px] font-bold text-amber-500/90 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">اختیاری</span>
                                    </label>
                                    <input type="text" id="settings-national-id" value="<?= e($nationalId) ?>"
                                        maxlength="10" inputmode="numeric" placeholder="۱۰ رقم — در صورت تمایل"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition font-mono text-left"
                                        dir="ltr">
                                    <p class="text-[11px] text-gray-500 leading-relaxed">
                                        وارد کردن کد ملی <strong class="text-gray-300">الزامی نیست</strong>.
                                        در صورت تکمیل، روی فاکتور رسمی برای احراز هویت خریدار درج می‌شود و در مراجع
                                        قانونی/دادگاهی قابل استنادتر خواهد بود.
                                        بدون کد ملی هم امکان ثبت سفارش و دریافت فاکتور وجود دارد.
                                    </p>
                                </div>
                                <div class="space-y-2 sm:col-span-2">
                                    <label class="text-xs text-gray-400 ml-1" for="settings-city">شهر</label>
                                    <input type="text" id="settings-city" value="<?= e($city) ?>"
                                        placeholder="مثال: سقز"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                </div>
                            </div>
                            <button type="submit"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold py-4 px-10 rounded-2xl text-sm transition shadow-lg">
                                ذخیره اطلاعات
                            </button>
                        </form>

                        <div class="pt-6 border-t border-white/10 space-y-6">
                            <h4 class="font-bold text-white">تغییر رمز عبور</h4>
                            <form onsubmit="handleChangePassword(event)" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <input type="password" id="settings-current-pass" required placeholder="رمز فعلی"
                                        autocomplete="current-password"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                    <input type="password" id="settings-new-pass" required minlength="6"
                                        placeholder="رمز جدید (حداقل ۶ کاراکتر)" autocomplete="new-password"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                    <input type="password" id="settings-confirm-pass" required minlength="6"
                                        placeholder="تکرار رمز جدید" autocomplete="new-password"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                </div>
                                <button type="submit"
                                    class="bg-brand-red hover:bg-red-700 !text-white font-bold py-3.5 px-8 rounded-2xl text-sm transition shadow-lg border border-brand-red">
                                    تغییر رمز عبور
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div><!-- /content -->
        </div>
    </main>

    <!-- ================= مودال‌ها ================= -->

    <!-- جزئیات سفارش / فاکتور رسمی -->
    <div id="order-detail-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-3 sm:p-4 no-print"
        onclick="closeOrderDetailModal()">
        <div class="w-full max-w-3xl max-h-[92vh] overflow-y-auto bg-[#F8F6F0] border border-[#E8E2D9] rounded-3xl p-4 sm:p-6 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()" id="order-detail-modal-content"></div>
    </div>

    <!-- خودرو -->
    <div id="add-vehicle-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeAddVehicleModal()">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl max-h-[90vh] overflow-y-auto"
            onclick="event.stopPropagation()">
            <button class="absolute top-5 left-5 text-gray-500 hover:text-white p-1" onclick="closeAddVehicleModal()"
                type="button">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 id="vehicle-modal-title" class="font-black text-xl text-white mb-6">ثبت خودرو جدید</h3>
            <form id="vehicle-form" onsubmit="handleAddVehicle(event)" class="space-y-4">
                <input type="hidden" id="vehicle-form-id" value="">
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">مدل خودرو *</label>
                    <div class="relative">
                        <select id="veh-model" required
                            class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none appearance-none cursor-pointer">
                            <option value="">انتخاب مدل از لیست...</option>
                            <?php
                            $carModelsList = $carModelsList ?? [];
                            foreach ($carModelsList as $cm):
                                $cmSlug = is_array($cm) ? ($cm['slug'] ?? '') : '';
                                $cmName = is_array($cm) ? ($cm['name'] ?? '') : (string) $cm;
                                if ($cmName === '')
                                    continue;
                                ?>
                                <option value="<?= e($cmSlug) ?>" data-name="<?= e($cmName) ?>"><?= e($cmName) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i data-lucide="chevron-down"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none w-4 h-4"></i>
                    </div>
                    <p class="text-[10px] text-gray-500 mt-1.5">مدل فقط از کاتالوگ رسمی فروشگاه قابل انتخاب است.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-400 mb-1.5 block">سال ساخت</label>
                        <input type="text" id="veh-year" placeholder="۲۰۱۵ یا ۱۳۹۴" maxlength="8"
                            class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1.5 block">تیپ</label>
                        <input type="text" id="veh-trim" placeholder="GLX / VX"
                            class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">شماره شاسی (VIN)</label>
                    <input type="text" id="veh-vin" placeholder="۱۷ کاراکتر" maxlength="17" dir="ltr"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none font-mono text-left uppercase">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">کد موتور</label>
                    <input type="text" id="veh-engine" placeholder="مثال: 2AR-FE" dir="ltr"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none font-mono text-left">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">یادداشت</label>
                    <input type="text" id="veh-notes" placeholder="توضیح اختیاری"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                    <input type="checkbox" id="veh-is-primary" class="rounded accent-brand-red w-4 h-4">
                    به‌عنوان خودروی اصلی تنظیم شود
                </label>
                <button type="submit"
                    class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-2xl text-sm transition">
                    ثبت خودرو
                </button>
            </form>
        </div>
    </div>

    <!-- آدرس -->
    <div id="add-address-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeAddAddressModal()">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl max-h-[90vh] overflow-y-auto"
            onclick="event.stopPropagation()">
            <button class="absolute top-5 left-5 text-gray-500 hover:text-white p-1" onclick="closeAddAddressModal()"
                type="button">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 id="address-modal-title" class="font-black text-xl text-white mb-6">ثبت آدرس جدید</h3>
            <form id="address-form" onsubmit="handleAddAddress(event)" class="space-y-4">
                <input type="hidden" id="address-form-id" value="">
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">استان - شهر *</label>
                    <input type="text" id="addr-province-city" required placeholder="مثال: کردستان - سقز"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">نشانی دقیق *</label>
                    <textarea id="addr-detail" required rows="3" minlength="6" placeholder="خیابان، کوچه، پلاک، واحد"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none resize-none"></textarea>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">کد پستی ۱۰ رقمی</label>
                    <input type="text" id="addr-postal" maxlength="10" inputmode="numeric" placeholder="6681898204"
                        dir="ltr"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none font-mono text-left">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-400 mb-1.5 block">نام گیرنده</label>
                        <input type="text" id="addr-name" placeholder="<?= e($fullName) ?>"
                            class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                    </div>
                    <div>
                        <label class="text-xs text-gray-400 mb-1.5 block">موبایل گیرنده</label>
                        <input type="tel" id="addr-phone" placeholder="<?= e($phone) ?>" dir="ltr"
                            class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none font-mono text-left">
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer">
                    <input type="checkbox" id="addr-is-default" class="rounded accent-brand-red w-4 h-4">
                    به‌عنوان آدرس پیش‌فرض
                </label>
                <button type="submit"
                    class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-2xl text-sm transition">
                    ثبت آدرس
                </button>
            </form>
        </div>
    </div>

    <!-- تیکت جدید -->
    <div id="new-ticket-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeNewTicketModal()">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()">
            <button class="absolute top-5 left-5 text-gray-500 hover:text-white p-1" onclick="closeNewTicketModal()"
                type="button">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <h3 class="font-black text-xl text-white mb-6">تیکت پشتیبانی جدید</h3>
            <form id="ticket-form" onsubmit="handleCreateTicket(event)" class="space-y-4">
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">موضوع *</label>
                    <input type="text" id="ticket-subject" required minlength="5" maxlength="150"
                        placeholder="مثال: استعلام کد شمع پرادو ۲۰۰۸"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">اولویت</label>
                    <select id="ticket-priority"
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none cursor-pointer">
                        <option value="normal">عادی</option>
                        <option value="low">کم</option>
                        <option value="high">فوری</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1.5 block">پیام *</label>
                    <textarea id="ticket-message" required minlength="10" rows="5" maxlength="2000"
                        placeholder="شرح کامل درخواست خود را بنویسید..."
                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-3.5 text-sm text-white focus:border-brand-red focus:outline-none resize-none"></textarea>
                </div>
                <button type="submit"
                    class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-2xl text-sm transition">
                    ثبت تیکت
                </button>
            </form>
        </div>
    </div>

    <!-- جزئیات تیکت -->
    <div id="ticket-detail-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeTicketDetailModal()">
        <div class="w-full max-w-lg bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl max-h-[90vh] overflow-y-auto"
            onclick="event.stopPropagation()" id="ticket-detail-content"></div>
    </div>

    <!-- خروج -->
    <div id="logout-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeLogoutModal()">
        <div class="w-full max-w-sm bg-brand-grey border border-white/10 rounded-3xl p-8 text-center space-y-6 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()">
            <div
                class="w-20 h-20 bg-brand-dark border border-rose-500/20 rounded-full flex items-center justify-center mx-auto text-rose-500">
                <i data-lucide="log-out" style="width:36px;height:36px;"></i>
            </div>
            <h3 class="font-black text-xl text-white">خروج از حساب</h3>
            <p class="text-sm text-gray-400">آیا مطمئن هستید که می‌خواهید خارج شوید؟</p>
            <div class="flex gap-4">
                <button type="button" onclick="closeLogoutModal()"
                    class="flex-1 bg-[#2A201C] border border-white/20 !text-white py-3.5 rounded-2xl font-bold text-sm hover:border-white/40 hover:bg-white/5 transition">
                    انصراف
                </button>
                <button type="button" onclick="confirmLogout()"
                    class="flex-1 bg-rose-600 hover:bg-rose-700 !text-white py-3.5 rounded-2xl font-bold text-sm transition shadow-lg">
                    خروج
                </button>
            </div>
        </div>
    </div>

    <?php include 'assets/php/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/profile.js"></script>
</body>

</html>
