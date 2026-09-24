<?php
namespace Admin\core;

use Core\Database;
use PDO;

class Auth
{
    private static ?array $cache = null;
    private static bool $loaded = false;
    private static ?array $perms = null;

    /** مدت بی‌فعالیتی مجاز (ثانیه) */
    public const IDLE_TIMEOUT = 3600;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$cache;
        }
        self::$loaded = true;

        if (empty($_SESSION['admin_user_id'])) {
            return self::$cache = null;
        }

        // انقضای نشست بر اثر بی‌فعالیتی
        $last = (int) ($_SESSION['admin_last_seen'] ?? 0);
        if ($last && (time() - $last) > self::IDLE_TIMEOUT) {
            self::logout();
            return self::$cache = null;
        }
        $_SESSION['admin_last_seen'] = time();

        // گره زدن نشست به مرورگر برای جلوگیری از سرقت کوکی
        $fp = self::fingerprint();
        if (!empty($_SESSION['admin_fp']) && !hash_equals($_SESSION['admin_fp'], $fp)) {
            self::logout();
            return self::$cache = null;
        }

        $db = Database::getInstance();
        $st = $db->prepare(
            "SELECT u.id, u.full_name, u.phone, u.email, u.role, u.admin_role_id,
                    COALESCE(u.is_active, 1) AS is_active,
                    r.slug AS role_slug, r.name AS role_name, r.permissions
             FROM users u
             LEFT JOIN admin_roles r ON r.id = u.admin_role_id
             WHERE u.id = ? AND u.role = 'admin' LIMIT 1"
        );
        $st->execute([(int) $_SESSION['admin_user_id']]);
        $row = $st->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int) $row['is_active'] !== 1) {
            self::logout();
            return self::$cache = null;
        }

        return self::$cache = $row;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** لیست دسترسی‌های مدیر جاری */
    public static function permissions(): array
    {
        if (self::$perms !== null) {
            return self::$perms;
        }
        $u = self::user();
        if (!$u) {
            return self::$perms = [];
        }
        // مدیری که نقشی به او تخصیص نیافته، مدیر کل در نظر گرفته می‌شود (سازگاری با نصب قبلی)
        if (empty($u['admin_role_id']) || $u['permissions'] === null) {
            return self::$perms = ['*'];
        }
        $list = array_values(array_filter(array_map('trim', explode(',', (string) $u['permissions']))));
        return self::$perms = $list;
    }

    public static function can(string $permission): bool
    {
        return Permission::granted(self::permissions(), $permission);
    }

    public static function isSuperAdmin(): bool
    {
        return in_array('*', self::permissions(), true);
    }

    /** اگر دسترسی نبود، متوقف کن */
    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (self::can($permission)) {
            return;
        }
        if (self::wantsJson()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'شما به این بخش دسترسی ندارید.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        http_response_code(403);
        $label = Permission::label($permission);
        require ADMIN_PATH . '/views/403.php';
        exit;
    }

    public static function requireLogin(): array
    {
        $u = self::user();
        if (!$u) {
            if (self::wantsJson()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'نشست منقضی شده است.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? '/admin';
            \Core\UrlCanonicalizer::redirect('/admin/login', 302, 'admin-auth');
        }
        return $u;
    }

    public static function attempt(string $phone, string $password): array
    {
        $phone = self::normalizePhone($phone);
        $ip = self::ip();
        $db = Database::getInstance();

        $st = $db->prepare("SELECT COUNT(*) FROM login_attempts
                            WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $st->execute([$ip]);
        if ((int) $st->fetchColumn() >= 10) {
            return ['success' => false, 'message' => 'تعداد تلاش‌های ناموفق زیاد است. ۱۵ دقیقه دیگر تلاش کنید.'];
        }

        $st = $db->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        $fail = function (string $msg) use ($db, $phone, $ip) {
            $db->prepare('INSERT INTO login_attempts (phone, ip_address, attempted_at) VALUES (?, ?, NOW())')
                ->execute([$phone, $ip]);
            // تأخیر کوتاه برای کند کردن حملات
            usleep(300000);
            return ['success' => false, 'message' => $msg];
        };

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return $fail('شماره موبایل یا رمز عبور اشتباه است.');
        }
        if (($user['role'] ?? 'user') !== 'admin') {
            return $fail('این حساب دسترسی مدیریت ندارد.');
        }
        if (array_key_exists('is_active', $user) && (int) $user['is_active'] !== 1) {
            return ['success' => false, 'message' => 'این حساب مدیریتی غیرفعال شده است.'];
        }

        // ارتقای الگوریتم هش در صورت نیاز
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), (int) $user['id']]);
        }

        $db->prepare('DELETE FROM login_attempts WHERE phone = ?')->execute([$phone]);

        session_regenerate_id(true);
        $_SESSION['admin_user_id']  = (int) $user['id'];
        $_SESSION['admin_login_at'] = time();
        $_SESSION['admin_last_seen'] = time();
        $_SESSION['admin_fp'] = self::fingerprint();
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));

        self::$loaded = false;
        self::$perms = null;

        Audit::log('auth.login', 'user', (int) $user['id'], 'ورود موفق به پنل مدیریت');

        return ['success' => true];
    }

    public static function logout(): void
    {
        $u = self::$cache;
        if ($u) {
            Audit::log('auth.logout', 'user', (int) $u['id'], 'خروج از پنل مدیریت');
        }
        unset(
            $_SESSION['admin_user_id'], $_SESSION['admin_login_at'],
            $_SESSION['admin_last_seen'], $_SESSION['admin_fp'], $_SESSION['admin_csrf']
        );
        self::$cache = null;
        self::$loaded = true;
        self::$perms = null;
    }

    // ---------------- CSRF ----------------

    public static function csrf(): string
    {
        if (empty($_SESSION['admin_csrf'])) {
            $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['admin_csrf'];
    }

    /** فیلد مخفی آماده برای فرم‌ها */
    public static function csrfField(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::csrf(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validCsrf(?string $token): bool
    {
        $stored = $_SESSION['admin_csrf'] ?? '';
        return $stored !== '' && is_string($token) && $token !== '' && hash_equals($stored, $token);
    }

    /**
     * اعتبارسنجی CSRF روی هر درخواست تغییر‌دهنده وضعیت.
     * توجه: اکنون شامل GET هم می‌شود؛ مسیریاب تعیین می‌کند کدام مسیرها تغییر‌دهنده‌اند.
     */
    public static function verifyCsrf(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::validCsrf(is_string($token) ? $token : '')) {
            self::rejectCsrf();
        }
        self::verifyOrigin();
    }

    /** بررسی هم‌مبدأ بودن درخواست (لایه دوم دفاع CSRF) */
    public static function verifyOrigin(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            $ref = $_SERVER['HTTP_REFERER'] ?? '';
            if ($ref === '') return; // بعضی پروکسی‌ها هدر نمی‌فرستند
            $origin = (string) (parse_url($ref, PHP_URL_SCHEME) . '://' . parse_url($ref, PHP_URL_HOST));
            $port = parse_url($ref, PHP_URL_PORT);
            if ($port) $origin .= ':' . $port;
        }
        // برای جلوگیری از Host Header Injection، هاست معتبر را از Seo::base() می‌گیریم، نه HTTP_HOST
        $base = \Core\Seo::base();
        $baseHost = strtolower((string) parse_url($base, PHP_URL_HOST));
        $originHost = strtolower((string) parse_url($origin, PHP_URL_HOST));
        // اگر origin ارسال شده، باید با هاست معتبر سایت یا زیردامنه آن مطابقت داشته باشد
        if ($origin !== '' && $originHost !== '' && $baseHost !== '') {
            if ($originHost !== $baseHost && !str_ends_with($originHost, '.' . $baseHost) && !in_array($originHost, ['localhost', '127.0.0.1'], true)) {
                self::rejectCsrf('مبدأ درخواست نامعتبر است.');
            }
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $self = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '');
            if (rtrim($origin, '/') !== rtrim($self, '/')) {
                self::rejectCsrf('مبدأ درخواست نامعتبر است.');
            }
        }
    }

    public static function rejectCsrf(string $msg = 'نشست شما منقضی شده یا درخواست نامعتبر است.'): void
    {
        Audit::log('security.csrf_blocked', 'request', null, $msg . ' | ' . ($_SERVER['REQUEST_URI'] ?? ''));
        http_response_code(419);
        if (self::wantsJson()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        $safe = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><html lang='fa' dir='rtl'><meta charset='utf-8'>
              <body style=\"font-family:Tahoma,sans-serif;padding:40px;text-align:center;background:#f6f7fb\">
              <div style='max-width:420px;margin:60px auto;background:#fff;padding:30px;border-radius:16px'>
              <h2 style='color:#be123c'>درخواست مسدود شد</h2>
              <p style='color:#555;font-size:14px'>{$safe}</p>
              <a href='/admin' style='color:#8b533a;font-weight:bold'>بازگشت به پنل</a></div></body></html>";
        exit;
    }

    // ---------------- کمکی ----------------

    public static function wantsJson(): bool
    {
        $a = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($a, 'application/json')
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
    }

    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    private static function fingerprint(): string
    {
        return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    }

    public static function normalizePhone(string $phone): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $ar = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $phone = str_replace($fa, range(0, 9), $phone);
        $phone = str_replace($ar, range(0, 9), $phone);
        $phone = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($phone, '98')) $phone = '0' . substr($phone, 2);
        if (str_starts_with($phone, '9') && strlen($phone) === 10) $phone = '0' . $phone;
        return $phone;
    }
}
