<?php

namespace App\controllers;

use Core\Database;
use Core\Seo;
use Core\UrlCanonicalizer;
use PDO;
use Throwable;

class ImageController
{
    private const MAX_SOURCE_BYTES = 12582912; // 12MB
    private const CACHE_SECONDS = 31536000;

    /**
     * سرو تصویر با URL سئوشده. نام قبل از -- توضیحی است و شناسه بعد از آن
     * منبع واقعی تلگرام؛ اگر نام توضیحی اشتباه باشد به URL canonical ریدایرکت می‌شود.
     */
    public function seo(): void
    {
        $raw = rawurldecode((string) ($_GET['file'] ?? ''));
        $raw = preg_replace('/\.(jpe?g|png|webp|gif|avif)$/i', '', $raw) ?? $raw;
        $pos = strrpos($raw, '--');
        $fileId = $pos !== false ? substr($raw, $pos + 2) : $raw;

        if (!$this->validId($fileId)) {
            $this->error(400, 'شناسه تصویر نامعتبر است.');
        }

        // URLهای generic یا نام قدیمی به تنها URL سئوشده ثبت‌شده در دیتابیس می‌روند.
        $canonical = $this->canonicalPath($fileId);
        $current = UrlCanonicalizer::normalizePath(
            (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '')
        );
        if ($canonical !== '' && $current !== UrlCanonicalizer::normalizePath($canonical)) {
            UrlCanonicalizer::redirect($canonical, 301, 'image-canonical');
        }

        $this->serveTelegram($fileId);
    }

    /**
     * endpoint قدیمی /image?id= فقط برای سازگاری باقی مانده و هیچ تصویر مستقیمی
     * سرو نمی‌کند؛ بنابراین یک فایل هرگز دو URL قابل ایندکس نخواهد داشت.
     */
    public function show(): void
    {
        $fileId = trim((string) ($_GET['id'] ?? ''));
        if (!$this->validId($fileId)) {
            $this->error(400, 'شناسه تصویر نامعتبر است.');
        }
        $target = $this->canonicalPath($fileId);
        if ($target === '') {
            $target = Seo::imageUrl($fileId, 'image');
        }
        UrlCanonicalizer::redirect($target, 301, 'legacy-image');
    }

    /** URL سئوشده واقعی بر اساس product_images یا کاور مقاله. */
    private function canonicalPath(string $fileId): string
    {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare(
                'SELECT pi.*, p.name, p.oem_code, p.car_model
                 FROM product_images pi
                 JOIN products p ON p.id = pi.product_id
                 WHERE pi.telegram_file_id = ?
                 ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1'
            );
            $stmt->execute([$fileId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $model = $GLOBALS['car_models'][$row['car_model'] ?? ''] ?? ($row['car_model'] ?? '');
                $model = is_array($model) ? ($model['name'] ?? '') : (string) $model;
                $seoName = trim((string) ($row['seo_filename'] ?? ''))
                    ?: Seo::imageSlug((string) $row['name'], $row['oem_code'] ?? null, $model ?: null, (int) ($row['sort_order'] ?? 0));
                return Seo::imageUrl($fileId, $seoName);
            }

            // سازگاری با تصاویر legacy که هنوز فقط در telegram_photo_id محصول‌اند.
            $legacyProduct = $db->prepare(
                'SELECT name, oem_code, car_model FROM products
                 WHERE telegram_photo_id = ? OR LOCATE(?, telegram_photo_id) > 0 ORDER BY id LIMIT 1'
            );
            $legacyProduct->execute([$fileId, '"' . $fileId . '"']);
            $legacy = $legacyProduct->fetch(PDO::FETCH_ASSOC);
            if ($legacy) {
                $model = $GLOBALS['car_models'][$legacy['car_model'] ?? ''] ?? ($legacy['car_model'] ?? '');
                $model = is_array($model) ? ($model['name'] ?? '') : (string) $model;
                return Seo::imageUrl(
                    $fileId,
                    Seo::imageSlug((string) $legacy['name'], $legacy['oem_code'] ?? null, $model ?: null)
                );
            }

            $article = $db->prepare(
                "SELECT title FROM articles WHERE cover_image = ? AND status = 'published' ORDER BY id DESC LIMIT 1"
            );
            $article->execute([$fileId]);
            $title = $article->fetchColumn();
            if ($title) {
                return Seo::imageUrl($fileId, Seo::imageSlug((string) $title));
            }
        } catch (Throwable $e) {
            // دیتابیس یا جدول گالری در دسترس نیست؛ URL generic همچنان معتبر است.
        }
        return Seo::imageUrl($fileId, 'image');
    }

    private function serveTelegram(string $fileId): void
    {
        global $settings;
        $botToken = trim((string) ($settings['telegram_bot_token'] ?? ''));
        if ($botToken === '') {
            $this->error(503, 'سرویس تصویر موقتاً در دسترس نیست.');
        }

        $cacheDir = CORE_PATH . '/cache/images';
        $pathDir = CORE_PATH . '/cache/paths';
        foreach ([$cacheDir, $pathDir] as $dir) {
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $this->error(503, 'حافظه موقت تصویر در دسترس نیست.');
            }
        }

        $cacheKey = hash('sha256', $fileId);
        $sourceFile = $cacheDir . '/' . $cacheKey . '.source';

        if (!is_file($sourceFile) || filesize($sourceFile) <= 0) {
            $telegramPath = $this->telegramPath($fileId, $botToken, $pathDir);
            $url = "https://api.telegram.org/file/bot{$botToken}/{$telegramPath}";
            $bytes = $this->httpGet($url, 12);
            if ($bytes === null || $bytes === '' || strlen($bytes) > self::MAX_SOURCE_BYTES) {
                $this->error(404, 'تصویر یافت نشد.');
            }

            $tmp = $sourceFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($tmp, $bytes, LOCK_EX) === false) {
                $this->error(503, 'ذخیره موقت تصویر ناموفق بود.');
            }
            if (@getimagesize($tmp) === false) {
                @unlink($tmp);
                $this->error(415, 'محتوای دریافتی تصویر معتبر نیست.');
            }
            @chmod($tmp, 0644);
            @rename($tmp, $sourceFile);
        }

        $info = @getimagesize($sourceFile);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'];
        if (!in_array($mime, $allowedMimes, true)) {
            $this->error(415, 'فرمت تصویر پشتیبانی نمی‌شود.');
        }

        [$outputFile, $outputMime] = $this->negotiatedVariant($sourceFile, $mime, $cacheKey, $cacheDir);
        $mtime = (int) (filemtime($outputFile) ?: time());
        $size = (int) (filesize($outputFile) ?: 0);
        $etag = '"' . hash_file('sha256', $outputFile) . '"';

        header('Content-Type: ' . $outputMime);
        header('Content-Length: ' . $size);
        header('Cache-Control: public, max-age=' . self::CACHE_SECONDS . ', immutable');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + self::CACHE_SECONDS) . ' GMT');
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');
        header('Vary: Accept');
        header('X-Content-Type-Options: nosniff');
        header('X-Robots-Tag: all');

        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        $ifModifiedSince = strtotime((string) ($_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? '')) ?: 0;
        if (($ifNoneMatch !== '' && $ifNoneMatch === $etag)
            || ($ifNoneMatch === '' && $ifModifiedSince >= $mtime)) {
            http_response_code(304);
            header_remove('Content-Length');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'HEAD') {
            $stream = @fopen($outputFile, 'rb');
            if (!$stream) {
                $this->error(503, 'خواندن تصویر ناموفق بود.');
            }
            fpassthru($stream);
            fclose($stream);
        }
        exit;
    }

    /** @return array{0:string,1:string} */
    private function negotiatedVariant(string $source, string $sourceMime, string $key, string $cacheDir): array
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $format = null;
        if (str_contains($accept, 'image/avif') && function_exists('imageavif')) {
            $format = 'avif';
        } elseif (str_contains($accept, 'image/webp') && function_exists('imagewebp')) {
            $format = 'webp';
        }

        // تبدیل GIF متحرک با GD فقط فریم اول را نگه می‌دارد؛ بنابراین منبع حفظ می‌شود.
        if ($format === null || $sourceMime === 'image/gif' || $sourceMime === 'image/' . $format
            || !function_exists('imagecreatefromstring')) {
            return [$source, $sourceMime];
        }

        $variant = $cacheDir . '/' . $key . '.' . $format;
        if (!is_file($variant) || filesize($variant) <= 0) {
            $bytes = @file_get_contents($source);
            $image = $bytes !== false ? @imagecreatefromstring($bytes) : false;
            if ($image === false) {
                return [$source, $sourceMime];
            }

            if (function_exists('imagepalettetotruecolor')) {
                @imagepalettetotruecolor($image);
            }
            @imagealphablending($image, true);
            @imagesavealpha($image, true);

            $tmp = $variant . '.' . bin2hex(random_bytes(4)) . '.tmp';
            $ok = $format === 'avif'
                ? @imageavif($image, $tmp, 65)
                : @imagewebp($image, $tmp, 82);
            imagedestroy($image);
            if (!$ok || !is_file($tmp) || filesize($tmp) <= 0) {
                @unlink($tmp);
                return [$source, $sourceMime];
            }
            @chmod($tmp, 0644);
            @rename($tmp, $variant);
        }

        return [$variant, 'image/' . $format];
    }

    private function telegramPath(string $fileId, string $token, string $pathDir): string
    {
        $cache = $pathDir . '/' . hash('sha256', $fileId) . '.txt';
        if (is_file($cache) && (time() - (int) filemtime($cache)) < 86400) {
            $path = trim((string) file_get_contents($cache));
            if ($this->validTelegramPath($path)) {
                return $path;
            }
        }

        $response = $this->httpGet(
            'https://api.telegram.org/bot' . $token . '/getFile?file_id=' . rawurlencode($fileId),
            6
        );
        $data = $response !== null ? json_decode($response, true) : null;
        $path = is_array($data) && !empty($data['ok']) ? (string) ($data['result']['file_path'] ?? '') : '';
        if (!$this->validTelegramPath($path)) {
            $this->error(404, 'تصویر در منبع یافت نشد.');
        }
        @file_put_contents($cache, $path, LOCK_EX);
        return $path;
    }

    private function httpGet(string $url, int $timeout): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'PradoYadak-ImageProxy/2.0',
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status >= 200 && $status < 300 && is_string($body) ? $body : null;
    }

    private function validId(string $id): bool
    {
        return $id !== '' && strlen($id) <= 512 && (bool) preg_match('/^[A-Za-z0-9_-]+$/', $id);
    }

    private function validTelegramPath(string $path): bool
    {
        return $path !== '' && strlen($path) <= 512
            && !str_contains($path, '..')
            && (bool) preg_match('#^[A-Za-z0-9_./-]+$#', $path);
    }

    private function error(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        exit($message);
    }
}
