<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">
    <?php include 'assets/php/header.php'; ?>

    <main class="flex-1 max-w-2xl mx-auto px-4 py-12 sm:py-20 flex flex-col items-center justify-center text-center">
        <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-10 shadow-2xl space-y-6 w-full relative">
            <div
                class="w-20 h-20 bg-emerald-500/10 border-2 border-emerald-500/30 rounded-full flex items-center justify-center mx-auto text-emerald-400">
                <i data-lucide="check" class="w-10 h-10"></i>
            </div>

            <div class="space-y-2">
                <span
                    class="text-xs text-emerald-400 font-bold bg-emerald-500/10 px-3 py-1 rounded-full border border-emerald-500/20">
                    پرداخت و ثبت اولیه موفق
                </span>
                <h1 class="text-2xl sm:text-3xl font-black text-white">فاکتور شما با موفقیت ثبت گردید</h1>
                <p class="text-xs sm:text-sm text-gray-400 leading-relaxed max-w-md mx-auto">
                    فیش پرداختی شما به کارشناسان مالی تحویل شد. به زودی پس از تایید فیش و بررسی شماره شاسی، قطعات
                    بسته‌بندی و ارسال می‌شوند.
                </p>
            </div>

            <div class="bg-brand-dark border border-brand-red/40 rounded-2xl p-4 sm:p-5 text-center space-y-1">
                <span class="text-xs text-gray-400 block">شماره سفارش و پیگیری شما:</span>
                <span class="text-xl sm:text-2xl font-black font-mono text-brand-red select-all tracking-wider">
                    <?= e($order['tracking_code']) ?>
                </span>
            </div>

            <div class="space-y-2 text-xs text-gray-300 border-t border-white/10 pt-4 text-right">
                <div class="flex justify-between py-1">
                    <span class="text-gray-400">تحویل‌گیرنده:</span>
                    <span class="font-bold text-white">
                        <?= e($order['recipient_name']) ?>
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-400">مبلغ پرداخت شده:</span>
                    <span class="font-bold text-emerald-400">
                        <?= number_format($order['total_amount']) ?> تومان
                    </span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-400">آدرس تحویل:</span>
                    <span class="text-white line-clamp-1">
                        <?= e($order['shipping_address']) ?>
                    </span>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-4">
                <a href="/profile"
                    class="flex-1 bg-brand-red hover:bg-red-700 text-white font-bold py-3.5 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg">
                    <i data-lucide="user" class="w-4 h-4"></i> مشاهده وضعیت در پنل کاربری
                </a>
                <a href="/parts"
                    class="flex-1 bg-brand-dark border border-white/10 hover:border-white/30 text-white font-bold py-3.5 rounded-xl text-xs transition flex items-center justify-center gap-2">
                    <i data-lucide="shopping-bag" class="w-4 h-4"></i> بازگشت به فروشگاه
                </a>
            </div>
        </div>
    </main>

    <?php include 'assets/php/footer.php'; ?>
    <script src="/assets/js/main.js"></script>
    <script>
        // خالی کردن سبد خرید محلی مرورگر پس از ثبت موفقیت‌آمیز فاکتور
        localStorage.removeItem('toyota_cart');
        if (typeof cart !== 'undefined') {
            cart = [];
            updateCartUI();
        }
    </script>
</body>

</html>