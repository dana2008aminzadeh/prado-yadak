<?php
namespace App\controllers;

use Core\Database;
use PDO;

class AuthController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

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

    // بررسی اینکه آیا شماره موبایل در سیستم وجود دارد یا کاربر جدید است
    public function checkUser()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');

        if (!preg_match('/^09[0-9]{9}$/', $phone)) {
            http_response_code(400);
            echo json_encode(['error' => 'شماره موبایل نامعتبر است.']);
            exit;
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $exists = $stmt->fetch() ? true : false;

        echo json_encode(['exists' => $exists]);
    }

    // ورود کاربر قدیمی با رمز عبور
    public function loginPassword()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';

        $stmt = $this->db->prepare("SELECT id, password_hash, role FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['is_logged_in'] = true;

            echo json_encode(['message' => 'ورود با موفقیت انجام شد.']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'رمز عبور اشتباه است.']);
        }
    }

    // متد اختصاصی اتصال به پنل sms.ir
    private function sendSmsIr($mobile, $code)
    {
        $api_key = 'rPyOycfmesAuqqRHlk1UV8fYfXvl7bvktcgtcgyYFnBxWsst';

        // شما باید در پنل sms.ir یک قالب (Template) بسازید و آیدی آن را اینجا قرار دهید
        $template_id = 100000; // این عدد را با شناسه قالب خودتان جایگزین کنید

        $data = [
            "mobile" => $mobile,
            "templateId" => $template_id,
            "parameters" => [
                [
                    "name" => "CODE", // این نام باید دقیقا با متغیری که در پنل پیامک تعریف کردید یکی باشد
                    "value" => (string) $code
                ]
            ]
        ];

        $ch = curl_init("https://api.sms.ir/v1/send/verify");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Accept: text/plain",
            "x-api-key: " . $api_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // تایم‌اوت 5 ثانیه تا سایت کند نشود

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    // تولید و ارسال کد یکبار مصرف (مشترک بین کاربر جدید و قدیمی)
    // تولید و ارسال کد یکبار مصرف
    public function sendOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');

        // تولید کد 5 رقمی تصادفی
        $otp_code = rand(10000, 99999);

        // ذخیره در دیتابیس (انقضا 2 دقیقه)
        $stmt = $this->db->prepare("INSERT INTO otp_codes (phone, code, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 2 MINUTE))");
        $stmt->execute([$phone, $otp_code]);

        // ارسال پیامک واقعی از طریق sms.ir
        $this->sendSmsIr($phone, $otp_code);

        // لاگ کردن کد در فایل برای زمان برنامه‌نویسی و تست (اختیاری)
        error_log("کد ورود پرادو یدک برای {$phone} : {$otp_code}");

        echo json_encode(['message' => 'کد تایید با موفقیت پیامک شد.']);
    }

    // بررسی کد OTP و تکمیل عملیات (ورود کاربر قدیمی یا ثبت نام کاربر جدید)
    public function verifyOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $code = trim($input['code'] ?? '');

        // بررسی صحت کد و منقضی نشدن آن
        $stmt = $this->db->prepare("SELECT id FROM otp_codes WHERE phone = ? AND code = ? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone, $code]);
        $valid_otp = $stmt->fetch();

        if (!$valid_otp) {
            http_response_code(401);
            echo json_encode(['error' => 'کد تایید اشتباه است یا منقضی شده.']);
            exit;
        }

        // بررسی اینکه آیا کاربر از قبل وجود دارد؟
        $stmt = $this->db->prepare("SELECT id, role FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            // کاربر قدیمی است -> ورود موفق
            $user_id = $user['id'];
            $role = $user['role'];
            $msg = 'ورود با موفقیت انجام شد.';
        } else {
            // کاربر جدید است -> ثبت‌نام
            $full_name = trim($input['full_name'] ?? '');
            $password = $input['password'] ?? '';

            if (empty($full_name) || empty($password)) {
                http_response_code(400);
                echo json_encode(['error' => 'وارد کردن نام و رمز عبور برای ثبت نام الزامی است.']);
                exit;
            }

            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

            $stmt = $this->db->prepare("INSERT INTO users (full_name, phone, password_hash, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$full_name, $phone, $password_hash]);

            $user_id = $this->db->lastInsertId();
            $role = 'user';
            $msg = 'ثبت‌نام شما با موفقیت انجام شد.';
        }

        // پاک کردن کد OTP بعد از استفاده موفقیت آمیز
        $this->db->prepare("DELETE FROM otp_codes WHERE phone = ?")->execute([$phone]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_role'] = $role;
        $_SESSION['is_logged_in'] = true;

        echo json_encode(['message' => $msg]);
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('Location: /login');
        exit;
    }
}