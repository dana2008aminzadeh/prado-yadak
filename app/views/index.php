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

                    <!-- عنوان اصلی -->
                    <h2 class="text-3xl md:text-5xl lg:text-6xl font-black leading-tight mb-5 text-[#2b170c]"
                        id="hero-title">
                        سریع‌ترین راه برای یافتن<br>
                        <span class="text-[#8b533a]">قطعات اصلی تویوتا</span>
                    </h2>

                    <p class="text-[#5c473b] text-sm md:text-base mb-8 leading-relaxed max-w-lg" id="hero-subtitle">
                        جستجوی دقیق با شماره شاسی (VIN) یا نام قطعه. تضمین ۱۰۰٪ اصالت کالا و ارسال سریع به سراسر ایران.
                    </p>

                    <!-- باکس فرم جستجوی پیشرفته -->
                    <div
                        class="bg-white/70 backdrop-blur-xl p-4 sm:p-6 rounded-3xl shadow-[0_15px_40px_rgba(43,23,12,0.08)] border border-[#a88d7c]/30">
                        <form action="/parts" method="GET" class="space-y-4">

                            <!-- ردیف انتخاب مدل و دسته‌بندی -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="relative">
                                    <select name="model"
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
                                    <select name="category"
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
                                <i data-lucide="search" class="absolute right-4 text-[#8b533a]"
                                    style="width:20px;height:20px;pointer-events:none;"></i>
                                <input type="text" name="q" placeholder="شماره فنی (VIN) یا نام قطعه..."
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
                        <a href="/parts?q=لنت+کمری"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">لنت
                            ترمز کمری</a>
                        <a href="/parts?category=consumables"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">فیلتر
                            روغن اصلی</a>
                        <a href="/parts?q=شمع+لندکروزر"
                            class="bg-white/60 border border-[#a88d7c]/30 px-3 py-1.5 rounded-full hover:bg-[#8b533a] hover:text-white hover:border-[#8b533a] transition">شمع
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
                                    <svg viewBox="0 0 200 200" class="w-24 h-24 lg:w-32 lg:h-32">
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
                <h3 class="text-3xl md:text-5xl font-black mt-3">قطعات مورد نیاز خود را پیدا کنید</h3>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($GLOBALS['part_categories'] as $slug => $cat):
                    // خواندن امن مقادیر (اگر آرایه کامل از دیتابیس آمد، آن‌ها را می‌خوانیم)
                    $catName = is_array($cat) ? ($cat['name'] ?? '') : $cat;
                    $catIcon = is_array($cat) && !empty($cat['icon_svg']) ? $cat['icon_svg'] : '<i data-lucide="box" style="width:36px;height:36px;"></i>';
                    $catDesc = is_array($cat) && !empty($cat['description']) ? $cat['description'] : '';

                    // تبدیل رشته متنی تگ‌ها (که در دیتابیس با کاما جدا شده‌اند) به آرایه
                    $catTags = [];
                    if (is_array($cat) && !empty($cat['tags'])) {
                        $catTags = explode(',', $cat['tags']);
                    }
                    ?>
                    <div onclick="window.location.href='/parts?category=<?= e($slug) ?>'"
                        class="cat-card bg-brand-grey rounded-2xl p-8 border border-white/5 cursor-pointer scroll-reveal">
                        <div class="relative z-10 flex flex-col h-full">
                            <div
                                class="cat-icon w-16 h-16 bg-brand-accent/10 rounded-xl flex items-center justify-center mb-5 text-brand-accent">
                                <?= $catIcon ?>
                            </div>

                            <h4 class="text-xl font-bold mb-2"><?= e($catName) ?></h4>

                            <!-- توضیحات (اگر در دیتابیس وجود داشت) -->
                            <?php if (!empty($catDesc)): ?>
                                <p class="text-gray-400 text-sm leading-relaxed mb-4 flex-grow">
                                    <?= e($catDesc) ?>
                                </p>
                            <?php endif; ?>

                            <!-- تگ‌ها (اگر در دیتابیس وجود داشت) -->
                            <?php if (!empty($catTags)): ?>
                                <div class="flex flex-wrap gap-2 mb-5">
                                    <?php foreach ($catTags as $tag): ?>
                                        <span class="bg-white/5 text-xs px-3 py-1 rounded-full text-gray-300">
                                            <?= e(trim($tag)) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div
                                class="flex items-center gap-2 text-brand-accent text-sm font-bold <?= empty($catTags) ? 'mt-auto' : '' ?>">
                                مشاهده قطعات <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="features" class="py-16 md:py-24 bg-black">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">چرا ما؟</span>
                <h3 class="text-3xl md:text-5xl font-black mt-3">خدمات ویژه پرادو یدک</h3>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="shield-check" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">ضمانت اصالت</h4>
                    <p class="text-gray-400 text-sm">تمامی قطعات دارای ضمانت اصالت و گارانتی معتبر</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="truck" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">ارسال سریع</h4>
                    <p class="text-gray-400 text-sm">ارسال به سراسر کشور با بسته‌بندی ایمن و مطمئن</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="tag" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">قیمت مناسب</h4>
                    <p class="text-gray-400 text-sm">بهترین قیمت بازار با تخفیف‌های ویژه عمده‌فروشی</p>
                </div>
                <div
                    class="text-center p-8 bg-brand-grey rounded-2xl border border-white/5 scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-5">
                        <i data-lucide="headphones" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">مشاوره تخصصی</h4>
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
                    <h3 class="text-2xl sm:text-3xl md:text-4xl font-black text-white">قطعات اختصاصی هر مدل</h3>
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
                        <a href="/parts?model=<?= e($slug) ?>"
                            class="group flex items-center justify-between p-4 md:p-5 bg-brand-grey rounded-2xl border border-white/5 transition-all duration-300 ease-out hover:-translate-y-1.5 hover:border-brand-accent/30 active:border-brand-accent/30 hover:shadow-[0_15px_40px_-10px_rgba(139,83,58,0.15)] active:bg-white/5 relative overflow-hidden">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-brand-accent/0 via-transparent to-brand-accent/10 opacity-0 group-hover:opacity-100 group-active:opacity-100 transition-opacity duration-300 ease-out pointer-events-none">
                            </div>
                            <div
                                class="absolute right-0 top-1/2 -translate-y-1/2 w-1 h-0 md:group-hover:h-full bg-brand-accent transition-all duration-300 ease-out rounded-l-full pointer-events-none">
                            </div>

                            <div class="flex items-center gap-3 md:gap-4 pr-1 md:pr-2 relative z-10">
                                <div
                                    class="w-12 h-12 md:w-14 md:h-14 rounded-full bg-brand-dark border border-white/10 flex items-center justify-center text-gray-500 group-hover:text-brand-accent group-active:text-brand-accent group-hover:bg-brand-dark transition-all duration-300 ease-out shrink-0">
                                    <?= $modelLogo ?>
                                </div>
                                <div class="text-right">
                                    <h4
                                        class="text-sm md:text-base font-bold text-gray-200 group-hover:text-white group-active:text-white transition-colors duration-300">
                                        <?= e($modelName) ?>
                                    </h4>
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
                <h3 class="text-3xl md:text-5xl font-black mt-3">رضایت خریداران پس از تحویل قطعه</h3>
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
                                <h4 class="font-bold text-sm text-white">محمدرضا سلیمی</h4>
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
                                <h4 class="font-bold text-sm text-white">کیوان احمدی</h4>
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
                                <h4 class="font-bold text-sm text-white">مهدی ذوالفقاری</h4>
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
                <h3 class="text-xl font-bold text-white mb-1">نیاز به راهنمایی در خرید قطعه دارید؟</h3>
                <p class="text-sm text-gray-400">کارشناسان ما آماده پاسخگویی و بررسی خودرو شما هستند.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="tel:09189998852"
                    class="bg-brand-grey border border-white/10 hover:border-white/30 text-white px-6 py-3 rounded-xl text-sm font-bold transition flex items-center gap-2 shadow-sm">
                    <i data-lucide="phone-call" class="w-4 h-4 text-brand-accent"></i> 09189998852
                </a>
                <a href="https://wa.me/989189998852" target="_blank"
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
                    <h3 class="text-2xl md:text-4xl font-black mt-2">آخرین مقالات و راهنمای فنی</h3>
                </div>
                <a href="/blog"
                    class="border border-white/10 hover:border-brand-accent px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 bg-brand-grey/40 shrink-0">
                    مشاهده همه مقالات وبلاگ <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- کارت ۱ -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-accent/30 transition duration-300 flex flex-col justify-between">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-accent border-b border-white/5 relative">
                        <i data-lucide="zap" style="width:40px;height:40px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between"
                        onclick="window.location.href='/blog-detail?id=1'">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-accent transition-colors line-clamp-1">
                                چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم?</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">بررسی جامع هولوگرام جنیون
                                پارتس، آلیاژ ایریدیوم دنسو ژاپن و کدهای حک شده برای جلوگیری از آسیب به موتور.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۵ دقیقه</span>
                            <span class="text-brand-accent font-bold flex items-center gap-1">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- کارت ۲ -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-accent/30 transition duration-300 flex flex-col justify-between">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-accent border-b border-white/5 relative">
                        <i data-lucide="wrench" style="width:40px;height:40px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between"
                        onclick="window.location.href='/blog-detail?id=1'">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-accent transition-colors line-clamp-1">
                                بهترین زمان تعویض تسمه تایم تویوتا کمری و پرادو</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">علائم خرابی زنجیر تایم، صداهای
                                غیرعادی موتور در حالت سرد و کیلومتر استاندارد تعویض کیت کامل تسمه تایم تویوتا.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۴ دقیقه</span>
                            <span class="text-brand-accent font-bold flex items-center gap-1">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- کارت ۳ -->
                <div
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-accent/30 transition duration-300 flex flex-col justify-between">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-accent border-b border-white/5 relative">
                        <i data-lucide="droplet" style="width:40px;height:40px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between"
                        onclick="window.location.href='/blog-detail?id=1'">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-accent transition-colors line-clamp-1">
                                راهنمای جامع انتخاب و تعویض روغن گیربکس (ATF)</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">تفاوت روانکارهای نوع WS با
                                Type IV و تاثیر حیاتی تعویض به موقع فیلتر بر روانی دنده‌های لندکروزر و کرولا.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۶ دقیقه</span>
                            <span class="text-brand-accent font-bold flex items-center gap-1">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-16 md:py-24 bg-brand-dark border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-14 scroll-reveal">
                <span class="text-brand-accent text-sm font-bold tracking-widest">ارتباط با ما</span>
                <h3 class="text-3xl md:text-5xl font-black mt-3">راه‌های تماس</h3>
                <div class="w-20 h-1 bg-brand-accent mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="phone" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">تلفن</h4>
                    <p class="text-gray-400" id="contact-phone"><?= e($settings['phone_number'] ?? '09189998852') ?></p>
                </div>
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="map-pin" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">آدرس</h4>
                    <p class="text-gray-400" id="contact-address">
                        <?= e($settings['address'] ?? 'استان کردستان سقز جاده کانی جژنی صنوف آلاینده-2 پلاک 350 فروشگاه پرادو یدک<br>کد پستی: 6681898204') ?>
                    </p>
                </div>
                <div
                    class="bg-brand-grey rounded-2xl p-8 border border-white/5 text-center scroll-reveal hover:border-brand-accent/30 transition">
                    <div class="w-14 h-14 bg-brand-accent/10 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="clock" style="width:28px;height:28px;color:#8b533a;"></i>
                    </div>
                    <h4 class="font-bold text-lg mb-2">ساعات کاری</h4>
                    <p class="text-gray-400">
                        <?= e($settings['work_hours'] ?? 'شنبه تا پنجشنبه ۹ صبح تا ۶ عصر') ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>