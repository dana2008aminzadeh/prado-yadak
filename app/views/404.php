<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">

    <?php include 'assets/php/header.php'; ?>

    <!-- بدنه اصلی 404 -->
    <main
        class="flex-1 max-w-5xl mx-auto px-4 py-12 sm:py-20 flex flex-col items-center justify-center text-center relative z-10">

        <!-- پس‌زمینه گرافیکی تزیینی -->
        <div class="absolute inset-0 -z-10 flex items-center justify-center pointer-events-none opacity-20">
            <div class="w-96 h-96 bg-brand-red/20 rounded-full blur-3xl"></div>
        </div>

        <!-- عدد ۴۰۴ با گرافیک خودرو و علامت هشدار -->
        <div class="relative mb-8 select-none">
            <div
                class="text-[100px] sm:text-[180px] font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-white via-gray-300 to-gray-700 leading-none">
                404
            </div>
            <div class="absolute inset-0 flex items-center justify-center">
                <div
                    class="w-20 h-20 sm:w-28 sm:h-28 bg-brand-red/90 rounded-2xl border-2 border-white/30 flex items-center justify-center shadow-[0_0_50px_rgba(225,6,0,0.6)] anim-float backdrop-blur-md">
                    <i data-lucide="alert-triangle" style="width:48px;height:48px;"
                        class="text-white sm:w-14 sm:h-14"></i>
                </div>
            </div>
        </div>

        <!-- عناوین و توضیحات -->
        <div class="space-y-4 max-w-2xl mx-auto mb-10 anim-fade-up">
            <span
                class="inline-block bg-brand-red/10 border border-brand-red/30 text-brand-red text-xs font-bold px-4 py-1.5 rounded-full">
                خطای عدم دسترسی (Page Not Found)
            </span>
            <h2 class="text-2xl sm:text-4xl font-black text-white">صفحه مورد نظر پیدا نشد!</h2>
            <div class="w-16 h-1 bg-brand-red mx-auto rounded-full"></div>
            <p class="text-gray-400 text-xs sm:text-base leading-relaxed">
                آدرس وارد شده اشتباه است، صفحه منتقل شده یا قطعه مورد نظر از سیستم خارج شده است.<br
                    class="hidden sm:block">
                می‌توانید از کادر زیر نام قطعه را جستجو کنید یا به صفحه اصلی بازگردید.
            </p>
        </div>

        <!-- باکس جستجوی سریع قطعات در صفحه 404 -->
        <form action="/parts" method="GET" class="w-full max-w-lg mb-10 relative">
            <div class="relative flex items-center">
                <i data-lucide="search" class="absolute right-4 text-gray-400" style="width:20px;height:20px;"></i>
                <input type="text" name="q" placeholder="جستجوی مستقیم نام قطعه یا شماره فنی (مثلا: لنت کمری)..."
                    class="w-full bg-brand-grey border border-white/10 rounded-2xl pr-12 pl-28 py-4 text-xs sm:text-sm text-white focus:outline-none focus:border-brand-red shadow-[0_10px_30px_rgba(0,0,0,0.5)] transition">
                <button type="submit"
                    class="absolute left-2 bg-brand-red hover:bg-red-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition flex items-center gap-1.5">
                    جستجو
                </button>
            </div>
        </form>

        <!-- دکمه‌های اقدام سریع -->
        <div class="flex flex-wrap justify-center gap-3 sm:gap-4 mb-16">
            <a href="/"
                class="bg-brand-red hover:bg-red-700 text-white font-bold px-6 py-3.5 rounded-xl transition flex items-center gap-2 text-xs sm:text-sm shadow-[0_4px_20px_rgba(225,6,0,0.3)]">
                <i data-lucide="home" style="width:18px;height:18px;"></i> صفحه اصلی سایت
            </a>
            <a href="/parts"
                class="bg-brand-grey border border-white/10 hover:border-brand-red text-white font-bold px-6 py-3.5 rounded-xl transition flex items-center gap-2 text-xs sm:text-sm">
                <i data-lucide="grid" style="width:18px;height:18px;"></i> کاتالوگ تمام قطعات
            </a>
            <a href="https://wa.me/989189998852" target="_blank"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3.5 rounded-xl transition flex items-center gap-2 text-xs sm:text-sm shadow-[0_4px_20px_rgba(16,185,129,0.3)]">
                <i data-lucide="message-circle" style="width:18px;height:18px;"></i> استعلام قطعه در واتساپ
            </a>
        </div>

        <!-- بخش دسترسی سریع به دسته‌بندی‌های اصلی -->
        <div class="w-full border-t border-white/10 pt-10">
            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-6">میان‌برهای پیشنهادی دسته‌بندی
                قطعات تویوتا</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <a href="/parts?category=engine"
                    class="bg-brand-grey/60 border border-white/5 hover:border-brand-red/30 p-4 rounded-xl text-center space-y-2 group transition">
                    <div
                        class="w-10 h-10 bg-brand-dark rounded-lg flex items-center justify-center mx-auto text-brand-red group-hover:scale-110 transition-transform">
                        <i data-lucide="zap" style="width:20px;height:20px;"></i>
                    </div>
                    <span class="block text-xs font-bold text-gray-300 group-hover:text-white transition">قطعات
                        موتور</span>
                </a>
                <a href="/parts?category=brakes"
                    class="bg-brand-grey/60 border border-white/5 hover:border-brand-red/30 p-4 rounded-xl text-center space-y-2 group transition">
                    <div
                        class="w-10 h-10 bg-brand-dark rounded-lg flex items-center justify-center mx-auto text-brand-red group-hover:scale-110 transition-transform">
                        <i data-lucide="disc" style="width:20px;height:20px;"></i>
                    </div>
                    <span class="block text-xs font-bold text-gray-300 group-hover:text-white transition">سیستم
                        ترمز</span>
                </a>
                <a href="/parts?category=suspension"
                    class="bg-brand-grey/60 border border-white/5 hover:border-brand-red/30 p-4 rounded-xl text-center space-y-2 group transition">
                    <div
                        class="w-10 h-10 bg-brand-dark rounded-lg flex items-center justify-center mx-auto text-brand-red group-hover:scale-110 transition-transform">
                        <i data-lucide="arrow-up-down" style="width:20px;height:20px;"></i>
                    </div>
                    <span class="block text-xs font-bold text-gray-300 group-hover:text-white transition">زیربندی و
                        جلوبندی</span>
                </a>
                <a href="/parts?category=consumables"
                    class="bg-brand-grey/60 border border-white/5 hover:border-brand-red/30 p-4 rounded-xl text-center space-y-2 group transition">
                    <div
                        class="w-10 h-10 bg-brand-dark rounded-lg flex items-center justify-center mx-auto text-brand-red group-hover:scale-110 transition-transform">
                        <i data-lucide="droplet" style="width:20px;height:20px;"></i>
                    </div>
                    <span class="block text-xs font-bold text-gray-300 group-hover:text-white transition">لوازم
                        مصرفی</span>
                </a>
            </div>
        </div>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>

</html>