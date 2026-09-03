<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">

    <?php include 'assets/php/header.php'; ?>

    <!-- بدنه اصلی پنل کاربری -->
    <main class="flex-1 max-w-[1400px] w-full mx-auto px-4 py-8 sm:py-12 relative z-10">

        <!-- مسیر دسترسی (Breadcrumb) -->
        <div class="flex items-center gap-2 text-xs text-gray-400 whitespace-nowrap mb-6">
            <a href="/" class="hover:text-white transition">صفحه اصلی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <span class="text-brand-red font-bold">پنل مدیریت حساب کاربری</span>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start w-full">

            <!-- ================= سایدبار (اطلاعات کاربر + منو) ================= -->
            <aside class="w-full lg:w-1/4 xl:w-1/5 flex flex-col gap-6 lg:sticky lg:top-24">

                <!-- کارت پروفایل سایدبار -->
                <div
                    class="bg-brand-grey border border-white/10 rounded-3xl p-6 text-center shadow-lg relative overflow-hidden group">
                    <div class="relative z-10 flex flex-col items-center">
                        <div class="relative mb-4">
                            <!-- فریم دور عکس -->
                            <div
                                class="w-24 h-24 bg-brand-grey border-2 border-brand-red rounded-full p-1 shadow-lg shadow-brand-red/20">
                                <div
                                    class="w-full h-full bg-brand-dark rounded-full flex items-center justify-center text-white font-black text-3xl">
                                    ع‌م
                                </div>
                            </div>
                            <span
                                class="absolute bottom-1 right-1 bg-emerald-500 text-white p-1.5 rounded-full border-4 border-brand-dark"
                                title="حساب تایید شده">
                                <i data-lucide="check" style="width:14px;height:14px;"></i>
                            </span>
                        </div>

                        <h2 class="text-xl font-black text-white mb-2">علی محمدی</h2>
                        <span
                            class="inline-flex items-center gap-1.5 bg-amber-500/10 border border-amber-500/20 text-amber-400 text-[11px] font-bold px-3 py-1 rounded-full mb-4 shadow-sm">
                            <i data-lucide="crown" style="width:12px;height:12px;"></i> خریدار طلایی
                        </span>

                        <div class="w-full border-t border-white/10 pt-4 space-y-3 text-xs text-right">
                            <div class="flex items-center gap-2.5 text-gray-400">
                                <i data-lucide="smartphone" class="text-brand-red shrink-0"
                                    style="width:16px;height:16px;"></i>
                                <span class="font-mono text-white" style="direction: ltr;">09189998852</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-gray-400">
                                <i data-lucide="calendar" class="text-brand-red shrink-0"
                                    style="width:16px;height:16px;"></i>
                                <span class="text-white">عضویت: ۱۴۰۲/۱۰/۱۴</span>
                            </div>
                            <div class="flex items-center gap-2.5 text-gray-400">
                                <i data-lucide="map-pin" class="text-brand-red shrink-0"
                                    style="width:16px;height:16px;"></i>
                                <span class="text-white">سقز</span>
                            </div>
                        </div>
                    </div>
                </div>

                <nav class="bg-brand-grey border border-white/10 rounded-3xl p-3 space-y-1.5 shadow-lg">
                    <button onclick="switchTab('dashboard')" id="nav-dashboard"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-brand-red bg-brand-red/10 border border-brand-red/30 font-bold text-sm">
                        <div class="flex items-center gap-3"><i data-lucide="layout-dashboard"
                                style="width:18px;height:18px;"></i> پیشخوان</div>
                        <i data-lucide="chevron-left" style="width:16px;height:16px;"></i>
                    </button>

                    <button onclick="switchTab('orders')" id="nav-orders"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="shopping-bag"
                                style="width:18px;height:18px;"></i> سفارش‌های من</div>
                        <span
                            class="bg-brand-red text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm">۱۲</span>
                    </button>

                    <button onclick="switchTab('vehicles')" id="nav-vehicles"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="car" style="width:18px;height:18px;"></i>
                            خودروهای من</div>
                        <span
                            class="bg-brand-red text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm">۲</span>
                    </button>

                    <button onclick="switchTab('addresses')" id="nav-addresses"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="map-pin"
                                style="width:18px;height:18px;"></i> آدرس‌ها</div>
                    </button>

                    <button onclick="switchTab('wishlist')" id="nav-wishlist"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="heart" style="width:18px;height:18px;"></i>
                            نشان‌شده‌ها</div>
                    </button>

                    <button onclick="switchTab('wallet')" id="nav-wallet"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="wallet"
                                style="width:18px;height:18px;"></i> کیف پول</div>
                    </button>

                    <button onclick="switchTab('tickets')" id="nav-tickets"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="headphones"
                                style="width:18px;height:18px;"></i> تیکت‌ها</div>
                        <span
                            class="bg-emerald-500 text-white text-[10px] px-2 py-0.5 rounded-full font-bold shadow-sm">جدید</span>
                    </button>

                    <button onclick="switchTab('settings')" id="nav-settings"
                        class="tab-btn w-full flex items-center justify-between px-4 py-3.5 rounded-2xl transition-all duration-300 text-gray-400 hover:text-brand-red hover:bg-brand-red/5 font-bold text-sm border border-transparent">
                        <div class="flex items-center gap-3"><i data-lucide="settings"
                                style="width:18px;height:18px;"></i> تنظیمات</div>
                    </button>

                    <div class="pt-3 mt-3 border-t border-white/10">
                        <button onclick="openLogoutModal()"
                            class="w-full flex items-center gap-3 px-4 py-3.5 rounded-2xl transition text-rose-500 font-bold text-sm hover:bg-rose-500/10 border border-transparent">
                            <i data-lucide="log-out" style="width:18px;height:18px;"></i> خروج از حساب
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- ================= بخش محتوای اصلی (سمت چپ) ================= -->
            <div class="w-full lg:w-3/4 xl:w-4/5">

                <!-- TAB 1: پیشخوان (DASHBOARD) -->
                <div id="tab-content-dashboard" class="tab-page space-y-6 lg:space-y-8 block">

                    <!-- کارت‌های بالای داشبورد (کیف پول و خودرو) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                        <!-- کیف پول -->
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-6 relative flex items-center justify-between shadow-sm">
                            <div class="relative z-10 space-y-1">
                                <span class="text-gray-400 text-xs font-bold">موجودی کیف پول شما</span>
                                <div class="text-emerald-400 font-black text-2xl sm:text-3xl">۱,۲۵۰,۰۰۰ <span
                                        class="text-sm font-medium text-gray-500">تومان</span></div>
                            </div>
                            <button onclick="switchTab('wallet')"
                                class="relative z-10 bg-brand-dark border border-emerald-500/30 text-emerald-400 p-3 rounded-2xl hover:border-emerald-500 transition shadow-sm">
                                <i data-lucide="plus" style="width:24px;height:24px;"></i>
                            </button>
                        </div>

                        <!-- خودرو فعال -->
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-6 relative flex items-center justify-between shadow-sm">
                            <div class="relative z-10 space-y-1">
                                <span class="text-gray-400 text-xs font-bold">خودروی اصلی شما</span>
                                <div class="text-white font-black text-lg sm:text-xl">تویوتا کمری (۲۰۱۵)</div>
                                <div class="text-xs text-gray-400 font-mono">VIN: JTD2015CAMRY8821</div>
                            </div>
                            <div
                                class="relative z-10 w-12 h-12 bg-brand-dark text-brand-red rounded-2xl flex items-center justify-center border border-white/10">
                                <i data-lucide="car" style="width:24px;height:24px;"></i>
                            </div>
                        </div>
                    </div>

                    <!-- چهار آمار کلیدی -->
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-brand-red transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-brand-red rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="shopping-bag" style="width:20px;height:20px;"></i></div>
                                <span class="text-xs text-gray-400 font-medium">کل سفارشات</span>
                            </div>
                            <div class="text-2xl font-black text-white">۱۲</div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-amber-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-amber-500 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="clock" style="width:20px;height:20px;"></i></div>
                                <span class="text-xs text-gray-400 font-medium">در حال پردازش</span>
                            </div>
                            <div class="text-2xl font-black text-white">۲</div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-emerald-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-emerald-400 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i></div>
                                <span class="text-xs text-gray-400 font-medium">تحویل شده</span>
                            </div>
                            <div class="text-2xl font-black text-white">۱۰</div>
                        </div>
                        <div
                            class="bg-brand-grey border border-white/10 rounded-3xl p-5 hover:border-blue-500 transition duration-300 shadow-sm">
                            <div class="flex items-center gap-3 mb-3">
                                <div
                                    class="w-10 h-10 bg-brand-dark text-blue-400 rounded-xl flex items-center justify-center border border-white/10">
                                    <i data-lucide="heart" style="width:20px;height:20px;"></i></div>
                                <span class="text-xs text-gray-400 font-medium">علاقه‌مندی‌ها</span>
                            </div>
                            <div class="text-2xl font-black text-white">۳</div>
                        </div>
                    </div>

                    <!-- ابزار سریع جستجوی قطعه -->
                    <div
                        class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 flex flex-col md:flex-row items-center justify-between gap-6 shadow-sm">
                        <div class="relative z-10 flex-1 space-y-2 text-center md:text-right">
                            <h3 class="font-bold text-lg text-white">استعلام دقیق با شماره شاسی (VIN)</h3>
                            <p class="text-sm text-gray-400">قطعاتی که دقیقاً با خودروی شما سازگار هستند را بیابید.</p>
                        </div>
                        <div class="relative z-10 w-full md:w-auto flex flex-col sm:flex-row gap-3">
                            <div class="relative w-full">
                                <select id="quick-vin-select"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl pl-10 pr-4 py-3 text-sm text-gray-300 appearance-none focus:outline-none focus:border-brand-red transition cursor-pointer">
                                    <option value="JTD2015CAMRY8821">تویوتا کمری ۲۰۱۵</option>
                                    <option value="JTE2021HILUX7743">تویوتا هایلوکس ۲۰۲۱</option>
                                </select>

                                <i data-lucide="chevron-down"
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none w-4 h-4"></i>
                            </div>
                            <a href="/parts"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-sm px-8 py-3 rounded-2xl transition flex items-center justify-center gap-2 whitespace-nowrap shadow-lg">
                                جستجوی قطعات
                            </a>
                        </div>
                    </div>

                    <!-- لیست آخرین سفارشات -->
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-4 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center">
                            <h3 class="font-black text-lg text-white">آخرین سفارش‌ها</h3>
                            <button onclick="switchTab('orders')"
                                class="text-brand-red text-sm font-bold flex items-center gap-1 hover:underline">
                                همه سفارش‌ها
                                <i data-lucide="arrow-left" class="w-[14px] h-[14px]"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <!-- سفارش ۱ -->
                            <div
                                class="bg-brand-dark border border-white/10 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-brand-red transition">

                                <div class="flex items-center gap-3 sm:gap-5 w-full md:w-auto">
                                    <div
                                        class="w-12 h-12 bg-brand-grey text-amber-500 rounded-2xl flex items-center justify-center shrink-0 border border-white/10">
                                        <i data-lucide="package-search" class="w-6 h-6"></i>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="font-bold text-white truncate">سفارش <span
                                                    class="font-mono text-brand-red">#403192</span></span>
                                            <span
                                                class="bg-amber-500/10 border border-amber-500/20 text-amber-500 text-[10px] font-bold px-2 py-0.5 rounded-lg whitespace-nowrap shrink-0">در
                                                حال پردازش</span>
                                        </div>
                                        <p class="text-xs text-gray-400 truncate">ثبت شده در: ۱۱ مرداد ۱۴۰۳</p>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center w-full md:w-auto justify-between md:justify-end gap-6 border-t border-white/10 md:border-none pt-4 md:pt-0 mt-2 md:mt-0">
                                    <div class="text-right md:text-left">
                                        <div class="text-xs text-gray-400 mb-0.5">مبلغ کل</div>
                                        <div class="font-black text-white">۳,۹۰۰,۰۰۰ <span
                                                class="text-[10px] text-gray-500">تومان</span></div>
                                    </div>
                                    <button onclick="openOrderDetailModal(403192)"
                                        class="bg-transparent text-brand-red border border-brand-red hover:bg-brand-red hover:!text-white px-5 py-2 sm:py-2.5 rounded-xl font-bold transition text-xs shrink-0">
                                        جزئیات
                                    </button>
                                </div>
                            </div>

                            <!-- سفارش ۲ -->
                            <div
                                class="bg-brand-dark border border-white/10 rounded-2xl p-4 sm:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:border-brand-red transition">
                                <div class="flex items-center gap-3 sm:gap-5 w-full md:w-auto">
                                    <div
                                        class="w-12 h-12 bg-brand-grey text-emerald-400 rounded-2xl flex items-center justify-center shrink-0 border border-white/10">
                                        <i data-lucide="package-check" class="w-6 h-6"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1">
                                            <span class="font-bold text-white truncate">سفارش <span
                                                    class="font-mono text-gray-400">#402881</span></span>
                                            <span
                                                class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded-lg whitespace-nowrap shrink-0">تحویل
                                                شده</span>
                                        </div>
                                        <p class="text-xs text-gray-400 truncate">ثبت شده در: ۲ تیر ۱۴۰۳</p>
                                    </div>
                                </div>
                                <div
                                    class="flex items-center w-full md:w-auto justify-between md:justify-end gap-6 border-t border-white/10 md:border-none pt-4 md:pt-0 mt-2 md:mt-0">
                                    <div class="text-right md:text-left">
                                        <div class="text-xs text-gray-400 mb-0.5">مبلغ کل</div>
                                        <div class="font-black text-white">۱,۲۰۰,۰۰۰ <span
                                                class="text-[10px] text-gray-500">تومان</span></div>
                                    </div>
                                    <button onclick="openOrderDetailModal(402881)"
                                        class="bg-transparent text-brand-red border border-brand-red hover:bg-brand-red hover:!text-white px-5 py-2 sm:py-2.5 rounded-xl font-bold transition text-xs shrink-0">
                                        جزئیات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: سفارش‌های من (ORDERS) -->
                <div id="tab-content-orders" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">سوابق سفارشات</h3>
                            <div class="flex gap-2 bg-brand-dark p-1.5 rounded-xl border border-white/10">
                                <!-- اضافه شدن event به تابع -->
                                <button onclick="filterOrders('all', event)"
                                    class="order-filter-btn bg-brand-red text-white px-5 py-2.5 rounded-lg font-bold text-xs transition">همه</button>
                                <button onclick="filterOrders('processing', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-5 py-2.5 rounded-lg font-bold text-xs transition">جاری</button>
                                <button onclick="filterOrders('delivered', event)"
                                    class="order-filter-btn text-gray-400 hover:text-white px-5 py-2.5 rounded-lg font-bold text-xs transition">تحویل‌شده</button>
                            </div>
                        </div>

                        <!-- کارت سفارش تفصیلی -->
                        <div class="order-card bg-brand-dark border border-white/10 rounded-3xl p-6 space-y-5"
                            data-status="processing">
                            <div
                                class="flex flex-wrap justify-between items-center gap-3 border-b border-white/10 pb-4">
                                <div class="space-y-1">
                                    <span class="text-sm font-bold text-gray-400">سفارش: <strong
                                            class="text-white font-mono">#403192</strong></span>
                                    <span class="text-xs text-gray-500 block">۱۱ مرداد ۱۴۰۳</span>
                                </div>
                                <span
                                    class="bg-amber-500/10 text-amber-500 text-xs font-bold px-4 py-2 rounded-xl border border-amber-500/20">در
                                    حال آماده‌سازی</span>
                            </div>

                            <div class="space-y-3">
                                <div
                                    class="flex items-center gap-4 bg-brand-grey p-3 rounded-2xl border border-white/10">
                                    <div
                                        class="w-14 h-14 bg-brand-dark rounded-xl flex items-center justify-center text-brand-red border border-white/10 shrink-0">
                                        <i data-lucide="disc"></i></div>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-sm font-bold text-white truncate">لنت ترمز جلو اصلی تویوتا کمری
                                        </h5>
                                        <span class="text-xs text-gray-400 mt-1 block">تعداد: ۱ عدد</span>
                                    </div>
                                    <span class="text-sm font-black text-brand-red">۳,۴۵۰,۰۰۰ <span
                                            class="text-[10px] text-gray-400">ت</span></span>
                                </div>
                            </div>

                            <div
                                class="flex flex-wrap justify-between items-center pt-4 border-t border-white/10 gap-4">
                                <div class="text-xs text-gray-400">ارسال با: <strong class="text-white">تیپاکس</strong>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-sm text-gray-400">جمع کل: <strong
                                            class="text-emerald-400 text-lg font-black">۳,۹۰۰,۰۰۰ تومان</strong></span>
                                    <button onclick="openOrderDetailModal(403192)"
                                        class="bg-transparent text-brand-red border border-brand-red hover:bg-brand-red hover:text-white px-6 py-2.5 rounded-xl font-bold transition text-xs">
                                        فاکتور
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: خودروها (VEHICLES) -->
                <div id="tab-content-vehicles" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">خودروهای من</h3>
                            <button onclick="openAddVehicleModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2 shadow-lg">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> افزودن خودرو
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="bg-brand-dark border border-brand-red/30 rounded-3xl p-6 relative">
                                <span
                                    class="bg-brand-red/10 text-brand-red text-[10px] font-bold px-3 py-1 rounded-lg absolute top-6 left-6 border border-brand-red/20">خودروی
                                    اصلی</span>
                                <div class="flex items-center gap-4 mb-6">
                                    <div
                                        class="w-16 h-16 bg-brand-grey border border-white/10 rounded-2xl flex items-center justify-center text-brand-red">
                                        <i data-lucide="car" style="width:32px;height:32px;"></i></div>
                                    <div>
                                        <h4 class="font-black text-lg text-white">کمری ۲۰۱۵</h4>
                                        <span class="text-xs text-gray-400">تیپ GLX</span>
                                    </div>
                                </div>
                                <div
                                    class="bg-brand-grey border border-white/10 rounded-2xl p-4 space-y-3 text-xs mb-4">
                                    <div class="flex justify-between"><span class="text-gray-400">شاسی (VIN)</span><span
                                            class="font-mono text-white">JTD2015CAMRY8821</span></div>
                                    <div class="flex justify-between"><span class="text-gray-400">موتور</span><span
                                            class="text-white">2.5L 2AR-FE</span></div>
                                </div>
                                <a href="/parts"
                                    class="block w-full text-center bg-brand-grey hover:border-brand-red text-white text-xs py-3 rounded-xl transition font-bold border border-white/10">جستجوی
                                    قطعات این خودرو</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: آدرس‌ها (ADDRESSES) -->
                <div id="tab-content-addresses" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">آدرس‌های پستی</h3>
                            <button onclick="openAddAddressModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> ثبت آدرس
                            </button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="bg-brand-dark border border-emerald-500/30 rounded-3xl p-6 relative">
                                <span
                                    class="bg-emerald-500/10 text-emerald-400 text-[10px] font-bold px-3 py-1 rounded-lg absolute top-6 left-6 border border-emerald-500/20">پیش‌فرض</span>
                                <h4 class="font-bold text-base text-white mb-3">تحویل به علی محمدی</h4>
                                <p class="text-sm text-gray-400 leading-relaxed mb-4">کردستان، سقز، خیابان ساحلی، پلاک
                                    ۴۵</p>
                                <div
                                    class="bg-brand-grey border border-white/10 rounded-2xl p-4 flex justify-between text-xs">
                                    <span class="text-gray-400">کد پستی: <strong
                                            class="text-white font-mono">۶۶۱۸۹۹۳۸۲۱</strong></span>
                                    <span class="text-gray-400">تماس: <strong
                                            class="text-white font-mono">09189998852</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: علاقه‌مندی‌ها (WISHLIST) -->
                <div id="tab-content-wishlist" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">قطعات نشان‌شده</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                            <div
                                class="bg-brand-dark border border-white/10 p-5 rounded-3xl space-y-4 hover:border-brand-red transition">
                                <div
                                    class="w-full h-40 bg-brand-grey border border-white/10 rounded-2xl flex items-center justify-center text-brand-red">
                                    <i data-lucide="zap" style="width:48px;height:48px;"></i></div>
                                <h4 class="font-bold text-sm text-white line-clamp-2">شمع سوزنی ایریدیوم لندکروزر</h4>
                                <div class="flex items-center justify-between border-t border-white/10 pt-4">
                                    <span class="font-black text-sm text-brand-red">۱,۲۰۰,۰۰۰ ت</span>
                                    <a href="/product?id=2"
                                        class="bg-brand-grey hover:border-brand-red border border-white/10 text-white p-3 rounded-xl transition"><i
                                            data-lucide="shopping-cart" style="width:18px;height:18px;"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: کیف پول (WALLET) -->
                <div id="tab-content-wallet" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <h3 class="font-black text-xl text-white border-b border-white/10 pb-6">کیف پول و تراکنش‌ها</h3>

                        <div class="bg-brand-dark border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6">
                            <h4 class="font-bold text-sm text-white">شارژ سریع اعتبار</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <button onclick="setChargeAmount(500000)"
                                    class="bg-brand-grey hover:border-brand-red border border-white/10 py-4 rounded-2xl text-xs font-bold text-gray-400 hover:text-white transition">۵۰۰,۰۰۰
                                    ت</button>
                                <button onclick="setChargeAmount(1000000)"
                                    class="bg-brand-grey hover:border-brand-red border border-white/10 py-4 rounded-2xl text-xs font-bold text-gray-400 hover:text-white transition">۱,۰۰۰,۰۰۰
                                    ت</button>
                                <button onclick="setChargeAmount(2000000)"
                                    class="bg-brand-grey hover:border-brand-red border border-white/10 py-4 rounded-2xl text-xs font-bold text-gray-400 hover:text-white transition">۲,۰۰۰,۰۰۰
                                    ت</button>
                                <button onclick="setChargeAmount(5000000)"
                                    class="bg-brand-grey hover:border-brand-red border border-white/10 py-4 rounded-2xl text-xs font-bold text-gray-400 hover:text-white transition">۵,۰۰۰,۰۰۰
                                    ت</button>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <input type="number" id="custom-wallet-amount" placeholder="مبلغ دلخواه به تومان..."
                                    class="flex-1 bg-brand-grey border border-white/10 rounded-2xl px-6 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                <button onclick="submitWalletCharge()"
                                    class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-10 py-4 rounded-2xl text-sm transition shadow-lg">پرداخت
                                    آنلاین</button>
                            </div>
                        </div>

                        <div class="space-y-3 pt-4">
                            <h4 class="font-bold text-sm text-white mb-4">تراکنش‌های اخیر</h4>
                            <div
                                class="bg-brand-dark p-5 rounded-2xl border border-white/10 flex justify-between items-center">
                                <div><span class="font-bold text-sm text-white block">شارژ آنلاین</span><span
                                        class="text-xs text-gray-400">۱۴۰۳/۰۵/۰۱</span></div>
                                <span class="font-black text-emerald-400">+ ۲,۰۰۰,۰۰۰ تومان</span>
                            </div>
                            <div
                                class="bg-brand-dark p-5 rounded-2xl border border-white/10 flex justify-between items-center">
                                <div><span class="font-bold text-sm text-white block">پرداخت فاکتور #402881</span><span
                                        class="text-xs text-gray-400">۱۴۰۳/۰۴/۰۲</span></div>
                                <span class="font-black text-rose-500">- ۷۵۰,۰۰۰ تومان</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: پشتیبانی (TICKETS) -->
                <div id="tab-content-tickets" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <div class="flex justify-between items-center border-b border-white/10 pb-6">
                            <h3 class="font-black text-xl text-white">تیکت‌های پشتیبانی</h3>
                            <button onclick="openNewTicketModal()"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition flex items-center gap-2">
                                <i data-lucide="plus" style="width:16px;height:16px;"></i> تیکت جدید
                            </button>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-brand-dark border border-white/10 p-6 rounded-3xl space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-sm text-white">استعلام کد شمع پرادو ۲۰۰۸</span>
                                    <span
                                        class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold px-3 py-1 rounded-lg">پاسخ
                                        داده شد</span>
                                </div>
                                <div
                                    class="bg-brand-grey p-4 rounded-2xl border border-white/10 text-sm text-gray-400 leading-relaxed">
                                    <strong class="text-brand-red block mb-1">پشتیبان سایت پرادو یدک:</strong>
                                    کد 90919-01235 اصلی در انبار موجود است.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 8: تنظیمات (SETTINGS) -->
                <div id="tab-content-settings" class="tab-page hidden space-y-6">
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 lg:p-8 space-y-6 shadow-sm">
                        <h3 class="font-black text-xl text-white border-b border-white/10 pb-6">تنظیمات حساب</h3>
                        <form onsubmit="handleSaveSettings(event)" class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1">نام و نام خانوادگی</label>
                                    <input type="text" value="علی محمدی"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1">موبایل</label>
                                    <!-- opacity-60 برای حالت غیرفعال به جای تغییر رنگ پس زمینه -->
                                    <input type="tel" value="09189998852" disabled
                                        class="w-full bg-brand-dark opacity-60 border border-white/10 rounded-2xl px-5 py-4 text-sm text-gray-500 cursor-not-allowed font-mono text-left">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-xs text-gray-400 ml-1">کد ملی</label>
                                    <input type="text" value="3820198821"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition font-mono text-left">
                                </div>
                            </div>

                            <div class="pt-6 border-t border-white/10 space-y-6">
                                <h4 class="font-bold text-white">تغییر رمز عبور</h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <input type="password" placeholder="رمز فعلی"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                    <input type="password" placeholder="رمز جدید"
                                        class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:outline-none focus:border-brand-red transition">
                                </div>
                            </div>
                            <button type="submit"
                                class="bg-brand-red hover:bg-red-700 text-white font-bold py-4 px-10 rounded-2xl text-sm transition shadow-lg">ذخیره
                                اطلاعات</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- ================= مودال‌های تعاملی ================= -->
    <div id="order-detail-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeOrderDetailModal()">
        <div class="w-full max-w-2xl bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()" id="order-detail-modal-content"></div>
    </div>

    <div id="add-vehicle-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeAddVehicleModal()">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-3xl p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()">
            <button class="absolute top-6 left-6 text-gray-500 hover:text-white" onclick="closeAddVehicleModal()"><i
                    data-lucide="x"></i></button>
            <h3 class="font-black text-xl text-white mb-6">ثبت خودرو جدید</h3>
            <form onsubmit="handleAddVehicle(event)" class="space-y-5">
                <input type="text" id="veh-model" required placeholder="مدل (مثلا پرادو)"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red">
                <input type="text" id="veh-year" required placeholder="سال ساخت"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red">
                <input type="text" id="veh-vin" required placeholder="VIN (شاسی)"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red font-mono text-left">
                <button type="submit" class="w-full bg-brand-red text-white font-bold py-4 rounded-2xl">ثبت
                    خودرو</button>
            </form>
        </div>
    </div>

    <div id="add-address-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeAddAddressModal()">
        <div class="w-full max-w-md bg-brand-grey border border-white/10 rounded-3xl p-8 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()">
            <button class="absolute top-6 left-6 text-gray-500 hover:text-white" onclick="closeAddAddressModal()"><i
                    data-lucide="x"></i></button>
            <h3 class="font-black text-xl text-white mb-6">آدرس جدید</h3>
            <form onsubmit="handleAddAddress(event)" class="space-y-5">
                <input type="text" required placeholder="استان - شهر"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red">
                <textarea required rows="3" placeholder="نشانی دقیق"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red"></textarea>
                <input type="text" required placeholder="کد پستی"
                    class="w-full bg-brand-dark border border-white/10 rounded-2xl px-5 py-4 text-sm text-white focus:border-brand-red font-mono text-left">
                <button type="submit" class="w-full bg-brand-red text-white font-bold py-4 rounded-2xl">ثبت
                    آدرس</button>
            </form>
        </div>
    </div>

    <div id="logout-modal-overlay"
        class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="closeLogoutModal()">
        <div class="w-full max-w-sm bg-brand-grey border border-white/10 rounded-3xl p-8 text-center space-y-6 relative scale-95 opacity-0 transition-all duration-300 shadow-2xl"
            onclick="event.stopPropagation()">
            <div
                class="w-20 h-20 bg-brand-dark border border-rose-500/20 rounded-full flex items-center justify-center mx-auto text-rose-500">
                <i data-lucide="log-out" style="width:36px;height:36px;"></i></div>
            <h3 class="font-black text-xl text-white">خروج از حساب</h3>
            <p class="text-sm text-gray-400">آیا مطمئن هستید؟</p>
            <div class="flex gap-4">
                <button onclick="closeLogoutModal()"
                    class="flex-1 bg-brand-dark border border-white/10 text-black py-3.5 rounded-2xl font-bold">انصراف</button>
                <a href="/login" class="flex-1 bg-rose-600 text-white py-3.5 rounded-2xl font-bold">خروج</a>
            </div>
        </div>
    </div>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>