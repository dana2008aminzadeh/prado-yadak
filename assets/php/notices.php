<?php
/**
 * پارشیال مشترک نمایش اطلاعیه‌های سایت
 * ---------------------------------------------------------------------------
 * این فایل در assets/php/header.php برای «همه» صفحات فروشگاه گنجانده می‌شود؛
 * بنابراین اطلاعیه‌ای که از پنل مدیریت برای صفحه اصلی، کاتالوگ قطعات، صفحه
 * محصول، وبلاگ، تسویه حساب، ورود، پنل کاربری یا قوانین ثبت شده باشد،
 * همان‌جا (به‌علاوه اطلاعیه‌های «همه صفحات») نمایش داده می‌شود.
 *
 * متغیرهای اختیاری (اگر کنترلر از قبل آماده کرده باشد):
 *   $notices         — آرایه اطلاعیه‌های واکشی‌شده
 *   $notice_page_key — کلید صفحه؛ پیش‌فرض از URI جاری تشخیص داده می‌شود
 *
 * اطلاعیه‌های کشوی سبد خرید سمت کلاینت و از مسیر /api/notices?page=cart
 * بارگذاری می‌شوند و به این پارشیال ربطی ندارند.
 *
 * توجه: این فایل در scope صفحه اجرا می‌شود؛ متغیرهای داخلی با پیشوند
 * «notice_» نام‌گذاری شده‌اند تا با متغیرهای view تداخل نکنند.
 */

if (!isset($notices) || !is_array($notices)) {
    $notice_page_key = $notice_page_key ?? \App\models\Notice::pageKeyFromUri($_SERVER['REQUEST_URI'] ?? '/');
    $notices = \App\models\Notice::getForPage($notice_page_key);
}
?>
<?php if (!empty($notices)): ?>
    <section aria-label="اطلاعیه‌های سایت" class="max-w-7xl mx-auto w-full px-4 pt-5 space-y-3">
        <?php foreach ($notices as $notice_item):
            $notice_type = $notice_item['type'] ?? 'info';
            if ($notice_type === 'warning') {
                $notice_box = 'bg-amber-50/80 border-amber-200 text-amber-900';
                $notice_icon_color = 'text-amber-600';
            } elseif ($notice_type === 'danger') {
                $notice_box = 'bg-rose-50/80 border-rose-200 text-rose-900';
                $notice_icon_color = 'text-rose-600';
            } else {
                $notice_box = 'bg-[#F0EBE1] border-[#d8cfc4] text-[#251E1B]';
                $notice_icon_color = 'text-[#8B533A]';
            }
            $notice_icon = \App\models\Notice::sanitizeIcon($notice_item['icon'] ?? 'info');
            ?>
            <div class="border rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-xs <?= $notice_box ?>">
                <i data-lucide="<?= e($notice_icon) ?>" class="w-5 h-5 shrink-0 mt-0.5 <?= $notice_icon_color ?>"></i>
                <div class="text-xs sm:text-sm leading-relaxed">
                    <strong class="block font-bold mb-0.5"><?= e($notice_item['title'] ?? '') ?></strong>
                    <?php if (!empty($notice_item['message'])): ?>
                        <p class="opacity-90"><?= nl2br(e($notice_item['message'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
        // پاک‌سازی متغیرهای داخلی پارشیال از scope صفحه
        unset($notice_item, $notice_type, $notice_box, $notice_icon_color, $notice_icon, $notice_page_key);
        ?>
    </section>
<?php endif; ?>
