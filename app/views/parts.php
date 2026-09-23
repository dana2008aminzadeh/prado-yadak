<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <!-- بدنه اصلی صفحه قطعات -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">

            <!-- ================= فیلترها در دسکتاپ (Sidebar) ================= -->
            <aside
                class="w-full lg:w-72 flex-shrink-0 hidden lg:block bg-brand-grey border border-white/10 rounded-2xl p-6 self-start space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <span class="font-extrabold text-base flex items-center gap-2">
                        <i data-lucide="sliders-horizontal" class="text-brand-red" style="width:18px;height:18px;"></i>
                        فیلترهای پیشرفته
                    </span>
                    <button onclick="resetFilters()" class="text-xs text-gray-400 hover:text-brand-red transition">حذف
                        همه</button>
                </div>

                <div role="group" aria-labelledby="desktop-model-filter-label">
                    <p id="desktop-model-filter-label" class="font-bold text-sm text-gray-200 mb-3">مدل‌های تویوتا</p>
                    <div class="space-y-2.5" id="model-filters">
                        <?php
                        global $car_models;
                        if (!empty($car_models)) {
                            foreach ($car_models as $slug => $data) {
                                $name = is_array($data) ? $data['name'] : $data;
                                echo '<label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer transition">
                                        <input type="checkbox" name="model" value="' . e($slug) . '" class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10" onchange="syncCheckboxes(\'model\', this.value, this.checked)">
                                        ' . e($name) . '
                                      </label>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <!-- فیلتر دسته‌بندی قطعه -->
                <div role="group" aria-labelledby="desktop-category-filter-label">
                    <p id="desktop-category-filter-label" class="font-bold text-sm text-gray-200 mb-3">دسته‌بندی قطعه</p>
                    <div class="space-y-2.5" id="category-filters">
                        <?php
                        global $part_categories;
                        if (!empty($part_categories)) {
                            foreach ($part_categories as $slug => $data) {
                                $name = is_array($data) ? $data['name'] : $data;
                                echo '<label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer transition">
                                        <input type="checkbox" name="category" value="' . e($slug) . '" class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10" onchange="syncCheckboxes(\'category\', this.value, this.checked)">
                                        ' . e($name) . '
                                      </label>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div role="group" aria-labelledby="desktop-price-filter-label">
                    <p id="desktop-price-filter-label" class="font-bold text-sm text-gray-200 mb-3">حدود قیمت (تومان)</p>
                    <input type="range" id="price-slider" aria-labelledby="desktop-price-filter-label" min="0" max="300000000" step="1000000" value="300000000"
                        class="w-full h-1 bg-brand-dark rounded-lg appearance-none cursor-pointer"
                        oninput="updatePriceLabel(this.value)">
                    <div class="flex justify-between items-center text-xs text-gray-400 mt-2">
                        <span>از ۱۰۰,۰۰۰</span>
                        <span id="price-val" class="font-bold text-white text-sm">تا ۳۰۰ میلیون</span>
                    </div>
                </div>

                <!-- فیلتر اصالت و برند کالا -->
                <div role="group" aria-labelledby="desktop-brand-filter-label">
                    <p id="desktop-brand-filter-label" class="font-bold text-sm text-gray-200 mb-3">اصالت و برند کالا</p>
                    <div class="space-y-2">
                        <!-- دسته‌بندی‌های کلی -->
                        <label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer">
                            <input type="checkbox" name="brand" value="genuine"
                                class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10"
                                onchange="syncCheckboxes('brand', this.value, this.checked)">
                            تویوتا جنیون پارت (اصلی)
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer">
                            <input type="checkbox" name="brand" value="oem"
                                class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10"
                                onchange="syncCheckboxes('brand', this.value, this.checked)">
                            همه وارداتی‌های معتبر (OEM)
                        </label>

                        <!-- برندهای اختصاصی خوانده شده از دیتابیس -->
                        <?php if (!empty($brands)): ?>
                            <div class="mt-3 border-t border-white/5 pt-3 space-y-2">
                                <?php foreach ($brands as $brandName): ?>
                                    <label
                                        class="flex items-center gap-2.5 text-xs text-gray-400 hover:text-white cursor-pointer mt-2">
                                        <input type="checkbox" name="brand" value="<?= e(strtolower($brandName)) ?>"
                                            class="rounded accent-brand-red w-4 h-4 bg-brand-dark border-white/10"
                                            onchange="syncCheckboxes('brand', this.value, this.checked)">
                                        برند <?= e($brandName) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- فقط کالاهای موجود (نسخه دسکتاپ) -->
                <div class="pt-4 border-t border-white/10 flex items-center justify-between">
                    <label for="in-stock-toggle" id="in-stock-toggle-label" class="text-sm text-gray-300 cursor-pointer">فقط کالاهای موجود</label>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0">
                        <input type="checkbox" id="in-stock-toggle" class="sr-only peer"
                            aria-labelledby="in-stock-toggle-label" onchange="toggleInStock(this.checked)">
                        <!-- پس‌زمینه سوییچ -->
                        <div
                            class="w-11 h-6 bg-gray-400/50 rounded-full peer-checked:bg-brand-red transition-colors duration-300">
                        </div>
                        <!-- دایره سوییچ -->
                        <div
                            class="absolute right-[2px] top-[2px] w-5 h-5 bg-white rounded-full shadow-md transition-transform duration-300 peer-checked:-translate-x-[20px]">
                        </div>
                    </label>
                </div>
            </aside>

            <!-- ================= محتوای اصلی (جستجو و لیست محصولات) ================= -->
            <div class="flex-1 space-y-6">
                <div class="pb-2">
                    <?php
                        // در لندینگ‌پیج اختصاصی، H1 و متن معرفی از پنل مدیریت می‌آید.
                        // لندینگ تمیز دسته/مدل نیز H1 را در کنترلر تنظیم می‌کند.
                        $isLanding = !empty($landingPage);
                        if ($isLanding) {
                            $h1_title = $landingPage['h1'];
                        } elseif (!isset($h1_title)) {
                            $h1_title = 'کاتالوگ و قیمت لوازم یدکی تویوتا';
                            $requestedCategory = $selectedCat ?? ($_GET['category'] ?? null);
                            $requestedModel = $selectedModel ?? ($_GET['model'] ?? null);
                            if ($requestedCategory && isset($GLOBALS['part_categories'][$requestedCategory])) {
                                $catData = $GLOBALS['part_categories'][$requestedCategory];
                                $catName = is_array($catData) ? ($catData['name'] ?? '') : $catData;
                                $h1_title = 'خرید لوازم ' . $catName . ' تویوتا';
                            } elseif ($requestedModel && isset($GLOBALS['car_models'][$requestedModel])) {
                                $modData = $GLOBALS['car_models'][$requestedModel];
                                $modName = is_array($modData) ? ($modData['name'] ?? '') : $modData;
                                $h1_title = 'قطعات یدکی تویوتا ' . $modName;
                            }
                        }
                    ?>
                    <h1 class="text-xl sm:text-2xl font-black text-white">
                        <?= e($h1_title) ?>
                    </h1>
                    <p class="text-xs text-gray-400 mt-1">تامین قطعات جنیون پارتس و OEM اصلی با ضمانت تطابق شاسی (VIN)</p>

                    <?php if ($isLanding && !empty($landingPage['intro_html'])): ?>
                        <div class="mt-4 bg-brand-grey border border-white/10 rounded-2xl p-5 text-sm text-gray-300 leading-loose text-justify">
                            <?= clean_html($landingPage['intro_html']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- نوار جستجو و دکمه فیلتر موبایل -->
                <div
                    class="bg-brand-grey border border-white/10 rounded-2xl p-4 flex flex-col sm:flex-row gap-3 items-center">
                    <!-- فرم استاندارد GET برای جستجوی قابل خزش و قابل استفاده بدون جاوااسکریپت -->
                    <form action="/parts" method="GET" role="search" aria-label="جستجو در کاتالوگ قطعات"
                        class="relative w-full flex-1">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none">
                            <i data-lucide="search" class="text-gray-400 w-5 h-5" aria-hidden="true"></i>
                        </div>
                        <label for="search-input" class="sr-only">نام قطعه یا شماره فنی</label>
                        <input type="search" id="search-input" name="q"
                            value="<?= e((string) ($_GET['q'] ?? '')) ?>"
                            placeholder="نام قطعه یا شماره فنی آن را جستجو کنید... (مثلا: لنت ترمز)"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl pr-12 pl-12 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition"
                            oninput="triggerFilter()">
                        <button type="submit" aria-label="اجرای جستجو"
                            class="absolute inset-y-1.5 left-1.5 w-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-white hover:bg-brand-red transition">
                            <i data-lucide="arrow-left" class="w-4 h-4" aria-hidden="true"></i>
                        </button>
                    </form>

                    <div class="flex w-full sm:w-auto gap-2">
                        <!-- دکمه فیلتر مخصوص موبایل -->
                        <button type="button" onclick="toggleMobileFilters(true)" aria-controls="mobile-filter-drawer" aria-expanded="false"
                            class="lg:hidden flex flex-1 items-center justify-center gap-2 border border-white/10 hover:border-brand-red px-5 py-3 rounded-xl text-sm font-bold bg-brand-dark/50 transition">
                            <i data-lucide="sliders-horizontal" style="width:16px;height:16px;"></i> فیلترها
                        </button>
                        <!-- انتخاب مرتب‌سازی -->
                        <div class="relative flex-1 sm:flex-none">
                            <label for="sort-select" class="sr-only">مرتب‌سازی قطعات</label>
                            <select id="sort-select" onchange="applyFilters(1)"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl pr-4 pl-10 py-3 text-sm text-gray-300 appearance-none focus:outline-none focus:border-brand-red transition cursor-pointer">
                                <option value="newest">جدیدترین قطعات</option>
                                <option value="price-asc">ارزان‌ترین</option>
                                <option value="price-desc">گران‌ترین</option>
                                <option value="popular">محبوب‌ترین</option>
                            </select>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i data-lucide="chevron-down" class="text-gray-400 w-4 h-4"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center text-xs text-gray-400 px-1">
                    <h2 class="text-sm font-bold text-white m-0" id="results-count" aria-live="polite">
                        <?= !empty($products) ? "یافت شده: {$totalCount} قطعه" : "در حال بارگذاری..." ?>
                    </h2>
                    <span>ضمانت تطابق قطعه با شماره شاسی خودرو (VIN)</span>
                </div>

                <!-- گرید رندر شده با PHP برای سئو -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="parts-grid"
                    data-total="<?= (int) ($totalCount ?? 0) ?>" data-page="<?= (int) ($page ?? 1) ?>"
                    data-has-ssr="<?= !empty($products) ? 'true' : 'false' ?>">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $part): ?>
                            <?php
                            $carModelName = $GLOBALS['car_models'][$part['model']]['name'] ?? $part['model'];
                            // آدرس تصویر سئوشده + متن جایگزین معنادار (نام قطعه + خودرو + کد فنی)
                            $imgSrc = (string) ($part['image_url'] ?? '');
                            $imgAlt = (string) ($part['image_alt']
                                ?? \Core\Seo::suggestAlt((string) $part['name'], is_string($carModelName) ? $carModelName : null, $part['oem'] ?? null));
                            $safeSlug = rawurlencode($part['slug']);
                            ?>
                            <article
                                class="bg-brand-grey border border-white/5 hover:border-brand-red/30 p-5 rounded-2xl flex flex-col justify-between transition duration-300 hover:shadow-[0_10px_35px_rgba(225,6,0,0.12)]">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <?php if ($part['inStock']): ?>
                                            <span
                                                class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold border border-emerald-500/20">موجود
                                                در انبار</span>
                                        <?php else: ?>
                                            <span
                                                class="bg-rose-500/10 text-rose-400 text-[10px] px-2 py-1 rounded-md font-bold border border-rose-500/20">ناموجود</span>
                                        <?php endif; ?>

                                        <?php if ($part['isGenuine']): ?>
                                            <span
                                                class="bg-brand-red/10 text-brand-red text-[10px] px-2 py-1 rounded-md font-bold border border-brand-red/20">اصلی
                                                Genuine</span>
                                        <?php else: ?>
                                            <span
                                                class="bg-gray-400/10 text-gray-300 text-[10px] px-2 py-1 rounded-md font-bold border border-gray-400/20">وارداتی
                                                OEM</span>
                                        <?php endif; ?>
                                    </div>

                                    <a href="/product/<?= $safeSlug ?>"
                                        class="w-full h-40 bg-brand-dark rounded-xl flex items-center justify-center mb-4 text-brand-red relative group overflow-hidden border border-white/5 cursor-pointer block">
                                        <?php if ($imgSrc !== ''): ?>
                                            <img src="<?= e($imgSrc) ?>" alt="<?= e($imgAlt) ?>" loading="lazy" decoding="async"
                                                width="300" height="300"
                                                class="max-w-full max-h-full object-contain transition transform group-hover:scale-110 duration-300">
                                        <?php else: ?>
                                            <span class="text-gray-600 flex flex-col items-center gap-2" role="img" aria-label="تصویر محصول ثبت نشده است">
                                                <i data-lucide="image-off" class="w-10 h-10" aria-hidden="true"></i>
                                                <small class="text-[10px]">بدون تصویر</small>
                                            </span>
                                        <?php endif; ?>
                                        <span
                                            class="absolute bottom-2 left-2 text-[10px] text-gray-500 bg-brand-dark/80 px-2 py-0.5 rounded border border-white/10"
                                            dir="ltr">OEM: <?= e($part['oem']) ?></span>
                                    </a>

                                    <!-- اصلاح سلسله‌مراتب سئو: عناوین کارت‌ها تبدیل به H3 شدند -->
                                    <a href="/product/<?= $safeSlug ?>" class="block">
                                        <h3
                                            class="font-bold text-sm text-white leading-relaxed line-clamp-2 hover:text-brand-red transition">
                                            <?= e($part['name']) ?>
                                        </h3>
                                    </a>

                                    <p class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                                        <i data-lucide="car" style="width:13px;height:13px;"></i>
                                        سازگار با: <?= e($carModelName) ?>
                                    </p>
                                </div>

                                <div class="mt-6 pt-4 border-t border-white/5 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-gray-500 block mb-0.5">قیمت مصرف‌کننده:</span>
                                        <span class="font-black text-sm text-brand-red"><?= number_format($part['price']) ?>
                                            تومان</span>
                                    </div>
                                    <button type="button" <?= $part['inStock'] ? 'onclick="addToCart(' . (int) $part['id'] . ')"' : 'disabled' ?>
                                        aria-label="<?= e($part['inStock'] ? 'افزودن ' . $part['name'] . ' به سبد خرید' : $part['name'] . ' ناموجود است') ?>"
                                        class="p-2.5 rounded-xl transition <?= $part['inStock'] ? 'bg-brand-red hover:bg-red-700 text-white shadow-[0_4px_15px_rgba(225,6,0,0.2)]' : 'bg-white/5 text-gray-500 cursor-not-allowed' ?>">
                                        <i data-lucide="shopping-cart" style="width:18px;height:18px;" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- نشانگر لود اسکرول نامحدود -->
                <div id="scroll-sentinel" class="w-full h-8"></div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="صفحات محصولات"
                        class="flex justify-center items-center gap-2 py-8 my-4 border-t border-white/5">
                        <?php
                        // لینک‌های صفحه‌بندی همیشه با ترتیب پارامتر نرمال‌شده ساخته می‌شوند
                        // تا نسخه‌های موازی از یک صفحه برای گوگل ایجاد نشود.
                        $listBase = !empty($landingPage)
                            ? '/parts/' . rawurlencode($landingPage['slug'])
                            : ($catalogBasePath ?? '/parts');
                        $isCleanList = !empty($landingPage) || !empty($catalogBasePath);
                        $pageLink = function ($n) use ($listBase, $isCleanList) {
                            if ($isCleanList) {
                                return $listBase . ($n > 1 ? '?page=' . (int) $n : '');
                            }
                            $params = $_GET;
                            $params['page'] = $n;
                            $qs = \Core\Seo::normalizeQuery($params);
                            return $listBase . ($qs ? '?' . $qs : '');
                        };
                        $prevPage = $page > 1 ? $page - 1 : null;
                        $nextPage = $page < $totalPages ? $page + 1 : null;
                        ?>
                        <?php if ($prevPage): ?>
                            <a href="<?= e($pageLink($prevPage)) ?>"
                                class="px-4 py-2 bg-brand-dark border border-white/10 hover:border-brand-red rounded-xl text-xs font-bold text-gray-300 hover:text-white transition">صفحه
                                قبل</a>
                        <?php endif; ?>

                        <div class="flex gap-1">
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <a href="<?= e($pageLink($p)) ?>"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl text-xs font-bold transition <?= $p === $page ? 'bg-brand-red text-white' : 'bg-brand-dark border border-white/10 text-gray-400 hover:text-white' ?>">
                                    <?= $p ?>
                                </a>
                            <?php endfor; ?>
                        </div>

                        <?php if ($nextPage): ?>
                            <a href="<?= e($pageLink($nextPage)) ?>"
                                class="px-4 py-2 bg-brand-dark border border-white/10 hover:border-brand-red rounded-xl text-xs font-bold text-gray-300 hover:text-white transition">صفحه
                                بعد</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>

                <!-- لودینگ چرخان انتهای صفحه -->
                <div id="infinite-loader" class="hidden w-full py-8 flex flex-col items-center justify-center gap-3">
                    <div class="w-8 h-8 border-4 border-white/10 border-t-brand-red rounded-full animate-spin"></div>
                    <span class="text-xs font-bold text-gray-400">در حال دریافت قطعات بیشتر...</span>
                </div>

                <!-- پیام پایان کل محصولات -->
                <div id="end-of-catalog"
                    class="hidden w-full text-center py-8 text-xs font-bold text-gray-500 border-t border-white/5 my-4">
                    به پایان کاتالوگ قطعات رسیدید.
                </div>

                <!-- وضعیت خالی بودن -->
                <div id="empty-state" class="hidden text-center py-20 bg-brand-grey border border-white/5 rounded-2xl">
                    <div class="w-16 h-16 bg-brand-red/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="info" class="text-brand-red" style="width:32px;height:32px;"></i>
                    </div>
                    <h2 class="font-bold text-lg mb-2">قطعه مورد نظر پیدا نشد!</h2>
                    <p class="text-gray-400 text-sm max-w-sm mx-auto mb-6">احتمالاً فیلترهای زیادی انتخاب کرده‌اید یا
                        قطعه در انبار موجود نیست.</p>
                    <button onclick="resetFilters()"
                        class="bg-brand-red hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl transition text-xs">
                        حذف فیلترها و نمایش همه
                    </button>
                </div>

                <?php if (!empty($landingPage) && !empty($landingPage['outro_html'])): ?>
                    <!-- متن تکمیلی اختصاصی لندینگ‌پیج (محتوای یکتا، نه تکراری) -->
                    <section class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-7 text-sm text-gray-300 leading-loose text-justify mt-6">
                        <?= clean_html($landingPage['outro_html']) ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="mobile-filter-overlay"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"
        onclick="toggleMobileFilters(false)"></div>

    <div id="mobile-filter-drawer" role="dialog" aria-modal="true" aria-label="فیلتر قطعات"
        class="fixed top-0 bottom-0 right-0 w-80 z-50 p-6 flex flex-col justify-between border-l border-white/10 translate-x-full transition-transform duration-300 ease-in-out hidden bg-brand-grey shadow-[0_0_50px_rgba(0,0,0,0.8)]">

        <div
            class="overflow-y-auto pr-1 space-y-6 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
            <div class="flex items-center justify-between pb-4 border-b border-black/10 dark:border-white/10 mb-2">
                <span class="font-extrabold text-base flex items-center gap-2">
                    <i data-lucide="sliders-horizontal" class="text-brand-red w-[18px] h-[18px]"></i>
                    فیلتر قطعات
                </span>
                <button
                    class="p-1 hover:bg-black/5 dark:hover:bg-white/10 rounded-lg transition opacity-60 hover:opacity-100"
                    onclick="toggleMobileFilters(false)" aria-label="بستن پنل فیلترها">
                    <i data-lucide="x" class="w-6 h-6" aria-hidden="true"></i>
                </button>
            </div>

            <div id="mobile-model-filters" class="space-y-2" role="group" aria-labelledby="mobile-model-filter-label">
                <p id="mobile-model-filter-label" class="font-bold text-sm mb-2 opacity-90">مدل خودرو</p>
                <?php
                if (!empty($car_models)) {
                    foreach ($car_models as $slug => $data) {
                        $name = is_array($data) ? $data['name'] : $data;
                        echo '<label class="flex items-center gap-2.5 text-xs opacity-80 hover:opacity-100 cursor-pointer">
                                <input type="checkbox" name="model" value="' . e($slug) . '" class="rounded accent-brand-red w-4 h-4 bg-transparent border-gray-400" onchange="syncCheckboxes(\'model\', this.value, this.checked)">
                                ' . e($name) . '
                              </label>';
                    }
                }
                ?>
            </div>

            <div id="mobile-category-filters" class="space-y-2 pt-2 border-t border-black/10 dark:border-white/10" role="group" aria-labelledby="mobile-category-filter-label">
                <p id="mobile-category-filter-label" class="font-bold text-sm mb-2 opacity-90">دسته‌بندی</p>
                <?php
                if (!empty($part_categories)) {
                    foreach ($part_categories as $slug => $data) {
                        $name = is_array($data) ? $data['name'] : $data;
                        echo '<label class="flex items-center gap-2.5 text-xs opacity-80 hover:opacity-100 cursor-pointer">
                                <input type="checkbox" name="category" value="' . e($slug) . '" class="rounded accent-brand-red w-4 h-4 bg-transparent border-gray-400" onchange="syncCheckboxes(\'category\', this.value, this.checked)">
                                ' . e($name) . '
                              </label>';
                    }
                }
                ?>
            </div>

            <div class="space-y-2 pt-4 border-t border-black/10 dark:border-white/10" role="group" aria-labelledby="mobile-price-filter-label">
                <p id="mobile-price-filter-label" class="font-bold text-sm mb-2 opacity-90">حدود قیمت (تومان)</p>
                <input type="range" id="mobile-price-slider" aria-labelledby="mobile-price-filter-label" min="0" max="300000000" step="1000000" value="300000000"
                    class="w-full h-1 bg-brand-dark rounded-lg appearance-none cursor-pointer"
                    oninput="updatePriceLabel(this.value)">
                <div class="flex justify-between items-center text-xs opacity-70 mt-2">
                    <span>از ۱۰۰,۰۰۰</span>
                    <span id="mobile-price-val" class="font-bold text-sm">تا ۳۰۰ میلیون</span>
                </div>
            </div>

            <div class="space-y-2 pt-4 border-t border-black/10 dark:border-white/10" role="group" aria-labelledby="mobile-brand-filter-label">
                <p id="mobile-brand-filter-label" class="font-bold text-sm mb-2 opacity-90">اصالت و برند کالا</p>
                <div class="space-y-2">
                    <label class="flex items-center gap-2.5 text-xs opacity-80 hover:opacity-100 cursor-pointer">
                        <input type="checkbox" name="brand" value="genuine"
                            class="rounded accent-brand-red w-4 h-4 bg-transparent border-gray-400"
                            onchange="syncCheckboxes('brand', this.value, this.checked)">
                        تویوتا جنیون پارت (اصلی)
                    </label>
                    <label class="flex items-center gap-2.5 text-xs opacity-80 hover:opacity-100 cursor-pointer">
                        <input type="checkbox" name="brand" value="oem"
                            class="rounded accent-brand-red w-4 h-4 bg-transparent border-gray-400"
                            onchange="syncCheckboxes('brand', this.value, this.checked)">
                        وارداتی معتبر OEM
                    </label>

                    <?php if (!empty($brands)): ?>
                        <div class="mt-3 border-t border-black/10 dark:border-white/10 pt-3 space-y-2">
                            <?php foreach ($brands as $brandName): ?>
                                <label class="flex items-center gap-2.5 text-xs opacity-80 hover:opacity-100 cursor-pointer">
                                    <input type="checkbox" name="brand" value="<?= e(strtolower($brandName)) ?>"
                                        class="rounded accent-brand-red w-4 h-4 bg-transparent border-gray-400"
                                        onchange="syncCheckboxes('brand', this.value, this.checked)">
                                    برند <?= e($brandName) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="pt-4 pb-12 border-t border-black/10 dark:border-white/10 flex items-center justify-between">
                <label for="mobile-in-stock-toggle" id="mobile-in-stock-toggle-label" class="text-sm opacity-90 cursor-pointer">فقط کالاهای موجود</label>
                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                    <input type="checkbox" id="mobile-in-stock-toggle" class="sr-only peer"
                        aria-labelledby="mobile-in-stock-toggle-label" onchange="toggleInStock(this.checked)">
                    <!-- پس‌زمینه سوییچ -->
                    <div
                        class="w-11 h-6 bg-gray-400/50 rounded-full peer-checked:bg-brand-red transition-colors duration-300">
                    </div>
                    <!-- دایره سوییچ -->
                    <div
                        class="absolute right-[2px] top-[2px] w-5 h-5 bg-white border border-gray-200 rounded-full shadow-md transition-transform duration-300 peer-checked:-translate-x-[20px]">
                    </div>
                </label>
            </div>
        </div>

        <!-- ۲. حذف بک‌گراند مشکی bg-[#1A1A1A] (جایگزین با bg-transparent) -->
        <div class="pt-4 border-t border-black/10 dark:border-white/10 flex gap-2 bg-transparent">
            <!-- ۳. اصلاح رنگ دکمه "حذف همه" با استفاده از border-current و opacity به جای text-white -->
            <button onclick="resetFilters(); toggleMobileFilters(false)"
                class="flex-1 border border-current opacity-60 hover:opacity-100 py-3 rounded-xl font-bold text-xs transition">حذف
                همه</button>
            <button onclick="toggleMobileFilters(false)"
                class="flex-1 bg-brand-red text-white py-3 rounded-xl font-bold text-xs transition hover:bg-red-700 shadow-md">اعمال
                فیلترها</button>
        </div>
    </div>

    <!-- ================= مودال جزئیات محصول (Product Detail Modal) ================= -->
    <div id="detail-modal-overlay"
        class="fixed inset-0 bg-black/75 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4"
        onclick="toggleDetailModal(false)">
        <div id="detail-modal" role="dialog" aria-modal="true" aria-label="جزئیات قطعه"
            class="w-full max-w-2xl bg-brand-grey rounded-2xl border border-white/10 p-6 md:p-8 relative scale-95 opacity-0 transition-all duration-300"
            onclick="event.stopPropagation()">
            <button type="button" aria-label="بستن جزئیات قطعه"
                class="absolute top-4 left-4 text-gray-400 hover:text-white p-1 hover:bg-white/5 rounded-lg transition"
                onclick="toggleDetailModal(false)">
                <i data-lucide="x" style="width:20px;height:20px;" aria-hidden="true"></i>
            </button>
            <div id="detail-modal-content">
                <!-- ساختار پویا از جاوااسکریپت -->
            </div>
        </div>
    </div>

    <?php include 'assets/php/footer.php'; ?>
    <script type="application/json" id="ssr-parts-data">
    <?= json_encode($products ?? [], JSON_UNESCAPED_UNICODE) ?>
    </script>
    <script src="/assets/js/main.min.js" defer></script>


</body>

</html>
