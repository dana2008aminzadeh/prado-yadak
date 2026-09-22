-- ============================================================
-- مهاجرت سئو — پرادو یدک
-- تاریخ: ۱۴۰۵/۰۷/۰۱ (2026-09-22)
-- این فایل توسط admin/migrate-seo.php به صورت idempotent اجرا می‌شود
-- ولی اجرای دستی آن در phpMyAdmin هم بی‌خطر است.
-- ============================================================

-- ---------- ۱) فیلدهای اختصاصی سئو روی محصولات ----------
ALTER TABLE `products`
    ADD COLUMN `meta_title`       VARCHAR(255) NULL AFTER `slug`,
    ADD COLUMN `meta_description` VARCHAR(320) NULL AFTER `meta_title`,
    ADD COLUMN `focus_keyword`    VARCHAR(120) NULL AFTER `meta_description`,
    ADD COLUMN `robots_directive` VARCHAR(20)  NOT NULL DEFAULT 'default' AFTER `focus_keyword`,
    ADD COLUMN `canonical_url`    VARCHAR(255) NULL AFTER `robots_directive`,
    ADD COLUMN `seo_score`        TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `canonical_url`;

-- ---------- ۲) فیلدهای اختصاصی سئو روی مقالات ----------
ALTER TABLE `articles`
    ADD COLUMN `meta_title`       VARCHAR(255) NULL AFTER `slug`,
    ADD COLUMN `meta_description` VARCHAR(320) NULL AFTER `meta_title`,
    ADD COLUMN `focus_keyword`    VARCHAR(120) NULL AFTER `meta_description`,
    ADD COLUMN `robots_directive` VARCHAR(20)  NOT NULL DEFAULT 'default' AFTER `focus_keyword`,
    ADD COLUMN `canonical_url`    VARCHAR(255) NULL AFTER `robots_directive`,
    ADD COLUMN `seo_score`        TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `canonical_url`;

-- ---------- ۳) سئوی تصاویر ----------
ALTER TABLE `product_images`
    ADD COLUMN `seo_filename` VARCHAR(160) NULL AFTER `alt_text`;

-- ---------- ۴) جدول ریدایرکت‌های ۳۰۱ ----------
CREATE TABLE IF NOT EXISTS `seo_redirects` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `from_path`   VARCHAR(255) NOT NULL,
    `to_path`     VARCHAR(255) NOT NULL,
    `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
    `entity_type` VARCHAR(30)  NULL,
    `entity_id`   INT UNSIGNED NULL,
    `source`      VARCHAR(20)  NOT NULL DEFAULT 'auto',
    `hits`        INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `note`        VARCHAR(255) NULL,
    `last_hit_at` DATETIME NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_from_path` (`from_path`),
    KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ۵) لاگ خطاهای ۴۰۴ ----------
CREATE TABLE IF NOT EXISTS `seo_404_logs` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `path`         VARCHAR(255) NOT NULL,
    `hits`         INT UNSIGNED NOT NULL DEFAULT 1,
    `last_referer` VARCHAR(255) NULL,
    `last_agent`   VARCHAR(255) NULL,
    `is_bot`       TINYINT(1) NOT NULL DEFAULT 0,
    `resolved`     TINYINT(1) NOT NULL DEFAULT 0,
    `first_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_seen_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_path` (`path`),
    KEY `idx_resolved_hits` (`resolved`, `hits`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ۶) پیوند ساختاری وبلاگ ↔ فروشگاه (Silo) ----------
CREATE TABLE IF NOT EXISTS `article_products` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_article_product` (`article_id`, `product_id`),
    KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ۷) لندینگ‌پیج‌های سئو (ترکیب‌های پرجستجو با آدرس تمیز) ----------
CREATE TABLE IF NOT EXISTS `seo_landing_pages` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`             VARCHAR(160) NOT NULL,
    `h1`               VARCHAR(200) NOT NULL,
    `meta_title`       VARCHAR(255) NULL,
    `meta_description` VARCHAR(320) NULL,
    `focus_keyword`    VARCHAR(120) NULL,
    `intro_html`       MEDIUMTEXT NULL,
    `outro_html`       MEDIUMTEXT NULL,
    `filter_category`  VARCHAR(120) NULL,
    `filter_model`     VARCHAR(120) NULL,
    `filter_brand`     VARCHAR(120) NULL,
    `robots_directive` VARCHAR(20) NOT NULL DEFAULT 'default',
    `is_active`        TINYINT(1) NOT NULL DEFAULT 1,
    `views`            INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
