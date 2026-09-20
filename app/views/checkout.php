<!doctype html>
<html lang="fa" dir="rtl" class="scroll-smooth">

<head>
    <?php include 'assets/php/head.php'; ?>
</head>

<body class="bg-[#F8F6F0] text-[#251E1B] overflow-x-hidden antialiased flex flex-col min-h-screen font-sans">
    <?php include 'assets/php/header.php'; ?>

    <?php
    global $settings;
    $bankName = $settings['bank_name'] ?? 'بانک ملت';
    $bankSheba = $settings['bank_sheba'] ?? 'IR580120000000001234567890';
    $bankCard = $settings['bank_card_number'] ?? '';
    $bankOwner = $settings['bank_account_owner'] ?? ($settings['site_title'] ?? 'پرادو یدک');

    // تفکیک ۴ کاراکتری شماره شبا جهت خوانایی استاندارد بانکی
    $cleanSheba = preg_replace('/[^a-zA-Z0-9]/', '', strtoupper($bankSheba));
    $formattedSheba = trim(chunk_split($cleanSheba, 4, ' '));

    // تبدیل و تفکیک ۴ رقمی شماره کارت
    $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $cleanCard = str_replace($persianDigits, $englishDigits, $bankCard);
    $cleanCard = preg_replace('/\D/', '', $cleanCard);
    $formattedCard = !empty($cleanCard) ? trim(chunk_split($cleanCard, 4, '  -  ')) : '';
    ?>

    <main class="flex-1 max-w-7xl mx-auto px-4 py-8 sm:py-12 w-full">

        <!-- عنوان و مسیر بالای صفحه -->
        <div class="mb-8 space-y-2">
            <div
                class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#8B533A]/10 border border-[#8B533A]/20 text-[#8B533A] text-xs font-bold">
                <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                <span>درگاه امن ثبت مستقیم فاکتور و قطعات اصلی</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-[#251E1B]">تکمیل خرید و تسویه نهایی</h1>
            <p class="text-xs sm:text-sm text-[#5F605C]">مشخصات گیرنده را وارد کرده و پس از واریز، رسید پرداخت را آپلود
                نمایید.</p>
        </div>

        <!-- اطلاعیه‌های ضروری بالای صفحه -->
        <?php if (!empty($notices)): ?>
            <div class="space-y-3 mb-8">
                <?php foreach ($notices as $notice):
                    $type = $notice['type'] ?? 'info';
                    if ($type === 'warning') {
                        $boxStyle = 'bg-amber-50/80 border-amber-200 text-amber-900';
                        $iconColor = 'text-amber-600';
                    } elseif ($type === 'danger') {
                        $boxStyle = 'bg-rose-50/80 border-rose-200 text-rose-900';
                        $iconColor = 'text-rose-600';
                    } else {
                        $boxStyle = 'bg-[#F0EBE1] border-[#d8cfc4] text-[#251E1B]';
                        $iconColor = 'text-[#8B533A]';
                    }
                    ?>
                    <div class="border rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-xs <?= $boxStyle ?>">
                        <i data-lucide="<?= e($notice['icon'] ?: 'info') ?>"
                            class="w-5 h-5 shrink-0 mt-0.5 <?= $iconColor ?>"></i>
                        <div class="text-xs sm:text-sm leading-relaxed">
                            <h4 class="font-bold mb-0.5"><?= e($notice['title']) ?></h4>
                            <p class="opacity-90"><?= nl2br(e($notice['message'])) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['checkout_error'])): ?>
            <div
                class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs sm:text-sm font-bold flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 text-rose-600"></i>
                <span><?= e($_SESSION['checkout_error']);
                unset($_SESSION['checkout_error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- ساختار گرید دو ستونه تفکیک‌شده (کاملاً ثابت و طبیعی) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- ستون اقلام فاکتور (ثابت و بدون sticky) -->
            <div class="lg:col-span-5 space-y-6 order-1 lg:order-2">

                <div
                    class="bg-white border border-[#E8E2D9] rounded-3xl p-5 sm:p-7 shadow-[0_10px_30px_rgba(43,23,12,0.03)] space-y-5">
                    <div class="flex items-center justify-between pb-4 border-b border-[#F0EBE1]">
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-8 h-8 rounded-xl bg-[#8B533A]/10 text-[#8B533A] flex items-center justify-center">
                                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                            </div>
                            <h3 class="font-black text-sm sm:text-base text-[#251E1B]">اقلام سفارش شما</h3>
                        </div>
                        <span id="items-count-badge"
                            class="bg-[#F8F6F0] border border-[#E8E2D9] text-xs px-3 py-1 rounded-full text-[#5F605C] font-bold">
                            ۰ قطعه
                        </span>
                    </div>

                    <!-- لیست محصولات سفارش -->
                    <div id="checkout-items-list" class="space-y-3 max-h-[340px] overflow-y-auto pr-1">
                        <!-- اقلام سبد توسط checkout.js تزریق می‌شوند -->
                    </div>

                    <!-- محاسبه مالی فاکتور با تایپوگرافی اصلاح‌شده -->
                    <div class="pt-4 border-t border-[#F0EBE1] space-y-3 text-xs text-[#5F605C]">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-sm">جمع کل قطعات:</span>
                            <span id="cart-subtotal" class="font-extrabold text-sm text-[#251E1B]">۰ تومان</span>
                        </div>

                        <div id="discount-row" class="flex justify-between items-center text-emerald-600 hidden">
                            <span class="font-medium text-sm">تخفیف اعمال‌شده:</span>
                            <span id="discount-amount" class="font-extrabold text-sm">۰ تومان</span>
                        </div>

                        <div class="h-px bg-[#F0EBE1] my-2"></div>

                        <div class="flex justify-between items-center">
                            <span class="font-bold text-[#251E1B] text-sm">مبلغ نهایی قابل واریز:</span>
                            <span id="final-price" class="text-xl sm:text-2xl font-black text-emerald-600">۰
                                تومان</span>
                        </div>
                    </div>

                    <!-- باکس تضمین اصالت -->
                    <div
                        class="bg-[#F8F6F0] border border-[#E8E2D9] rounded-2xl p-4 text-[11px] text-[#5F605C] flex items-start gap-2.5 leading-relaxed">
                        <i data-lucide="shield-check" class="w-4 h-4 text-[#8B533A] shrink-0 mt-0.5"></i>
                        <span>تمامی قطعات پیش از ارسال از نظر انطباق فنی با شماره شاسی خودرو (VIN) بازبینی شده و با
                            بسته‌بندی ضدضربه ارسال خواهند شد.</span>
                    </div>
                </div>

            </div>

            <!-- ستون فرم اطلاعات گیرنده و پرداخت -->
            <div class="lg:col-span-7 space-y-6 order-2 lg:order-1">
                <form action="/checkout/process" method="POST" enctype="multipart/form-data" id="checkout-form"
                    class="space-y-6" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="cart_data" id="cart_data_input" value="">
                    <input type="hidden" name="applied_discount_code" id="applied_discount_input" value="">

                    <!-- ============================================== -->
                    <!-- مرحله ۱: مشخصات تحویل‌گیرنده و نشانی پستی -->
                    <!-- ============================================== -->
                    <div
                        class="bg-white border border-[#E8E2D9] rounded-3xl p-6 sm:p-8 shadow-[0_10px_30px_rgba(43,23,12,0.03)] space-y-6">
                        <div class="flex items-center gap-3 pb-4 border-b border-[#F0EBE1]">
                            <div
                                class="w-10 h-10 rounded-2xl bg-[#8B533A]/10 text-[#8B533A] flex items-center justify-center font-black text-sm shrink-0">
                                ۱
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-[#251E1B]">مشخصات تحویل‌گیرنده و آدرس
                                    ارسال</h2>
                                <p class="text-xs text-[#5F605C]">اطلاعات دقیق پستی جهت صدور بارنامه و تحویل مرسوله</p>
                            </div>
                        </div>

                        <!-- دراپ‌داون آدرس‌های پیشین -->
                        <?php if (!empty($savedAddresses)): ?>
                            <div class="bg-[#F8F6F0] border border-[#E8E2D9] p-4 rounded-2xl">
                                <label class="block text-xs font-bold text-[#251E1B] mb-2">انتخاب از آدرس‌های قبلی
                                    شما:</label>
                                <select id="saved-addresses-select"
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-xs text-[#251E1B] focus:border-[#8B533A] cursor-pointer">
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
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">نام و نام‌خانوادگی
                                    تحویل‌گیرنده *</label>
                                <input type="text" name="recipient_name" id="rec_name" required
                                    value="<?= e($currentUser['full_name'] ?? '') ?>" placeholder="مثال: علی محمدی"
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">شماره همراه تحویل‌گیرنده
                                    *</label>
                                <input type="tel" name="recipient_phone" id="rec_phone" required dir="ltr"
                                    value="<?= e($currentUser['phone'] ?? '') ?>" placeholder="09189998852"
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-left font-mono text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition">
                            </div>
                        </div>

                        <!-- استان و شهر از دیتابیس لوکال -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">استان *</label>
                                <select name="province" id="province-select" required
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition cursor-pointer">
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
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">شهر *</label>
                                <select name="city" id="city-select" required disabled
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition cursor-pointer disabled:opacity-50 disabled:bg-[#F8F6F0] disabled:cursor-not-allowed">
                                    <option value="">ابتدا استان را انتخاب کنید</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#251E1B] mb-1.5">نشانی پستی دقیق (خیابان، پلاک،
                                واحد) *</label>
                            <textarea name="address_detail" id="rec_address_detail" rows="2" required
                                placeholder="مثال: میدان آزادی، خیابان فردوسی، کوچه بهار ۴، پلاک ۱۲، زنگ ۲"
                                class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition resize-none"></textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">کد پستی ۱۰ رقمی *</label>
                                <input type="text" name="postal_code" id="rec_postal" dir="ltr" maxlength="10" required
                                    pattern="[0-9]{10}" inputmode="numeric" placeholder="مثال: 6681898204"
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm font-mono text-center text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#251E1B] mb-1.5">یادداشت سفارش یا کد VIN
                                    (اختیاری)</label>
                                <input type="text" name="user_notes" id="rec_notes"
                                    placeholder="شماره شاسی خودرو جهت تطابق مضاعف..."
                                    class="w-full bg-white border border-[#D5CAC0] rounded-xl px-4 py-3 text-sm text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition">
                            </div>
                        </div>

                        <!-- ================= بخش انتخاب شیوه ارسال و باکس توضیحات پس‌کرایه ================= -->
                        <div class="space-y-4 pt-4 border-t border-[#F0EBE1]" id="shipping-method-section">
                            <label class="block text-xs font-bold text-[#251E1B]">
                                انتخاب شیوه ارسال مرسوله *
                            </label>

                            <?php if (!empty($shippingMethods)): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <?php foreach ($shippingMethods as $index => $sm): ?>
                                        <label
                                            class="relative flex items-center gap-3 p-3.5 bg-[#FAF8F5] border border-[#D5CAC0] rounded-2xl cursor-pointer hover:border-[#8B533A] transition has-checked:border-[#8B533A] has-checked:bg-[#8B533A]/5 has-checked:ring-1 has-checked:ring-[#8B533A]">
                                            <input type="radio" name="shipping_method_id" value="<?= (int) $sm['id'] ?>"
                                                class="accent-[#8B533A] w-4 h-4" <?= $index === 0 ? 'checked' : '' ?>>
                                            <div class="text-xs">
                                                <span class="block font-bold text-[#251E1B]"><?= e($sm['title']) ?></span>
                                                <?php if (!empty($sm['subtitle'])): ?>
                                                    <span class="text-[10px] text-[#5F605C]"><?= e($sm['subtitle']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-3 rounded-xl bg-rose-50 text-rose-700 text-xs border border-rose-200">
                                    شیوه ارسالی تعریف نشده است. لطفاً با پشتیبانی تماس بگیرید.
                                </div>
                            <?php endif; ?>

                            <!-- باکس توضیحات هزینه ارسال (پس‌کرایه) -->
                            <div
                                class="bg-amber-50/90 border border-amber-200/90 rounded-2xl p-4 flex items-start gap-3 shadow-xs">
                                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
                                <div class="text-xs leading-relaxed text-amber-950 space-y-1">
                                    <h5 class="font-bold text-amber-900">هزینه ارسال به‌صورت «پس‌کرایه» (پرداخت هنگام
                                        تحویل):</h5>
                                    <p>
                                        با توجه به متغیر بودن ابعاد، وزن و نوع بسته‌بندی لوازم یدکی، کرایه حمل در
                                        پیش‌فاکتور محاسبه نشده است؛ بنابراین <strong>پرداخت کل هزینه ارسال کاملاً بر
                                            عهده خریدار بوده و باید هنگام تحویل کالا، مستقیماً به مأمور ارسال یا شرکت
                                            باربری پرداخت شود.</strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- مرحله ۲: واریز وجه و بارگذاری فیش بانکی -->
                    <!-- ============================================== -->
                    <div
                        class="bg-white border border-[#E8E2D9] rounded-3xl p-6 sm:p-8 shadow-[0_10px_30px_rgba(43,23,12,0.03)] space-y-6">

                        <div class="flex items-center gap-3 pb-4 border-b border-[#F0EBE1]">
                            <div
                                class="w-10 h-10 rounded-2xl bg-[#8B533A]/10 text-[#8B533A] flex items-center justify-center font-black text-sm shrink-0">
                                ۲
                            </div>
                            <div>
                                <h2 class="text-base sm:text-lg font-black text-[#251E1B]">واریز وجه و پیوست فیش بانکی
                                </h2>
                                <p class="text-xs text-[#5F605C]">واریز به شماره حساب رسمی و ثبت فیش جهت تایید کارشناس
                                    مالی</p>
                            </div>
                        </div>

                        <!-- باکس راهنمای فرآیند پرداخت (رنگ سبز ملایم و خوانا) -->
                        <div
                            class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-4 sm:p-5 text-xs leading-relaxed space-y-2">
                            <div class="flex items-center gap-2 font-black text-emerald-800 text-xs sm:text-sm">
                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                                <span>دستورالعمل پرداخت سفارش</span>
                            </div>
                            <p class="text-emerald-950 font-medium">
                                لطفاً دقیقاً مبلغ
                                <strong id="instruction-payable-amount"
                                    class="text-emerald-700 font-black text-sm sm:text-base px-2.5 py-1 bg-white border border-emerald-300 rounded-lg inline-block mx-1 shadow-2xs">
                                    ۰ تومان
                                </strong>
                                را به یکی از حساب‌های زیر انتقال داده و تصویر رسید را در کادر زیر بارگذاری نمایید.
                            </p>
                        </div>

                        <!-- کارت اطلاعات حساب بانکی -->
                        <div
                            class="bg-gradient-to-br from-[#FAF8F5] to-[#F3EEE6] border border-[#E0D6CB] rounded-2xl p-5 sm:p-6 space-y-5 shadow-xs">

                            <!-- هدر کارت بانکی -->
                            <div class="flex items-center justify-between pb-3 border-b border-[#E8E0D5]">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-9 h-9 rounded-xl bg-white border border-[#E0D6CB] text-[#8B533A] flex items-center justify-center shadow-2xs">
                                        <i data-lucide="landmark" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="block text-[11px] text-[#5F605C]">بانک عامل</span>
                                        <strong class="text-sm font-black text-[#251E1B]"><?= e($bankName) ?></strong>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <span class="block text-[11px] text-[#5F605C]">صاحب حساب رسمی</span>
                                    <strong
                                        class="text-xs sm:text-sm font-bold text-[#251E1B]"><?= e($bankOwner) ?></strong>
                                </div>
                            </div>

                            <!-- شماره شبا (IBAN) -->
                            <div class="space-y-1.5">
                                <span class="text-[11px] font-bold text-[#5F605C] block">شماره شبای حساب (شبا):</span>
                                <div
                                    class="bg-white border border-[#D8CEBF] rounded-xl p-3 flex items-center justify-between gap-3 shadow-2xs group hover:border-[#8B533A] transition">
                                    <span
                                        class="font-mono text-xs sm:text-sm font-bold text-[#251E1B] tracking-[0.14em] select-all truncate text-left"
                                        dir="ltr">
                                        <?= e($formattedSheba) ?>
                                    </span>
                                    <div class="relative shrink-0">
                                        <button type="button" id="btn-copy-sheba" data-copy="<?= e($cleanSheba) ?>"
                                            class="px-3 py-1.5 rounded-lg bg-[#F8F6F0] hover:bg-emerald-600 text-[#251E1B] hover:!text-white border border-[#D8CEBF] hover:border-emerald-600 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            <span>کپی شبا</span>
                                        </button>
                                        <span id="toast-sheba"
                                            class="absolute -top-9 left-1/2 -translate-x-1/2 bg-emerald-600 !text-white font-bold text-xs px-2.5 py-1 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-md z-20">
                                            ✓ کپی شد!
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- شماره کارت بانکی -->
                            <?php if (!empty($bankCard)): ?>
                                <div class="space-y-1.5">
                                    <span class="text-[11px] font-bold text-[#5F605C] block">شماره کارت بانکی:</span>
                                    <div
                                        class="bg-white border border-[#D8CEBF] rounded-xl p-3 flex items-center justify-between gap-3 shadow-2xs group hover:border-[#8B533A] transition">
                                        <span
                                            class="font-mono text-sm sm:text-base font-black text-[#251E1B] tracking-[0.18em] select-all truncate text-left"
                                            dir="ltr">
                                            <?= e($formattedCard) ?>
                                        </span>
                                        <div class="relative shrink-0">
                                            <button type="button" id="btn-copy-card" data-copy="<?= e($cleanCard) ?>"
                                                class="px-3 py-1.5 rounded-lg bg-[#F8F6F0] hover:bg-emerald-600 text-[#251E1B] hover:!text-white border border-[#D8CEBF] hover:border-emerald-600 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer shadow-2xs">
                                                <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                                <span>کپی کارت</span>
                                            </button>
                                            <span id="toast-card"
                                                class="absolute -top-9 left-1/2 -translate-x-1/2 bg-emerald-600 !text-white font-bold text-xs px-2.5 py-1 rounded-lg opacity-0 transition-opacity duration-300 pointer-events-none whitespace-nowrap shadow-md z-20">
                                                ✓ کپی شد!
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- بخش آپلود فیش بانکی -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-[#251E1B] flex items-center gap-2">
                                <i data-lucide="upload" class="w-4 h-4 text-[#8B533A]"></i>
                                تصویر یا فایل فیش واریز شده *
                            </label>
                            <div
                                class="relative border-2 border-dashed border-[#D5CAC0] hover:border-[#8B533A] rounded-2xl p-6 text-center transition bg-[#FAF7F2] group cursor-pointer">
                                <input type="file" name="receipt_image" id="receipt-file-input" required
                                    accept="image/jpeg,image/png,image/webp,application/pdf"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

                                <div id="upload-placeholder" class="space-y-2">
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-white border border-[#E0D6CB] text-[#8B533A] flex items-center justify-center mx-auto shadow-2xs group-hover:scale-105 transition">
                                        <i data-lucide="file-up" class="w-6 h-6"></i>
                                    </div>
                                    <p class="text-xs font-bold text-[#251E1B]">جهت انتخاب یا رها کردن فایل کلیک کنید
                                    </p>
                                    <p class="text-[10px] text-[#5F605C]">فرمت‌های معتبر: JPG, PNG, WEBP یا PDF (حداکثر
                                        ۵ مگابایت)</p>
                                </div>

                                <div id="file-preview-container" class="hidden flex flex-col items-center gap-2">
                                    <img id="receipt-preview-img" src="" alt="پیش‌نمایش فیش"
                                        class="max-h-44 rounded-xl object-contain border border-[#E0D6CB] shadow-sm">
                                    <span id="file-name-display"
                                        class="text-xs text-[#251E1B] font-mono font-bold"></span>
                                    <span class="text-[10px] text-[#8B533A]">برای تعویض فایل دوباره کلیک کنید</span>
                                </div>
                            </div>
                        </div>

                        <!-- کوپن و کد تخفیف -->
                        <div class="bg-[#F8F6F0] border border-[#E8E2D9] p-4 rounded-2xl">
                            <label class="block text-xs font-bold text-[#251E1B] mb-2 flex items-center gap-1.5">
                                <i data-lucide="tag" class="w-3.5 h-3.5 text-[#8B533A]"></i>
                                <span>کد تخفیف دارید؟</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="discount_code" placeholder="کد را وارد کنید (مثال: PRADO10)"
                                    dir="ltr"
                                    class="flex-1 bg-white border border-[#D5CAC0] rounded-xl px-4 py-2.5 text-xs sm:text-sm font-mono uppercase tracking-widest text-[#251E1B] focus:outline-none focus:border-[#8B533A] transition">
                                <button type="button" id="btn-apply-coupon"
                                    class="px-5 py-2.5 rounded-xl bg-white hover:bg-[#8B533A] hover:text-white border border-[#D5CAC0] hover:border-[#8B533A] text-xs font-bold text-[#251E1B] transition active:scale-95 cursor-pointer shadow-2xs">
                                    اعمال کد
                                </button>
                            </div>
                            <div id="discount-message"
                                class="hidden mt-2 text-xs font-bold px-3 py-2 rounded-xl border"></div>
                        </div>

                        <!-- دکمه نهایی اقدام (سبز رنگ و باوقار) -->
                        <button type="submit" id="submit-order-btn"
                            class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-extrabold py-4 rounded-2xl text-sm transition shadow-[0_6px_20px_rgba(5,150,105,0.25)] flex items-center justify-center gap-2 cursor-pointer">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                            <span>ثبت نهایی فاکتور و ارسال رسید به انبار</span>
                        </button>

                    </div>
                </form>
            </div>

        </div>
    </main>

    <?php include 'assets/php/footer.php'; ?>

    <!-- اسکریپت‌های تسویه حساب -->
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/checkout.js"></script>
</body>

</html>