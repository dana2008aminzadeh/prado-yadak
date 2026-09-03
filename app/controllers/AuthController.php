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

    private function sendSmsIr($mobile, $code)
    {
        $api_key = '6yvodOobNXvR0bKclRjAAZTffumOuyQmeIOGJXKdEMO0JkHD';
        
        $template_id = 219706; 

        $data = [
            "mobile" => $mobile,
            "templateId" => $template_id,
            "parameters" => [
                [
                    "name" => "Code", 
                    "value" => (string)$code
                ]
            ]
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.sms.ir/v1/send/verify',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: text/plain',
                'x-api-key: ' . $api_key
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            http_response_code(500);
            echo json_encode(['error' => 'خطای ارتباط با سرور پیامک: ' . $err]);
            exit;
        }

        $result = json_decode($response, true);

        if (!isset($result['status']) || $result['status'] != 1) {
            $api_error_message = $result['message'] ?? 'خطای نامشخص از پنل sms.ir';
            http_response_code(400);
            echo json_encode(['error' => 'خطای پیامک: ' . $api_error_message]);
            exit;
        }

        return true;
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

        $user = User::findByPhone($phone);
        echo json_encode(['exists' => $user ? true : false]);
    }

    public function loginPassword()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';

        $user = User::findByPhone($phone);

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

    public function sendOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');

        $otp_code = rand(10000, 99999);

        // ذخیره کد در دیتابیس از طریق مدل
        Otp::create($phone, $otp_code);

        // ارسال پیامک واقعی
        $this->sendSmsIr($phone, $otp_code);

        echo json_encode(['message' => 'کد تایید با موفقیت پیامک شد.']);
    }

    public function verifyOtp()
    {
        $input = $this->validateApiRequest();
        $phone = trim($input['phone'] ?? '');
        $code = trim($input['code'] ?? '');

        // بررسی صحت کد از طریق مدل
        if (!Otp::verify($phone, $code)) {
            http_response_code(401);
            echo json_encode(['error' => 'کد تایید اشتباه است یا منقضی شده.']);
            exit;
        }

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
            
            // ثبت کاربر جدید از طریق مدل
            $user_id = User::create($full_name, $phone, $password_hash);
            $role = 'user';
            $msg = 'ثبت‌نام شما با موفقیت انجام شد.';
        }

        // پاک کردن کدهای مصرف شده
        Otp::deleteByPhone($phone);

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