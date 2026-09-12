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
        <header class="text-center space-y-3 py-6">
            <span class="text-brand-accent text-xs sm:text-sm font-bold tracking-widest uppercase">آموزش و مقالات
                تخصصی</span>
            <h1 class="text-2xl sm:text-4xl font-black text-white">دانشنامه و راهنمای فنی تویوتا</h1>
            <div class="w-16 h-1 bg-brand-accent mx-auto rounded-full"></div>
            <p class="text-gray-400 text-xs sm:text-sm max-w-xl mx-auto">راهنمای جامع تشخیص اصالت لوازم یدکی تویوتا،
                سرویس‌های دوره‌ای و عیب‌یابی خودرو توسط کارشناسان پرادو یدک</p>
        </header>

        <!-- باکس جستجو -->
        <section aria-label="جستجوی مقالات" class="max-w-xl mx-auto mb-8 relative">
            <i data-lucide="search" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
                style="width:20px;height:20px;"></i>
            <input type="text" id="blog-search" oninput="searchBlog()" placeholder="جستجو در عنوان یا متن مقالات..."
                class="w-full bg-brand-dark border border-white/10 rounded-xl pr-12 pl-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition">
        </section>

        <!-- فیلتر دسته‌بندی‌ها -->
        <nav aria-label="دسته‌بندی موضوعی" class="flex flex-wrap justify-center gap-2 border-b border-white/10 pb-6">
            <button type="button" onclick="filterBlog('all')"
                class="bg-brand-red text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">همه مقالات</button>
            <button type="button" onclick="filterBlog('technical')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">آموزش
                فنی</button>
            <button type="button" onclick="filterBlog('genuine')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">تشخیص
                اصالت قطعه</button>
            <button type="button" onclick="filterBlog('maintenance')"
                class="bg-brand-grey border border-white/10 text-gray-300 hover:text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">نگهداری
                خودرو</button>
        </nav>

        <!-- گرید مقالات وبلاگ -->
        <section aria-label="لیست مقالات" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="blog-grid">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $art):
                    $articleUrl = '/blog/' . urlencode($art['slug']);
                    ?>
                    <div class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between"
                        data-category="<?= e($art['category']) ?>">
                        <a href="<?= $articleUrl ?>"
                            class="h-44 bg-brand-dark flex items-center justify-center text-brand-red border-b border-white/5 relative block">
                            <i data-lucide="<?= e($art['icon'] ?: 'wrench') ?>" style="width:48px;height:48px;"
                                class="group-hover:scale-110 transition-transform"></i>
                            <span
                                class="absolute top-3 right-3 bg-brand-red/20 text-brand-red text-[10px] font-bold px-2 py-1 rounded">
                                <?= e($art['category_label']) ?>
                            </span>
                        </a>
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-2">
                                <a href="<?= $articleUrl ?>" class="block">
                                    <h3
                                        class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                                        <?= e($art['title']) ?>
                                    </h3>
                                </a>
                                <p class="text-xs text-gray-400 leading-relaxed line-clamp-3">
                                    <?= e($art['summary']) ?>
                                </p>
                            </div>
                            <div
                                class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500">
                                <span><?= e(toShamsi($art['created_at'])) ?></span>
                                <a href="<?= $articleUrl ?>"
                                    class="text-brand-red font-bold flex items-center gap-1 hover:underline">ادامه مطلب <i
                                        data-lucide="arrow-left" style="width:12px;height:12px;"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 text-center text-gray-500 text-sm">
                    هنوز مقاله‌ای در این بخش منتشر نشده است.
                </div>
            <?php endif; ?>
        </section>

    </main>

    <?php include 'assets/php/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
</body>

</html>