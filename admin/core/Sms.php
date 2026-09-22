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
}
