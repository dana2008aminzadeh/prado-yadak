<?php

global $settings;

if (!isset($is_logged_in)) {
    $is_logged_in = false;
}

if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}
?>
<footer class="bg-black border-t border-white/10 py-10 mt-auto">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">

            <!-- ستون ۱: معرفی مجموعه -->
            <div class="md:col-span-1">
                <a href="/index" class="flex items-center gap-3 mb-4">
                    <img src="assets/logo/logo.webp" alt="پرادو یدک"
                        class="h-10 sm:h-12 w-auto object-contain transition-transform duration-300 group-hover:scale-105">
                    <div>
                        <h4 class="font-bold text-white" id="footer-title">
                            <?= e($settings['site_title'] ?? 'پرادو یدک') ?></h4>
                        <p class="text-xs text-gray-500"><?= e($settings['site_subtitle'] ?? 'PRADO YADAK') ?></p>
                    </div>
                </a>
                <?php if (in_array($current_page, ['/blog', '/blog-detail'])): ?>
                    <p class="text-gray-400 text-sm leading-relaxed mb-3">مرجع تخصصی مقالات آموزشی، عیب‌یابی خودروهای
                        تویوتا، راهنمای نگهداری و شیوه‌های تشخیص قطعات اصلی از تقلبی.</p>
                <?php elseif (in_array($current_page, ['/parts', '/product'])): ?>
                    <p class="text-gray-400 text-sm leading-relaxed mb-3">تأمین‌کننده تخصصی قطعات اصلی تویوتا و لکسوس با
                        ضمانت ۱۰۰٪ اصالت کالا و تطابق با شماره شاسی (VIN).</p>
                <?php else: ?>
                    <p class="text-gray-400 text-sm leading-relaxed mb-3">تأمین‌کننده تخصصی قطعات اصلی تویوتا و لکسوس با بیش
                        از ۱۵ سال تجربه، ضمانت اصالت و ارسال سریع به سراسر کشور.</p>
                <?php endif; ?>
                <p class="text-xs text-gray-500 flex items-start gap-1.5 mt-3 leading-relaxed">
                    <i data-lucide="map-pin" style="width:16px;height:16px;" class="text-brand-red shrink-0 mt-1"></i>
                    <span><?= e($settings['address'] ?? 'استان کردستان، سقز، جاده کانی جژنی، صنوف آلاینده-۲، پلاک ۳۵۰، فروشگاه پرادو یدک - کد پستی: ۶۶۸۱۸۹۸۲۰۴') ?></span>
                </p>
            </div>

            <div>
                <?php if (in_array($current_page, ['/blog', '/blog-detail'])): ?>
                    <h4 class="font-bold mb-4 text-brand-accent text-sm">دسته‌بندی موضوعی</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/blog" onclick="filterBlog('genuine')" class="hover:text-white transition">تشخیص اصالت
                                قطعه</a></li>
                        <li><a href="/blog" onclick="filterBlog('technical')" class="hover:text-white transition">آموزش و
                                سرویس‌های فنی</a></li>
                        <li><a href="/blog" onclick="filterBlog('maintenance')" class="hover:text-white transition">راهنمای
                                نگهداری خودرو</a></li>
                        <li><a href="/blog" class="hover:text-white transition">معرفی روغن‌ها و روانکارها</a></li>
                    </ul>
                <?php else: ?>
                    <h4 class="font-bold mb-4 text-brand-accent text-sm">بخش‌های اصلی</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/index" class="hover:text-white transition">صفحه اصلی</a></li>
                        <li><a href="/parts" class="hover:text-white transition">کاتالوگ قطعات</a></li>
                        <li><a href="/blog" class="hover:text-white transition">وبلاگ فنی</a></li>
                        <li><a href="/terms" class="hover:text-white transition">قوانین و ضمانت اصالت</a></li>

                        <?php if ($is_logged_in): ?>
                            <li><a href="/profile" class="hover:text-white transition text-brand-red font-bold">پنل کاربری
                                    من</a></li>
                        <?php else: ?>
                            <li><a href="/login" class="hover:text-white transition">ورود / ثبت‌نام در سایت</a></li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div>
                <?php if (in_array($current_page, ['/blog', '/blog-detail'])): ?>
                    <h4 class="font-bold mb-4 text-brand-accent text-sm">مقالات پربازدید</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/blog-detail?id=1" class="hover:text-white transition line-clamp-1">تشخیص شمع اصلی
                                تویوتا از تقلبی</a></li>
                        <li><a href="/blog-detail?id=1" class="hover:text-white transition line-clamp-1">زمان تعویض تسمه
                                تایم کمری و پرادو</a></li>
                        <li><a href="/blog-detail?id=1" class="hover:text-white transition line-clamp-1">راهنمای انتخاب روغن
                                گیربکس ATF</a></li>
                    </ul>
                <?php else: ?>
                    <h4 class="font-bold mb-4 text-brand-accent text-sm">مدل‌های خودرو</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/parts?model=camry" class="hover:text-white transition">تویوتا کمری</a></li>
                        <li><a href="/parts?model=landcruiser" class="hover:text-white transition">تویوتا لندکروزر</a></li>
                        <li><a href="/parts?model=prado" class="hover:text-white transition">تویوتا پرادو</a></li>
                        <li><a href="/parts?model=hilux" class="hover:text-white transition">تویوتا هایلوکس</a></li>
                    </ul>
                <?php endif; ?>
            </div>

            <div>
                <h4 class="font-bold mb-4 text-brand-accent text-sm">مشاوره و راه‌های ارتباطی</h4>
                <div class="space-y-2 text-xs text-gray-400 mb-4">
                    <p class="flex items-center gap-2">
                        <i data-lucide="phone" style="width:14px;height:14px;" class="text-brand-red"></i>
                        تلفن: <a href="tel:<?= e($settings['phone_number'] ?? '09189998852') ?>"
                            class="text-white font-mono hover:text-brand-red transition"><?= e($settings['phone_number'] ?? '09189998852') ?></a>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="message-circle" style="width:14px;height:14px;" class="text-emerald-500"></i>
                        واتساپ: <a href="<?= e($settings['whatsapp_link'] ?? 'https://wa.me/989189998852') ?>"
                            target="_blank"
                            class="text-white font-mono hover:text-emerald-400 transition"><?= e($settings['phone_number'] ?? '09189998852') ?></a>
                    </p>
                    <p class="flex items-center gap-2">
                        <i data-lucide="clock" style="width:14px;height:14px;" class="text-brand-red"></i>
                        ساعات کاری: <?= e($settings['work_hours'] ?? 'شنبه تا پنجشنبه ۹ الی ۱۸') ?>
                    </p>
                </div>

                <div class="flex gap-2">
                    <a href="<?= e($settings['whatsapp_link'] ?? 'https://wa.me/989189998852') ?>" target="_blank"
                        class="w-8 h-8 bg-brand-grey rounded-lg flex items-center justify-center border border-white/10 hover:border-[#25D366] text-gray-400 hover:text-[#25D366] transition">
                        <i data-lucide="message-circle" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="tel:<?= e($settings['phone_number'] ?? '09189998852') ?>"
                        class="w-8 h-8 bg-brand-grey rounded-lg flex items-center justify-center border border-white/10 hover:border-brand-red text-gray-400 hover:text-brand-red transition">
                        <i data-lucide="phone" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="<?= e($settings['instagram_link'] ?? 'https://www.instagram.com/toyota_yadak_hilux/') ?>"
                        class="w-8 h-8 bg-brand-grey rounded-lg flex items-center justify-center border border-white/10 hover:border-[#E1306C] text-gray-400 hover:text-[#E1306C] transition">
                        <i data-lucide="instagram" style="width:16px;height:16px;"></i>
                    </a>
                    <a href="<?= e($settings['telegram_link'] ?? 'https://t.me/toyota_yadak_hilux') ?>"
                        class="w-8 h-8 bg-brand-grey rounded-lg flex items-center justify-center border border-white/10 hover:border-[#229ED9] text-gray-400 hover:text-[#229ED9] transition">
                        <i data-lucide="send" style="width:16px;height:16px;"></i>
                    </a>
                </div>
            </div>

        </div>

        <div
            class="border-t border-white/10 pt-6 text-center text-gray-500 text-xs flex flex-col sm:flex-row justify-between items-center gap-2">
            <span>© ۱۴۰۳ - ۱۴۰۵ پرادو یدک. تمامی حقوق محفوظ است.</span>
            <div class="flex gap-4">
                <a href="/index" class="hover:text-white transition">صفحه اصلی</a>
                <a href="/parts" class="hover:text-white transition">کاتالوگ قطعات</a>
                <a href="/terms" class="hover:text-white transition">قوانین و ضمانت</a>
            </div>
        </div>
    </div>
</footer>

<!-- کشوی سبد خرید -->
<div id="cart-drawer-overlay"
    class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden opacity-0 transition-opacity duration-300"
    onclick="toggleCart(false)"></div>

<div id="cart-drawer"
    class="fixed top-0 left-0 bottom-0 w-full sm:w-96 z-50 p-6 flex flex-col justify-between border-r border-[#a88d7c]/30 -translate-x-full transition-transform duration-300 ease-in-out hidden shadow-[0_0_50px_rgba(43,23,12,0.3)] bg-[#f5efe9]/95 backdrop-blur-xl">

    <div>
        <div class="flex items-center justify-between pb-4 border-b border-[#a88d7c]/30 mb-6">
            <div class="flex items-center gap-2">
                <i data-lucide="shopping-cart" class="text-brand-red" style="width:20px;height:20px;"></i>
                <span class="font-extrabold text-base text-[#2b170c]">سبد خرید شما</span>
            </div>
            <button class="p-1 rounded-lg transition text-[#5c473b] hover:bg-[#a88d7c]/20" onclick="toggleCart(false)">
                <i data-lucide="x" style="width:24px;height:24px;"></i>
            </button>
        </div>

        <!-- لیست قطعات -->
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1" id="cart-items-container">

            <!-- آیتم سبد خرید -->
            <div
                class="relative flex gap-3 p-3 rounded-xl border border-[#a88d7c]/30 bg-white/60 items-center transition shadow-sm group hover:-translate-y-0.5 hover:border-brand-red/50">

                <!-- دکمه ضربدر (حذف قطعه) -->
                <button
                    class="absolute top-2 left-2 transition-colors z-10 p-1 rounded-md text-[#a88d7c] hover:text-brand-red hover:bg-brand-red/10"
                    title="حذف از سبد">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>

                <!-- آیکون قطعه (لینک‌دار) -->
                <a href="/product?id=1"
                    class="w-16 h-16 rounded-lg flex items-center justify-center border border-[#a88d7c]/40 bg-[#eae0d6] flex-shrink-0 hover:scale-105 transition-transform text-brand-red">
                    <i data-lucide="wind" style="width:28px;height:28px;"></i>
                </a>

                <!-- اطلاعات محصول -->
                <div class="flex-1 min-w-0 pl-6">
                    <!-- عنوان قطعه (لینک‌دار) -->
                    <a href="/product?id=1" class="block transition-colors hover:opacity-80">
                        <h5 class="text-xs font-bold truncate text-[#2b170c]">رادیاتور آب کامل تویوتا کرولا</h5>
                    </a>
                    <p class="text-[10px] mt-0.5 text-[#5c473b]">OEM: 16400-0T040</p>

                    <div class="flex items-center justify-between mt-2">
                        <!-- دکمه های تعداد -->
                        <div
                            class="flex items-center gap-3 rounded-md px-2 py-1 text-xs shadow-sm border border-[#a88d7c]/30 bg-white">
                            <button class="font-bold transition-colors text-[#2b170c] hover:text-brand-red">+</button>
                            <span class="font-bold text-[#2b170c]">۱</span>
                            <button class="font-bold transition-colors text-[#2b170c] hover:text-brand-red">-</button>
                        </div>
                        <span class="text-xs font-black text-brand-red">۶,۳۰۰,۰۰۰ تومان</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- بخش تکمیل خرید -->
    <div class="pt-4 border-t border-[#a88d7c]/30 space-y-4 bg-transparent">
        <div class="flex items-center justify-between text-sm">
            <span class="font-bold text-[#5c473b]">جمع کل خرید:</span>
            <span class="font-black text-lg text-[#2b170c]" id="cart-total-price">۶,۳۰۰,۰۰۰ <span
                    class="text-sm font-normal">تومان</span></span>
        </div>

        <!-- دکمه تکمیل خرید -->
        <a href="/checkout"
            class="w-full py-3.5 rounded-xl font-bold text-sm transition flex items-center justify-center gap-2 bg-brand-red text-white hover:bg-red-700 shadow-[0_4px_20px_rgba(225,6,0,0.3)]">
            <i data-lucide="credit-card" style="width:18px;height:18px;"></i> تکمیل فرآیند خرید
        </a>
    </div>
</div>