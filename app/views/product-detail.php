<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <!-- محتوای اصلی -->
    <main class="max-w-7xl mx-auto px-4 py-6 sm:py-12 space-y-12 sm:space-y-16">

        <!-- باکس اصلی محصول -->
        <div id="product-container"
            class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-10 shadow-[0_20px_50px_rgba(0,0,0,0.3)]">
            <div class="text-center py-20 text-gray-400">در حال بارگذاری مشخصات قطعه...</div>
        </div>

        <!-- بخش خدمات آنلاین (اصالت‌سنجی و پیگیری سفارش) -->
        <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- کارت اصالت سنجی -->
            <div class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
                <h3 class="text-base sm:text-lg font-extrabold flex items-center gap-2.5">
                    <i data-lucide="shield-check" class="text-brand-red" style="width:22px;height:22px;"></i>
                    سامانه اصالت‌سنجی هوشمند قطعات
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">کد هولوگرام یا بارکد روی جعبه قطعه تویوتا را وارد کنید
                    تا اصالت آن از انبار مرکزی امین‌زاده استعلام شود. (کدهای پیش‌فرض دیتابیس شامل واژه <span
                        class="text-brand-red font-mono">toy</span> هستند)</p>
                <div class="flex gap-2">
                    <input type="text" id="authenticity-code" placeholder="مثال: TOY-109283"
                        class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition text-center font-mono placeholder:text-gray-600">
                    <button onclick="checkAuthenticity()"
                        class="bg-brand-red hover:bg-red-700 text-white px-4 sm:px-5 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">استعلام
                        اصالت</button>
                </div>
                <div id="authenticity-result" class="hidden text-xs p-3 rounded-xl border transition-all duration-300">
                </div>
            </div>

            <!-- کارت پیگیری سفارش -->
            <div class="bg-brand-grey border border-white/10 rounded-2xl p-5 sm:p-6 space-y-4">
                <h3 class="text-base sm:text-lg font-extrabold flex items-center gap-2.5">
                    <i data-lucide="truck" class="text-brand-red" style="width:22px;height:22px;"></i>
                    پیگیری سریع وضعیت سفارش مرسوله
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">کد سفارش خود را وارد کنید تا از وضعیت فرآیند بسته‌بندی،
                    زمان تحویل به تیپاکس/باربری و کد رهگیری پست مطلع شوید.</p>
                <div class="flex gap-2">
                    <input type="text" id="tracking-code" placeholder="مثال: 403192"
                        class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition text-center placeholder:text-gray-600">
                    <button onclick="trackOrder()"
                        class="bg-white text-brand-dark hover:bg-gray-200 px-4 sm:px-5 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">پیگیری
                        قطعه</button>
                </div>
                <div id="tracking-result" class="hidden text-xs p-3 rounded-xl border transition-all duration-300">
                </div>
            </div>
        </section>

        <!-- بخش نظرات و امتیازدهی قطعه -->
        <section class="bg-brand-grey border border-white/10 rounded-2xl sm:rounded-3xl p-4 sm:p-8 space-y-8">
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-white/5 pb-6">
                <div>
                    <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                        <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                        نظرات و امتیاز کاربران
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">امتیازدهی و ثبت نظر فقط برای خریدارانی که کالا به دستشان رسیده
                        مجاز است.</p>
                </div>
                <div
                    class="flex items-center gap-3 bg-brand-dark/50 border border-white/5 px-4 py-2 rounded-xl self-stretch sm:self-auto justify-between">
                    <div class="flex text-amber-400" id="avg-stars"></div>
                    <div class="flex items-center gap-1.5">
                        <span class="font-black text-sm text-white" id="avg-rating-num">۴.۸</span>
                        <span class="text-xs text-gray-500" id="total-comments-count">(۲ نظر)</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <div
                    class="lg:col-span-5 bg-brand-dark/40 border border-white/5 p-4 sm:p-5 rounded-2xl h-fit space-y-4">
                    <h4 class="font-bold text-sm text-gray-200">ثبت نظر و امتیاز (ویژه خریداران)</h4>
                    <form id="comment-form" onsubmit="submitComment(event)" class="space-y-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-2">نام و نام خانوادگی</label>
                            <input type="text" id="comment-name" required placeholder="مثال: علی محمدی"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-brand-red transition">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-2">امتیاز شما به قطعه</label>
                            <div class="flex gap-1.5 text-gray-600 direction-ltr justify-end" id="star-picker">
                                <button type="button" onclick="setStarRating(5)" class="hover:text-amber-400 transition"
                                    data-star="5"><i data-lucide="star"
                                        style="width:20px;height:20px;fill:currentColor;"></i></button>
                                <button type="button" onclick="setStarRating(4)" class="hover:text-amber-400 transition"
                                    data-star="4"><i data-lucide="star"
                                        style="width:20px;height:20px;fill:currentColor;"></i></button>
                                <button type="button" onclick="setStarRating(3)" class="hover:text-amber-400 transition"
                                    data-star="3"><i data-lucide="star"
                                        style="width:20px;height:20px;fill:currentColor;"></i></button>
                                <button type="button" onclick="setStarRating(2)" class="hover:text-amber-400 transition"
                                    data-star="2"><i data-lucide="star"
                                        style="width:20px;height:20px;fill:currentColor;"></i></button>
                                <button type="button" onclick="setStarRating(1)" class="hover:text-amber-400 transition"
                                    data-star="1"><i data-lucide="star"
                                        style="width:20px;height:20px;fill:currentColor;"></i></button>
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
                            ثبت و تایید نظر خریدار
                        </button>
                    </form>
                </div>
                <div class="lg:col-span-7 space-y-4 max-h-[500px] overflow-y-auto pr-1" id="comments-list-container">
                </div>
            </div>
        </section>

        <!-- بخش قطعات مشابه -->
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    قطعات مشابه و پیشنهادی
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" id="similar-parts-grid"></div>
        </section>

        <!-- بخش جدیدترین قطعات -->
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    جدیدترین قطعات تویوتا
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" id="newest-parts-grid"></div>
        </section>

        <!-- بخش وبلاگ و راهنمای فنی تخصصی تویوتا -->
        <section class="space-y-6">
            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-red rounded-full"></span>
                    راهنمای فنی و وبلاگ تخصصی تویوتا
                </h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- مقاله ۱ -->
                <div onclick="window.location.href='/blog'"
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="zap" style="width:44px;height:44px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                                چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم؟</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">بررسی کامل تفاوت هولوگرام باکس
                                جنیون و آلیاژ ایریدیوم دنسو ژاپن.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۵ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1"
                                onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- مقاله ۲ -->
                <div onclick="window.location.href='/blog'"
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="wrench" style="width:44px;height:44px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                                بهترین زمان تعویض تسمه تایم تویوتا کمری و پرادو</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">علائم خرابی زنجیر تایم، صداهای
                                غیرعادی موتور در حالت سرد و کیلومتر استاندارد.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۴ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1"
                                onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- مقاله ۳ -->
                <div onclick="window.location.href='/blog'"
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div
                        class="h-40 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="droplet" style="width:44px;height:44px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-5 space-y-3 flex-1 flex flex-col justify-between">
                        <div class="space-y-2">
                            <h4
                                class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                                راهنمای جامع انتخاب و تعویض روغن گیربکس (ATF)</h4>
                            <p class="text-xs text-gray-400 leading-relaxed line-clamp-2">تفاوت روانکارهای نوع WS با
                                Type IV و تاثیر حیاتی تعویض به موقع فیلتر گیربکس.</p>
                        </div>
                        <div
                            class="flex justify-between items-center pt-3 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۶ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1"
                                onclick="window.location.href='/blog-detail?id=1'">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- مودال بزرگ‌نمایی تصاویر (Lightbox) -->
    <div id="image-zoom-modal"
        class="fixed inset-0 bg-black/90 z-50 hidden opacity-0 transition-opacity duration-300 flex items-center justify-center p-4 cursor-zoom-out"
        onclick="toggleZoomModal(false)">
        <button
            class="absolute top-4 left-4 bg-brand-grey text-white p-2 rounded-xl border border-white/10 hover:bg-brand-red transition-colors"
            onclick="toggleZoomModal(false)">
            <i data-lucide="x" style="width:24px;height:24px;"></i>
        </button>
        <div id="zoom-modal-content"
            class="max-w-full max-h-[85vh] transform scale-95 transition-transform duration-300 flex items-center justify-center text-brand-red"
            onclick="event.stopPropagation()"></div>
    </div>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>