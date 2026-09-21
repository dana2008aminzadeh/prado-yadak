<?php
namespace Admin\core;

use Core\Database;
use PDO;

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['admin_user_id'])) {
            return null;
        }
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $db = Database::getInstance();
        $st = $db->prepare("SELECT id, full_name, phone, email, role FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
        $st->execute([(int) $_SESSION['admin_user_id']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $cache = $row ?: null;
        return $cache;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) {
            if (self::wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
                exit;
            }
            $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? '/admin';
            header('Location: /admin/login');
            exit;
        }
        return $u;
    }

    public static function attempt(string $phone, string $password): array
    {
        $phone = trim($phone);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db = Database::getInstance();

        $st = $db->prepare("SELECT COUNT(*) FROM login_attempts
                            WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $st->execute([$ip]);
        if ((int) $st->fetchColumn() >= 10) {
            return ['success' => false, 'message' => 'تعداد تلاش‌های ناموفق زیاد است. ۱۵ دقیقه دیگر تلاش کنید.'];
        }

        $st = $db->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
        $st->execute([$phone]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $db->prepare("INSERT INTO login_attempts (phone, ip_address, attempted_at) VALUES (?, ?, NOW())")
                ->execute([$phone, $ip]);
            return ['success' => false, 'message' => 'شماره موبایل یا رمز عبور اشتباه است.'];
        }

        if (($user['role'] ?? 'user') !== 'admin') {
            return ['success' => false, 'message' => 'این حساب دسترسی مدیریت ندارد.'];
        }

        $db->prepare("DELETE FROM login_attempts WHERE phone = ?")->execute([$phone]);
        session_regenerate_id(true);
        $_SESSION['admin_user_id'] = (int) $user['id'];
        $_SESSION['admin_login_at'] = time();
        return ['success' => true];
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_user_id'], $_SESSION['admin_login_at']);
    }

    public static function wantsJson(): bool
    {
        $a = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($a, 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public static function csrf(): string
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf'];
    }

    public static function verifyCsrf(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['admin_csrf'] ?? '', (string) $token)) {
            http_response_code(419);
            exit('نشست منقضی شده است. صفحه را رفرش کنید.');
        }
    }
}
