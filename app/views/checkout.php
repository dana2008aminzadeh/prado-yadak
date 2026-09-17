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

        <!-- ============================================== -->
        <!-- ۱. اطلاعیه‌های داینامیک بالای صفحه با ۳ استایل رنگی واضح -->
        <!-- ============================================== -->
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
                                <?= e($notice['title']) ?></h4>
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

        <!-- ============================================== -->
        <!-- ۲. ساختار دو ستونه: فرم ثبت (راست) + خلاصه اقلام سفارش (چپ) -->
        <!-- ============================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- ستون فرم اطلاعات ارسال و فیش (عرض ۷ از ۱۲) -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-brand-grey border border-white/10 rounded-3xl p-5 sm:p-8 shadow-xl">
                    <div class="flex items-center gap-3 pb-4 border-b border-white/10 mb-6">
                        <div
                            class="w-10 h-10 rounded-xl bg-brand-red/10 border border-brand-red/20 text-brand-red flex items-center justify-center">
                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h2 class="text-base sm:text-lg font-black text-white">مشخصات تحویل‌گیرنده و نشانی پستی</h2>
                            <p class="text-xs text-gray-400">قطعات به این آدرس بسته‌بندی و ارسال خواهند شد.</p>
                        </div>
                    </div>

                    <form action="/checkout/process" method="POST" enctype="multipart/form-data" id="checkout-form"
                        class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="cart_data" id="cart_data_input" value="">
                        <input type="hidden" name="applied_discount_code" id="applied_discount_input" value="">

                        <!-- دراپ‌داون نشانی‌های ذخیره‌شده (پیش‌فرض: نشانی جدید بدون انتخاب خودکار) -->
                        <?php if (!empty($savedAddresses)): ?>
                            <div class="bg-brand-dark/40 border border-white/5 p-4 rounded-2xl">
                                <label class="block text-xs font-bold text-gray-300 mb-2">انتخاب نشانی از خریدهای
                                    پیشین:</label>
                                <select id="saved-addresses-select" onchange="handleAddressSelection(this)"
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

                        <!-- انتخاب استان و شهر با فراخوانی API -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">استان *</label>
                                <select name="province" id="province-select" required onchange="loadCities(this.value)"
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition cursor-pointer">
                                    <option value="">در حال بارگذاری استان‌ها...</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">شهر *</label>
                                <select name="city" id="city-select" required
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition cursor-pointer">
                                    <option value="">ابتدا استان را انتخاب کنید</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-300 mb-1.5">نشانی پستی دقیق (خیابان، کوچه،
                                پلاک، زنگ یا واحد) *</label>
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
                                <label class="block text-xs font-bold text-gray-300 mb-1.5">یادداشت برای سفارش
                                    (اختیاری)</label>
                                <input type="text" name="user_notes" id="rec_notes"
                                    placeholder="شماره شاسی خودرو یا توضیحات ارسال..."
                                    class="w-full bg-brand-dark border border-white/10 rounded-xl px-4 py-3 text-sm text-white focus:outline-none focus:border-brand-red transition">
                            </div>
                        </div>

                        <!-- آپلود تصویر رسید واریز -->
                        <div class="pt-4 border-t border-white/10 space-y-3">
                            <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                                <i data-lucide="upload-cloud" class="w-4 h-4 text-emerald-400"></i> تصویر یا فایل رسید
                                بانکی
                            </h3>
                            <div
                                class="relative border-2 border-dashed border-white/20 hover:border-brand-red/50 rounded-2xl p-6 text-center transition bg-brand-dark/50 group">
                                <input type="file" name="receipt_image" id="receipt-file-input" required
                                    accept="image/jpeg,image/png,image/webp,application/pdf"
                                    onchange="previewReceiptFile(this)"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div id="upload-placeholder" class="space-y-2">
                                    <i data-lucide="image-plus"
                                        class="w-10 h-10 mx-auto text-gray-400 group-hover:text-brand-red transition"></i>
                                    <p class="text-xs font-bold text-gray-300">عکس یا فایل PDF فیش واریز را اینجا انتخاب
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
                                <i data-lucide="ticket" class="w-4 h-4 text-brand-red"></i> کد تخفیف دارید؟
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="discount_code" placeholder="کد تخفیف (مثال: PRADO10)" dir="ltr"
                                    class="flex-1 bg-brand-dark border border-white/10 rounded-xl px-4 py-2.5 text-xs sm:text-sm text-left text-white focus:outline-none focus:border-brand-red transition uppercase tracking-widest">
                                <button type="button" id="btn-apply-coupon" onclick="validateCouponAjax()"
                                    class="bg-brand-grey border border-white/10 hover:border-brand-red text-brand-red px-5 py-2.5 rounded-xl text-xs font-bold transition whitespace-nowrap active:scale-95">
                                    اعمال
                                </button>
                            </div>
                            <div id="discount-message"
                                class="hidden mt-2 text-xs font-bold px-3 py-2 rounded-lg border"></div>
                        </div>

                        <button type="submit" id="submit-order-btn"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold py-4 rounded-2xl text-sm transition shadow-[0_4px_25px_rgba(16,185,129,0.35)] flex items-center justify-center gap-2">
                            <i data-lucide="check-circle" class="w-5 h-5"></i> ثبت نهایی فاکتور و ارسال به انبار
                        </button>
                    </form>
                </div>
            </div>

            <!-- ستون سایدبار: مشخصات حساب بانکی و لیست اقلام سبد خرید (عرض ۵ از ۱۲) -->
            <div class="lg:col-span-5 space-y-6 lg:sticky lg:top-24">

                <!-- کارت اقلام در حال سفارش -->
                <div class="bg-brand-grey border border-white/10 rounded-3xl p-5 sm:p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shopping-bag" class="w-5 h-5 text-brand-red"></i>
                            <h3 class="font-extrabold text-sm sm:text-base text-white">اقلام فاکتور خرید</h3>
                        </div>
                        <span id="items-count-badge"
                            class="bg-brand-dark border border-white/10 text-xs px-2.5 py-1 rounded-full text-gray-300 font-bold">۰
                            قطعه</span>
                    </div>

                    <!-- ریل نمایش محصولات سفارش -->
                    <div id="checkout-items-list" class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
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
                            <span class="font-bold text-white text-sm">مبلغ قابل واریز:</span>
                            <span id="final-price" class="text-xl font-black text-brand-red">۰ تومان</span>
                        </div>
                    </div>
                </div>

                <!-- باکس اطلاعات بانکی (خوانده‌شده از دیتابیس) -->
                <div class="bg-brand-grey border border-brand-red/30 rounded-3xl p-5 sm:p-6 shadow-xl space-y-4">
                    <div
                        class="flex items-center gap-2 text-white font-extrabold text-sm pb-3 border-b border-white/10">
                        <i data-lucide="credit-card" class="w-4 h-4 text-brand-red"></i>
                        <span>اطلاعات حساب جهت واریز وجه (<?= e($bankName) ?>)</span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <span class="text-gray-400 block">شماره شبای حساب رسمی:</span>
                        <div class="relative group">
                            <button type="button" onclick="copyText('<?= e($bankSheba) ?>', 'toast-sheba')"
                                class="w-full bg-[#f8f6f0] text-black font-mono font-bold text-xs sm:text-sm py-3 px-3 rounded-xl border border-white/10 flex items-center justify-between hover:border-brand-red transition cursor-pointer"
                                dir="ltr">
                                <span class="tracking-wider"><?= e($bankSheba) ?></span>
                                <div class="flex items-center gap-1 text-gray-500 hover:text-brand-red">
                                    <span class="text-[10px] font-sans">کپی</span>
                                    <i data-lucide="copy" class="w-4 h-4"></i>
                                </div>
                            </button>
                            <span id="toast-sheba"
                                class="absolute -top-9 left-1/2 -translate-x-1/2 bg-white text-brand-red font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">
                                شماره شبا کپی شد!
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($bankCard)): ?>
                        <div class="space-y-2 text-xs">
                            <span class="text-gray-400 block">شماره کارت:</span>
                            <div class="relative group">
                                <button type="button"
                                    onclick="copyText('<?= e(str_replace('-', '', $bankCard)) ?>', 'toast-card')"
                                    class="w-full bg-[#f8f6f0] text-black font-mono font-bold text-xs sm:text-sm py-3 px-3 rounded-xl border border-white/10 flex items-center justify-between hover:border-brand-red transition cursor-pointer"
                                    dir="ltr">
                                    <span class="tracking-widest"><?= e($bankCard) ?></span>
                                    <div class="flex items-center gap-1 text-gray-500 hover:text-brand-red">
                                        <span class="text-[10px] font-sans">کپی</span>
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                    </div>
                                </button>
                                <span id="toast-card"
                                    class="absolute -top-9 left-1/2 -translate-x-1/2 bg-white text-brand-red font-bold text-[10px] px-3 py-1.5 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-lg">
                                    شماره کارت کپی شد!
                                </span>
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

            </div>
        </div>
    </main>

    <?php include 'assets/php/footer.php'; ?>
    <script src="/assets/js/main.js"></script>

    <script>
        let subtotalAmount = 0;
        let discountAmount = 0;
        let iranLocationsData = [];

        // پایگاه داده محلی استان‌ها و شهرهای کلیدی جهت پاسخ‌دهی آنی در صورت قطعی API
        const fallbackLocations = [
            { name: "تهران", cities: ["تهران", "شهریار", "اسلامشهر", "ورامین", "ری", "دماوند", "پردیس", "قدس"] },
            { name: "کردستان", cities: ["سقز", "سنندج", "بانه", "مریوان", "قروه", "بیجار", "کامیاران", "دیواندره", "دهگلان"] },
            { name: "آذربایجان شرقی", cities: ["تبریز", "مراغه", "مرند", "میانه", "اهر", "بناب", "سراب"] },
            { name: "آذربایجان غربی", cities: ["ارومیه", "خوی", "بوکان", "مهاباد", "میاندوآب", "سلماس", "پیرانشهر", "نقده"] },
            { name: "اصفهان", cities: ["اصفهان", "کاشان", "خمینی شهر", "نجف آباد", "شاهین شهر", "شهرضا"] },
            { name: "فارس", cities: ["شیراز", "مرودشت", "جهرم", "فسا", "کازرون", "لارستان", "آباده"] },
            { name: "خراسان رضوی", cities: ["مشهد", "نیشابور", "سبزوار", "تربت حیدریه", "کاشمر", "قوچان"] },
            { name: "مازندران", cities: ["ساری", "بابل", "آمل", "قائم شهر", "بهشهر", "چالوس", "تنکابن", "نوشهر"] },
            { name: "گیلان", cities: ["رشت", "بندر انزلی", "لاهیجان", "لنگرود", "تالش", "آستارا", "صومعه سرا"] },
            { name: "البرز", cities: ["کرج", "فردیس", "کمال شهر", "نظرآباد", "محمدشهر", "هشتگرد"] },
            { name: "خوزستان", cities: ["اهواز", "دزفول", "آبادان", "بندر ماهشهر", "خرمشهر", "اندیمشک", "ایذه"] },
            { name: "کرمانشاه", cities: ["کرمانشاه", "اسلام آباد غرب", "کنگاور", "جوانرود", "صحنه", "پاوه"] },
            { name: "همدان", cities: ["همدان", "ملایر", "نهاوند", "تویسرکان", "اسدآباد", "بهار"] },
            { name: "یزد", cities: ["یزد", "میبد", "اردکان", "بافق", "مهریز"] },
            { name: "مرکزی", cities: ["اراک", "ساوه", "خمین", "محلات", "دلیجان"] },
            { name: "قزوین", cities: ["قزوین", "الوند", "تاکستان", "آبیک", "بویین زهرا"] },
            { name: "قم", cities: ["قم", "جعفریه", "کهک"] },
            { name: "سمنان", cities: ["سمنان", "شاهرود", "دامغان", "گرمسار", "مهدی شهر"] },
            { name: "زنجان", cities: ["زنجان", "ابهر", "خرمدره", "قیدار"] },
            { name: "لرستان", cities: ["خرم آباد", "بروجرد", "دورود", "کوهدشت", "الیگودرز", "نورآباد"] },
            { name: "کرمان", cities: ["کرمان", "سیرجان", "رفسنجان", "جیرفت", "بم", "زرند"] },
            { name: "هرمزگان", cities: ["بندرعباس", "میناب", "قشم", "کیش", "بندر لنگه", "حاجی آباد"] },
            { name: "بوشهر", cities: ["بوشهر", "برازجان", "بندر گناوه", "بندر کنگان", "خورموج", "عسلویه"] },
            { name: "گلستان", cities: ["گرگان", "گنبد کاووس", "علی آباد کتول", "بندر ترکمن", "آق قلا"] },
            { name: "اردبیل", cities: ["اردبیل", "پارس آباد", "مشگین شهر", "خلخال", "گرمی"] },
            { name: "سیستان و بلوچستان", cities: ["زاهدان", "زابل", "ایرانشهر", "چابهار", "سراوان", "خاش"] },
            { name: "چهارمحال و بختیاری", cities: ["شهرکرد", "بروجن", "لردگان", "فارسان"] },
            { name: "کهگیلویه و بویراحمد", cities: ["یاسوج", "دوگنبدان", "دهدشت"] },
            { name: "خراسان جنوبی", cities: ["بیرجند", "قائن", "طبس", "فردوس"] },
            { name: "خراسان شمالی", cities: ["بجنورد", "شیروان", "اسفراین"] },
            { name: "ایلام", cities: ["ایلام", "ایوان", "دهلران", "آبدانان"] }
        ];

        document.addEventListener('DOMContentLoaded', async () => {
            const cart = JSON.parse(localStorage.getItem('toyota_cart')) || [];
            document.getElementById('cart_data_input').value = JSON.stringify(cart);

            renderCheckoutItems(cart);
            updateUI();

            // دریافت لیست استان‌ها از API یا Fallback
            await initProvinces();

            const form = document.getElementById('checkout-form');
            form.addEventListener('submit', () => {
                const btn = document.getElementById('submit-order-btn');
                btn.disabled = true;
                btn.innerHTML = '<i data-lucide="loader-2" class="w-5 h-5 animate-spin"></i><span>در حال ثبت فاکتور و آپلود رسید...</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });

        // رندر کارت‌های اقلام در سایدبار فاکتور
        function renderCheckoutItems(cart) {
            const container = document.getElementById('checkout-items-list');
            const badge = document.getElementById('items-count-badge');
            if (!container) return;

            container.innerHTML = '';
            subtotalAmount = 0;
            let totalCount = 0;

            if (cart.length === 0) {
                container.innerHTML = '<div class="text-center py-8 text-gray-500 text-xs">سبد خرید شما خالی است.</div>';
                badge.innerText = '۰ قطعه';
                return;
            }

            cart.forEach(item => {
                const prod = item.product;
                const qty = item.quantity || 1;
                const price = parseFloat(prod.price) || 0;
                const lineTotal = price * qty;
                subtotalAmount += lineTotal;
                totalCount += qty;

                const imgSrc = (prod.images && prod.images[0]) ? '/image?id=' + encodeURIComponent(prod.images[0]) : '/assets/logo/logo.webp';

                const html = `
                <div class="flex items-center gap-3 bg-brand-dark/50 border border-white/5 p-3 rounded-2xl">
                    <img src="${imgSrc}" alt="${escapeHtml(prod.name)}" class="w-14 h-14 object-contain rounded-xl bg-brand-dark p-1 border border-white/10 shrink-0">
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-xs text-white truncate mb-0.5">${escapeHtml(prod.name)}</h4>
                        <div class="flex items-center justify-between text-[11px] text-gray-400">
                            <span>کد فنی: <span class="font-mono text-gray-300" dir="ltr">${escapeHtml(prod.oem || '---')}</span></span>
                            <span class="bg-brand-dark px-2 py-0.5 rounded text-white font-bold">${qty} عدد</span>
                        </div>
                        <div class="text-left mt-1">
                            <span class="text-xs font-black text-brand-red">${lineTotal.toLocaleString('fa-IR')} تومان</span>
                        </div>
                    </div>
                </div>
            `;
                container.insertAdjacentHTML('beforeend', html);
            });

            badge.innerText = `${totalCount} قطعه`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        // لود استان‌ها از API یا Fallback داخلی
        async function initProvinces() {
            const provinceSelect = document.getElementById('province-select');
            provinceSelect.innerHTML = '<option value="">انتخاب استان...</option>';

            try {
                const res = await fetch('https://iran-locations-api.ir/api/v1/fa/states');
                if (res.ok) {
                    iranLocationsData = await res.json();
                    iranLocationsData.forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p.name;
                        opt.textContent = p.name;
                        opt.dataset.id = p.id;
                        provinceSelect.appendChild(opt);
                    });
                    return;
                }
            } catch (e) {
                console.warn('استفاده از دیتابیس پشتیبان استان‌ها');
            }

            // در صورت عدم دسترسی به API اینترنتی
            fallbackLocations.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.name;
                opt.textContent = p.name;
                provinceSelect.appendChild(opt);
            });
        }

        // لود شهرها بر اساس استان انتخاب شده
        async function loadCities(provinceName, selectedCity = '') {
            const citySelect = document.getElementById('city-select');
            citySelect.innerHTML = '<option value="">انتخاب شهر...</option>';

            if (!provinceName) {
                citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
                return;
            }

            // تلاش برای دریافت از API اگر state id داریم
            const provOption = document.querySelector(`#province-select option[value="${provinceName}"]`);
            const stateId = provOption ? provOption.dataset.id : null;

            if (stateId) {
                try {
                    const res = await fetch(`https://iran-locations-api.ir/api/v1/fa/cities?state_id=${stateId}`);
                    if (res.ok) {
                        const data = await res.json();
                        const cities = data.cities || data;
                        cities.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.name;
                            opt.textContent = c.name;
                            if (selectedCity && c.name === selectedCity) opt.selected = true;
                            citySelect.appendChild(opt);
                        });
                        return;
                    }
                } catch (e) { }
            }

            // جستجو در داده‌های فال‌بک
            const found = fallbackLocations.find(p => p.name === provinceName);
            if (found && found.cities) {
                found.cities.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c;
                    opt.textContent = c;
                    if (selectedCity && c === selectedCity) opt.selected = true;
                    citySelect.appendChild(opt);
                });
            }
        }

        // منطق تغییر آدرس: در حالت نشانی جدید تمام ورودی‌ها خالی می‌شوند
        function handleAddressSelection(select) {
            const nameInput = document.getElementById('rec_name');
            const phoneInput = document.getElementById('rec_phone');
            const provinceSelect = document.getElementById('province-select');
            const citySelect = document.getElementById('city-select');
            const addressInput = document.getElementById('rec_address_detail');
            const postalInput = document.getElementById('rec_postal');
            const notesInput = document.getElementById('rec_notes');

            if (!select.value) {
                // حالت انتخاب: "-- ثبت نشانی جدید --" -> پاک‌سازی کامل
                nameInput.value = '';
                phoneInput.value = '';
                provinceSelect.value = '';
                citySelect.innerHTML = '<option value="">ابتدا استان را انتخاب کنید</option>';
                addressInput.value = '';
                postalInput.value = '';
                if (notesInput) notesInput.value = '';
                return;
            }

            try {
                const addr = JSON.parse(select.value);
                addressInput.value = addr.address_detail || '';
                postalInput.value = addr.postal_code || '';

                // تفکیک استان و شهر
                if (addr.province_city) {
                    const parts = addr.province_city.split(' - ');
                    const prov = parts[0] ? parts[0].trim() : '';
                    const city = parts[1] ? parts[1].trim() : '';

                    provinceSelect.value = prov;
                    loadCities(prov, city);
                }
            } catch (e) {
                console.error('Error parsing address JSON:', e);
            }
        }

        function updateUI() {
            const finalPrice = Math.max(0, subtotalAmount - discountAmount);
            document.getElementById('cart-subtotal').innerText = subtotalAmount.toLocaleString('fa-IR') + ' تومان';
            document.getElementById('final-price').innerText = finalPrice.toLocaleString('fa-IR') + ' تومان';

            const discountRow = document.getElementById('discount-row');
            if (discountAmount > 0) {
                discountRow.classList.remove('hidden');
                document.getElementById('discount-amount').innerText = discountAmount.toLocaleString('fa-IR') + ' تومان';
            } else {
                discountRow.classList.add('hidden');
            }
        }

        async function validateCouponAjax() {
            const code = document.getElementById('discount_code').value.trim();
            const msgEl = document.getElementById('discount-message');
            const appliedInput = document.getElementById('applied_discount_input');
            const btn = document.getElementById('btn-apply-coupon');

            if (!code) {
                msgEl.className = 'mt-2 text-xs font-bold px-3 py-2 rounded-lg border bg-amber-500/10 text-amber-500 border-amber-500/30 block';
                msgEl.innerText = 'لطفاً کد تخفیف را وارد کنید.';
                return;
            }

            btn.disabled = true;
            btn.innerText = '...';

            try {
                const res = await fetch('/api/checkout/validate-coupon', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': getCsrfToken()
                    },
                    body: JSON.stringify({ code: code, subtotal: subtotalAmount })
                });

                const data = await res.json();
                btn.disabled = false;
                btn.innerText = 'اعمال';

                msgEl.classList.remove('hidden');
                if (res.ok && data.valid) {
                    discountAmount = parseFloat(data.discount) || 0;
                    appliedInput.value = data.code;
                    msgEl.className = 'mt-2 text-xs font-bold px-3 py-2 rounded-lg border bg-emerald-500/10 text-emerald-500 border-emerald-500/30 block';
                    msgEl.innerText = data.message;
                } else {
                    discountAmount = 0;
                    appliedInput.value = '';
                    msgEl.className = 'mt-2 text-xs font-bold px-3 py-2 rounded-lg border bg-rose-500/10 text-rose-500 border-rose-500/30 block';
                    msgEl.innerText = data.message || 'کد تخفیف نامعتبر است.';
                }
                updateUI();
            } catch (e) {
                btn.disabled = false;
                btn.innerText = 'اعمال';
                msgEl.className = 'mt-2 text-xs font-bold px-3 py-2 rounded-lg border bg-rose-500/10 text-rose-500 border-rose-500/30 block';
                msgEl.innerText = 'خطا در برقراری ارتباط با سرور.';
            }
        }

        function previewReceiptFile(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const placeholder = document.getElementById('upload-placeholder');
                const previewContainer = document.getElementById('file-preview-container');
                const previewImg = document.getElementById('receipt-preview-img');
                const nameDisplay = document.getElementById('file-name-display');

                nameDisplay.innerText = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';

                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        previewImg.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImg.classList.add('hidden');
                }

                placeholder.classList.add('hidden');
                previewContainer.classList.remove('hidden');
            }
        }

        function copyText(text, toastId) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById(toastId);
                if (toast) {
                    toast.classList.remove('opacity-0');
                    toast.classList.add('opacity-100');
                    setTimeout(() => {
                        toast.classList.remove('opacity-100');
                        toast.classList.add('opacity-0');
                    }, 2000);
                }
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    </script>
</body>

</html>