<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">
    <?php include 'assets/php/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 sm:py-12 space-y-8">
        <!-- مسیر ناوبری -->
        <nav aria-label="مسیر صفحه"
            class="flex items-center gap-2 text-xs text-gray-400 mb-4 overflow-x-auto whitespace-nowrap pb-2">
            <a href="/" class="hover:text-brand-accent transition">صفحه اصلی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <a href="/blog" class="hover:text-brand-accent transition">وبلاگ فنی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <span class="text-brand-red"><?= e($article['title']) ?></span>
        </nav>

        <!-- هدر مقاله -->
        <header class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <span
                    class="bg-[#eae0d6] text-[#2b170c] text-xs font-bold px-3 py-1 rounded-full border border-[#a88d7c]/50">
                    <?= e($article['category_label']) ?>
                </span>
                <span
                    class="bg-brand-grey border border-white/10 text-gray-300 text-xs font-bold px-3 py-1 rounded-full">
                    تویوتا جنیون
                </span>
            </div>
            <h1 class="text-2xl sm:text-4xl font-black text-white leading-snug lg:leading-tight">
                <?= e($article['title']) ?>
            </h1>
            <div class="flex flex-wrap items-center gap-4 sm:gap-6 text-xs text-gray-400 border-b border-white/10 pb-6">
                <span class="flex items-center gap-1.5"><i data-lucide="calendar" style="width:14px;height:14px;"></i>
                    <?= e(toShamsi($article['created_at'])) ?></span>
                <span class="flex items-center gap-1.5"><i data-lucide="clock" style="width:14px;height:14px;"></i> زمان
                    مطالعه: <?= (int) $article['reading_time'] ?> دقیقه</span>
                <span class="flex items-center gap-1.5"><i data-lucide="user" style="width:14px;height:14px;"></i>
                    <?= e($article['author']) ?></span>
            </div>
        </header>

        <!-- عکس کاور مقاله -->
        <?php if (!empty($article['cover_image'])): ?>
            <div
                class="w-full aspect-video sm:h-[450px] bg-brand-grey rounded-3xl overflow-hidden relative shadow-2xl border border-white/10 group">
                <img src="<?= e($article['cover_image']) ?>" alt="<?= e($article['title']) ?>"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-t from-brand-dark/80 via-transparent to-transparent pointer-events-none">
                </div>
            </div>
        <?php endif; ?>

        <!-- متن اصلی مقاله خوانده شده از دیتابیس -->
        <article
            class="prose prose-invert prose-red max-w-none text-gray-300 text-sm sm:text-base leading-loose sm:leading-loose text-justify space-y-6">
            <?= clean_html($article['content']) ?>

            <div class="bg-brand-dark border border-white/10 p-6 rounded-2xl text-center mt-10 space-y-4">
                <h3 class="text-white font-bold text-lg">نیاز به راهنمایی در انتخاب قطعات اصلی دارید؟</h3>
                <p class="text-xs sm:text-sm text-gray-400">تمامی قطعات تویوتا در فروشگاه با ضمانت کتبی اصالت کالا و
                    بازگشت وجه تقدیم شما می‌گردد.</p>
                <a href="/parts"
                    class="inline-flex items-center gap-2 bg-brand-red hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
                    <i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> کاتالوگ و استعلام آنلاین قطعات
                </a>
            </div>
        </article>

        <!-- بخش اشتراک‌گذاری و تگ‌ها -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8 border-t border-white/10">
            <div class="flex flex-wrap gap-2">
                <span
                    class="bg-brand-grey text-gray-400 px-3 py-1.5 rounded-lg text-xs border border-white/5">#تویوتا</span>
                <span
                    class="bg-brand-grey text-gray-400 px-3 py-1.5 rounded-lg text-xs border border-white/5">#قطعات_اصلی</span>
                <span
                    class="bg-brand-grey text-gray-400 px-3 py-1.5 rounded-lg text-xs border border-white/5">#ضمانت_اصالت</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-bold">اشتراک‌گذاری:</span>
                <button onclick="copyArticleLink()"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-brand-red transition relative group"
                    title="کپی لینک مقاله">
                    <i data-lucide="link" style="width:14px;height:14px;"></i>
                    <span id="copy-toast"
                        class="absolute -top-10 bg-white text-brand-dark font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">لینک
                        کپی شد!</span>
                </button>
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($pageTitle . "\n" . 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']) ?>"
                    target="_blank"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-[#25D366] transition">
                    <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                </a>
                <a href="https://t.me/share/url?url=<?= urlencode('https://' . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($article['title']) ?>"
                    target="_blank"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-[#229ED9] transition">
                    <i data-lucide="send" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </div>

        <!-- مقالات پیشنهادی و مرتبط -->
        <?php if (!empty($relatedArticles)): ?>
            <section class="pt-12">
                <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-6">
                    <h2 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-accent rounded-full"></span> مقالات پیشنهادی
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <?php foreach ($relatedArticles as $rel):
                        $relUrl = '/blog/' . urlencode($rel['slug']);
                        ?>
                        <a href="<?= $relUrl ?>"
                            class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between">
                            <div
                                class="h-32 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                                <i data-lucide="<?= e($rel['icon'] ?: 'wrench') ?>" style="width:36px;height:36px;"
                                    class="group-hover:scale-110 transition-transform"></i>
                            </div>
                            <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                                <h3
                                    class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                                    <?= e($rel['title']) ?>
                                </h3>
                                <div
                                    class="flex justify-between items-center pt-2 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                                    <span>زمان مطالعه: <?= (int) $rel['reading_time'] ?> دقیقه</span>
                                    <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                            data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php include 'assets/php/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
</body>

</html>