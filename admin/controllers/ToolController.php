<?php
namespace Admin\controllers;

use Admin\core\Model;
use Core\Database;
use PDO;

/**
 * ابزارهای سیستمی: پشتیبان‌گیری از پایگاه داده، خروجی اکسل، سلامت سیستم
 */
class ToolController extends BaseController
{
    protected string $section = 'tools';

    protected array $permissions = [
        'backup'   => 'tools.backup',
        'download' => 'tools.backup',
        'delete'   => 'tools.backup',
        'optimize' => 'tools.backup',
    ];

    public function index($id = 0): void
    {
        $backups = $this->listBackups();
        $health = $this->healthCheck();
        $tables = $this->tableStats();

        $exports = [
            ['products', 'محصولات و موجودی انبار', 'package'],
            ['orders', 'سفارش‌ها', 'shopping-bag'],
            ['users', 'کاربران', 'users'],
            ['wallet', 'تراکنش‌های کیف پول', 'wallet'],
            ['sms', 'گزارش پیامک‌ها', 'message-square'],
            ['stock', 'حرکات انبار', 'boxes'],
            ['audit', 'لاگ مدیران', 'shield'],
        ];

        $this->view('tools/index', compact('backups', 'health', 'tables', 'exports'),
            'ابزارها و پشتیبان‌گیری', 'مدیریت فنی سیستم');
    }

    // ------------------------------------------------------- پشتیبان‌گیری

    public function backup($id = 0): void
    {
        @set_time_limit(0);
        $dir = SITE_ROOT . '/backups';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            flash('error', 'امکان ساخت پوشه backups وجود ندارد.');
            redirect(admin_url('tools'));
        }

        // محافظت از پوشه پشتیبان
        $guard = $dir . '/.htaccess';
        if (!is_file($guard)) {
            @file_put_contents($guard, "Require all denied\nDeny from all\n");
        }

        $structureOnly = (bool) post('structure_only');
        $filename = 'backup-' . date('Y-m-d_His') . ($structureOnly ? '-structure' : '') . '.sql';
        $path = $dir . '/' . $filename;

        try {
            $sql = $this->dumpDatabase(!$structureOnly);
            if (function_exists('gzencode')) {
                $filename .= '.gz';
                $path .= '.gz';
                file_put_contents($path, gzencode($sql, 6));
            } else {
                file_put_contents($path, $sql);
            }
            @chmod($path, 0600);
        } catch (\Throwable $e) {
            flash('error', 'خطا در پشتیبان‌گیری: ' . $e->getMessage());
            redirect(admin_url('tools'));
        }

        $size = @filesize($path) ?: 0;
        $this->audit('backup.create', 'backup', $filename,
            'ایجاد نسخه پشتیبان ' . $filename . ' (' . $this->humanSize($size) . ')');

        // نگهداری فقط ۱۰ نسخه آخر
        $this->rotateBackups($dir, 10);

        flash('success', 'نسخه پشتیبان ساخته شد: ' . $filename . ' — حجم: ' . $this->humanSize($size));
        redirect(admin_url('tools'));
    }

    public function download($id = 0): void
    {
        $name = basename((string) param('file', ''));
        if (!preg_match('/^backup-[\w\-\.]+\.sql(\.gz)?$/', $name)) {
            http_response_code(400);
            exit('نام فایل نامعتبر است.');
        }

        $path = SITE_ROOT . '/backups/' . $name;
        $real = realpath($path);
        $base = realpath(SITE_ROOT . '/backups');
        if (!$real || !$base || !str_starts_with($real, $base) || !is_file($real)) {
            http_response_code(404);
            exit('فایل یافت نشد.');
        }

        $this->audit('backup.download', 'backup', $name, 'دانلود نسخه پشتیبان ' . $name);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($real));
        header('Pragma: no-cache');
        readfile($real);
        exit;
    }

    public function delete($id = 0): void
    {
        $name = basename((string) post('file', ''));
        if (!preg_match('/^backup-[\w\-\.]+\.sql(\.gz)?$/', $name)) {
            flash('error', 'نام فایل نامعتبر است.');
            redirect(admin_url('tools'));
        }
        $real = realpath(SITE_ROOT . '/backups/' . $name);
        $base = realpath(SITE_ROOT . '/backups');
        if ($real && $base && str_starts_with($real, $base)) {
            @unlink($real);
            $this->audit('backup.create', 'backup', $name, 'حذف نسخه پشتیبان ' . $name);
            flash('success', 'نسخه پشتیبان حذف شد.');
        } else {
            flash('error', 'فایل یافت نشد.');
        }
        redirect(admin_url('tools'));
    }

    public function optimize($id = 0): void
    {
        $db = Database::getInstance();
        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $done = 0;
        foreach ($tables as $t) {
            try {
                $db->exec('OPTIMIZE TABLE `' . str_replace('`', '', (string) $t) . '`');
                $done++;
            } catch (\Throwable $e) {
                // برخی موتورها پشتیبانی نمی‌کنند
            }
        }
        $this->audit('backup.create', 'database', null, "بهینه‌سازی {$done} جدول پایگاه داده");
        flash('success', $done . ' جدول بهینه‌سازی شد.');
        redirect(admin_url('tools'));
    }

    // ------------------------------------------------------- خروجی اکسل

    /** خروجی CSV سازگار با اکسل برای هر بخش */
    public function export($id = 0): void
    {
        $type = (string) param('type', 'products');

        switch ($type) {
            case 'orders':
                $rows = Model::all("SELECT o.tracking_code, u.full_name, u.phone, o.total_amount,
                                           o.shipping_carrier, o.shipping_tracking_code, o.status, o.created_at
                                    FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC");
                $data = array_map(fn($r) => [$r['tracking_code'], $r['full_name'] ?? '', $r['phone'] ?? '',
                    $r['total_amount'], $r['shipping_carrier'] ?? '', $r['shipping_tracking_code'] ?? '',
                    OrderController::STATUSES[$r['status']] ?? $r['status'], shamsiTime($r['created_at'])], $rows);
                $this->streamCsv('orders-' . date('Y-m-d') . '.csv',
                    ['کد رهگیری', 'مشتری', 'موبایل', 'مبلغ', 'شرکت حمل', 'بارنامه', 'وضعیت', 'تاریخ'], $data);
                break;

            case 'users':
                $rows = Model::all("SELECT id, full_name, phone, email, national_code, city, role, wallet_balance, created_at
                                    FROM users ORDER BY id");
                $data = array_map(fn($r) => [$r['id'], $r['full_name'], $r['phone'], $r['email'] ?? '',
                    $r['national_code'] ?? '', $r['city'] ?? '', $r['role'] === 'admin' ? 'مدیر' : 'کاربر',
                    $r['wallet_balance'], shamsiTime($r['created_at'])], $rows);
                $this->streamCsv('users-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'نام', 'موبایل', 'ایمیل', 'کد ملی', 'شهر', 'نقش', 'کیف پول', 'عضویت'], $data);
                break;

            case 'wallet':
                $rows = Model::all("SELECT w.id, u.full_name, u.phone, w.type, w.amount, w.balance_after,
                                           w.description, w.status, w.created_at
                                    FROM wallet_transactions w LEFT JOIN users u ON u.id = w.user_id ORDER BY w.id DESC");
                $data = array_map(fn($r) => [$r['id'], $r['full_name'] ?? '', $r['phone'] ?? '', $r['type'],
                    $r['amount'], $r['balance_after'], $r['description'] ?? '', $r['status'],
                    shamsiTime($r['created_at'])], $rows);
                $this->streamCsv('wallet-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'کاربر', 'موبایل', 'نوع', 'مبلغ', 'مانده', 'توضیح', 'وضعیت', 'تاریخ'], $data);
                break;

            case 'sms':
                $rows = Model::all('SELECT id, phone, message, template_key, status, error_message, created_at
                                    FROM sms_logs ORDER BY id DESC LIMIT 10000');
                $data = array_map(fn($r) => [$r['id'], $r['phone'], $r['message'], $r['template_key'] ?? '',
                    $r['status'], $r['error_message'] ?? '', shamsiTime($r['created_at'])], $rows);
                $this->streamCsv('sms-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'موبایل', 'متن', 'قالب', 'وضعیت', 'خطا', 'تاریخ'], $data);
                break;

            case 'stock':
                $rows = Model::all('SELECT sm.id, p.name, sm.change_qty, sm.qty_after, sm.reason,
                                           sm.reference_id, sm.note, u.full_name, sm.created_at
                                    FROM stock_movements sm
                                    LEFT JOIN products p ON p.id = sm.product_id
                                    LEFT JOIN users u ON u.id = sm.admin_id
                                    ORDER BY sm.id DESC LIMIT 10000');
                $data = array_map(fn($r) => [$r['id'], $r['name'] ?? '', $r['change_qty'], $r['qty_after'],
                    \Admin\core\Inventory::REASONS[$r['reason']] ?? $r['reason'], $r['reference_id'] ?? '',
                    $r['note'] ?? '', $r['full_name'] ?? '', shamsiTime($r['created_at'])], $rows);
                $this->streamCsv('stock-movements-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'محصول', 'تغییر', 'موجودی پس از', 'علت', 'مرجع', 'یادداشت', 'کاربر', 'تاریخ'], $data);
                break;

            case 'audit':
                $this->need('audit.view');
                $rows = Model::all('SELECT * FROM admin_audit_logs ORDER BY id DESC LIMIT 10000');
                $data = array_map(fn($r) => [$r['id'], shamsiTime($r['created_at']), $r['admin_name'] ?? '',
                    \Admin\core\Audit::label($r['action']), $r['description'] ?? '', $r['ip_address'] ?? ''], $rows);
                $this->streamCsv('audit-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'تاریخ', 'مدیر', 'رویداد', 'توضیح', 'IP'], $data);
                break;

            case 'products':
            default:
                $rows = Model::all("SELECT p.id, p.name, c.name AS category, p.price, p.oem_code, p.brand,
                                           p.stock_qty, p.is_genuine FROM products p
                                    LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id");
                $data = array_map(fn($r) => [$r['id'], $r['name'], $r['category'] ?? '', $r['price'],
                    $r['oem_code'] ?? '', $r['brand'] ?? '', $r['stock_qty'], $r['is_genuine'] ? 'اصل' : 'متفرقه'], $rows);
                $this->streamCsv('products-' . date('Y-m-d') . '.csv',
                    ['شناسه', 'نام', 'دسته', 'قیمت', 'کد فنی', 'برند', 'موجودی', 'اصالت'], $data);
        }
    }

    // ------------------------------------------------------- داخلی

    /** تولید دامپ SQL از کل پایگاه داده */
    private function dumpDatabase(bool $withData = true): string
    {
        $db = Database::getInstance();
        $dbName = (string) $db->query('SELECT DATABASE()')->fetchColumn();

        $out = "-- پشتیبان پایگاه داده {$dbName}\n";
        $out .= '-- تاریخ: ' . date('Y-m-d H:i:s') . ' (' . shamsiTime(date('Y-m-d H:i:s')) . ")\n";
        $out .= "-- ساخته‌شده توسط پنل مدیریت پرادو یدک\n\n";
        $out .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) ?: [];

        foreach ($tables as $table) {
            $table = (string) $table;
            $safe = str_replace('`', '', $table);

            $create = $db->query("SHOW CREATE TABLE `{$safe}`")->fetch(PDO::FETCH_NUM);
            $out .= "\n-- ساختار جدول `{$safe}`\n";
            $out .= "DROP TABLE IF EXISTS `{$safe}`;\n";
            $out .= ($create[1] ?? '') . ";\n";

            if (!$withData) continue;

            $count = (int) $db->query("SELECT COUNT(*) FROM `{$safe}`")->fetchColumn();
            if ($count === 0) continue;

            $out .= "\n-- داده‌های جدول `{$safe}` ({$count} ردیف)\n";

            // خواندن تکه‌تکه برای جلوگیری از مصرف حافظه
            $chunk = 500;
            for ($offset = 0; $offset < $count; $offset += $chunk) {
                $rows = $db->query("SELECT * FROM `{$safe}` LIMIT {$chunk} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
                if (!$rows) break;

                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
                $values = [];
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($db) {
                        if ($v === null) return 'NULL';
                        if (is_numeric($v) && !preg_match('/^0\d/', (string) $v)) return (string) $v;
                        return $db->quote((string) $v);
                    }, array_values($row));
                    $values[] = '(' . implode(',', $vals) . ')';
                }
                $out .= "INSERT INTO `{$safe}` ({$cols}) VALUES\n" . implode(",\n", $values) . ";\n";
            }
        }

        $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        return $out;
    }

    private function listBackups(): array
    {
        $dir = SITE_ROOT . '/backups';
        if (!is_dir($dir)) return [];

        $files = glob($dir . '/backup-*.sql*') ?: [];
        $out = [];
        foreach ($files as $f) {
            $out[] = [
                'name'     => basename($f),
                'size'     => $this->humanSize((int) @filesize($f)),
                'bytes'    => (int) @filesize($f),
                'modified' => (int) @filemtime($f),
            ];
        }
        usort($out, fn($a, $b) => $b['modified'] <=> $a['modified']);
        return $out;
    }

    private function rotateBackups(string $dir, int $keep): void
    {
        $files = glob($dir . '/backup-*.sql*') ?: [];
        if (count($files) <= $keep) return;
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }

    private function tableStats(): array
    {
        try {
            $rows = Model::all('SELECT TABLE_NAME AS name, TABLE_ROWS AS rows_count,
                                       (DATA_LENGTH + INDEX_LENGTH) AS size_bytes
                                FROM information_schema.TABLES
                                WHERE TABLE_SCHEMA = DATABASE()
                                ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC LIMIT 30');
            return array_map(fn($r) => $r + ['size' => $this->humanSize((int) $r['size_bytes'])], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function healthCheck(): array
    {
        $checks = [];

        $uploadDirs = ['uploads/products', 'uploads/tickets', 'backups'];
        foreach ($uploadDirs as $d) {
            $path = SITE_ROOT . '/' . $d;
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $checks[] = [
                'label'  => 'پوشه ' . $d,
                'ok'     => $writable,
                'detail' => !$exists ? 'وجود ندارد' : ($writable ? 'قابل نوشتن' : 'قابل نوشتن نیست (chmod 755)'),
            ];
        }

        $checks[] = ['label' => 'افزونه cURL', 'ok' => function_exists('curl_init'),
            'detail' => function_exists('curl_init') ? 'فعال' : 'غیرفعال — آپلود تلگرام و پیامک کار نمی‌کند'];
        $checks[] = ['label' => 'افزونه GD', 'ok' => extension_loaded('gd'),
            'detail' => extension_loaded('gd') ? 'فعال' : 'غیرفعال'];
        $checks[] = ['label' => 'فشرده‌سازی gzip', 'ok' => function_exists('gzencode'),
            'detail' => function_exists('gzencode') ? 'فعال' : 'غیرفعال — پشتیبان‌ها فشرده نمی‌شوند'];

        $smsOk = \Admin\core\Settings::bool('sms_enabled')
            && \Admin\core\Settings::get('smsir_api_key') && \Admin\core\Settings::get('smsir_line_number');
        $checks[] = ['label' => 'سرویس پیامک', 'ok' => $smsOk,
            'detail' => $smsOk ? 'پیکربندی شده' : 'کلید API یا خط ارسال تنظیم نشده'];

        $tgOk = (bool) \Admin\core\Settings::get('telegram_bot_token');
        $checks[] = ['label' => 'ربات تلگرام (میزبانی تصاویر)', 'ok' => $tgOk,
            'detail' => $tgOk ? 'توکن ثبت شده' : 'توکن ثبت نشده — تصاویر روی دیسک ذخیره می‌شوند'];

        $maxUpload = ini_get('upload_max_filesize');
        $checks[] = ['label' => 'حداکثر حجم آپلود', 'ok' => true, 'detail' => $maxUpload];

        $migrated = false;
        try {
            $migrated = (bool) Model::scalar("SELECT COUNT(*) FROM information_schema.TABLES
                                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_audit_logs'");
        } catch (\Throwable $e) {
        }
        $checks[] = ['label' => 'مهاجرت پایگاه داده', 'ok' => $migrated,
            'detail' => $migrated ? 'انجام شده' : 'اجرا نشده — php admin/migrate.php را اجرا کنید'];

        $installer = is_file(ADMIN_PATH . '/make-admin.php');
        $checks[] = ['label' => 'فایل نصب make-admin.php', 'ok' => !$installer,
            'detail' => $installer ? '⚠ هنوز روی سرور است — حذفش کنید' : 'حذف شده'];

        $migrateFile = is_file(ADMIN_PATH . '/migrate.php');
        $checks[] = ['label' => 'فایل migrate.php', 'ok' => !$migrateFile,
            'detail' => $migrateFile ? '⚠ پس از مهاجرت حذفش کنید' : 'حذف شده'];

        return $checks;
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
