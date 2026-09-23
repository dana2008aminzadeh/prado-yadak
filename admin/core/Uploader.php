<?php
namespace Admin\core;

use Throwable;

/**
 * آپلود امن فایل با پشتیبانی از ارسال به تلگرام و URL واحد /media/
 */
class Uploader
{
    public const MAX_IMAGE_SIZE = 8388608;   // 8MB
    public const MAX_FILE_SIZE  = 20971520;  // 20MB

    public const IMAGE_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/avif' => 'avif',
        'image/gif'  => 'gif',
    ];

    public const DOC_MIMES = [
        'application/pdf' => 'pdf',
        'text/plain'      => 'txt',
    ];

    /**
     * آپلود یک تصویر. ابتدا تلاش می‌کند به تلگرام بفرستد (از طریق URL یکتای
     * /media/ سرو می‌شود) و در صورت شکست روی دیسک ذخیره می‌کند.
     *
     * @return array{success:bool, message?:string, telegram_file_id?:string, path?:string}
     */
    public static function image(array $file, string $subdir = 'products'): array
    {
        $check = self::validate($file, self::IMAGE_MIMES, self::MAX_IMAGE_SIZE);
        if (!$check['success']) {
            return $check;
        }

        // تأیید اینکه واقعاً تصویر است
        $info = @getimagesize($file['tmp_name']);
        if ($info === false) {
            return ['success' => false, 'message' => 'فایل ارسالی یک تصویر معتبر نیست.'];
        }
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > 12000 || $height > 12000 || ($width * $height) > 40000000) {
            return ['success' => false, 'message' => 'ابعاد تصویر نامعتبر یا بیش از حد مجاز (۴۰ مگاپیکسل) است.'];
        }

        $telegramId = self::sendToTelegram($file['tmp_name'], $file['name'] ?? 'image.jpg');
        if ($telegramId) {
            return ['success' => true, 'telegram_file_id' => $telegramId];
        }

        $saved = self::saveToDisk($file, $subdir, $check['ext']);
        if (!$saved['success']) {
            return $saved;
        }
        return ['success' => true, 'path' => $saved['path']];
    }

    /** آپلود پیوست تیکت (تصویر یا PDF) */
    public static function attachment(array $file, string $subdir = 'tickets'): array
    {
        $allowed = self::IMAGE_MIMES + self::DOC_MIMES;
        $check = self::validate($file, $allowed, self::MAX_FILE_SIZE);
        if (!$check['success']) {
            return $check;
        }

        $isImage = isset(self::IMAGE_MIMES[$check['mime']]);
        if ($isImage) {
            $tg = self::sendToTelegram($file['tmp_name'], $file['name'] ?? 'file');
            if ($tg) {
                return [
                    'success'          => true,
                    'telegram_file_id' => $tg,
                    'original_name'    => self::safeName($file['name'] ?? 'image'),
                    'mime_type'        => $check['mime'],
                    'file_size'        => (int) $file['size'],
                ];
            }
        }

        $saved = self::saveToDisk($file, $subdir, $check['ext']);
        if (!$saved['success']) {
            return $saved;
        }
        return [
            'success'       => true,
            'path'          => $saved['path'],
            'original_name' => self::safeName($file['name'] ?? 'file'),
            'mime_type'     => $check['mime'],
            'file_size'     => (int) $file['size'],
        ];
    }

    /** اعتبارسنجی مشترک */
    private static function validate(array $file, array $allowedMimes, int $maxSize): array
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => self::errorText((int) ($file['error'] ?? -1))];
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'فایل ارسالی معتبر نیست.'];
        }
        if ((int) $file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'حجم فایل بیش از حد مجاز است (' . round($maxSize / 1048576) . ' مگابایت).'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!isset($allowedMimes[$mime])) {
            return ['success' => false, 'message' => 'نوع فایل مجاز نیست. (' . $mime . ')'];
        }

        return ['success' => true, 'mime' => $mime, 'ext' => $allowedMimes[$mime]];
    }

    /** ذخیره روی دیسک با نام تصادفی (بدون اجراپذیری) */
    private static function saveToDisk(array $file, string $subdir, string $ext): array
    {
        $subdir = preg_replace('/[^a-z0-9_\-]/i', '', $subdir) ?: 'misc';
        $dir = SITE_ROOT . '/uploads/' . $subdir . '/' . date('Y/m');

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return ['success' => false, 'message' => 'امکان ساخت پوشه آپلود وجود ندارد.'];
        }
        if (!is_writable($dir)) {
            return ['success' => false, 'message' => 'پوشه آپلود قابل نوشتن نیست. دسترسی 755 بدهید.'];
        }

        $name = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = $dir . '/' . $name;

        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'انتقال فایل ناموفق بود.'];
        }
        @chmod($dest, 0644);

        // جلوگیری از اجرای PHP در پوشه آپلود
        $guard = SITE_ROOT . '/uploads/.htaccess';
        if (!is_file($guard)) {
            @file_put_contents($guard, "php_flag engine off\nOptions -ExecCGI\n<FilesMatch \"\\.(php|phtml|php\\d|pl|py|cgi|sh)$\">\n Require all denied\n</FilesMatch>\n");
        }

        $relative = 'uploads/' . $subdir . '/' . date('Y/m') . '/' . $name;
        return ['success' => true, 'path' => $relative];
    }

    /** ارسال تصویر به تلگرام و دریافت file_id */
    public static function sendToTelegram(string $tmpPath, string $filename = 'image.jpg'): ?string
    {
        try {
            $token = Settings::get('telegram_bot_token');
            $chatId = Settings::get('telegram_chat_id') ?: Settings::get('telegram_storage_chat_id');

            if (!$token || !$chatId || !function_exists('curl_init')) {
                return null;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION)) ?: 'jpg';
            $safeName = 'upload_' . bin2hex(random_bytes(6)) . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);

            $ch = curl_init("https://api.telegram.org/bot{$token}/sendPhoto");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 25,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'chat_id' => $chatId,
                    'photo'   => new \CURLFile($tmpPath, mime_content_type($tmpPath) ?: 'image/jpeg', $safeName),
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                return null;
            }
            $data = json_decode((string) $response, true);
            if (empty($data['ok']) || empty($data['result']['photo'])) {
                return null;
            }
            $photos = $data['result']['photo'];
            $largest = end($photos);
            return $largest['file_id'] ?? null;
        } catch (Throwable $e) {
            @error_log('[uploader/telegram] ' . $e->getMessage());
            return null;
        }
    }

    /** حذف فایل ذخیره‌شده روی دیسک */
    public static function deleteFile(?string $relativePath): bool
    {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/')) {
            return false;
        }
        $full = SITE_ROOT . '/' . ltrim($relativePath, '/');
        $real = realpath($full);
        $base = realpath(SITE_ROOT . '/uploads');
        if (!$real || !$base || !str_starts_with($real, $base)) {
            return false; // جلوگیری از path traversal
        }
        return @unlink($real);
    }

    /** آدرس قابل نمایش برای یک رکورد تصویر */
    public static function url(?string $telegramId, ?string $path, string $fallback = ''): string
    {
        if ($telegramId) {
            return \Core\Seo::imageUrl($telegramId, 'image');
        }
        if ($path) {
            return '/' . ltrim($path, '/');
        }
        return $fallback;
    }

    private static function safeName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^\p{L}\p{N}\.\-_ ]/u', '', $name) ?? 'file';
        return mb_substr(trim($name), 0, 120) ?: 'file';
    }

    private static function errorText(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیش از حد مجاز سرور است.',
            UPLOAD_ERR_PARTIAL    => 'فایل به‌طور کامل آپلود نشد.',
            UPLOAD_ERR_NO_FILE    => 'فایلی انتخاب نشده است.',
            UPLOAD_ERR_NO_TMP_DIR => 'پوشه موقت سرور در دسترس نیست.',
            UPLOAD_ERR_CANT_WRITE => 'نوشتن فایل روی دیسک ناموفق بود.',
            UPLOAD_ERR_EXTENSION  => 'یک افزونه PHP آپلود را متوقف کرد.',
            default               => 'خطای ناشناخته در آپلود فایل.',
        };
    }
}
