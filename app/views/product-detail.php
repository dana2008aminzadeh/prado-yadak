<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
    <title><?= e($product['name']) ?> | پرادو یدک</title>
    <meta name="description" content="<?= e(mb_substr($product['desc'], 0, 150)) ?>...">
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">
    <?php include 'assets/php/header.php'; ?>

    <!-- محتوای اصلی -->
    <main class="flex-1 max-w-7xl mx-auto px-4 py-6 sm:py-12 space-y-12 sm:space-y-16 w-full">

        <!-- مسیر دسترسی (Breadcrumbs) -->
        <div class="flex items-center gap-2 text-xs text-gray-400 overflow-x-auto whitespace-nowrap pb-2">
            <a href="/" class="hover:text-white transition">صفحه اصلی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <a href="/parts" class="hover:text-white transition">فروشگاه قطعات</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <a href="/parts?category=<?= e($product['category']) ?>" class="hover:text-white transition">
                <?= e($GLOBALS['part_categories'][$product['category']]['name'] ?? $product['category']) ?>
            </a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <span class="text-brand-red font-bold"><?= e($product['name']) ?></span>
        </div>

        <!-- باکس اصلی محصول -->
        <div id="product-container"
            class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- تصویر محصول و گالری -->
                <div class="md:w-5/12 flex flex-col gap-4">
                    <div id="main-product-image" onclick="toggleZoomModal(true)"
                        class="bg-brand-dark rounded-2xl p-8 border border-white/5 relative flex items-center justify-center cursor-zoom-in group h-64 sm:h-80 overflow-hidden">
                        <?php
                        $images = $product['images'] ?? [];
                        $mainImage = !empty($images) ? "/image?id=" . e($images[0]) : "assets/logo/logo.webp";
                        ?>
                        <div id="main-product-inner" class="w-full h-full flex items-center justify-center">
                            <img src="<?= $mainImage ?>" alt="<?= e($product['name']) ?>"
                                class="max-w-full max-h-full object-contain drop-shadow-2xl transition transform group-hover:scale-110 duration-300">
                        </div>

                        <div
                            class="absolute top-3 right-3 bg-brand-grey/80 border border-white/10 p-2 rounded-xl opacity-100 sm:opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                            <i data-lucide="zoom-in" style="width:16px;height:16px;" class="text-white"></i>
                        </div>

                        <?php if ($product['isGenuine']): ?>
                            <span
                                class="absolute top-4 left-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5">
                                <i data-lucide="shield-check" class="w-4 h-4"></i> جنیون پارت
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- تصاویر کوچک (Thumbnails) -->
                    <?php if (!empty($images) && count($images) > 1): ?>
                        <div class="grid grid-cols-5 gap-2 sm:gap-3">
                            <?php foreach ($images as $index => $img): ?>
                                <div onclick="changeMainImage('/image?id=<?= e($img) ?>', this)"
                                    class="thumb-btn h-16 sm:h-20 border rounded-xl flex items-center justify-center cursor-pointer transition duration-200 hover:border-brand-red/50 p-2 <?= $index === 0 ? 'border-brand-red bg-brand-dark' : 'border-white/5 bg-brand-dark/40' ?>">
                                    <img src="/image?id=<?= e($img) ?>" class="max-w-full max-h-full object-contain anim-float"
                                        alt="Thumbnail">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- اطلاعات محصول -->
                <div class="lg:col-span-7 space-y-6 w-full flex flex-col justify-center">
                    <div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span
                                class="bg-brand-red/10 text-brand-red text-xs px-3 py-1 rounded-full font-bold border border-brand-red/20">
                                <?= $product['isGenuine'] ? "اصلی جنیون پارت" : "وارداتی OEM معتبر" ?>
                            </span>
                            <span
                                class="<?= $product['inStock'] ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border-rose-500/20' ?> text-xs px-3 py-1 rounded-full font-bold border">
                                <?= $product['inStock'] ? 'موجود در انبار' : 'ناموجود' ?>
                            </span>
                        </div>
                        <h1 class="text-xl sm:text-3xl font-black text-white leading-tight mb-2">
                            <?= e($product['name']) ?>
                        </h1>
                        <p class="text-xs text-gray-400 font-mono">OEM: <?= e($product['oem'] ?? 'ندارد') ?></p>
                    </div>

                    <div class="h-px w-full bg-gradient-to-r from-brand-red via-brand-red/50 to-transparent"></div>

                    <div class="space-y-2">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-400 flex items-center gap-2">
                            <i data-lucide="file-text" style="width:16px;height:16px;"></i> بررسی تخصصی قطعه
                        </h4>
                        <p class="text-gray-300 text-sm leading-loose text-justify">
                            <?= nl2br(e($product['desc'])) ?>
                        </p>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-400 flex items-center gap-2">
                            <i data-lucide="info" style="width:16px;height:16px;"></i> مشخصات فنی
                        </h4>
                        <div
                            class="border border-white/10 rounded-xl overflow-hidden text-xs sm:text-sm bg-brand-dark/20">
                            <div class="grid grid-cols-2 p-3 border-b border-white/5">
                                <span class="text-gray-400">خودرو سازگار</span>
                                <span
                                    class="text-white font-bold"><?= e($GLOBALS['car_models'][$product['model']]['name'] ?? $product['model']) ?></span>
                            </div>
                            <div class="grid grid-cols-2 p-3 border-b border-white/5 bg-black/20">
                                <span class="text-gray-400">دسته‌بندی</span>
                                <span
                                    class="text-white"><?= e($GLOBALS['part_categories'][$product['category']]['name'] ?? $product['category']) ?></span>
                            </div>
                            <div class="grid grid-cols-2 p-3 border-b border-white/5">
                                <span class="text-gray-400">برند قطعه</span>
                                <span class="text-white"><?= e(strtoupper($product['brand'] ?? 'نامشخص')) ?></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-brand-dark/50 border border-white/5 p-4 sm:p-5 rounded-xl sm:rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-center sm:text-right w-full sm:w-auto">
                            <span class="text-[11px] text-gray-500 block mb-1">قیمت نهایی قطعه:</span>
                            <span
                                class="font-black text-2xl sm:text-3xl text-brand-red"><?= number_format($product['price']) ?>
                                <span class="text-sm font-normal text-white">تومان</span></span>
                        </div>
                        <button <?= $product['inStock'] ? '' : 'disabled' ?> onclick="addToCart(<?= $product['id'] ?>)"
                            class="w-full sm:w-auto <?= $product['inStock'] ? 'bg-brand-red hover:bg-red-700 text-white shadow-[0_5px_20px_rgba(225,6,0,0.3)]' : 'bg-white/5 text-gray-500 cursor-not-allowed' ?> font-black px-6 sm:px-8 py-3.5 rounded-xl transition flex items-center justify-center gap-3 text-sm">
                            <i data-lucide="<?= $product['inStock'] ? 'shopping-cart' : 'x-circle' ?>"
                                style="width:18px;height:18px;"></i>
                            <?= $product['inStock'] ? 'افزودن به سبد خرید' : 'ناموجود در انبار' ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- پیگیری سفارش -->
        <section class="max-w-2xl mx-auto">
            <div class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4 shadow-lg">
                <h3 class="text-base sm:text-lg font-extrabold flex items-center gap-2.5">
                    <i data-lucide="truck" class="text-brand-red" style="width:22px;height:22px;"></i>
                    پیگیری سریع وضعیت سفارش مرسوله
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">کد سفارش خود را وارد کنید تا از وضعیت فرآیند بسته‌بندی
                    و زمان تحویل مطلع شوید.</p>
                <div class="flex gap-2">
                    <input type="text" id="tracking-code" placeholder="مثال: 403192"
                        class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition text-center font-mono placeholder:text-gray-600">
                    <button onclick="trackOrder()"
                        class="bg-white text-brand-dark hover:bg-gray-200 px-4 sm:px-5 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">پیگیری
                        قطعه</button>
                </div>
                <div id="tracking-result"
                    class="hidden text-xs p-3 rounded-xl border transition-all duration-300 font-bold"></div>
            </div>
        </section>

        <!-- بخش نظرات و امتیازدهی قطعه (یکپارچه با PHP) -->
        <section class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-8 space-y-8">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span> نظرات و امتیاز کاربران
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">امتیازدهی و ثبت نظر واقعی خریداران</p>
                </div>
                <?php
                $avgRating = 0;
                $commentCount = count($comments ?? []);
                if ($commentCount > 0) {
                    $sum = 0;
                    foreach ($comments as $c)
                        $sum += $c['rating'];
                    $avgRating = round($sum / $commentCount, 1);
                }
                ?>
                <div
                    class="flex items-center gap-3 bg-brand-dark/50 border border-white/5 px-4 py-2 rounded-xl self-stretch sm:self-auto justify-between">
                    <div class="flex text-amber-400">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i data-lucide="star" style="width:16px;height:16px;"
                                class="<?= $i <= round($avgRating) ? 'fill-amber-400 text-amber-400' : 'text-gray-600' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-black text-sm text-white"><?= $avgRating > 0 ? $avgRating : '۰.۰' ?></span>
                        <span class="text-xs text-gray-500">(<?= $commentCount ?> نظر)</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                <!-- فرم ثبت نظر -->
                <div
                    class="lg:col-span-4 bg-brand-dark/40 border border-white/5 p-4 sm:p-5 rounded-2xl h-fit space-y-4 order-2 lg:order-1">
                    <h4 class="font-bold text-sm text-gray-200">ثبت نظر و امتیاز</h4>
                    <?php if ($can_comment): ?>
                        <form id="comment-form" onsubmit="submitProductComment(event)" class="space-y-4">
                            <input type="hidden" id="comment-product-id" value="<?= $product['id'] ?>">
                            <div id="comment-msg" class="hidden text-xs font-bold p-2 rounded-lg text-center"></div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">نام و نام خانوادگی</label>
                                <input type="text" id="comment-name" required placeholder="مثال: علی محمدی"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">امتیاز شما به قطعه</label>
                                <div class="flex gap-1.5 text-gray-600 direction-ltr justify-end" id="star-picker">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <button type="button" onclick="setStarRating(<?= $i ?>)"
                                            class="hover:text-amber-400 transition <?= $i == 5 ? 'text-amber-400' : '' ?>"
                                            data-star="<?= $i ?>">
                                            <i data-lucide="star" style="width:20px;height:20px;"
                                                class="<?= $i == 5 ? 'fill-current' : '' ?>"></i>
                                        </button>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-2">متن نظر شما</label>
                                <textarea id="comment-text" required rows="4"
                                    placeholder="تجربه خود را از کیفیت و تطابق قطعه بنویسید..."
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition resize-none"></textarea>
                            </div>
                            <button type="submit"
                                class="w-full bg-brand-red hover:bg-red-700 text-white font-bold py-3 rounded-xl text-xs transition shadow-[0_4px_15px_rgba(225,6,0,0.15)]">
                                ثبت و ارسال نظر
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-10 bg-black/20 rounded-xl border border-white/5">
                            <i data-lucide="lock" class="w-10 h-10 text-gray-600 mx-auto mb-3"></i>
                            <p class="text-xs text-gray-400 leading-loose px-2">ثبت نظر و امتیاز دادن، تنها برای کاربرانی
                                امکان‌پذیر است که این قطعه را خریداری کرده و تحویل گرفته‌اند.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- لیست نظرات تایید شده -->
                <div id="comments-list-container" class="lg:col-span-12 mt-8 space-y-4">
                    <?php if (empty($comments)): ?>
                        <div class="text-center py-8 text-gray-500 text-xs border border-white/5 rounded-2xl border-dashed">
                            هنوز نظری برای این قطعه ثبت نشده است. اولین خریدار باشید که نظر می‌دهد!
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <div class="bg-brand-dark/20 border border-white/5 p-4 sm:p-5 rounded-2xl space-y-3">
                                <div class="flex justify-between items-start gap-2">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-sm text-white"><?= e($c['name']) ?></span>
                                            <span
                                                class="bg-emerald-500/10 text-emerald-400 text-[9px] sm:text-[10px] px-2 py-0.5 rounded border border-emerald-500/20 flex items-center gap-1">
                                                <i data-lucide="check-circle" style="width:10px;height:10px;"></i> خریدار
                                            </span>
                                        </div>
                                        <span
                                            class="text-[10px] text-gray-500 block"><?= e(\jdf\jdate('Y/m/d', strtotime($c['created_at'] ?? 'now'))) ?? 'ثبت شده' ?></span>
                                    </div>
                                    <div class="flex gap-0.5 direction-ltr shrink-0 text-amber-400">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i data-lucide="star"
                                                style="width:14px;height:14px; <?= $i <= $c['rating'] ? 'fill:currentColor;' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-300 leading-relaxed"><?= e($c['comment_text']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- تابع کمکی رندر کارت محصولات -->
        <?php
        if (!function_exists('renderProductCardHTML')) {
            function renderProductCardHTML($p)
            {
                global $car_models;
                $img = (!empty($p['images']) && isset($p['images'][0])) ? "/image?id=" . e($p['images'][0]) : "assets/logo/logo.webp";
                $price = number_format($p['price']);
                $modelName = current(explode(' ', $car_models[$p['model']]['name'] ?? $p['model']));
                $genuineBadge = $p['isGenuine'] ? '<span class="absolute top-3 right-3 bg-emerald-500/10 text-emerald-400 text-[10px] font-bold px-2 py-1 rounded border border-emerald-500/20 shadow-sm backdrop-blur-md">جنیون پارت</span>' : '';
                $stockBtn = $p['inStock']
                    ? "<button onclick=\"addToCart({$p['id']}); event.preventDefault();\" class=\"w-10 h-10 bg-brand-dark border border-white/10 hover:border-brand-red text-gray-400 hover:text-white rounded-xl flex items-center justify-center transition shrink-0\"><i data-lucide=\"shopping-cart\" style=\"width:18px;height:18px;\"></i></button>"
                    : "<span class=\"text-[10px] text-gray-500 font-bold bg-brand-dark px-2 py-2 rounded-lg border border-white/5\">ناموجود</span>";

                return "
                <a href=\"/product?id={$p['id']}\" class=\"bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between relative shadow-lg\">
                    $genuineBadge
                    <div class=\"h-48 bg-brand-dark flex items-center justify-center p-6 border-b border-white/5 relative overflow-hidden\">
                        <img src=\"$img\" alt=\"{$p['name']}\" class=\"max-w-full max-h-full object-contain group-hover:scale-110 transition duration-500 drop-shadow-lg\">
                    </div>
                    <div class=\"p-5 flex-1 flex flex-col justify-between space-y-4\">
                        <div class=\"space-y-2\">
                            <h3 class=\"font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-2 leading-relaxed\">{$p['name']}</h3>
                            <div class=\"flex justify-between items-center\">
                                <p class=\"text-[11px] text-gray-500 font-mono\">OEM: {$p['oem']}</p>
                                <span class=\"text-[10px] text-brand-accent\"><i data-lucide=\"car\" class=\"w-3 h-3 inline\"></i> $modelName</span>
                            </div>
                        </div>
                        <div class=\"flex justify-between items-center pt-4 border-t border-white/5\">
                            <div class=\"space-y-0.5\">
                                <span class=\"block text-[10px] text-gray-500\">قیمت</span>
                                <span class=\"font-black text-sm text-white tracking-wide\">$price <span class=\"text-[10px] text-gray-500 font-normal\">تومان</span></span>
                            </div>
                            $stockBtn
                        </div>
                    </div>
                </a>";
            }
        }
        ?>

        <!-- قطعات مشابه -->
        <?php if (!empty($similar_parts)): ?>
            <section class="space-y-6">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span> قطعات مشابه و پیشنهادی
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <?php foreach ($similar_parts as $sp)
                        echo renderProductCardHTML($sp); ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- جدیدترین قطعات -->
        <?php if (!empty($newest_parts)): ?>
            <section class="space-y-6">
                <div class="flex items-center justify-between border-b border-white/10 pb-4">
                    <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span> جدیدترین قطعات تویوتا
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <?php foreach ($newest_parts as $np)
                        echo renderProductCardHTML($np); ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <!-- مودال زوم تصویر -->
    <div id="image-zoom-modal"
        class="fixed inset-0 z-[100] bg-black/90 backdrop-blur-md hidden items-center justify-center p-4 opacity-0 transition-opacity duration-300"
        onclick="toggleZoomModal(false)">
        <button class="absolute top-6 right-6 text-white bg-white/10 p-2 rounded-full hover:bg-brand-red transition"><i
                data-lucide="x"></i></button>
        <div id="zoom-modal-content"
            class="w-full max-w-4xl h-full max-h-[80vh] flex items-center justify-center scale-95 transition-transform duration-300">
            <img src="<?= $mainImage ?>" class="max-w-full max-h-full object-contain drop-shadow-2xl">
        </div>
    </div>

    <!-- یکپارچه‌سازی پایگاه داده JS برای افزودن به سبد خرید بدون ارور -->
    <?php
    $all_rendered_parts = array_merge([$product], $similar_parts ?? [], $newest_parts ?? []);
    // حذف موارد تکراری برای بهینه‌سازی
    $unique_parts = [];
    foreach ($all_rendered_parts as $p) {
        $unique_parts[$p['id']] = $p;
    }
    ?>
    <script>
        // تزریق تمام قطعات موجود در این صفحه به جاوااسکریپت برای عملکرد صحیح سبد خرید
        window.dynamicPartsDatabase = <?= json_encode(array_values($unique_parts), JSON_UNESCAPED_UNICODE) ?>;

        // تابع تغییر عکس اصلی (بهبود یافته برای عدم استفاده از innerHTML)
        function changeMainImage(imgSrc, element) {
            const mainImg = document.getElementById('main-img-display');
            const zoomModalContent = document.querySelector('#zoom-modal-content img');

            if (mainImg) mainImg.src = imgSrc;
            if (zoomModalContent) zoomModalContent.src = imgSrc;

            document.querySelectorAll('.thumb-btn').forEach(btn => {
                btn.classList.remove('border-brand-red', 'bg-brand-dark');
                btn.classList.add('border-white/5', 'bg-brand-dark/40');
            });
            if (element) {
                element.classList.remove('border-white/5', 'bg-brand-dark/40');
                element.classList.add('border-brand-red', 'bg-brand-dark');
            }
        }

        // نمایش مدال زوم عکس
        function toggleZoomModal(show) {
            const modal = document.getElementById('image-zoom-modal');
            const content = document.getElementById('zoom-modal-content');
            if (show) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    content.classList.remove('scale-95');
                }, 10);
            } else {
                modal.classList.remove('opacity-100');
                content.classList.add('scale-95');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }, 300);
            }
        }
    </script>
    <script src="assets/js/main.js"></script>
</body>

</html>