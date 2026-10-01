<?php
/**
 * اطلاعیه‌های سایت — لایه نمایش مشترک تمام صفحات
 * ---------------------------------------------------------------------------
 * این پارشال از assets/php/header.php برای تمام صفحات سایت فراخوانی می‌شود
 * و از assets/php/footer.php یک‌بار دیگر با کلید «cart» برای کشوی سبد خرید.
 *
 * متغیرهای اختیاری (پیش از include قابل تنظیم):
 *   $noticePageKey  کلید صفحه‌ی موردنظر (پیش‌فرض: تشخیص خودکار از URI)
 *   $noticeContext  «page» برای نمایش با کانتینر استاندارد، «bare» فقط پشته
 *                   اطلاعیه‌ها بدون کانتینر (مثلاً داخل کشوی سبد خرید)
 *
 * استایل‌ها فقط از کلاس‌های موجود در assets/css/style.css (خروجی Tailwind
 * کامپایل‌شده) استفاده می‌کنند تا نیازی به بیلد مجدد CSS نباشد.
 */

use App\models\Notice;

if (!defined('BASE_PATH')) {
    // دسترسی مستقیم به فایل پارشال ممنوع است؛ فقط از طریق include.
    if (!isset($_SERVER['REQUEST_URI'])) {
        return;
    }
}

$noticePageKey = (isset($noticePageKey) && is_string($noticePageKey)) ? $noticePageKey : null;
$noticeContext = (isset($noticeContext) && $noticeContext === 'bare') ? 'bare' : 'page';

$notices = Notice::getForPage($noticePageKey ?: Notice::currentPageKey());

if (empty($notices)) {
    return;
}
?>
<section class="site-notices <?= $noticeContext === 'page' ? 'max-w-7xl mx-auto px-4 pt-4 w-full' : 'mb-4' ?>"
    aria-label="اطلاعیه‌های سایت">
    <div class="space-y-3">
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
            // آیکون‌ها از کتابخانه Lucide هستند؛ نام فیلد icon فقط حروف/عدد/خط تیره می‌پذیرد
            $noticeIcon = preg_replace('/[^a-z0-9-]/i', '', (string) ($notice['icon'] ?? '')) ?: 'info';
            ?>
            <div role="<?= $type === 'info' ? 'status' : 'alert' ?>"
                class="border rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 shadow-xs <?= $boxStyle ?>">
                <i data-lucide="<?= e($noticeIcon) ?>"
                    class="w-5 h-5 shrink-0 mt-0.5 <?= $iconColor ?>"></i>
                <div class="text-xs sm:text-sm leading-relaxed">
                    <strong class="block font-bold mb-0.5"><?= e($notice['title'] ?? '') ?></strong>
                    <?php if (!empty($notice['message'])): ?>
                        <p class="opacity-90"><?= nl2br(e($notice['message'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
