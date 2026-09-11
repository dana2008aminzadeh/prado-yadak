<?php
namespace App\controllers;

use App\models\User;
use App\models\Otp;

class AuthController
{
    public function loginForm()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /profile');
            exit;
        }
        require_once VIEWS_PATH . '/login.php';
    }

    private function validateApiRequest()
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $headers = getallheaders();
        $client_csrf = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? '');

        if (empty($client_csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $client_csrf)) {
            http_response_code(403);
            echo json_encode(['error' => 'درخواست نامعتبر است (خطای امنیتی)']);
            exit;
        }
        return $input;
    }

    public function checkUser()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');

        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            http_response_code(400);
            echo json_encode(['error' => 'شماره موبایل نامعتبر است.']);
            exit;
        }

        // استفاده از مدل User
        $user = User::findByPhone($phone);
        echo json_encode(['exists' => $user ? true : false]);
    }

    public function loginPassword()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';

        $attempt_key = 'login_attempts_' . $phone;
        $lockout_key = 'login_lockout_' . $phone;

        if (isset($_SESSION[$lockout_key]) && time() < $_SESSION[$lockout_key]) {
            $remaining = ceil(($_SESSION[$lockout_key] - time()) / 60);
            http_response_code(429);
            echo json_encode(['error' => "حساب شما موقتاً مسدود شده است. لطفاً $remaining دقیقه دیگر مجدداً تلاش کنید."]);
            exit;
        }

        // استفاده از مدل User
        $user = User::findByPhone($phone);

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION[$attempt_key]);
            unset($_SESSION[$lockout_key]);

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_logged_in'] = true;

            $redirectUrl = $_SESSION['redirect_after_login'] ?? '/profile';
            unset($_SESSION['redirect_after_login']);

            echo json_encode(['message' => 'ورود با موفقیت انجام شد.', 'redirect' => $redirectUrl]);
        } else {
            $_SESSION[$attempt_key] = ($_SESSION[$attempt_key] ?? 0) + 1;

            if ($_SESSION[$attempt_key] >= 5) {
                $_SESSION[$lockout_key] = time() + (15 * 60);
                http_response_code(429);
                echo json_encode(['error' => 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً ۱۵ دقیقه دیگر تلاش کنید.']);
            } else {
                $rem = 5 - $_SESSION[$attempt_key];
                http_response_code(401);
                echo json_encode(['error' => "رمز عبور اشتباه است. ($rem تلاش باقیمانده)"]);
            }
        }
    }

    private function sendSmsIr($mobile, $code)
    {
        $api_key = '6yvodOobNXvR0bKclRjAAZTffumOuyQmeIOGJXKdEMO0JkHD';
        $template_id = 424335;
        $data = ["mobile" => $mobile, "templateId" => $template_id, "parameters" => [["name" => "CODE", "value" => (string) $code]]];

        $ch = curl_init("https://api.sms.ir/v1/send/verify");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Accept: text/plain", "x-api-key: " . $api_key]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function sendOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');

        // کنترل محدودیت‌ها از طریق مدل Otp
        $limitStatus = Otp::checkRateLimits($phone);

        if ($limitStatus === 'wait_2_min') {
            http_response_code(429);
            echo json_encode(['error' => 'لطفاً ۲ دقیقه صبر کنید و سپس مجدداً درخواست دهید.']);
            exit;
        }
        if ($limitStatus === 'hourly_limit') {
            http_response_code(429);
            echo json_encode(['error' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً یک ساعت دیگر تلاش کنید.']);
            exit;
        }

        $otp_code = rand(10000, 99999);

        // ساخت کد تایید با مدل
        Otp::create($phone, $otp_code);

        $this->sendSmsIr($phone, $otp_code);
        error_log("کد ورود پرادو یدک برای {$phone} : {$otp_code}");

        echo json_encode(['message' => 'کد تایید با موفقیت پیامک شد.']);
    }

    public function verifyOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $code = trim($input['code'] ?? '');

        // تایید با مدل
        if (!Otp::verify($phone, $code)) {
            http_response_code(401);
            echo json_encode(['error' => 'کد تایید اشتباه است یا منقضی شده.']);
            exit;
        }

        // جستجوی کاربر با مدل
        $user = User::findByPhone($phone);

        if ($user) {
            $user_id = $user['id'];
            $role = $user['role'];
            $msg = 'ورود با موفقیت انجام شد.';
        } else {
            $full_name = trim($input['full_name'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($full_name) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'وارد کردن نام و رمز عبور برای ثبت نام الزامی است.']);
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            // ثبت کاربر با مدل
            $user_id = User::create($full_name, $phone, $password_hash);
            $role = 'user';
            $msg = 'ثبت‌نام شما با موفقیت انجام شد.';
        }

        // پاک کردن کدها با مدل
        Otp::deleteByPhone($phone);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $role;
        $_SESSION['is_logged_in'] = true;

        $defaultRedirect = $user ? '/profile' : '/parts';
        $redirectUrl = $_SESSION['redirect_after_login'] ?? $defaultRedirect;
        unset($_SESSION['redirect_after_login']);

        echo json_encode(['message' => $msg, 'redirect' => $redirectUrl]);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }
}