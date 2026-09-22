<?php
/**
 * اجراکننده‌ی مهاجرت سئو (idempotent)
 * ------------------------------------------------------------------
 * روش اجرا:
 *   ۱) از طریق خط فرمان:  php admin/migrate-seo.php
 *   ۲) یا از پنل مدیریت: ابزارها ← «اجرای مهاجرت سئو»
 *
 * این اسکریپت ستون‌ها و جدول‌های موردنیاز سئو را در صورت نبودن می‌سازد
 * و اجرای چندباره‌ی آن هیچ خطایی ایجاد نمی‌کند.
 * پس از اطمینان از اجرا، این فایل را از روی سرور حذف کنید.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    // در حالت وب فقط از داخل پنل و توسط مدیر قابل اجراست
    if (!defined('ADMIN_PATH')) {
        http_response_code(403);
        exit('این اسکریپت فقط از خط فرمان یا از داخل پنل مدیریت قابل اجراست.');
    }
}

if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
}
require_once SITE_ROOT . '/core/Database.php';

/**
 * @return array<int,string> گزارش کارهای انجام‌شده
 */
function seo_migrate(): array
{
    $db = \Core\Database::getInstance();
    $log = [];

    $columnExists = static function (string $table, string $column) use ($db): bool {
        $st = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $st->execute([$table, $column]);
        return (bool) $st->fetchColumn();
    };

    $tableExists = static function (string $table) use ($db): bool {
        $st = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES
                            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
        $st->execute([$table]);
        return (bool) $st->fetchColumn();
    };

    // ---------- ستون‌های سئو ----------
    $seoColumns = [
        'meta_title'       => "VARCHAR(255) NULL",
        'meta_description' => "VARCHAR(320) NULL",
        'focus_keyword'    => "VARCHAR(120) NULL",
        'robots_directive' => "VARCHAR(20) NOT NULL DEFAULT 'default'",
        'canonical_url'    => "VARCHAR(255) NULL",
        'seo_score'        => "TINYINT UNSIGNED NOT NULL DEFAULT 0",
    ];

    foreach (['products', 'articles'] as $table) {
        if (!$tableExists($table)) {
            $log[] = "⚠ جدول {$table} وجود ندارد — رد شد.";
            continue;
        }
        foreach ($seoColumns as $col => $def) {
            if ($columnExists($table, $col)) continue;
            $db->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` {$def}");
            $log[] = "✔ ستون {$table}.{$col} اضافه شد.";
        }
    }

    if ($tableExists('product_images')) {
        if (!$columnExists('product_images', 'alt_text')) {
            $db->exec("ALTER TABLE `product_images` ADD COLUMN `alt_text` VARCHAR(255) NULL");
            $log[] = '✔ ستون product_images.alt_text اضافه شد.';
        }
        if (!$columnExists('product_images', 'seo_filename')) {
            $db->exec("ALTER TABLE `product_images` ADD COLUMN `seo_filename` VARCHAR(160) NULL");
            $log[] = '✔ ستون product_images.seo_filename اضافه شد.';
        }
    }

    // ---------- جدول‌ها ----------
    $tables = [
        'seo_redirects' => "CREATE TABLE `seo_redirects` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `from_path` VARCHAR(255) NOT NULL,
            `to_path` VARCHAR(255) NOT NULL,
            `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 301,
            `entity_type` VARCHAR(30) NULL,
            `entity_id` INT UNSIGNED NULL,
            `source` VARCHAR(20) NOT NULL DEFAULT 'auto',
            `hits` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `note` VARCHAR(255) NULL,
            `last_hit_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_from_path` (`from_path`),
            KEY `idx_entity` (`entity_type`,`entity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'seo_404_logs' => "CREATE TABLE `seo_404_logs` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `path` VARCHAR(255) NOT NULL,
            `hits` INT UNSIGNED NOT NULL DEFAULT 1,
            `last_referer` VARCHAR(255) NULL,
            `last_agent` VARCHAR(255) NULL,
            `is_bot` TINYINT(1) NOT NULL DEFAULT 0,
            `resolved` TINYINT(1) NOT NULL DEFAULT 0,
            `first_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `last_seen_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_path` (`path`),
            KEY `idx_resolved_hits` (`resolved`,`hits`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'article_products' => "CREATE TABLE `article_products` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `article_id` INT UNSIGNED NOT NULL,
            `product_id` INT UNSIGNED NOT NULL,
            `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_article_product` (`article_id`,`product_id`),
            KEY `idx_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        'seo_landing_pages' => "CREATE TABLE `seo_landing_pages` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(160) NOT NULL,
            `h1` VARCHAR(200) NOT NULL,
            `meta_title` VARCHAR(255) NULL,
            `meta_description` VARCHAR(320) NULL,
            `focus_keyword` VARCHAR(120) NULL,
            `intro_html` MEDIUMTEXT NULL,
            `outro_html` MEDIUMTEXT NULL,
            `filter_category` VARCHAR(120) NULL,
            `filter_model` VARCHAR(120) NULL,
            `filter_brand` VARCHAR(120) NULL,
            `robots_directive` VARCHAR(20) NOT NULL DEFAULT 'default',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `views` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach ($tables as $name => $ddl) {
        if ($tableExists($name)) continue;
        $db->exec($ddl);
        $log[] = "✔ جدول {$name} ساخته شد.";
    }

    if (!$log) {
        $log[] = 'همه‌چیز از قبل به‌روز بود؛ تغییری لازم نشد.';
    }
    return $log;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    foreach (seo_migrate() as $line) {
        echo $line, PHP_EOL;
    }
}
