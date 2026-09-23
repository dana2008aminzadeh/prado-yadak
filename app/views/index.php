<?php
global $settings;
?>
<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <main id="main-content">
    <section id="hero" class="relative overflow-hidden text-[#2b170c]"
        style="background: linear-gradient(135deg, #f5efe9 0%, #eae0d6 50%, #f5efe9 100%);">

        <!-- هاله‌های رنگی پس‌زمینه (تم قهوه‌ای/مسی) -->
        <div class="absolute -top-20 -right-20 w-96 h-96 rounded-full bg-[#815c4d]/20 pointer-events-none blur-3xl">
        </div>
        <div class="absolute -bottom-20 -left-20 w-96 h-96 rounded-full bg-[#2b170c]/15 pointer-events-none blur-3xl">
        </div>

        <!-- الگوی شبکه پس‌زمینه -->
        <div
            class="absolute inset-0 opacity-10 pointer-events-none bg-[repeating-linear-gradient(45deg,transparent,transparent_35px,rgba(43,23,12,0.08)_35px,rgba(43,23,12,0.08)_36px)]">
        </div>

        <div class="max-w-7xl mx-auto px-4 py-16 md:py-28 relative z-10">
            <div class="grid lg:grid-cols-12 gap-12 items-center">

                <!-- ستون راست: متن و باکس جستجو (بزرگتر) -->
                <div class="lg:col-span-7 anim-fade-up">
                    <!-- بج نمایندگی -->
                    <div
                        class="inline-flex items-center gap-2 bg-[#eae0d6] border border-[#a88d7c]/50 rounded-full px-4 py-1.5 text-[#2b170c] text-xs font-bold mb-6 shadow-sm">
                        <span class="w-2.5 h-2.5 bg-[#8b533a] rounded-full animate-pulse"></span>
                        <span>نمایندگی رسمی قطعات تویوتا</span>
                    </div>

                    <h1 class="text-3xl md:text-5xl lg:text-6xl font-black leading-tight mb-5 text-[#2b170c]"
                        id="hero-title">
                        سریع‌ترین راه برای یافتن<br>
                        <span class="text-[#8b533a]">قطعات اصلی تویوتا</span>
                    </h1>

                    <p class="text-[#5c473b] text-sm md:text-base mb-8 leading-relaxed max-w-lg" id="hero-subtitle">
                        جستجوی دقیق با شماره شاسی (VIN) یا نام قطعه. تضمین ۱۰۰٪ اصالت کالا و ارسال سریع به سراسر ایران.
                    </p>

                    <!-- باکس فرم جستجوی پیشرفته -->
                    <div
                        class="bg-white/70 backdrop-blur-xl p-4 sm:p-6 rounded-3xl shadow-[0_15px_40px_rgba(43,23,12,0.08)] border border-[#a88d7c]/30">
                        <form action="/parts" method="GET" class="space-y-4" role="search" aria-label="جستجوی قطعات تویوتا">

                            <!-- ردیف انتخاب مدل و دسته‌بندی -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="relative">
                                    <label for="home-search-model" class="sr-only">مدل خودرو</label>
                                    <select id="home-search-model" name="model"
                                        class="w-full bg-[#f8f6f0] border border-[#a88d7c]/40 text-[#2b170c] text-sm rounded-2xl px-4 py-3.5 appearance-none focus:outline-none focus:border-[#8b533a] transition cursor-pointer font-bold">
                                        <option value="">انتخاب مدل (همه)</option>
                                        <?php
                                        global $car_models;
                                        if (!empty($car_models)) {
                                            foreach ($car_models as $key => $data) {
                                                $name = is_array($data) ? $data['name'] : $data;
                                                echo "<option value=\"" . e($key) . "\">" . e($name) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <i data-lucide="chevron-down"
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8b533a]"
                                        style="width:18px;height:18px;pointer-events:none;"></i>
                                </div>
                                <div class="relative">
                                    <label for="home-search-category" class="sr-only">دسته‌بندی قطعه</label>
                                    <select id="home-search-category" name="category"
                                        class="w-full bg-[#f8f6f0] border border-[#a88d7c]/40 text-[#2b170c] text-sm rounded-2xl px-4 py-3.5 appearance-none focus:outline-none focus:border-[#8b533a] transition cursor-pointer font-bold">
                                        <option value="">همه دسته‌بندی‌ها (All)</option>
                                        <?php
                                        global $part_categories;
                                        if (!empty($part_categories)) {
                                            foreach ($part_categories as $key => $data) {
                                                // بررسی می‌کنیم که خروجی آرایه است یا متن ساده
                                                $name = is_array($data) ? $data['name'] : $data;
                                                echo "<option value=\"" . e($key) . "\">" . e($name) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <i data-lucide="chevron-down"
                                        class="absolute left-4 top-1/2 -translate-y-1/2 text-[#8b533a]"
                                        style="width:18px;height:18px;pointer-events:none;"></i>
                                </div>
                            </div>

                            <!-- ردیف فیلد جستجو و دکمه -->
                            <div class="relative flex items-center">
                                <i data-lucide="search" class="absolute right-4 text-[#8b533a]" aria-hidden="true"
                                    style="width:20px;height:20px;pointer-events:none;"></i>
                                <label for="home-search-query" class="sr-only">نام قطعه، شماره فنی یا شماره شاسی</label>
                                <input id="home-search-query" type="search" name="q" placeholder="شماره فنی (VIN) یا نام قطعه..."
                                    class="w-full bg-[#f8f6f0] border border-[#a88d7c]/40 text-[#2b170c] text-sm rounded-2xl pr-12 pl-[110px] py-4 focus:outline-none focus:border-[#8b533a] transition placeholder:text-[#815c4d]">
                                <button type="submit"
                                    class="absolute left-1.5 top-1.5 bottom-1.5 bg-brand-red hover:bg-red-700 text-white font-bold px-6 rounded-xl text-sm transition shadow-md flex items-center gap-2">
                                    جستجو
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- تگ‌های جستجوی سریع زیر فرم -->
                    <div class="flex flex-wrap items-center gap-2 mt-5 text-xs text-[#5c473b]">
                        <i data-lucide="trending-up" style="width:16px;height:16px;" class="text-[#8b533a]"></i>
                        <span class="font-bold ml-1">پرجستجوها:</span>
                        <a href="<?= e(\Core\Seo::modelUrl('camry')) ?>"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">قطعات
                            تویوتا کمری</a>
                        <a href="<?= e(\Core\Seo::categoryUrl('consumables')) ?>"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">فیلتر
                            روغن اصلی</a>
                        <a href="<?= e(\Core\Seo::modelUrl('landcruiser')) ?>"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">قطعات
                            لندکروزر</a>
                    </div>

                </div>

                <!-- ستون چپ: گرافیک (کوچکتر شده برای جا دادن فرم جستجو) -->
                <div class="hidden lg:flex lg:col-span-5 justify-center anim-slide-in" style="animation-delay:0.3s;">
                    <div class="relative mt-8">
                        <div
                            class="w-72 h-72 lg:w-[380px] lg:h-[380px] rounded-full border-2 border-[#a88d7c]/40 flex items-center justify-center anim-float">
                            <div
                                class="w-56 h-56 lg:w-[280px] lg:h-[280px] rounded-full border border-[#a88d7c]/60 flex items-center justify-center">

                                <!-- دایره مرکزی و لوگو -->
                                <div
                                    class="w-40 h-40 lg:w-48 lg:h-48 bg-gradient-to-br from-[#eae0d6] via-[#d6c3b3] to-[#a88d7c]/40 rounded-full flex items-center justify-center shadow-inner relative z-10">
                                    <svg viewBox="0 0 200 200" class="w-24 h-24 lg:w-32 lg:h-32" aria-hidden="true" focusable="false">
                                        <ellipse cx="100" cy="100" rx="90" ry="50" fill="none" stroke="#8b533a"
                                            stroke-width="4" />
                                        <ellipse cx="100" cy="100" rx="55" ry="30" fill="none" stroke="#8b533a"
                                            stroke-width="3" />
                                        <ellipse cx="100" cy="100" rx="20" ry="90" fill="none" stroke="#8b533a"
                                            stroke-width="3" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- کارت شناور ۱ -->
                        <div class="absolute top-8 -right-4 bg-white/95 border border-[#d8c7ba] text-[#2b170c] rounded-2xl px-4 py-2 text-xs font-bold shadow-lg anim-fade-up flex flex-col items-center gap-1"
                            style="animation-delay:0.8s;">
                            <i data-lucide="check-circle" class="text-emerald-600" style="width:20px;height:20px;"></i>
                            <span>تطابق با شاسی</span>
                        </div>

                        <!-- کارت شناور ۲ -->
                        <div class="absolute bottom-12 -left-6 bg-white/95 border border-[#d8c7ba] text-[#2b170c] rounded-2xl px-5 py-3 text-xs font-bold shadow-lg anim-fade-up flex items-center gap-3"
                            style="animation-delay:1s;">
                            <div class="bg-[#f5efe9] p-2 rounded-xl">
                                <i data-lucide="package" class="text-[#8b533a]" style="width:24px;height:24px;"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[#2b170c] font-black text-sm">+۱۵,۰۰۰</span>
                                <span class="text-[#5c473b] text-[10px]">قطعه موجود در انبار</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <!-- خط پایینی تزئینی -->
        <div class="h-1 bg-[#2b170c] w-full anim-wipe"></div>
    </section>

    <section class="bg-black py-10 border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div class="anim-count" style="animation-delay:0.1s;">
                <div class="text-3xl md:text-4xl font-black text-brand-accent">+۱۵</div>
                <div class="text-gray-400 text-sm mt-1">سال تجربه</div>
            </div>
            <div class="anim-count" style="animation-delay:0.2s;">
                <div class="text-3xl md:text-4xl font-black text-brand-accent">+۱۵K</div>
                <div class="text-gray-400 text-sm mt-1">قطعه موجود</div>
            </div>
            <div class="anim-count" style="animation-delay:0.3s;">
                <div class="text-3xl md:text-4xl font-black text-brand-accent">+۸K</div>
                <div class="text-gray-400 text-sm mt-1">مشتری راضی</div>
            </div>
            <div class="anim-count" style="animation-delay:0.4s;">
                <div class="text-3xl md:text-4xl font-black text-brand-accent">۲۴/۷</div>
                <div class="text-gray-400 text-sm mt-1">پشتیبانی</div>
            </div>
        </div>
    </section>

    <section id="categories" class="py-16 md:py-24 bg-brand-dark">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">دسته‌بندی قطعات</span>
                <h2 class="text-3xl md:text-5xl font-black mt-3">قطعات مورد نیاز خود را پیدا کنید</h2>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($GLOBALS['part_categories'] as $slug => $cat):
                    $catName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                    $catIcon = is_array($cat) && !empty($cat['icon_svg']) ? $cat['icon_svg'] : '<i data-lucide="box" style="width:36px;height:36px;"></i>';
                    $catDesc = is_array($cat) && !empty($cat['description']) ? $cat['description'] : '';
                    $catTags = [];
                    if (is_array($cat) && !empty($cat['tags'])) {
                        $catTags = explode(',', $cat['tags']);
                    }
                ?>
                <a href="<?= e(\Core\Seo::categoryUrl((string) $slug)) ?>"
                class="cat-card bg-brand-grey rounded-2xl p-8 border border-white/5 block scroll-reveal group">
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="cat-icon w-16 h-16 bg-brand-accent/10 rounded-xl flex items-center justify-center mb-5 text-brand-accent" aria-hidden="true">
                            <?= $catIcon ?>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-white"><?= e($catName) ?></h3>

                        <?php if (!empty($catDesc)): ?>
                            <p class="text-gray-400 text-sm leading-relaxed mb-4 flex-grow"><?= e($catDesc) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($catTags)): ?>
                            <div class="flex flex-wrap gap-2 mb-5">
                                <?php foreach ($catTags as $tag): ?>
                                    <span class="bg-white/5 text-xs px-3 py-1 rounded-full text-gray-300"><?= e(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-2 text-brand-accent text-sm font-bold <?= empty($catTags) ? 'mt-auto' : '' ?>">
                            مشاهده قطعات <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- اسلایدر جدیدترین قطعات (نسخه ارتقایافته UX موبایل) -->
    <section id="digikala-latest" class="pt-1 pb-6 sm:pt-4 sm:pb-10 bg-brand-dark overflow-hidden select-none">
        <div class="max-w-7xl mx-auto px-4">
            
            <div class="relative rounded-3xl p-3 sm:p-6 shadow-[0_15px_35px_rgba(43,23,12,0.06)] border border-[#a88d7c]/35 overflow-hidden"
                style="background: linear-gradient(135deg, #f5efe9 0%, #eae0d6 50%, #f5efe9 100%);">

                <!-- در موبایل: هدر افقی کم‌جا | در دسکتاپ: بنر عمودی سمت راست -->
                <div class="flex flex-col lg:flex-row items-stretch gap-3 sm:gap-6 relative z-10">
                    
                    <!-- باکس هدر (در موبایل کاملاً جمع‌وجور و افقی می‌شود) -->
                    <div class="shrink-0 flex flex-row lg:flex-col justify-between items-center text-right lg:text-center p-3 sm:p-5 rounded-2xl bg-white/70 border border-[#a88d7c]/30 backdrop-blur-sm lg:w-52">
                        <div class="space-y-1 lg:space-y-2.5">
                            <div class="inline-flex items-center gap-1.5 bg-[#eae0d6] border border-[#a88d7c]/50 rounded-full px-2.5 py-0.5 text-[#2b170c] text-[10px] sm:text-[11px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full bg-brand-red animate-pulse"></span>
                                <span>ورودی‌های جدید</span>
                            </div>
                            
                            <h2 class="text-sm sm:text-2xl font-black text-[#2b170c] leading-tight">
                                جدیدترین قطعات انبار
                            </h2>
                            
                            <p class="hidden lg:block text-[#5c473b] text-xs leading-relaxed max-w-[170px] mx-auto font-medium">
                                تضمین ۱۰۰٪ اصالت جنیون با تطابق شماره شاسی (VIN)
                            </p>
                        </div>

                        <a href="/parts"
                        class="bg-brand-red hover:bg-red-700 text-white font-bold py-2 px-3.5 sm:py-2.5 sm:px-4 rounded-xl text-[11px] sm:text-xs transition flex items-center gap-1 shadow-sm shrink-0 lg:w-full lg:mt-4 justify-center">
                            <span>مشاهده همه</span>
                            <i data-lucide="chevron-left" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                        </a>
                    </div>

                    <!-- ریل متحرک افقی کارت‌ها (نمایش ۲ کارت کامل + لبه کارت سوم جهت راهنمایی اسکرول) -->
                    <div class="flex-1 min-w-0 relative">
                        <div id="latest-slider-track" 
                            class="flex gap-2.5 sm:gap-4 overflow-x-auto scroll-smooth py-1 px-0.5 scrollbar-none snap-x snap-mandatory h-full"
                            style="scrollbar-width: none; -ms-overflow-style: none;">
                            
                            <?php if (!empty($latestProducts)): ?>
                                <?php foreach ($latestProducts as $part): 
                                    $safeSlug = rawurlencode((string) $part['slug']);
                                    $imgSrc = (string) ($part['image_url'] ?? '');
                                    $imgAlt = (string) ($part['image_alt'] ?? $part['name']);
                                    $modelData = $GLOBALS['car_models'][$part['model']] ?? $part['model'];
                                    $modelName = is_array($modelData) ? ($modelData['name'] ?? $part['model']) : $modelData;
                                    $productUrl = "/product/" . $safeSlug;
                                ?>
                                <!-- داده‌ساختاریافته این کارت‌ها فقط از طریق بلوک واحد
                                     JSON-LD (@graph → ItemList) در HomeController ارسال می‌شود.
                                     microdata روی کارت‌ها عمداً حذف شده تا گوگل دو نسخه‌ی
                                     متناقض از Product/Offer دریافت نکند. -->
                                <article
                                        class="slider-card shrink-0 w-[44%] sm:w-[240px] snap-start bg-white border border-[#a88d7c]/25 rounded-2xl p-2.5 sm:p-4 flex flex-col justify-between shadow-sm hover:shadow-md transition-all duration-300 relative group">
                                    
                                    <div>
                                        <!-- نشان‌های وضعیت -->
                                        <div class="flex items-center justify-between gap-1 mb-2">
                                            <?php if ($part['isGenuine']): ?>
                                                <span class="bg-brand-red/10 text-brand-red text-[8px] sm:text-[10px] px-1.5 py-0.5 rounded font-bold border border-brand-red/20 truncate">
                                                    اصلی Genuine
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-[#eae0d6] text-[#5c473b] text-[8px] sm:text-[10px] px-1.5 py-0.5 rounded font-bold border border-[#a88d7c]/30 truncate">
                                                    وارداتی OEM
                                                </span>
                                            <?php endif; ?>

                                            <?php if ($part['inStock']): ?>
                                                <span class="text-emerald-600 text-[8px] sm:text-[10px] font-bold flex items-center gap-0.5 shrink-0">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> موجود
                                                </span>
                                            <?php else: ?>
                                                <span class="text-rose-500 text-[8px] sm:text-[10px] font-bold shrink-0">ناموجود</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- قاب تصویر محصول با مکانیزم جلوگیری از شکستگی تصویر -->
                                        <a href="<?= $productUrl ?>" 
                                        class="w-full h-24 sm:h-36 bg-[#f8f6f0] rounded-xl flex items-center justify-center p-1.5 sm:p-3 mb-2 sm:mb-3 relative overflow-hidden border border-[#a88d7c]/20 block group"
                                        title="<?= e($part['name']) ?>">
                                            <?php if ($imgSrc !== ''): ?>
                                                <img src="<?= e($imgSrc) ?>"
                                                    alt="<?= e($imgAlt) ?>"
                                                    loading="lazy" decoding="async"
                                                    class="max-w-full max-h-full object-contain transition-transform duration-500 group-hover:scale-105">
                                            <?php else: ?>
                                                <span class="text-[#8b533a]/50 flex flex-col items-center gap-1" role="img" aria-label="تصویر محصول ثبت نشده است">
                                                    <i data-lucide="image-off" class="w-8 h-8" aria-hidden="true"></i>
                                                    <small class="text-[8px]">بدون تصویر</small>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($part['oem'])): ?>
                                                <span class="absolute bottom-1 left-1 text-[7px] sm:text-[9px] font-mono text-[#5c473b] bg-white/95 px-1 py-0.5 rounded border border-[#a88d7c]/30 shadow-2xs" dir="ltr">
                                                    <?= e($part['oem']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>

                                        <!-- عنوان قطعه -->
                                        <a href="<?= $productUrl ?>" class="block">
                                            <h3
                                                class="font-bold text-[10px] sm:text-xs text-[#2b170c] leading-snug line-clamp-2 hover:text-brand-red transition-colors min-h-[28px] sm:min-h-[34px]">
                                                <?= e($part['name']) ?>
                                            </h3>
                                        </a>

                                        <p class="text-[9px] sm:text-[11px] text-[#5c473b] mt-1 flex items-center gap-1">
                                            <i data-lucide="car" class="w-3 h-3 text-[#8b533a] shrink-0"></i>
                                            <span class="truncate"><?= e($modelName) ?></span>
                                        </p>
                                    </div>

                                    <!-- بخش قیمت و دکمه خرید -->
                                    <div
                                        class="mt-2.5 pt-2 sm:pt-3 border-t border-[#a88d7c]/15 flex items-center justify-between gap-1">
                                        <div class="min-w-0">
                                            <span class="text-[8px] text-[#5c473b] block leading-none mb-0.5">قیمت:</span>
                                            <span class="font-black text-[10px] sm:text-xs text-brand-red truncate block">
                                                <?= number_format($part['price']) ?> <span class="text-[8px] sm:text-[9px] text-[#5c473b] font-normal">تومان</span>
                                            </span>
                                        </div>

                                        <?php if ($part['inStock']): ?>
                                            <button type="button"
                                                    onclick="addToCart(<?= (int) $part['id'] ?>); event.preventDefault();"
                                                    aria-label="افزودن به سبد خرید"
                                                    class="p-1.5 sm:p-2.5 rounded-xl transition bg-brand-red hover:bg-red-700 text-white shadow-2xs active:scale-90 shrink-0">
                                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    disabled
                                                    aria-label="ناموجود"
                                                    class="p-1.5 sm:p-2.5 rounded-xl transition bg-[#f0ebe1] text-gray-400 cursor-not-allowed shrink-0">
                                                <i data-lucide="shopping-cart" class="w-3.5 h-3.5 sm:w-4 sm:h-4"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </article>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <!-- کارت انتها: مشاهده کل کاتالوگ -->
                            <div class="slider-card shrink-0 w-28 sm:w-40 snap-start bg-white/70 border border-dashed border-[#a88d7c]/40 rounded-2xl p-2.5 flex flex-col items-center justify-center text-center group hover:border-brand-red transition">
                                <a href="/parts" class="flex flex-col items-center gap-1.5">
                                    <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-[#eae0d6] border border-[#a88d7c]/40 text-[#8b533a] flex items-center justify-center group-hover:scale-110 group-hover:bg-brand-red group-hover:text-white transition-all duration-300">
                                        <i data-lucide="arrow-left" class="w-4 h-4 sm:w-5 sm:h-5"></i>
                                    </div>
                                    <span class="text-[10px] sm:text-xs font-bold text-[#2b170c] group-hover:text-brand-red transition">مشاهده همه</span>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-16 md:py-24 bg-black">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">چرا ما؟</span>
                <h2 class="text-3xl md:text-5xl font-black mt-3">خدمات ویژه پرادو یدک</h2>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="shield-check" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">ضمانت اصالت</h3>
                    <p class="text-gray-400 text-sm">تمامی قطعات دارای ضمانت اصالت و گارانتی معتبر</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="truck" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">ارسال سریع</h3>
                    <p class="text-gray-400 text-sm">ارسال به سراسر کشور با بسته‌بندی ایمن و مطمئن</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="tag" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">قیمت مناسب</h3>
                    <p class="text-gray-400 text-sm">بهترین قیمت بازار با تخفیف‌های ویژه عمده‌فروشی</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="headphones" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">مشاوره تخصصی</h3>
                    <p class="text-gray-400 text-sm">مشاوره رایگان توسط کارشناسان مجرب قطعات تویوتا</p>
                </div>
            </div>
        </div>
    </section>

    <section id="brands" class="py-16 md:py-24 bg-brand-dark relative border-t border-white/5">
        <!-- افکت نوری پس‌زمینه -->
        <div
            class="absolute top-0 right-0 w-full h-full bg-[radial-gradient(circle_at_top_right,rgba(139,83,58,0.05),transparent_60%)] pointer-events-none">
        </div>

        <div class="max-w-7xl mx-auto px-4 relative z-10">

            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-end mb-8 md:mb-12 gap-5 md:gap-6">
                <div class="space-y-2 md:space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 md:w-12 h-[2px] bg-brand-accent"></div>
                        <span
                            class="text-brand-accent text-xs sm:text-sm font-bold tracking-widest uppercase font-mono">Toyota
                            Models</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-black text-white">قطعات اختصاصی هر مدل</h2>
                    <p class="text-gray-400 text-xs sm:text-sm max-w-md leading-relaxed">
                        با انتخاب مدل خودروی خود، به کاتالوگ دقیق قطعات سازگار و استاندارد دسترسی پیدا کنید.
                    </p>
                </div>
                <a href="/parts"
                    class="text-xs sm:text-sm font-bold hover:text-white active:text-white flex items-center gap-2 transition-colors border border-white/10 px-4 py-2.5 rounded-xl hover:border-white/30 active:border-white/30 bg-brand-grey/50 active:bg-white/10 w-full md:w-auto justify-center md:justify-start">
                    مشاهده همه قطعات <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                </a>
            </div>

            <!-- گرید کارت‌های افقی -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 md:gap-5">
                <?php
                $count = 0;
                if (!empty($GLOBALS['car_models'])) {
                    foreach ($GLOBALS['car_models'] as $slug => $model):
                        if ($count >= 3)
                            break;

                        $modelName = is_array($model) ? $model['name'] : $model;
                        $modelLogo = is_array($model) && !empty($model['logo_svg']) ? $model['logo_svg'] : '<i data-lucide="car" style="width:24px;height:24px;"></i>';
                        ?>
                        <a href="<?= e(\Core\Seo::modelUrl((string) $slug)) ?>"
                            class="group flex items-center justify-between p-4 md:p-5 bg-brand-grey rounded-2xl border border-white/5 transition-all duration-300 ease-out hover:-translate-y-1.5 hover:border-brand-accent/30 active:border-brand-accent/30 hover:shadow-[0_15px_40px_-10px_rgba(139,83,58,0.15)] active:bg-white/5 relative overflow-hidden">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-brand-accent/0 via-transparent to-brand-accent/10 opacity-0 group-hover:opacity-100 group-active:opacity-100 transition-opacity duration-300 ease-out pointer-events-none">
                            </div>
                            <div
                                class="absolute right-0 inset-y-0 w-1 bg-brand-accent scale-y-0 md:group-hover:scale-y-100 transition-transform duration-300 ease-out rounded-l-full origin-center pointer-events-none">
                            </div>

                            <div class="flex items-center gap-3 md:gap-4 pr-1 md:pr-2 relative z-10">
                                <div
                                    class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-brand-dark border border-white/10 flex items-center justify-center text-gray-500 group-hover:text-brand-accent group-active:text-brand-accent group-hover:bg-brand-dark transition-all duration-300 ease-out shrink-0" aria-hidden="true">
                                    <?= $modelLogo ?>
                                </div>
                                <div class="text-right">
                                    <h3
                                        class="text-sm md:text-base font-bold text-gray-200 group-hover:text-white group-active:text-white transition-colors duration-300">
                                        <?= e($modelName) ?>
                                    </h3>
                                    <span
                                        class="text-[9px] md:text-[10px] text-gray-500 uppercase tracking-widest mt-0.5 block font-mono group-hover:text-brand-accent/80 transition-colors duration-300">Toyota
                                        <?= e(ucwords(str_replace('-', ' ', $slug))) ?></span>
                                </div>
                            </div>

                            <div
                                class="relative z-10 w-7 h-7 md:w-8 md:h-8 rounded-full border border-white/10 flex items-center justify-center text-gray-600 group-hover:bg-brand-accent group-active:bg-brand-accent group-hover:text-white group-active:text-white group-hover:border-brand-accent transition-all duration-300 ease-out shrink-0">
                                <i data-lucide="chevron-left" style="width:16px;height:16px;" class="w-4 h-4 md:w-4 md:h-4"></i>
                            </div>
                        </a>
                        <?php
                        $count++;
                    endforeach;
                }
                ?>
            </div>
        </div>
    </section>

    <!-- بخش نظرات خریداران پس از تحویل کالا -->
    <section id="testimonials" class="py-16 md:py-24 bg-black border-t border-b border-white/5">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">صدای مشتریان</span>
                <h2 class="text-3xl md:text-5xl font-black mt-3">رضایت خریداران پس از تحویل قطعه</h2>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
                <p class="text-xs text-gray-500 mt-3 max-w-md mx-auto">نظرات واقعی مشتریانی که قطعات تویوتا را از
                    فروشگاه پرادو یدک تحویل گرفته و روی خودروی خود نصب کرده‌اند.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- کارت نظر اول -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl p-6 relative flex flex-col justify-between scroll-reveal hover:border-brand-accent/20 transition duration-300">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-sm text-white">محمدرضا سلیمی</h3>
                                <span class="text-[10px] text-gray-500 block mt-0.5">خریدار لنت ترمز کمری</span>
                            </div>
                            <div
                                class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold flex items-center gap-1 border border-emerald-500/10">
                                <i data-lucide="check" style="width:12px;height:12px;"></i> قطعه تحویل شد
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 leading-relaxed">"ساکن شیراز هستم. لنت ترمز جلو اصلی سفارش دادم.
                            کالا ۲ روزه با اتوبوس رسید دستم. بعد از باز کردن جعبه و چک کردن هولوگرام خیالم کاملاً از
                            اصالتش راحت شد. روی ماشین فوق‌العاده نرم کار میکنه."</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/5 flex justify-between items-center">
                        <div class="flex text-amber-400 gap-0.5">
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                        </div>
                        <span class="text-[10px] text-gray-500">ارسال از طریق ترمینال</span>
                    </div>
                </div>

                <!-- کارت نظر دوم -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl p-6 relative flex flex-col justify-between scroll-reveal hover:border-brand-accent/20 transition duration-300">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-sm text-white">کیوان احمدی</h3>
                                <span class="text-[10px] text-gray-500 block mt-0.5">خریدار شمع ایریدیوم لندکروزر</span>
                            </div>
                            <div
                                class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold flex items-center gap-1 border border-emerald-500/10">
                                <i data-lucide="check" style="width:12px;height:12px;"></i> قطعه تحویل شد
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 leading-relaxed">"برای لندکروزر ۸ سیلندر یک دست شمع سوزنی دنسو
                            خریدم. تطابق کالا رو با شماره شاسی زحمت کشیدن خودشون چک کردن که اشتباه نشه. بعد از نصب موتور
                            لرزشش کاملاً قطع شده و شتاب عالی شده."</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/5 flex justify-between items-center">
                        <div class="flex text-amber-400 gap-0.5">
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                        </div>
                        <span class="text-[10px] text-gray-500">ارسال با تیپاکس</span>
                    </div>
                </div>

                <!-- کارت نظر سوم -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl p-6 relative flex flex-col justify-between scroll-reveal hover:border-brand-accent/20 transition duration-300">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="font-bold text-sm text-white">مهدی ذوالفقاری</h3>
                                <span class="text-[10px] text-gray-500 block mt-0.5">خریدار کمک‌فنر جلو کرولا</span>
                            </div>
                            <div
                                class="bg-emerald-500/10 text-emerald-400 text-[10px] px-2 py-1 rounded-md font-bold flex items-center gap-1 border border-emerald-500/10">
                                <i data-lucide="check" style="width:12px;height:12px;"></i> قطعه تحویل شد
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 leading-relaxed">"ممنون از مشاوره تخصصی و صبوری کادر فروشگاه
                            پرادو یدک. کمک فنر KYB ژاپنی خریدم، قیمت بازار رو کامل گرفته بودم، قیمت ایشون از همه جا
                            منصفانه‌تر بود و اصالتش هم که حرف نداره."</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/5 flex justify-between items-center">
                        <div class="flex text-amber-400 gap-0.5">
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;fill:currentColor;"></i>
                            <i data-lucide="star" style="width:14px;height:14px;"></i>
                        </div>
                        <span class="text-[10px] text-gray-500">تحویل حضوری در فروشگاه</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-12 border-t border-white/5 bg-brand-dark/50">
        <div class="max-w-7xl mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-center md:text-right">
                <h2 class="text-xl font-bold text-white mb-1">نیاز به راهنمایی در خرید قطعه دارید؟</h2>
                <p class="text-sm text-gray-400">کارشناسان ما آماده پاسخگویی و بررسی خودرو شما هستند.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="tel:09189998852"
                    class="bg-brand-grey border border-white/10 hover:border-white/30 text-white px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm">
                    <i data-lucide="phone-call" class="w-4 h-4 text-brand-accent"></i> 09189998852
                </a>
                <a href="https://wa.me/989189998852" target="_blank" rel="noopener"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm">
                    <i data-lucide="message-circle" class="w-4 h-4"></i> پشتیبانی واتساپ
                </a>
            </div>
        </div>
    </section>

    <!-- بخش وبلاگ و دانشنامه فنی در صفحه اصلی -->
    <section id="home-blog" class="py-16 md:py-24 bg-brand-dark border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-12 gap-4">
                <div>
                    <span class="text-brand-accent text-sm font-bold tracking-widest">آموزش تخصصی</span>
                    <h2 class="text-2xl md:text-4xl font-black mt-2">آخرین مقالات و راهنمای فنی</h2>
                </div>
                <a href="/blog"
                    class="border border-white/10 hover:border-brand-accent px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-brand-grey/40 shrink-0">
                    مشاهده همه مقالات وبلاگ <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php if (!empty($latestArticles)): ?>
                    <?php foreach ($latestArticles as $art):
                        $artUrl = \Core\Seo::articleUrl($art['slug'] ?? '');
                        $artCover = !empty($art['cover_image'])
                            ? \Core\Seo::imageUrl((string) $art['cover_image'], \Core\Seo::imageSlug((string) $art['title']))
                            : null;
                        ?>
                        <article
                            class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-accent/30 transition duration-300 flex flex-col justify-between">
                            <a href="<?= e($artUrl) ?>" aria-label="مشاهده مقاله <?= e($art['title']) ?>"
                                class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-accent border-b border-white/5 relative block overflow-hidden">
                                <?php if ($artCover): ?>
                                    <img src="<?= e($artCover) ?>" alt="<?= e($art['title']) ?>" width="640" height="360"
                                        loading="lazy" decoding="async"
                                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <i data-lucide="<?= e($art['icon'] ?: 'wrench') ?>" style="width:40px;height:40px;"
                                        class="group-hover:scale-110 transition-transform"></i>
                                <?php endif; ?>
                            </a>
                            <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                                <div class="space-y-2">
                                    <a href="<?= e($artUrl) ?>" class="block">
                                        <h3
                                            class="font-bold text-sm text-white group-hover:text-brand-accent transition-colors line-clamp-1">
                                            <?= e($art['title']) ?>
                                        </h3>
                                    </a>
                                    <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">
                                        <?= e($art['summary']) ?>
                                    </p>
                                </div>
                                <div
                                    class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                                    <span>زمان مطالعه: <?= (int) $art['reading_time'] ?> دقیقه</span>
                                    <a href="<?= e($artUrl) ?>" class="text-brand-accent font-bold flex items-center gap-1">
                                        ادامه مطلب <i data-lucide="arrow-left" style="width:12px;height:12px;"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <section id="contact" class="py-16 md:py-24 bg-brand-dark border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">ارتباط با ما</span>
                <h2 class="text-3xl md:text-5xl font-black mt-3">راه‌های تماس</h2>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="phone" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">تلفن</h3>
                    <p class="text-gray-400" id="contact-phone"><?= e($settings['phone_number'] ?? '09189998852') ?></p>
                </div>
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="map-pin" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">آدرس</h3>
                    <p class="text-gray-400" id="contact-address">
                        <?= e($settings['address'] ?? 'استان کردستان سقز جاده کانی جژنی صنوف آلاینده-2 پلاک 350 فروشگاه پرادو یدک<br>کد پستی: 6681898204') ?>
                    </p>
                </div>
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="clock" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">ساعات کاری</h3>
                    <p class="text-gray-400">
                        <?= e($settings['work_hours'] ?? 'شنبه تا پنجشنبه ۹ صبح تا ۶ عصر') ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- بخش محتوای سئو (SEO Text) صفحه اصلی -->
    <section class="py-12 bg-black border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4">
            <div class="bg-brand-dark/50 border border-white/10 rounded-3xl p-6 sm:p-10">
                <h2 class="text-lg sm:text-xl font-black text-white mb-4">خرید لوازم یدکی و قطعات استوک تویوتا</h2>
                <div class="text-xs sm:text-sm text-gray-400 leading-loose text-justify space-y-4">
                    <p>
                        مجموعه <strong>پرادو یدک</strong> به عنوان یکی از معتبرترین مراجع تامین قطعات خودروهای تویوتا و
                        لکسوس، افتخار دارد کامل‌ترین سبد محصولات شامل <strong>لوازم استوک تویوتا</strong> و
                        <strong>قطعات نو</strong> را به مشتریان عزیز ارائه دهد. ما با درک دغدغه‌های صاحبان خودرو، تلاش
                        کرده‌ایم تا بهترین <strong>قیمت قطعات اصلی جنیون (Toyota Genuine Parts)</strong> را با تضمین
                        ۱۰۰٪ اصالت کالا و تطابق دقیق با شماره شاسی (VIN) فراهم کنیم.
                    </p>
                    <p>
                        یکی از سوالات متداول خریداران، <strong>تفاوت قطعات OEM و اورجینال</strong> است. قطعات اورجینال
                        دقیقاً همان قطعاتی هستند که در کارخانه روی خودرو نصب می‌شوند و با بسته‌بندی رسمی کمپانی تویوتا
                        عرضه می‌گردند. اما قطعات OEM (تولیدکننده تجهیزات اصلی) توسط همان کارخانه‌هایی (مانند آیسین، دنسو
                        و کایابا) تولید می‌شوند که تامین‌کننده رسمی تویوتا هستند، با این تفاوت که در بسته‌بندی شرکت
                        سازنده قطعه و با قیمت اقتصادی‌تری به فروش می‌رسند. در فروشگاه ما، هر دو نوع قطعه با شفافیت کامل
                        و فاکتور رسمی به فروش می‌رسد.
                    </p>
                </div>
            </div>
        </div>
    </section>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>
