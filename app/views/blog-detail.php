<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased">

    <?php include 'assets/php/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 sm:py-12 space-y-8">

        <!-- مسیر ناوبری -->
        <div class="flex items-center gap-2 text-xs text-gray-400 mb-4 overflow-x-auto whitespace-nowrap pb-2">
            <a href="/" class="hover:text-brand-accent transition">صفحه اصلی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <a href="/blog" class="hover:text-brand-accent transition">وبلاگ فنی</a>
            <i data-lucide="chevron-left" style="width:12px;height:12px;"></i>
            <span class="text-brand-red">چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم؟</span>
        </div>

        <!-- هدر مقاله -->
        <header class="space-y-6">
            <div class="flex flex-wrap items-center gap-3">
                <span
                    class="bg-[#eae0d6] text-[#2b170c] text-xs font-bold px-3 py-1 rounded-full border border-[#a88d7c]/50">تشخیص
                    اصالت</span>
                <span
                    class="bg-brand-grey border border-white/10 text-gray-300 text-xs font-bold px-3 py-1 rounded-full">قطعات
                    موتور</span>
            </div>

            <h1 class="text-2xl sm:text-4xl font-black text-white leading-snug lg:leading-tight">
                چگونه شمع اصلی تویوتا را از تقلبی تشخیص دهیم؟ راهنمای جامع
            </h1>

            <div class="flex flex-wrap items-center gap-4 sm:gap-6 text-xs text-gray-400 border-b border-white/10 pb-6">
                <span class="flex items-center gap-1.5"><i data-lucide="calendar" style="width:14px;height:14px;"></i>
                    ۱۵ خرداد ۱۴۰۵</span>
                <span class="flex items-center gap-1.5"><i data-lucide="clock" style="width:14px;height:14px;"></i> زمان
                    مطالعه: ۵ دقیقه</span>
                <span class="flex items-center gap-1.5"><i data-lucide="user" style="width:14px;height:14px;"></i> تیم
                    فنی امین‌زاده</span>
            </div>
        </header>

        <!-- عکس کاور مقاله (اصلاح شده) -->
        <div
            class="w-full aspect-video sm:h-[450px] bg-brand-grey rounded-3xl overflow-hidden relative shadow-2xl border border-white/10 group">
            <!-- می‌توانید آدرس عکس واقعی مقاله را در src قرار دهید -->
            <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?q=80&w=2000&auto=format&fit=crop"
                alt="کاور مقاله"
                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            <!-- گرادیانت ملایم برای زیبایی -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-brand-dark/80 via-transparent to-transparent pointer-events-none">
            </div>
        </div>

        <!-- متن اصلی مقاله -->
        <article
            class="prose prose-invert prose-red max-w-none text-gray-300 text-sm sm:text-base leading-loose sm:leading-loose text-justify space-y-6">
            <p>
                استفاده از شمع موتور تقلبی یکی از اصلی‌ترین دلایل افت توان، افزایش مصرف سوخت و در نهایت آسیب‌های جدی به
                موتور خودروهای تویوتا (به خصوص در مدل‌های لندکروزر، کمری و پرادو) است. امروزه قطعات تقلبی (Fakes) به
                قدری شبیه به قطعات اصلی ساخته می‌شوند که تشخیص آن‌ها برای افراد عادی و حتی گاهی مکانیک‌ها دشوار است. در
                این مقاله به صورت گام‌به‌گام نحوه تشخیص شمع جنیون پارت تویوتا (Toyota Genuine Parts) که معمولاً توسط
                برند <b>DENSO</b> ژاپن تولید می‌شود را بررسی می‌کنیم.
            </p>

            <h2 class="text-xl sm:text-2xl font-bold text-white mt-10 mb-4 flex items-center gap-2">
                <i data-lucide="check-circle" class="text-brand-red" style="width:20px;height:20px;"></i>
                ۱. بررسی بسته‌بندی و هولوگرام تویوتا
            </h2>
            <p>
                اولین سد دفاعی شما در برابر قطعات تقلبی، کارتن و بسته‌بندی آن است. شمع‌های اصلی تویوتا دارای هولوگرام
                سه‌بعدی با کیفیت بالا هستند. در زوایای مختلف نوری، باید تغییر رنگ و عمق لوگوی تویوتا را به وضوح مشاهده
                کنید. جعبه‌های تقلبی معمولاً از مقوای نازک‌تر ساخته شده‌اند و رنگ چاپ قرمز آن‌ها کمی کدر یا متمایل به
                نارنجی است.
            </p>

            <!-- ============================================== -->
            <!-- جایگاه قرارگیری عکس بین متن (اضافه شده) -->
            <!-- ============================================== -->
            <figure class="my-8">
                <img src="https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?q=80&w=1000&auto=format&fit=crop"
                    alt="نمونه هولوگرام"
                    class="w-full rounded-2xl shadow-lg border border-white/10 object-cover aspect-video sm:aspect-[21/9]">
                <figcaption class="text-center text-xs text-gray-500 mt-3 font-medium">تصویر شماره ۱: تفاوت هولوگرام
                    اصلی و تقلبی روی جعبه</figcaption>
            </figure>

            <div
                class="bg-brand-grey border border-white/10 p-5 sm:p-6 rounded-2xl border-r-4 border-r-brand-red my-8 flex gap-4 items-start">
                <i data-lucide="alert-triangle" class="text-brand-red flex-shrink-0"
                    style="width:24px;height:24px;"></i>
                <div>
                    <strong class="text-white block mb-1">نکته بسیار مهم:</strong>
                    <p class="text-sm text-gray-400 m-0">
                        همیشه دقت کنید که لیبل روی جعبه باید حاوی بارکد و شماره فنی دقیق (OEM Part Number) باشد. در صورت
                        امکان، این کد را در سایت‌های معتبر یا سامانه اصالت‌سنجی امین‌زاده بررسی کنید.
                    </p>
                </div>
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-white mt-10 mb-4 flex items-center gap-2">
                <i data-lucide="check-circle" class="text-brand-red" style="width:20px;height:20px;"></i>
                ۲. کیفیت چاپ روی سرامیک شمع
            </h2>
            <p>
                نوشته‌های روی بدنه سرامیکی شمع‌های اصلی DENSO (که برای تویوتا تولید می‌شوند)، با استفاده از روش پخت
                کوره‌ای چاپ شده‌اند. این نوشته‌ها به هیچ وجه با خراشیدن توسط ناخن پاک نمی‌شوند. در نمونه‌های تقلبی، رنگ
                چاپ کدر است و با کمی کشیدن دست یا حلال‌ها به راحتی مخدوش می‌شود.
            </p>

            <!-- ============================================== -->
            <!-- جایگاه قرارگیری ویدیو آموزشی (اضافه شده) -->
            <!-- ============================================== -->
            <div class="my-10 bg-brand-grey/50 p-4 rounded-3xl border border-white/5">
                <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2 px-2">
                    <i data-lucide="play-circle" class="text-brand-red" style="width:22px;height:22px;"></i>
                    ویدیوی آموزشی: بررسی فیزیکی الکترود شمع
                </h3>
                <div
                    class="relative w-full aspect-video rounded-2xl overflow-hidden border border-white/10 shadow-lg bg-black">
                    <!-- اگر از آپارات استفاده می‌کنید، کد iframe را اینجا قرار دهید -->
                    <!-- به عنوان مثال پلیر استاندارد HTML5: -->
                    <video controls class="w-full h-full object-cover">
                        <source src="آدرس-ویدیو-شما.mp4" type="video/mp4">
                        مرورگر شما از پخش ویدیو پشتیبانی نمی‌کند.
                    </video>
                </div>
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-white mt-10 mb-4 flex items-center gap-2">
                <i data-lucide="check-circle" class="text-brand-red" style="width:20px;height:20px;"></i>
                ۳. الکترود مرکزی و جوش ایریدیوم
            </h2>
            <p>
                الکترود مرکزی در شمع‌های سوزنی تویوتا بسیار ظریف (حدود 0.4 تا 0.6 میلی‌متر) است و جوش لیزری آن در زیر
                ذره‌بین کاملاً بی‌نقص، دایره‌ای و تمیز به نظر می‌رسد. در مدل‌های فیک، این جوش نامنظم و ضخامت سوزن بیشتر
                است که باعث احتراق ناقص در دور موتورهای بالا می‌شود.
            </p>

            <div class="bg-brand-dark border border-white/10 p-6 rounded-2xl text-center mt-10 space-y-4">
                <h4 class="text-white font-bold text-lg">نیاز به شمع اصلی برای خودروی خود دارید؟</h4>
                <p class="text-xs sm:text-sm text-gray-400">تمامی شمع‌های تویوتا در فروشگاه امین‌زاده با ضمانت کتبی
                    اصالت کالا و بازگشت وجه تقدیم شما می‌گردد.</p>
                <a href="/product?id=2"
                    class="inline-flex items-center gap-2 bg-brand-red hover:bg-red-700 text-white font-bold px-6 py-3 rounded-xl transition text-sm">
                    <i data-lucide="shopping-cart" style="width:16px;height:16px;"></i> مشاهده و خرید شمع اصلی
                </a>
            </div>
        </article>

        <!-- بخش اشتراک‌گذاری و تگ‌ها (کپی لینک اضافه شد) -->
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 pt-8 border-t border-white/10">
            <div class="flex flex-wrap gap-2">
                <span
                    class="bg-brand-grey text-gray-400 hover:text-white cursor-pointer px-3 py-1.5 rounded-lg text-xs transition border border-white/5 hover:border-brand-red/30">#تویوتا</span>
                <span
                    class="bg-brand-grey text-gray-400 hover:text-white cursor-pointer px-3 py-1.5 rounded-lg text-xs transition border border-white/5 hover:border-brand-red/30">#شمع_ایریدیوم</span>
                <span
                    class="bg-brand-grey text-gray-400 hover:text-white cursor-pointer px-3 py-1.5 rounded-lg text-xs transition border border-white/5 hover:border-brand-red/30">#قطعات_تقلبی</span>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs text-gray-500 font-bold">اشتراک‌گذاری:</span>

                <!-- دکمه کپی لینک -->
                <button onclick="copyArticleLink()"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-brand-red transition relative group"
                    title="کپی لینک مقاله">
                    <i data-lucide="link" style="width:14px;height:14px;"></i>
                    <span id="copy-toast"
                        class="absolute -top-10 bg-white text-brand-dark font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">لینک
                        کپی شد!</span>
                </button>

                <a href="whatsapp://send?text=چگونه شمع اصلی تویوتا را تشخیص دهیم؟ - <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-[#25D366] transition">
                    <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                </a>

                <a href="https://t.me/share/url?url=<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>"
                    target="_blank"
                    class="w-8 h-8 bg-brand-grey rounded-full flex items-center justify-center border border-white/10 hover:text-brand-red hover:bg-[#229ED9] transition">
                    <i data-lucide="send" style="width:14px;height:14px;"></i>
                </a>
            </div>
        </div>

        <!-- مقالات پیشنهادی -->
        <section class="pt-12">
            <div class="flex items-center justify-between border-b border-white/10 pb-4 mb-6">
                <h3 class="text-lg sm:text-xl font-extrabold flex items-center gap-2.5">
                    <span class="w-2 h-6 bg-brand-accent rounded-full"></span> مقالات پیشنهادی
                </h3>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- پیشنهاد ۱ -->
                <div onclick="window.location.href='/blog-detail'"
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div
                        class="h-32 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="wrench" style="width:36px;height:36px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                        <h4
                            class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                            بهترین زمان تعویض تسمه تایم تویوتا</h4>
                        <div
                            class="flex justify-between items-center pt-2 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۴ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
                <!-- پیشنهاد ۲ -->
                <div onclick="window.location.href='/blog-detail'"
                    class="bg-brand-grey border border-white/5 rounded-2xl overflow-hidden group hover:border-brand-red/30 transition duration-300 flex flex-col justify-between cursor-pointer">
                    <div
                        class="h-32 bg-brand-dark flex items-center justify-center p-6 text-brand-red border-b border-white/5 relative">
                        <i data-lucide="droplet" style="width:36px;height:36px;"
                            class="group-hover:scale-110 transition-transform"></i>
                    </div>
                    <div class="p-4 space-y-2 flex-1 flex flex-col justify-between">
                        <h4
                            class="font-bold text-sm text-white group-hover:text-brand-red transition-colors line-clamp-1">
                            راهنمای انتخاب روغن گیربکس (ATF)</h4>
                        <div
                            class="flex justify-between items-center pt-2 border-t border-white/5 text-[10px] text-gray-500 mt-2">
                            <span>زمان مطالعه: ۶ دقیقه</span>
                            <span class="text-brand-red font-bold flex items-center gap-1">ادامه مطلب <i
                                    data-lucide="arrow-left" style="width:12px;height:12px;"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

    <!-- اسکریپت مخصوص کپی کردن لینک -->
    <script>
        function copyArticleLink() {
            // دریافت آدرس فعلی صفحه
            const currentUrl = window.location.href;

            // کپی در کلیپ بورد
            navigator.clipboard.writeText(currentUrl).then(() => {
                // نمایش پیام "کپی شد"
                const toast = document.getElementById('copy-toast');
                toast.classList.remove('opacity-0');
                toast.classList.add('opacity-100');

                // پنهان کردن پیام بعد از 2 ثانیه
                setTimeout(() => {
                    toast.classList.remove('opacity-100');
                    toast.classList.add('opacity-0');
                }, 2000);
            }).catch(err => {
                console.error('خطا در کپی لینک:', err);
            });
        }
    </script>

</body>

</html>