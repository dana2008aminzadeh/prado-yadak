<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-brand-dark text-white overflow-x-hidden antialiased flex flex-col min-h-screen">

    <?php include 'assets/php/header.php'; ?>

    <?php
    global $settings;
    $bankName = $settings['bank_name'] ?? 'بانک ملت';
    $bankSheba = $settings['bank_sheba'] ?? 'IR580120000000001234567890';
    $bankCard = $settings['bank_card_number'] ?? '';
    $bankOwner = $settings['bank_account_owner'] ?? ($settings['site_title'] ?? 'پرادو یدک');
    ?>

    <main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:py-12 relative z-10 w-full">

        <!-- ۱. اطلاعیه‌های سراسری بالای صفحه تسویه حساب -->
        <?php if (!empty($notices)): ?>
            <div class="space-y-3 mb-8">
                <?php foreach ($notices as $notice):
                    $type = $notice['type'] ?? 'info';
                    if ($type === 'warning') {
                        $boxStyle = 'background-color: #fffbeb; border-color: #fde68a;';
                        $iconStyle = 'color: #d97706;';
                        $titleStyle = 'color: #92400e;';
                        $textStyle = 'color: #b45309;';
                    } elseif ($type === 'danger') {
                        $boxStyle = 'background-color: #fff1f2; border-color: #fecdd3;';
                        $iconStyle = 'color: #e11d48;';
                        $titleStyle = 'color: #9f1239;';
                        $textStyle = 'color: #be123c;';
                    } else {
                        $boxStyle = 'background-color: #f0f9ff; border-color: #bae6fd;';
                        $iconStyle = 'color: #0284c7;';
                        $titleStyle = 'color: #075985;';
                        $textStyle = 'color: #0369a1;';
                    }
                    ?>
                    <div style="<?= $boxStyle ?>" class="border-2 rounded-2xl p-4 sm:p-5 flex items-start gap-4 shadow-sm">
                        <div class="shrink-0 mt-0.5" style="<?= $iconStyle ?>">
                            <i data-lucide="<?= e($notice['icon'] ?: 'alert-circle') ?>" style="width:24px;height:24px;"></i>
                        </div>
                        <div class="flex-1 text-xs sm:text-sm leading-relaxed">
                            <h4 class="font-black text-sm sm:text-base mb-1" style="<?= $titleStyle ?>">
                                <?= e($notice['title']) ?>
                            </h4>
                            <p class="font-medium" style="<?= $textStyle ?>"><?= nl2br(e($notice['message'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['checkout_error'])): ?>
            <div
                class="mb-6 p-4 rounded-2xl bg-rose-500/10 border-2 border-rose-500/30 text-rose-500 text-xs sm:text-sm font-bold flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 shrink-0"></i>
                <span><?= e($_SESSION['checkout_error']);
                unset($_SESSION['checkout_error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- ۲. ساختار گرید دو ستونه تفکیک‌شده (ستون چپ در دسکتاپ ثابت و بدون اسکرول غیرعادی) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- مرحله ۱ در موبایل (order-1) / ستون چپ فاکتور در دسکتاپ (عرض ۵ از ۱۲ - lg:order-2) -->
            <div class="lg:col-span-5 space-y-6 order-1 lg:order-2">

                <!-- کارت اقلام در حال سفارش -->
                <div class="bg-brand-grey border border-white/10 rounded-3xl p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shopping-bag" class="w-5 h-5 text-brand-red"></i>
                            <h3 class="font-extrabold text-sm sm:text-base text-white">۱. اقلام فاکتور خرید</h3>
                        </div>
                        <span id="items-count-badge"
                            class="bg-brand-dark border border-white/10 text-xs px-2.5 py-1 rounded-full text-gray-300 font-bold">۰
                            قطعه</span>
                    </div>

                    <!-- ریل نمایش محصولات سفارش -->
                    <div id="checkout-items-list" class="space-y-3 max-h-[340px] overflow-y-auto pr-1">
                        <!-- اقلام توسط جاوااسکریپت تزریق می‌شوند -->
                    </div>

                    <!-- مبالغ سفارش -->
                    <div class="pt-4 border-t border-white/10 space-y-2.5 text-xs text-gray-300">
                        <div class="flex justify-between items-center">
                            <span>جمع مبالغ قطعات:</span>
                            <span id="cart-subtotal" class="font-bold text-white text-sm">۰ تومان</span>
                        </div>
                        <div id="discount-row" class="flex justify-between items-center text-emerald-400 hidden">
                            <span>تخفیف کسر شده:</span>
                            <span id="discount-amount" class="font-bold text-sm">۰ تومان</span>
                        </div>
                        <div class="w-full h-px bg-white/10 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-white text-sm">مبلغ نهایی قابل واریز:</span>
                            <span id="final-price" class="text-xl font-black text-brand-red">۰ تومان</span>
                        </div>
                    </div>
                </div>

                <!-- باکس راهنمای اطمینان خرید -->
                <div
                    class="hidden lg:block bg-brand-grey border border-white/10 rounded-3xl p-5 shadow-xl text-xs space-y-3">
                    <div class="flex items-center gap-2 font-bold text-white pb-2 border-b border-white/10">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>ضمانت اصالت و سلامت مرسوله</span>
                    </div>
                    <p class="text-gray-400 leading-relaxed">
                        کلیه قطعات پیش از ارسال از نظر انطباق فنی با شماره شاسی خودرو (VIN) بررسی شده و با بسته‌بندی
                        پلمپ و ضدضربه از طریق تیپاکس و باربری به سراسر کشور ارسال می‌گردند.
                    </p>
                </div>

            </div>

            <!-- فرم اصلی: مرحله ۲ و مرحله ۳ (ادغام‌شده) / ستون راست در دسکتاپ (عرض ۷ از ۱۲ - lg:order-1) -->
            <div class="lg:col-span-7 space-y-6 order-2 lg:order-1">

                <form action="/checkout/process" method="POST" enctype="multipart/form-data" id="checkout-form"
                    class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="cart_data" id="cart_data_input" value="">
                    <input type="hidden" name="applied_discount_code" id="applied_discount_input" value="">

                    <!-- ============================================== -->
                    <!-- مرحله ۲: مشخصات تحویل‌گیرنده و نشانی پستی -->
                    <!-- ============================================== -->
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-5 sm:p-8 shadow-xl space-y-5">
                        <div class="flex items-center gap-3 pb-4 border-b border-white/10">
                            <div
                                class="w-10 h-10 rounded-xl bg-brand-red/10 border border-brand-red/20 text-brand-red flex items-center justify-center">
                                <i data-lucide="map-pin" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-white">۲. مشخصات تحویل‌گیرنده و نشانی
                                    پستی</h2>
                                <p class="text-xs text-gray-400">قطعات به این نشانی بسته‌بندی و ارسال خواهند شد.</p>
                            </div>
                        </div>

                        <!-- دراپ‌داون نشانی‌های ذخیره‌شده -->
                        <?php if (!empty($savedAddresses)): ?>
                            <div class="bg-brand-dark/40 border border-white/5 p-4 rounded-2xl">
                                <label class="block text-xs font-bold text-gray-300 mb-2">انتخاب نشانی از خریدهای
                                    پیشین:</label>
                                <select id="saved-addresses-select"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-xs text-white focus:border-brand-red cursor-pointer">
                                    <option value="" selected>-- ثبت نشانی جدید --</option>
                                    <?php foreach ($savedAddresses as $addr): ?>
                                        <option value="<?= e(json_encode($addr, JSON_UNESCAPED_UNICODE)) ?>">
                                            <?= e(($addr['province_city'] ?? '') . ' | ' . ($addr['address_detail'] ?? '')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">نام و نام‌خانوادگی
                                    تحویل‌گیرنده *</label>
                                <input type="text" name="recipient_name" id="rec_name" required
                                    value="<?= e($currentUser['full_name'] ?? '') ?>" placeholder="مثال: علی محمدی"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">شماره موبایل تحویل‌گیرنده
                                    *</label>
                                <input type="tel" name="recipient_phone" id="rec_phone" required dir="ltr"
                                    value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="09189998852"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-left text-white font-mono focus:outline-none focus:border-brand-red transition">
                            </div>
                        </div>

                        <!-- انتخاب استان و شهر -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">استان *</label>
                                <select name="province" id="province-select" required
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition cursor-pointer">
                                    <option value="">انتخاب استان...</option>
                                    <?php if (!empty($provinces)): ?>
                                        <?php foreach ($provinces as $prov): ?>
                                            <option value="<?= e($prov['name']) ?>" data-id="<?= (int) $prov['id'] ?>">
                                                <?= e($prov['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">شهر *</label>
                                <select name="city" id="city-select" required disabled
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option value="">ابتدا استان را انتخاب کنید</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-300 mb-1.5">نشانی پستی دقیق (خیابان، پلاک،
                                واحد) *</label>
                            <textarea name="address_detail" id="rec_address_detail" rows="2" required
                                placeholder="مثال: خیابان ساحلی، روبروی قطعات تویوتا، پلاک ۴۵، طبقه ۲"
                                class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">کد پستی ۱۰ رقمی</label>
                                <input type="text" name="postal_code" id="rec_postal" dir="ltr" maxlength="10"
                                    placeholder="مثال: 6681898204"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm font-mono text-center text-white focus:outline-none focus:border-brand-red transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">یادداشت سفارش
                                    (اختیاری)</label>
                                <input type="text" name="user_notes" id="rec_notes"
                                    placeholder="شماره شاسی خودرو یا توضیحات ارسال..."
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition">
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- مرحله ۳: واریز وجه و بارگذاری رسید بانکی (ادغام شده) -->
                    <!-- ============================================== -->
                    <div class="bg-brand-grey border border-white/10 rounded-3xl p-5 sm:p-8 shadow-xl space-y-6">

                        <!-- هدر مرحله -->
                        <div class="flex items-center gap-3 pb-4 border-b border-white/10">
                            <div
                                class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-white">۳. پرداخت وجه و بارگذاری رسید
                                    بانکی</h2>
                                <p class="text-xs text-gray-400">واریز وجه به حساب رسمی فروشگاه و ارسال تصویر فیش</p>
                            </div>
                        </div>

                        <!-- باکس راهنما و مقدار دقیق واریز -->
                        <div
                            class="bg-gradient-to-r from-emerald-500/10 via-brand-dark to-brand-dark border border-emerald-500/30 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                                    <i data-lucide="info" class="w-4 h-4"></i>
                                    راهنمای فرآیند پرداخت
                                </span>
                                <p class="text-xs text-gray-300 leading-relaxed">
                                    لطفاً دقیقاً مبلغ <strong id="instruction-payable-amount"
                                        class="text-white font-black bg-black/40 px-2 py-0.5 rounded border border-white/10 font-mono">۰
                                        تومان</strong> را به یکی از شماره حساب‌های زیر انتقال داده و تصویر رسید تراکنش
                                    را در کادر زیر پیوست نمایید.
                                </p>
                            </div>
                        </div>

                        <!-- کارت‌های بانکی با قابلیت کپی مستقیم -->
                        <div class="space-y-3">
                            <div class="space-y-1.5 text-xs">
                                <span class="text-gray-400 block font-medium">شماره شبای حساب رسمی
                                    (<?= e($bankName) ?>):</span>
                                <div class="relative">
                                    <button type="button" id="btn-copy-sheba" data-copy="<?= e($bankSheba) ?>"
                                        class="w-full bg-[#f8f6f0] text-black font-mono font-bold text-xs sm:text-sm py-3 px-3 rounded-xl border border-white/10 flex items-center justify-between hover:border-brand-red transition cursor-pointer"
                                        dir="ltr">
                                        <span class="tracking-wider"><?= e($bankSheba) ?></span>
                                        <div class="flex items-center gap-1 text-gray-500 hover:text-brand-red">
                                            <span class="text-[10px] font-sans">کپی شبا</span>
                                            <i data-lucide="copy" class="w-4 h-4"></i>
                                        </div>
                                    </button>
                                    <span id="toast-sheba"
                                        class="absolute -top-9 left-1/2 -translate-x-1/2 bg-white text-brand-red font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">شماره
                                        شبا کپی شد!</span>
                                </div>
                            </div>

                            <?php if (!empty($bankCard)): ?>
                                <div class="space-y-1.5 text-xs">
                                    <span class="text-gray-400 block font-medium">شماره کارت بانکی:</span>
                                    <div class="relative">
                                        <button type="button" id="btn-copy-card"
                                            data-copy="<?= e(str_replace('-', '', $bankCard)) ?>"
                                            class="w-full bg-[#f8f6f0] text-black font-mono font-bold text-xs sm:text-sm py-3 px-3 rounded-xl border border-white/10 flex items-center justify-between hover:border-brand-red transition cursor-pointer"
                                            dir="ltr">
                                            <span class="tracking-widest"><?= e($bankCard) ?></span>
                                            <div class="flex items-center gap-1 text-gray-500 hover:text-brand-red">
                                                <span class="text-[10px] font-sans">کپی کارت</span>
                                                <i data-lucide="copy" class="w-4 h-4"></i>
                                            </div>
                                        </button>
                                        <span id="toast-card"
                                            class="absolute -top-9 left-1/2 -translate-x-1/2 bg-white text-brand-red font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">شماره
                                            کارت کپی شد!</span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div
                                class="bg-brand-dark/60 p-3 rounded-xl border border-white/5 text-[11px] text-gray-300 space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-gray-400">صاحب حساب:</span>
                                    <span class="font-bold text-white"><?= e($bankOwner) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-400">بانک عامل:</span>
                                    <span class="text-white"><?= e($bankName) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- باکس آپلود تصویر رسید بانکی -->
                        <div class="space-y-2 pt-2">
                            <label class="block text-xs font-bold text-gray-300 flex items-center gap-2">
                                <i data-lucide="upload-cloud" class="w-4 h-4 text-emerald-400"></i>
                                تصویر یا فایل PDF فیش پرداختی *
                            </label>
                            <div
                                class="relative border-2 border-dashed border-white/20 hover:border-brand-red/50 rounded-2xl p-6 text-center transition bg-brand-dark/50 group">
                                <input type="file" name="receipt_image" id="receipt-file-input" required
                                    accept="image/jpeg,image/png,image/webp,application/pdf"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div id="upload-placeholder" class="space-y-2">
                                    <i data-lucide="image-plus"
                                        class="w-10 h-10 mx-auto text-gray-400 group-hover:text-brand-red transition"></i>
                                    <p class="text-xs font-bold text-gray-300">عکس یا فایل فیش واریز را اینجا انتخاب
                                        کنید</p>
                                    <p class="text-[10px] text-gray-500">فرمت‌های مجاز: JPG, PNG, WEBP, PDF (حداکثر ۵
                                        مگابایت)</p>
                                </div>
                                <div id="file-preview-container" class="hidden flex flex-col items-center gap-2">
                                    <img id="receipt-preview-img" src="" alt="پیش‌نمایش رسید"
                                        class="max-h-48 rounded-xl object-contain border border-white/20 shadow-md">
                                    <span id="file-name-display"
                                        class="text-xs text-emerald-400 font-mono font-bold"></span>
                                    <span class="text-[10px] text-gray-400">برای تغییر فایل، مجدداً کلیک کنید.</span>
                                </div>
                            </div>
                        </div>

                        <!-- کد تخفیف -->
                        <div class="bg-brand-dark/50 border border-white/5 p-4 rounded-2xl">
                            <label class="block text-xs font-bold text-gray-300 mb-2 flex items-center gap-2">
                                <i data-lucide="ticket" class="w-4 h-4 text-brand-red"></i>
                                <span>کد تخفیف دارید؟</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="discount_code" placeholder="کد تخفیف (مثال: PRADO10)" dir="ltr"
                                    class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-2.5 text-xs sm:text-sm text-left text-white focus:outline-none focus:border-brand-red transition uppercase tracking-widest">
                                <button type="button" id="btn-apply-coupon"
                                    class="bg-brand-grey border border-white/10 hover:border-brand-red text-brand-red px-5 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap active:scale-95">اعمال</button>
                            </div>
                            <div id="discount-message"
                                class="hidden mt-2 text-xs font-bold px-3 py-2 rounded-lg border"></div>
                        </div>

                        <!-- دکمه نهایی ثبت سفارش -->
                        <button type="submit" id="submit-order-btn"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold py-4 rounded-2xl text-sm transition shadow-[0_4px_25px_rgba(16,185,129,0.35)] flex items-center justify-center gap-2 cursor-pointer">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span>ثبت نهایی فاکتور و ارسال به انبار</span>
                        </button>
                    </div>

                </form>

            </div>

        </div>

    </main>

    <?php include 'assets/php/footer.php'; ?>

    <!-- فایل اسکریپت اصلی و ماژول اختصاصی چک‌اوت -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/checkout.js"></script>
</body>

</html>