-- وضعیت مستقل انتشار محصول برای فرایند دو مرحله‌ای ایجاد و تکمیل رسانه
ALTER TABLE `products`
    ADD COLUMN `publication_status` VARCHAR(20) NOT NULL DEFAULT 'published' AFTER `sitemap_policy`;

CREATE INDEX `idx_products_publication_status`
    ON `products` (`publication_status`);
