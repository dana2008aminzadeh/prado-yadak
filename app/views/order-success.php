<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-[#F8F6F0] text-[#251E1B] overflow-x-hidden antialiased flex flex-col min-h-screen font-sans">
    <?php include 'assets/php/header.php'; ?>

    <main class="flex-1 max-w-xl mx-auto px-4 py-12 sm:py-16 flex flex-col items-center justify-center w-full">

        <div
            class="bg-white border border-[#E8E2D9] rounded-3xl p-6 sm:p-10 shadow-[0_15px_40px_rgba(43,23,12,0.04)] space-y-6 w-full text-center">

            <!-- آیکون تایید با استایل نرم زمردی -->
            <div
                class="w-20 h-20 bg-emerald-50 border border-emerald-200/80 rounded-full flex items-center justify-center mx-auto text-emerald-600 shadow-xs">
                <i data-lucide="check" class="w-10 h-10 stroke-[2.5]"></i>
            </div>

            <!-- پیام موفقیت -->
            <div class="space-y-2.5">
                <span
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 px-3.5 py-1.5 rounded-full border border-emerald-200 shadow-2xs">
                    <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                    <span>پرداخت و ثبت فاکتور موفق</span>
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-[#251E1B]">فاکتور شما با موفقیت ثبت گردید</h1>
                <p class="text-xs sm:text-sm text-[#5F605C] leading-relaxed max-w-md mx-auto">
                    فیش پرداختی شما تحویل واحد حسابداری شد. پس از تایید تراکنش و تطابق با شماره شاسی، فرآیند بسته‌بندی و
                    ارسال انجام می‌شود.
                </p>
            </div>

            <!-- باکس کد رهگیری اختصاصی -->
            <div
                class="bg-gradient-to-br from-[#FAF8F5] to-[#F3EEE6] border border-[#E0D6CB] rounded-2xl p-4 sm:p-5 text-center space-y-1.5 shadow-2xs">
                <span class="text-xs font-bold text-[#5F605C] block">شماره سفارش و پیگیری شما:</span>
                <span class="text-2xl sm:text-3xl font-black font-mono tracking-wider text-[#8B533A] select-all block"
                    dir="ltr">
                    <?= e($order['tracking_code']) ?>
                </span>
                <span class="text-[10px] text-[#8B533A] block">جهت پیگیری‌های بعدی این کد را نزد خود نگه دارید</span>
            </div>

            <!-- خلاصه مشخصات فاکتور ثبت‌شده -->
            <div
                class="bg-[#FAF8F5] border border-[#E8E2D9] rounded-2xl p-4 sm:p-5 divide-y divide-[#E8E2D9] text-xs text-right space-y-3">
                <div class="flex justify-between items-center pb-2">
                    <span class="text-[#5F605C]">تحویل‌گیرنده:</span>
                    <span class="font-bold text-[#251E1B]"><?= e($order['recipient_name']) ?></span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-[#5F605C]">مبلغ پرداخت شده:</span>
                    <span class="font-extrabold text-sm sm:text-base text-emerald-600">
                        <?= number_format($order['total_amount']) ?> <span
                            class="text-xs font-normal text-[#5F605C]">تومان</span>
                    </span>
                </div>
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-1 pt-2">
                    <span class="text-[#5F605C] shrink-0">نشانی تحویل:</span>
                    <span class="font-medium text-[#251E1B] leading-relaxed sm:text-left">
                        <?= e($order['shipping_address']) ?>
                    </span>
                </div>
            </div>

            <!-- دکمه‌های اقدام -->
            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <a href="/profile"
                    class="flex-1 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold py-3.5 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-[0_4px_15px_rgba(5,150,105,0.25)] cursor-pointer">
                    <i data-lucide="user" class="w-4 h-4"></i>
                    <span>مشاهده وضعیت در پنل کاربری</span>
                </a>
                <a href="/parts"
                    class="flex-1 bg-[#FAF8F5] hover:bg-[#F0EBE1] border border-[#D5CAC0] text-[#251E1B] font-bold py-3.5 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-2xs cursor-pointer">
                    <i data-lucide="shopping-bag" class="w-4 h-4 text-[#8B533A]"></i>
                    <span>بازگشت به کاتالوگ قطعات</span>
                </a>
            </div>

        </div>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <script src="/assets/js/main.js"></script>
    <script>
        // تخلیه سبد خرید پس از ثبت موفق فاکتور
        localStorage.removeItem('toyota_cart');
        if (typeof cart !== 'undefined') {
            cart = [];
            updateCartUI();
        }
    </script>
</body>

</html>