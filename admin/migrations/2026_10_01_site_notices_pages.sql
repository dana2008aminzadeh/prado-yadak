-- ============================================================
-- مهاجرت اطلاعیه‌های سایت — تعمیم نمایش به همه صفحات فروشگاه
-- تاریخ: ۱۴۰۵/۰۷/۱۰ (2026-10-01)
-- ------------------------------------------------------------------
-- با این مهاجرت، اطلاعیه‌ها علاوه بر «تسویه حساب» روی صفحه اصلی،
-- کاتالوگ قطعات، صفحه محصول، وبلاگ، کشوی سبد خرید، ورود، پنل
-- کاربری و قوانین هم نمایش داده می‌شوند.
--
-- اجرای دستی این فایل در phpMyAdmin بی‌خطر است و اجرای چندباره‌ی آن
-- خطا ایجاد نمی‌کند (CREATE IF NOT EXISTS و MODIFY همواره مجازند).
-- ============================================================

-- ---------- ۱) ساخت جدول در محیط‌هایی که هنوز وجود ندارد ----------
CREATE TABLE IF NOT EXISTS `site_notices` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page`       VARCHAR(32)  NOT NULL DEFAULT 'checkout',
    `type`       ENUM('info','warning','danger') NOT NULL DEFAULT 'info',
    `title`      VARCHAR(255) NOT NULL,
    `message`    TEXT NULL,
    `icon`       VARCHAR(64)  NOT NULL DEFAULT 'info',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `priority`   INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_page_active` (`page`, `is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ۲) تعمیم ستون page به کلیدهای جدید ----------
-- اگر ستون page قبلاً به‌صورت ENUM محدود (global/checkout/parts/home/cart)
-- ساخته شده باشد، کلیدهای جدید (product/blog/order/login/profile/terms)
-- ذخیره نمی‌شوند؛ تبدیل به VARCHAR این محدودیت را برمی‌دارد و مقادیر
-- موجود نیز دست‌نخورده باقی می‌مانند.
ALTER TABLE `site_notices`
    MODIFY `page` VARCHAR(32) NOT NULL DEFAULT 'checkout';
