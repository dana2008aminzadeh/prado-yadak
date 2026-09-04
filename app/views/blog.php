<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
 
<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <!-- بدنه اصلی وبلاگ -->
    <main class="max-w-7xl mx-auto px-4 py-8 space-y-12">

        <!-- هیرو وبلاگ -->
        <div class="text-center space-y-3 py-6">
            <span class="text-brand-accent text-xs sm:text-sm font-bold tracking-widest uppercase">آموزش و مقالات
                تخصصی</span>
            <h2 class="text-2xl sm:text-4xl font-black text-white">دانشنامه و راهنمای فنی تویوتا</h2>
            <div class="w-16 h-1 bg-brand-accent mx-auto rounded-full"></div>
            <p class="text-gray-400 text-xs sm:text-sm max-w-xl mx-auto">راهنمای جامع تشخیص اصالت لوازم یدکی تویوتا،
                سرویس‌های دوره‌ای و عیب‌یابی خودرو توسط کارشناسان پرادو یدک</p>
        </div>

        <!-- باکس جستجو -->
        <div class="max-w-xl mx-auto mb-8 relative">
            <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
                style="width:20px;height:20px;"></i>
            <input type="text" id="blog-search" oninput="searchBlog()" placeholder="جستجو در عنوان یا متن مقالات..."
                class="w-full bg-brand-dark border border-white/10 rounded-xl pr-12 pl-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition">
        </div>

        <!-- فیلتر دسته‌بندی‌ها -->
        <div class="flex flex-wrap justify-center gap-2 border-b border-white/10 pb-6">
            <button onclick="filterBlog('all')"
                class="bg-brand-red text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">همه مقالات</button>
            <button onclick="filterBlog('technical')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">آموزش
                فنی</button>
            <button onclick="filterBlog('genuine')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">تشخیص
                اصالت قطعه</button>
            <button onclick="filterBlog('maintenance')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">نگهداری
                خودرو</button>
        </div>

        <!-- گرید مقالات وبلاگ -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="blog-grid">
            <!-- مقاله ۱ -->
            <div onclick="window.location.href='/blog-detail?id=1'"
                class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between"
                data-category="genuine">
                <div
                    class="h-44 bg-brand-dark flex items-center justify-center text-brand-red border-b border-white/5 relative">
                    <i data-lucide="zap" style="width:48px;height:48px;"
                        class="group-hover:scale-110 transition-transform"></i>
                    <span
                        class="absolute top-3 right-3 bg-brand-red/20 text-brand-red text-[10px] font-bold px-2 py-1 rounded">تشخیص
                        اصالت</span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <h3
                            class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                            چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم؟</h3>
                        <p class="text-xs text-gray-400 leading-relaxed line-clamp-3">بررسی کامل تفاوت هولوگرام باکس
                            جنیون، نوع آلیاژ ایریدیوم دنسو ژاپن و کدهای حک شده روی سرامیک شمع‌های اصلی تویوتا برای
                            جلوگیری از آسیب به موتور.</p>
                    </div>
                    <div
                        class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500">
                        <span>۱۵ خرداد ۱۴۰۵</span>
                        <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                    </div>
                </div>
            </div>

            <!-- مقاله ۲ -->
            <div onclick="window.location.href='/blog-detail?id=1'"
                class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between"
                data-category="technical">
                <div
                    class="h-44 bg-brand-dark flex items-center justify-center text-brand-red border-b border-white/5 relative">
                    <i data-lucide="wrench" style="width:44px;height:44px;"
                        class="group-hover:scale-110 transition-transform"></i>
                    <span
                        class="absolute top-3 right-3 bg-brand-red/20 text-brand-red text-[10px] font-bold px-2 py-1 rounded">آموزش
                        فنی</span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <h3
                            class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                            بهترین زمان تعویض تسمه تایم تویوتا کمری و پرادو</h3>
                        <p class="text-xs text-gray-400 leading-relaxed line-clamp-3">علائم خرابی زنجیر تایم، صداهای
                            غیرعادی موتور در حالت سرد و کیلومتر استاندارد تعویض کیت کامل تسمه تایم تویوتا به همراه بررسی
                            خسارت‌های پاره شدن آن.</p>
                    </div>
                    <div
                        class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500">
                        <span>۰۲ خرداد ۱۴۰۵</span>
                        <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                    </div>
                </div>
            </div>

            <!-- مقاله ۳ -->
            <div onclick="window.location.href='/blog-detail?id=1'"
                class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between"
                data-category="maintenance">
                <div
                    class="h-44 bg-brand-dark flex items-center justify-center text-brand-red border-b border-white/5 relative">
                    <i data-lucide="droplet" style="width:44px;height:44px;"
                        class="group-hover:scale-110 transition-transform"></i>
                    <span
                        class="absolute top-3 right-3 bg-brand-red/20 text-brand-red text-[10px] font-bold px-2 py-1 rounded">نگهداری
                        خودرو</span>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                    <div class="space-y-2">
                        <h3
                            class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                            راهنمای جامع انتخاب و تعویض روغن گیربکس (ATF)</h3>
                        <p class="text-xs text-gray-400 leading-relaxed line-clamp-3">تفاوت روغن گیربکس‌های نوع WS با
                            Type IV و تاثیر حیاتی تعویض به موقع فیلتر داخلی گیربکس بر طول عمر دنده‌های لندکروزر، هایلوکس
                            و کرولا.</p>
                    </div>
                    <div
                        class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500">
                        <span>۲۰ اردیبهشت ۱۴۰۵</span>
                        <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>