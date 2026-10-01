-- ============================================================
-- جدول اطلاعیه‌های سایت — پرادو یدک
-- تاریخ: ۱۴۰۵/۰۸/۱۰ (2026-10-02)
-- هدف: نصب‌های تازه و یا سرورهایی که جدول site_notices را ندارند.
--
-- این مهاجرت idempotent است:
--   * اگر جدول از قبل وجود داشته باشد (مثل سرور فعلی) هیچ تغییری نمی‌کند.
--   * اجرای مطلق SQL در phpMyAdmin بی‌خطر است.
--
-- ساختار با کدهای خواننده هم‌خوان است:
--   * App\models\Notice::getForPage()      → SELECT (page | global + is_active)
--   * Admin\controllers\NoticeController   → INSERT/UPDATE ستون‌های همین جدول
--
-- فهرست مجاز کلید page در App\models\Notice::PAGES تعریف می‌شود؛
-- مقدار پیش‌فرض 'global' است تا اطلاعیه جدید هیچ‌گاه جایی دیده نشود مگر
-- مدیر صفحه‌ی دیگری انتخاب کرده باشد.
-- ============================================================

CREATE TABLE IF NOT EXISTS `site_notices` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `page`       VARCHAR(40)  NOT NULL DEFAULT 'global',
    `type`       VARCHAR(20)  NOT NULL DEFAULT 'info',
    `title`      VARCHAR(255) NOT NULL,
    `message`    TEXT         NULL,
    `icon`       VARCHAR(60)  NOT NULL DEFAULT 'info',
    `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
    `priority`   INT          NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_site_notices_page_active` (`page`, `is_active`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
