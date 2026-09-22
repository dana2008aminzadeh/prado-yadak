<?php

namespace App\models;

use Core\Database;
use PDO;
use Throwable;

/**
 * مدیریت ریدایرکت‌های ۳۰۱ و لاگ خطاهای ۴۰۴
 * ---------------------------------------------------------------------------
 * هر بار که اسلاگ یک محصول یا مقاله در پنل تغییر کند، آدرس قبلی اینجا ثبت
 * می‌شود تا اعتبار صفحه (PageRank) و لینک‌های ورودی از بین نرود.
 */
class Redirect
{
    /** نرمال‌سازی مسیر: بدون دامنه، بدون اسلش پایانی، با اسلش ابتدایی */
    public static function normalize(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '/';
        }
        if (preg_match('#^https?://#i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }
        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }
        return mb_substr($path, 0, 255, 'UTF-8');
    }

    /** یافتن ریدایرکت فعال برای یک مسیر */
    public static function find(string $path): ?array
    {
        try {
            $db = Database::getInstance();
            $st = $db->prepare('SELECT * FROM seo_redirects WHERE from_path = ? AND is_active = 1 LIMIT 1');
            $st->execute([self::normalize($path)]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;   // جدول هنوز مهاجرت نشده
        }
    }

    /**
     * ثبت یک ریدایرکت. اگر مقصد جدید خودش قبلاً مبدأ بوده،
     * زنجیره اصلاح می‌شود تا هیچ‌گاه ۳۰۱ پشت ۳۰۱ ایجاد نشود.
     */
    public static function add(string $from, string $to, array $options = []): bool
    {
        $from = self::normalize($from);
        $to   = self::normalize($to);

        if ($from === '' || $to === '' || $from === $to) {
            return false;
        }

        try {
            $db = Database::getInstance();

            // جلوگیری از حلقه: اگر مقصد به مبدأ برمی‌گردد، رکورد قدیمی حذف شود
            $db->prepare('DELETE FROM seo_redirects WHERE from_path = ?')->execute([$to]);

            $st = $db->prepare(
                'INSERT INTO seo_redirects (from_path, to_path, status_code, entity_type, entity_id, source, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE to_path = VALUES(to_path), status_code = VALUES(status_code),
                                         entity_type = VALUES(entity_type), entity_id = VALUES(entity_id),
                                         source = VALUES(source), note = VALUES(note), is_active = 1'
            );
            $st->execute([
                $from,
                $to,
                (int) ($options['status_code'] ?? 301),
                $options['entity_type'] ?? null,
                isset($options['entity_id']) ? (int) $options['entity_id'] : null,
                $options['source'] ?? 'auto',
                isset($options['note']) ? mb_substr((string) $options['note'], 0, 255, 'UTF-8') : null,
            ]);

            // به‌روزرسانی زنجیره‌های قدیمی: هر چیزی که به from می‌رفت، مستقیم به to برود
            $db->prepare('UPDATE seo_redirects SET to_path = ? WHERE to_path = ? AND from_path <> ?')
                ->execute([$to, $from, $to]);

            // اگر این مسیر در لاگ ۴۰۴ بوده، حل‌شده علامت بخورد
            $db->prepare('UPDATE seo_404_logs SET resolved = 1 WHERE path = ?')->execute([$from]);

            return true;
        } catch (Throwable $e) {
            error_log('Redirect::add failed: ' . $e->getMessage());
            return false;
        }
    }

    /** ثبت بازدید یک ریدایرکت (برای گزارش‌گیری) */
    public static function hit(int $id): void
    {
        try {
            Database::getInstance()
                ->prepare('UPDATE seo_redirects SET hits = hits + 1, last_hit_at = NOW() WHERE id = ?')
                ->execute([$id]);
        } catch (Throwable $e) {
            // بی‌اهمیت
        }
    }

    /**
     * میان‌افزار: قبل از نمایش ۴۰۴ فراخوانی می‌شود.
     * در صورت وجود ریدایرکت، کاربر و خزنده را با ۳۰۱ منتقل می‌کند.
     */
    public static function handle(string $path, string $queryString = ''): void
    {
        $row = self::find($path);
        if (!$row) {
            return;
        }

        self::hit((int) $row['id']);

        $code = (int) $row['status_code'];
        if (!in_array($code, [301, 302, 307, 308, 410], true)) {
            $code = 301;
        }

        if ($code === 410) {
            http_response_code(410);
            return;   // محتوا عمداً حذف شده — ۴۱۰ به گوگل سریع‌تر می‌فهماند
        }

        $target = $row['to_path'];
        if ($queryString !== '' && !str_contains($target, '?')) {
            $target .= '?' . $queryString;
        }

        http_response_code($code);
        header('Location: ' . $target, true, $code);
        header('X-Redirect-By: PradoYadak-SEO');
        exit;
    }

    /** ثبت/افزایش شمارنده یک خطای ۴۰۴ */
    public static function log404(string $path): void
    {
        $path = self::normalize($path);

        // مسیرهای بی‌ارزش لاگ نمی‌شوند
        if (preg_match('#^/(api|assets|uploads|favicon|robots|apple-touch|wp-|\.well-known)#i', $path)) {
            return;
        }

        $agent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255, 'UTF-8');
        $referer = mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255, 'UTF-8');
        $isBot = (int) (bool) preg_match('/(googlebot|bingbot|yandex|duckduck|baidu|slurp|crawler|spider)/i', $agent);

        try {
            Database::getInstance()->prepare(
                'INSERT INTO seo_404_logs (path, hits, last_referer, last_agent, is_bot)
                 VALUES (?, 1, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen_at = NOW(),
                                         last_referer = VALUES(last_referer),
                                         last_agent = VALUES(last_agent),
                                         is_bot = VALUES(is_bot)'
            )->execute([$path, $referer ?: null, $agent ?: null, $isBot]);
        } catch (Throwable $e) {
            // جدول هنوز ساخته نشده
        }
    }
}
