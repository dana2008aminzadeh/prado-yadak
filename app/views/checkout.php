<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">
 
<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">

    <?php include 'assets/php/header.php'; ?>

    <main class="flex-1 max-w-3xl mx-auto px-4 py-8 sm:py-12 relative z-10 w-full">
        <div class="bg-brand-grey border border-white/10 rounded-3xl p-6 sm:p-10 shadow-2xl relative overflow-hidden">

            <!-- هدر صفحه -->
            <div class="text-center mb-8 space-y-2">
                <div
                    class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center justify-center mx-auto text-emerald-400 mb-3">
                    <i data-lucide="receipt" class="w-9 h-9"></i>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white">تسویه حساب و آپلود فیش</h2>
                <p class="text-xs text-gray-400">لطفاً مبلغ فاکتور را به شماره شبای زیر واریز کرده و فیش را آپلود کنید.
                </p>
            </div>

            <!-- بخش اعمال کد تخفیف -->
            <div
                class="bg-brand-dark/40 border border-white/5 p-5 rounded-2xl mb-6 transition-all focus-within:border-brand-red/30">
                <label class="block text-xs font-medium text-gray-300 mb-3 flex items-center gap-2">
                    <i data-lucide="ticket" class="w-4 h-4 text-brand-red"></i>
                    کد تخفیف دارید؟
                </label>
                <div class="flex flex-col sm:flex-row gap-3">
                    <input type="text" id="discount_code" placeholder="کد تخفیف را وارد کنید..." dir="ltr"
                        class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-center sm:text-left text-white focus:outline-none focus:border-brand-red transition uppercase tracking-widest placeholder:tracking-normal">
                    <button type="button" onclick="applyDiscount()"
                        class="bg-brand-grey border border-white/10 hover:border-brand-red text-brand-red px-6 py-3 rounded-xl text-xs font-bold transition whitespace-nowrap">
                        اعمال تخفیف
                    </button>
                </div>
                <div id="discount-message" class="hidden mt-3 text-xs font-bold px-3 py-2 rounded-lg border"></div>
            </div>

            <!-- باکس اطلاعات حساب و مبالغ -->
            <div class="bg-brand-dark/50 border border-brand-red/30 p-5 rounded-2xl mb-8 space-y-4">

                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm text-gray-300">
                        <span>جمع مبلغ سفارش:</span>
                        <span id="cart-subtotal" class="font-bold">۰ تومان</span>
                    </div>

                    <div id="discount-row" class="flex justify-between items-center text-sm text-emerald-400 hidden">
                        <span>تخفیف اعمال شده:</span>
                        <span id="discount-amount" class="font-bold">۰ تومان</span>
                    </div>
                </div>

                <div class="w-full h-px bg-white/10 my-2"></div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <span class="text-sm text-white font-bold">مبلغ قابل پرداخت:</span>
                    <span id="final-price" class="text-2xl font-black text-brand-red">۰ تومان</span>
                </div>

                <div class="w-full h-px bg-white/10 my-2"></div>

                <div class="text-center pt-2">
                    <p class="text-sm text-gray-300 mb-4">شماره شبا جهت واریز (بانک ملت):</p>

                    <!-- کادر کپی شماره شبا -->
                    <div class="relative group inline-block w-full">
                        <button type="button" onclick="copySheba()"
                            class="flex items-center justify-between w-full text-sm sm:text-lg font-mono text-black bg-brand-dark py-4 px-4 sm:px-6 rounded-xl border border-white/10 hover:border-brand-red transition cursor-pointer"
                            dir="ltr">
                            <span id="sheba-text" class="tracking-[0.1em] sm:tracking-[0.15em]">IR12 3456 7890 1234 5678
                                9012 34</span>
                            <div class="flex items-center gap-2 text-gray-400 group-hover:text-brand-red transition">
                                <span class="text-xs font-sans hidden sm:block">کپی</span>
                                <i data-lucide="copy" class="w-5 h-5"></i>
                            </div>
                        </button>
                        <span id="copy-toast"
                            class="absolute -top-10 left-1/2 -translate-x-1/2 bg-white text-brand-red font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">شماره
                            شبا کپی شد!</span>
                    </div>

                    <p class="text-xs text-gray-400 mt-4">به نام: علی محمدی (مجموعه پرادو یدک)</p>
                </div>
            </div>

            <!-- فرم دریافت مشخصات و آپلود فیش -->
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-5" id="checkout-form">

                <input type="hidden" name="cart_data" id="cart_data_input" value="">
                <input type="hidden" name="applied_discount_code" id="applied_discount_input" value="">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-2">نام صاحب حساب پرداختی</label>
                        <input type="text" name="payer_name" required placeholder="مثلا: محمد حسینی"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-sm text-white focus:outline-none focus:border-brand-red transition">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-2">شماره پیگیری واریز</label>
                        <input type="text" name="tracking_number" required placeholder="شماره ارجاع یا پیگیری فیش"
                            dir="ltr"
                            class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3.5 text-sm font-mono text-center text-white focus:outline-none focus:border-brand-red transition">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-brand-red mb-2 font-bold flex items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-5 h-5"></i> آپلود تصویر فیش واریزی
                    </label>
                    <input type="file" name="receipt_image" accept="image/jpeg, image/png, application/pdf" required
                        class="w-full bg-brand-dark border border-dashed border-white/20 hover:border-brand-red/50 rounded-xl px-4 py-8 text-sm text-center text-gray-400 focus:outline-none transition file:mr-4 file:py-2 file:px-5 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-brand-red file:text-white hover:file:bg-red-700 cursor-pointer">
                    <p class="text-[10px] text-gray-500 mt-2 text-center">فرمت‌های مجاز: JPG, PNG, PDF (حداکثر حجم: ۲
                        مگابایت)</p>
                </div>

                <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 rounded-xl text-sm transition shadow-[0_4px_20px_rgba(16,185,129,0.3)] flex items-center justify-center gap-2 mt-4">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> تایید پرداخت و ثبت نهایی سفارش
                </button>
            </form>

        </div>
    </main>

    <?php include 'assets/php/footer.php'; ?>
    <script src="assets/js/main.js"></script>

    <script>
        let originalTotal = 0;
        let discountApplied = 0;

        document.addEventListener('DOMContentLoaded', () => {
            const cartData = JSON.parse(localStorage.getItem('toyota_cart')) || [];
            document.getElementById('cart_data_input').value = JSON.stringify(cartData);

            originalTotal = cartData.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
            updatePriceUI();
        });

        function updatePriceUI() {
            let finalPrice = originalTotal - discountApplied;

            document.getElementById('cart-subtotal').innerText = originalTotal.toLocaleString('fa-IR') + ' تومان';
            document.getElementById('final-price').innerText = finalPrice.toLocaleString('fa-IR') + ' تومان';

            const discountRow = document.getElementById('discount-row');
            if (discountApplied > 0) {
                discountRow.classList.remove('hidden');
                document.getElementById('discount-amount').innerText = discountApplied.toLocaleString('fa-IR') + ' تومان';
            } else {
                discountRow.classList.add('hidden');
            }
        }

        function applyDiscount() {
            const codeInput = document.getElementById('discount_code').value.trim().toUpperCase();
            const messageEl = document.getElementById('discount-message');
            const discountInput = document.getElementById('applied_discount_input');

            messageEl.classList.remove('hidden', 'bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20', 'bg-rose-500/10', 'text-rose-400', 'border-rose-500/20', 'bg-amber-500/10', 'text-amber-400', 'border-amber-500/20');

            if (originalTotal === 0) {
                messageEl.innerText = 'سبد خرید شما خالی است.';
                messageEl.classList.add('bg-amber-500/10', 'text-amber-400', 'border-amber-500/20');
                return;
            }

            if (codeInput === 'PRADO10') {
                discountApplied = originalTotal * 0.10;
                discountInput.value = codeInput;
                messageEl.innerText = 'کد تخفیف ۱۰ درصدی با موفقیت اعمال شد.';
                messageEl.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
            } else if (codeInput === 'YADAK500') {
                discountApplied = 500000;
                if (discountApplied > originalTotal) discountApplied = originalTotal;
                discountInput.value = codeInput;
                messageEl.innerText = 'مبلغ ۵۰۰,۰۰۰ تومان از فاکتور شما کسر شد.';
                messageEl.classList.add('bg-emerald-500/10', 'text-emerald-400', 'border-emerald-500/20');
            } else if (codeInput === '') {
                discountApplied = 0;
                discountInput.value = '';
                messageEl.innerText = 'لطفاً کد تخفیف را وارد کنید.';
                messageEl.classList.add('bg-amber-500/10', 'text-amber-400', 'border-amber-500/20');
            } else {
                discountApplied = 0;
                discountInput.value = '';
                messageEl.innerText = 'کد تخفیف وارد شده نامعتبر یا منقضی شده است.';
                messageEl.classList.add('bg-rose-500/10', 'text-rose-400', 'border-rose-500/20');
            }

            updatePriceUI();
        }

        function copySheba() {
            // شماره شبا بدون فاصله برای کپی شدن دقیق در کلیپ‌بورد
            const shebaNumber = "IR123456789012345678901234";

            navigator.clipboard.writeText(shebaNumber).then(() => {
                const toast = document.getElementById('copy-toast');
                toast.classList.remove('opacity-0');
                toast.classList.add('opacity-100');

                setTimeout(() => {
                    toast.classList.remove('opacity-100');
                    toast.classList.add('opacity-0');
                }, 2000);
            }).catch(err => {
                console.error('خطا در کپی شماره شبا:', err);
            });
        }
    </script>
</body>

</html>