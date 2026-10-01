<?php
namespace Admin\core;

use Core\Database;
use PDO;
use Throwable;

/**
 * سرویس ارسال پیامک مبتنی بر SMS.ir (نسخه ۳) با لاگ کامل
 */
class Sms
{
    /** جایگزینی متغیرهای قالب */
    public static function render(string $body, array $vars): string
    {
        $map = [];
        foreach ($vars as $k => $v) {
            $map['{' . $k . '}'] = (string) $v;
        }
        return strtr($body, $map);
    }

    public static function template(string $key): ?array
    {
        try {
            $st = Database::getInstance()->prepare('SELECT * FROM sms_templates WHERE template_key = ? LIMIT 1');
            $st->execute([$key]);
            $r = $st->fetch(PDO::FETCH_ASSOC);
            return $r ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * ارسال بر اساس یک قالب ذخیره‌شده.
     * اگر $onlyIfAuto=true باشد، فقط وقتی می‌فرستد که قالب auto_send داشته باشد.
     */
    public static function sendTemplate(string $key, string $phone, array $vars, ?int $userId = null, bool $onlyIfAuto = false): array
    {
        $tpl = self::template($key);
        if (!$tpl) {
            return ['success' => false, 'message' => 'قالب پیامک یافت نشد: ' . $key];
        }
        if ((int) $tpl['is_active'] !== 1) {
            return ['success' => false, 'message' => 'قالب پیامک غیرفعال است.'];
        }
        if ($onlyIfAuto && (int) $tpl['auto_send'] !== 1) {
            return ['success' => false, 'message' => 'ارسال خودکار این قالب خاموش است.', 'skipped' => true];
        }
        $text = self::render((string) $tpl['body'], $vars);
        return self::send($phone, $text, $userId, $key);
    }

    /**
     * ارسال یک پیامک و ثبت در sms_logs
     */
    public static function send(string $phone, string $message, ?int $userId = null, ?string $templateKey = null, ?int $campaignId = null): array
    {
        $phone = Auth::normalizePhone($phone);
        $message = trim($message);

        if (!preg_match('/^09\d{9}$/', $phone)) {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'شماره موبایل نامعتبر است.');
        }
        if ($message === '') {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'متن پیامک خالی است.');
        }
        if (!Settings::bool('sms_enabled')) {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'سرویس پیامک در تنظیمات غیرفعال است.');
        }

        $apiKey = Settings::get('smsir_api_key');
        $line   = Settings::get('smsir_line_number');
        if (!$apiKey || !$line) {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'کلید API یا شماره خط پیامک تنظیم نشده است.');
        }
        if (!function_exists('curl_init')) {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'افزونه cURL روی سرور فعال نیست.');
        }

        try {
            $payload = json_encode([
                'lineNumber'  => (int) $line,
                'messageText' => $message,
                'mobiles'     => [$phone],
            ], JSON_UNESCAPED_UNICODE);

            $ch = curl_init('https://api.sms.ir/v1/send/bulk');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-api-key: ' . $apiKey,
                ],
            ]);
            $response = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false || $curlErr) {
                return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, 'خطای ارتباط: ' . $curlErr);
            }

            $data = json_decode((string) $response, true);
            $status = (int) ($data['status'] ?? 0);

            if ($http === 200 && $status === 1) {
                $msgId = (string) ($data['data']['messageIds'][0] ?? ($data['data']['packId'] ?? ''));
                return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'sent', $msgId, null);
            }

            $err = (string) ($data['message'] ?? ('کد پاسخ: ' . $http));
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, $err);
        } catch (Throwable $e) {
            return self::logAndReturn($userId, $phone, $message, $templateKey, $campaignId, 'failed', null, $e->getMessage());
        }
    }

    private static function logAndReturn(
        ?int $userId, string $phone, string $message, ?string $templateKey,
        ?int $campaignId, string $status, ?string $providerId, ?string $error
    ): array {
        try {
            $st = Database::getInstance()->prepare(
                'INSERT INTO sms_logs (user_id, phone, message, template_key, campaign_id, status,
                                       provider_message_id, error_message, admin_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $st->execute([
                $userId, $phone, mb_substr($message, 0, 1000), $templateKey, $campaignId,
                $status, $providerId, $error !== null ? mb_substr($error, 0, 255) : null,
                $_SESSION['admin_user_id'] ?? null,
            ]);
        } catch (Throwable $e) {
            @error_log('[sms/log] ' . $e->getMessage());
        }

        return $status === 'sent'
            ? ['success' => true, 'message_id' => $providerId]
            : ['success' => false, 'message' => $error ?? 'ارسال ناموفق بود.'];
    }

    /** تعداد قطعه پیامک و تشخیص فارسی بودن */
    public static function parts(string $text): array
    {
        $isUnicode = (bool) preg_match('/[^\x20-\x7E]/', $text);
        $len = mb_strlen($text, 'UTF-8');
        $per = $isUnicode ? 70 : 160;
        $perMulti = $isUnicode ? 67 : 153;
        $count = $len <= $per ? 1 : (int) ceil($len / $perMulti);
        return ['length' => $len, 'parts' => max(1, $count), 'unicode' => $isUnicode];
    }

    // ---------------------------------------------------------------- قالب‌های Verify (سرویس پیامکی sms.ir)

    /**
     * ارسال با قالب تأییدشده (endpoint سرویس ارسال سریع sms.ir).
     * این متد عمداً هیچ کلید روشن/خاموشی را بررسی نمی‌کند تا برای
     * رمز یکبارمصرف ورود هم قابل استفاده باشد؛ کنترل هر سوییچ با فراخواننده است.
     *
     * @param array $params نگاشت نام‌پارامترِ قالب به مقدار، مثل ['CODE' => '12345']
     */
    public static function sendVerify(string $phone, ?int $templateId, array $params, ?int $userId = null, ?string $templateKey = null, ?string $apiKey = null): array
    {
        $phone = Auth::normalizePhone($phone);
        $templateId = (int) $templateId;

        // متن ذخیره‌شده در گزارش: برای OTP مقدار کد نگهداری نمی‌شود
        $logPairs = [];
        foreach ($params as $k => $v) {
            $logPairs[] = $k . ': ' . (($templateKey === 'otp' && strtoupper((string) $k) === 'CODE') ? '•••••' : (string) $v);
        }
        $logText = 'قالب #' . ($templateId ?: '؟') . ($logPairs ? ' — ' . implode('، ', $logPairs) : '');

        if (!preg_match('/^09\d{9}$/', $phone)) {
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, 'شماره موبایل نامعتبر است.');
        }
        if ($templateId <= 0) {
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, 'شناسه قالب (templateId) در تنظیمات ثبت نشده است.');
        }
        $apiKey = $apiKey !== null && $apiKey !== '' ? $apiKey : (string) Settings::get('smsir_api_key');
        if ($apiKey === '') {
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, 'کلید API پیامک در تنظیمات ثبت نشده است.');
        }
        if (!function_exists('curl_init')) {
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, 'افزونه cURL روی سرور فعال نیست.');
        }

        $parameters = [];
        foreach ($params as $k => $v) {
            $parameters[] = ['name' => (string) $k, 'value' => (string) $v];
        }

        try {
            $ch = curl_init('https://api.sms.ir/v1/send/verify');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'mobile'     => $phone,
                    'templateId' => $templateId,
                    'parameters' => $parameters,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'x-api-key: ' . $apiKey,
                ],
            ]);
            $response = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false || $curlErr) {
                return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, 'خطای ارتباط: ' . $curlErr);
            }

            $data = json_decode((string) $response, true);
            $status = (int) ($data['status'] ?? 0);

            if ($http === 200 && $status === 1) {
                $msgId = (string) ($data['data']['messageId'] ?? ($data['data']['packId'] ?? ''));
                return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'sent', $msgId, null);
            }

            $err = (string) ($data['message'] ?? ('کد پاسخ: ' . $http));
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, $err);
        } catch (Throwable $e) {
            return self::logAndReturn($userId, $phone, $logText, $templateKey, null, 'failed', null, $e->getMessage());
        }
    }

    // ---------------------------------------------------------------- وضعیت حساب sms.ir

    /** درخواست GET به یک endpoint عمومی sms.ir */
    private static function apiGet(string $path): array
    {
        $apiKey = (string) Settings::get('smsir_api_key');
        if ($apiKey === '') {
            return ['success' => false, 'message' => 'کلید API در تنظیمات ثبت نشده است.'];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'افزونه cURL روی سرور فعال نیست.'];
        }

        try {
            $ch = curl_init('https://api.sms.ir/v1/' . ltrim($path, '/'));
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_HTTPHEADER     => [
                    'Accept: application/json',
                    'x-api-key: ' . $apiKey,
                ],
            ]);
            $response = curl_exec($ch);
            $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false || $curlErr) {
                return ['success' => false, 'message' => 'خطای ارتباط: ' . $curlErr];
            }

            $data = json_decode((string) $response, true);
            if ($http === 200 && (int) ($data['status'] ?? 0) === 1) {
                return ['success' => true, 'data' => $data['data'] ?? null];
            }

            return ['success' => false, 'message' => (string) ($data['message'] ?? ('کد پاسخ: ' . $http))];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /** اعتبار (موجودی) فعلی پنل */
    public static function getCredit(): array
    {
        $r = self::apiGet('credit');
        if (empty($r['success'])) {
            return ['success' => false, 'credit' => null, 'message' => $r['message'] ?? 'خطا در دریافت اعتبار'];
        }
        $credit = $r['data']['credit'] ?? ($r['data'] ?? null);
        return ['success' => true, 'credit' => is_numeric($credit) ? (float) $credit : null, 'message' => null];
    }

    /** خطوط مجاز ارسال روی حساب */
    public static function getLines(): array
    {
        $r = self::apiGet('line');
        if (empty($r['success'])) {
            return ['success' => false, 'lines' => [], 'message' => $r['message'] ?? 'خطا در دریافت خطوط'];
        }
        $raw = $r['data']['lines'] ?? $r['data'] ?? [];
        $lines = [];
        foreach (is_array($raw) ? $raw : [] as $ln) {
            $lines[] = is_array($ln) ? (string) ($ln['lineNumber'] ?? reset($ln)) : (string) $ln;
        }
        return ['success' => true, 'lines' => $lines, 'message' => null];
    }

    /**
     * نمای کلی حساب sms.ir (اعتبار + خطوط) با کش کوتاه‌مدت در سشن
     * تا بارگذاری صفحه‌ها کند نشود. با ?refresh=1 تازه‌سازی می‌شود.
     */
    public static function accountOverview(bool $refresh = false, int $ttl = 600): array
    {
        $stored = $_SESSION['smsir_account_overview'] ?? null;
        if (!$refresh && is_array($stored) && (time() - (int) ($stored['_at'] ?? 0)) < $ttl) {
            return $stored;
        }

        $overview = [
            '_at'        => time(),
            'fetched'    => false,
            'credit'     => null,
            'lines'      => [],
            'error'      => null,
            'fetched_at' => date('Y-m-d H:i:s'),
        ];

        if (!Settings::get('smsir_api_key')) {
            $overview['error'] = 'کلید API تنظیم نشده است.';
        } else {
            $c = self::getCredit();
            if ($c['success']) {
                $overview['fetched'] = true;
                $overview['credit'] = $c['credit'];
            } else {
                $overview['error'] = $c['message'];
            }

            $l = self::getLines();
            if ($l['success']) {
                $overview['lines'] = $l['lines'];
                $overview['fetched'] = true;
            } elseif ($overview['error'] === null) {
                $overview['error'] = $l['message'];
            }
        }

        $_SESSION['smsir_account_overview'] = $overview;
        return $overview;
    }

    // ---------------------------------------------------------------- اعلان به مدیران

    /** فهرست موبایل مدیران گیرنده اعلان (از تنظیم sms_admin_phones) */
    public static function adminPhones(): array
    {
        $raw = (string) Settings::get('sms_admin_phones', '');
        $out = [];
        foreach (preg_split('/[\s,،;\r\n]+/u', $raw) ?: [] as $n) {
            $p = Auth::normalizePhone((string) $n);
            if (preg_match('/^09\d{9}$/', $p)) {
                $out[$p] = true;
            }
        }
        return array_keys($out);
    }

    /** ارسال یک متن به همه مدیران تعریف‌شده در تنظیمات */
    public static function notifyAdmins(string $message, ?string $templateKey = 'admin_alert'): array
    {
        $message = trim($message);
        $phones = self::adminPhones();
        if (!$phones) {
            return ['success' => false, 'skipped' => true, 'message' => 'موبایل مدیر در تنظیمات (sms_admin_phones) ثبت نشده است.'];
        }

        $sent = 0;
        $failed = 0;
        $lastError = '';
        foreach ($phones as $p) {
            $r = self::send($p, $message, null, $templateKey);
            if ($r['success']) {
                $sent++;
            } else {
                $failed++;
                $lastError = (string) ($r['message'] ?? '');
            }
            usleep(100000);
        }

        return [
            'success' => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'message' => $sent > 0
                ? "اعلان برای {$sent} مدیر ارسال شد" . ($failed ? " (ناموفق: {$failed})" : '')
                : ('ارسال اعلان به مدیران ناموفق بود' . ($lastError ? ': ' . $lastError : '')),
        ];
    }

    /**
     * رندر بدنه قالب از جدول sms_templates؛ اگر قالب وجود نداشته باشد یا
     * غیرفعال باشد، از متن پیش‌فرض استفاده می‌کند تا اعلان‌ها همیشه کار کنند.
     */
    public static function renderTemplate(string $key, string $defaultBody, array $vars): string
    {
        $tpl = self::template($key);
        $body = ($tpl && (int) $tpl['is_active'] === 1) ? (string) $tpl['body'] : $defaultBody;
        return self::render($body, $vars);
    }
}
