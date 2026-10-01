-- Product two-step workflow: first save is a draft, publish only from the media step.
ALTER TABLE `products` ADD COLUMN `publication_status` VARCHAR(20) NOT NULL DEFAULT 'published';
CREATE INDEX `idx_products_publication_status` ON `products` (`publication_status`);
