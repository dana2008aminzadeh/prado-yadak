-- چرخه انتشار محصول: ایجاد مرحله اول همیشه پیش‌نویس است.
ALTER TABLE `products`
    ADD COLUMN `publication_status` VARCHAR(20) NOT NULL DEFAULT 'published';
CREATE INDEX `idx_products_publication_status` ON `products` (`publication_status`);
