-- سیاست چرخه‌عمر محصول، Sitemap و اعتبارسنجی Review Schema
-- این migration در محیط‌هایی که ستون‌ها وجود ندارند اجرا شود.

ALTER TABLE `products`
    ADD COLUMN `lifecycle_status` VARCHAR(20) NOT NULL DEFAULT 'active',
    ADD COLUMN `replacement_product_id` INT UNSIGNED NULL,
    ADD COLUMN `sitemap_policy` VARCHAR(20) NOT NULL DEFAULT 'auto';

ALTER TABLE `product_comments`
    ADD COLUMN `user_id` INT UNSIGNED NULL,
    ADD COLUMN `verified_purchase` TINYINT(1) NOT NULL DEFAULT 0;

CREATE INDEX `idx_products_lifecycle_sitemap`
    ON `products` (`lifecycle_status`, `sitemap_policy`);
CREATE INDEX `idx_comments_product_user_status`
    ON `product_comments` (`product_id`, `user_id`, `status`);
