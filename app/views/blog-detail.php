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
                <img src="<?= e($coverUrl ?? $article['cover_image']) ?>"
                    alt="<?= e(trim((string) ($article['focus_keyword'] ?? '')) ?: $article['title']) ?>"
                    width="1200" height="675" fetchpriority="high"
                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div
                    class="absolute inset-0 bg-gradient-to-t from-brand-dark/80 via-transparent to-transparent pointer-events-none">
                </div>
            </div>
        <?php endif; ?>

        <!-- متن اصلی مقاله خوانده شده از دیتابیس -->
        <article
            class="prose prose-invert prose-red max-w-none text-gray-300 text-sm sm:text-base leading-loose sm:leading-loose text-justify space-y-6">
            <?php
            // بدنه مقاله در کنترلر پاک‌سازی شده و alt تمام تصاویر داخل آن تضمین شده است.
            // (fallback فقط برای موارد لود مستقیم قالب)
            echo $articleBody ?? \Core\Seo::ensureImageAlt(
                clean_html($article['content'] ?? ''),
                trim((string) ($article['focus_keyword'] ?? '')) ?: (string) $article['title']
            );
            ?>

            <!-- قطعات مرتبط با این مقاله (Silo): کارت خرید با قیمت و موجودی -->
            <?php if (!empty($relatedProducts)): ?>
                <div class="not-prose bg-brand-dark border border-white/10 rounded-2xl p-5 sm:p-6 mt-10 space-y-5">
                    <h2 class="text-white font-extrabold text-base sm:text-lg flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                        قطعات مرتبط با این مقاله
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($relatedProducts as $rp): ?>
                            <div class="bg-brand-grey border border-white/5 rounded-2xl p-3 flex gap-3 items-center hover:border-brand-red/40 transition">
                                <a href="<?= e(\Core\Seo::productUrl($rp['slug'])) ?>" class="shrink-0">
                                    <img src="<?= e($rp['image']) ?>" alt="<?= e($rp['alt'] ?: $rp['name']) ?>" loading="lazy"
                                        width="80" height="80"
                                        class="w-20 h-20 object-contain bg-brand-dark rounded-xl border border-white/5 p-1.5">
                                </a>
                                <div class="flex-1 min-w-0 space-y-1.5">
                                    <a href="<?= e(\Core\Seo::productUrl($rp['slug'])) ?>"
                                        class="block text-sm font-bold text-white hover:text-brand-red transition line-clamp-2">
                                        <?= e($rp['name']) ?>
                                    </a>
                                    <?php if (!empty($rp['oem'])): ?>
                                        <span class="block text-[10px] text-gray-500 font-mono">OEM: <?= e($rp['oem']) ?></span>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between gap-2 pt-1">
                                        <span class="text-brand-red font-black text-sm">
                                            <?= number_format($rp['price']) ?> <span class="text-[10px] font-normal text-gray-400">تومان</span>
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?= $rp['inStock'] ? 'text-emerald-500 border-emerald-500/30 bg-emerald-500/10' : 'text-rose-400 border-rose-500/30 bg-rose-500/10' ?>">
                                            <?= $rp['inStock'] ? 'موجود' : 'ناموجود' ?>
                                        </span>
                                    </div>
                                    <a href="<?= e(\Core\Seo::productUrl($rp['slug'])) ?>"
                                        class="inline-flex items-center gap-1.5 bg-brand-red hover:bg-red-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition mt-1">
                                        <i data-lucide="shopping-cart" style="width:13px;height:13px;"></i> مشاهده و خرید
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                <?php
                // آدرس اشتراک‌گذاری همیشه کانونیکال مقاله است، نه REQUEST_URI خام.
                // اینجا urlencode درست است، چون مقدارِ یک پارامتر کوئری‌استرینگ است.
                $shareUrl = $canonicalUrl ?? \Core\Seo::articleUrl($article['slug'] ?? '', true);
                ?>
                <a href="https://api.whatsapp.com/send?text=<?= urlencode($pageTitle . "\n" . $shareUrl) ?>"
                    target="_blank" rel="noopener nofollow"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-[#25D366] transition">
                    <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                </a>
                <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($article['title']) ?>"
                    target="_blank" rel="noopener nofollow"
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
                        $relUrl = \Core\Seo::articleUrl($rel['slug'] ?? '');
                        $relCover = !empty($rel['cover_image'])
                            ? \Core\Seo::imageUrl((string) $rel['cover_image'], \Core\Seo::imageSlug((string) $rel['title']))
                            : null;
                        ?>
                        <a href="<?= e($relUrl) ?>"
                            class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between">
                            <div
                                class="h-32 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative overflow-hidden">
                                <?php if ($relCover): ?>
                                    <img src="<?= e($relCover) ?>" alt="<?= e($rel['title']) ?>" width="480" height="270"
                                        loading="lazy" decoding="async"
                                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <i data-lucide="<?= e($rel['icon'] ?: 'wrench') ?>" style="width:36px;height:36px;"
                                        class="group-hover:scale-110 transition-transform"></i>
                                <?php endif; ?>
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