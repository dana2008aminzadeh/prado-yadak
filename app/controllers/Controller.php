<?php
namespace App\controllers;

class Controller
{
    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();
        }
    }
    
    protected function getClientIp(): string
    {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // بررسی اینکه آیا سایت از کلودفلر استفاده می‌کند و فرستنده واقعاً سرور کلودفلر است
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && $this->isCloudflareIp($remoteIp)) {
            $cfIp = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // اگر پشت کلودفلر معتبر نیستیم، هرگز به هدرهای ارسالی کاربر اعتماد نکنید
        return filter_var($remoteIp, FILTER_VALIDATE_IP) ? $remoteIp : '0.0.0.0';
    }

    private function isCloudflareIp(string $ip): bool
    {
        // رنج‌های رسمی IPv4 کلودفلر
        $cfRanges = [
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/13',
            '104.24.0.0/14',
            '172.64.0.0/13',
            '131.0.72.0/22'
        ];

        $ipLong = ip2long($ip);
        if ($ipLong === false)
            return false;

        foreach ($cfRanges as $range) {
            list($subnet, $bits) = explode('/', $range);
            $subnetLong = ip2long($subnet);
            $mask = -1 << (32 - (int) $bits);
            $subnetMasked = $subnetLong & $mask;
            if (($ipLong & $mask) === $subnetMasked) {
                return true;
            }
        }
        return false;
    }

    protected function verifyCsrfToken()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $client_csrf = $_POST['csrf_token'] ??
            $headers['X-CSRF-Token'] ??
            $headers['x-csrf-token'] ??
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($client_csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $client_csrf)) {
            http_response_code(403);
            echo json_encode(['error' => 'درخواست نامعتبر است (خطای امنیتی CSRF)']);
            exit;
        }
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}