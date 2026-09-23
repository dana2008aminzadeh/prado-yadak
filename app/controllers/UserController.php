<?php

namespace App\controllers;

use App\models\User;
use App\models\Order;
use App\models\Address;
use App\models\Vehicle;
use App\models\Wishlist;
use App\models\Wallet;
use App\models\Ticket;

class UserController extends Controller
{
    /**
     * صفحه اصلی پنل کاربری — SSR با داده واقعی
     */
    public function profile()
    {
        header('X-Robots-Tag: noindex, nofollow', true);

        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            header('Location: /login');
            exit;
        }

        $user = User::findById($userId);
        if (!$user) {
            session_unset();
            session_destroy();
            header('Location: /login');
            exit;
        }

        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $pageTitle = 'پنل کاربری | ' . $siteName;

        // داده‌های پنل
        $orderStats = Order::getStats($userId);
        $orders = Order::getByUserId($userId, null, 30);
        $addresses = Address::getByUserId($userId);
        $vehicles = Vehicle::getByUserId($userId);
        $wishlist = Wishlist::getByUserId($userId);
        $walletBalance = Wallet::getBalance($userId);
        $walletTransactions = Wallet::getTransactions($userId, 20);
        $tickets = Ticket::getByUserId($userId);
        $membership = User::getMembershipLevel($userId);
        $userInitials = User::getInitials($user['full_name'] ?? '');

        // آخرین سفارش‌ها برای داشبورد (حداکثر ۵)
        $recentOrders = array_slice($orders, 0, 5);

        // خودروی اصلی
        $primaryVehicle = null;
        foreach ($vehicles as $v) {
            if ((int) ($v['is_primary'] ?? 0) === 1) {
                $primaryVehicle = $v;
                break;
            }
        }
        if (!$primaryVehicle && !empty($vehicles)) {
            $primaryVehicle = $vehicles[0];
        }

        $openTicketsCount = Ticket::countOpen($userId);
        $wishlistCount = count($wishlist);
        $vehiclesCount = count($vehicles);
        $addressesCount = count($addresses);

        // مدل‌های خودرو برای دراپ‌داون (از دیتابیس / گلوبال)
        $carModelsList = [];
        if (!empty($GLOBALS['car_models']) && is_array($GLOBALS['car_models'])) {
            foreach ($GLOBALS['car_models'] as $slug => $data) {
                $carModelsList[] = [
                    'slug' => (string) $slug,
                    'name' => is_array($data) ? (string) ($data['name'] ?? $slug) : (string) $data,
                ];
            }
        } else {
            try {
                $carModelsList = \App\models\CarModel::getList();
            } catch (\Throwable $e) {
                $carModelsList = [];
            }
        }

        // مشخصات فروشگاه برای فاکتور رسمی
        $shopInfo = [
            'title' => $settings['site_title'] ?? 'پرادو یدک',
            'subtitle' => $settings['site_subtitle'] ?? 'PRADO YADAK',
            'phone' => $settings['phone_number'] ?? '',
            'address' => $settings['address'] ?? '',
            'work_hours' => $settings['work_hours'] ?? '',
            'bank_name' => $settings['bank_name'] ?? '',
            'bank_sheba' => $settings['bank_sheba'] ?? '',
            'bank_card' => $settings['bank_card_number'] ?? '',
            'bank_owner' => $settings['bank_account_owner'] ?? ($settings['site_title'] ?? 'پرادو یدک'),
            'economic_code' => $settings['economic_code'] ?? ($settings['shop_economic_code'] ?? ''),
            'national_id' => $settings['shop_national_id'] ?? ($settings['company_national_id'] ?? ''),
            'registration_number' => $settings['registration_number'] ?? '',
            'postal_code' => $settings['shop_postal_code'] ?? '6681898204',
            // آدرس سایت فقط از Core\Seo::base() گرفته می‌شود تا با SITE_URL دارای
            // پروتکل، خروجی خرابی مثل https://https://... ساخته نشود.
            'website' => \Core\Seo::base(),
        ];

        // تب اولیه از query string (اختیاری)
        $allowedTabs = ['dashboard', 'orders', 'vehicles', 'addresses', 'wishlist', 'wallet', 'tickets', 'settings'];
        $activeTab = isset($_GET['tab']) && in_array($_GET['tab'], $allowedTabs, true)
            ? $_GET['tab']
            : 'dashboard';

        require_once VIEWS_PATH . '/profile.php';
    }

    /* =========================================================
     * API: پروفایل و تنظیمات
     * ========================================================= */

    public function apiUpdateProfile()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();

        $fullName = trim((string) ($input['full_name'] ?? ''));
        if (mb_strlen($fullName, 'UTF-8') < 3) {
            $this->jsonResponse(['success' => false, 'message' => 'نام و نام‌خانوادگی باید حداقل ۳ کاراکتر باشد.'], 400);
        }

        $email = trim((string) ($input['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->jsonResponse(['success' => false, 'message' => 'ایمیل وارد شده معتبر نیست.'], 400);
        }

        $nationalId = preg_replace('/\D/', '', (string) ($input['national_id'] ?? ''));
        // کد ملی کاملاً اختیاری است؛ فقط اگر کاربر چیزی وارد کرد اعتبارسنجی می‌شود
        if ($nationalId !== '' && !$this->isValidNationalId($nationalId)) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'کد ملی واردشده معتبر نیست. می‌توانید این فیلد را خالی بگذارید (اختیاری است).',
            ], 400);
        }

        $ok = User::updateProfile($userId, [
            'full_name' => $fullName,
            'email' => $email,
            'national_id' => $nationalId,
            'city' => trim((string) ($input['city'] ?? '')),
        ]);

        if (!$ok) {
            $this->jsonResponse(['success' => false, 'message' => 'خطا در به‌روزرسانی مشخصات.'], 500);
        }

        $user = User::findById($userId);
        $this->jsonResponse([
            'success' => true,
            'message' => 'مشخصات حساب با موفقیت ذخیره شد.',
            'user' => [
                'full_name' => $user['full_name'] ?? '',
                'email' => $user['email'] ?? '',
                'national_id' => $user['national_id'] ?? '',
                'city' => $user['city'] ?? '',
                'initials' => User::getInitials($user['full_name'] ?? ''),
            ],
        ]);
    }

    public function apiChangePassword()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();

        $current = (string) ($input['current_password'] ?? '');
        $newPass = (string) ($input['new_password'] ?? '');
        $confirm = (string) ($input['confirm_password'] ?? '');

        if ($newPass !== $confirm) {
            $this->jsonResponse(['success' => false, 'message' => 'تکرار رمز عبور با رمز جدید یکسان نیست.'], 400);
        }

        $result = User::changePassword($userId, $current, $newPass);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * API: سفارش‌ها
     * ========================================================= */

    public function apiOrders()
    {
        $userId = $this->requireAuthApi();
        $status = trim($_GET['status'] ?? 'all');
        if (!in_array($status, ['all', 'processing', 'shipped', 'delivered', 'cancelled'], true)) {
            $status = 'all';
        }

        $orders = Order::getByUserId($userId, $status === 'all' ? null : $status, 50);
        $formatted = [];

        foreach ($orders as $o) {
            $formatted[] = $this->formatOrderSummary($o);
        }

        $this->jsonResponse([
            'success' => true,
            'orders' => $formatted,
            'stats' => Order::getStats($userId),
        ]);
    }

    public function apiOrderDetail()
    {
        $userId = $this->requireAuthApi();
        $orderId = (int) ($_GET['id'] ?? 0);

        if ($orderId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه سفارش نامعتبر است.'], 400);
        }

        $detail = Order::getDetailForUser($orderId, $userId);
        if (!$detail) {
            $this->jsonResponse(['success' => false, 'message' => 'سفارش یافت نشد.'], 404);
        }

        $this->jsonResponse([
            'success' => true,
            'order' => $this->formatOrderDetail($detail),
        ]);
    }

    /* =========================================================
     * API: آدرس‌ها
     * ========================================================= */

    public function apiAddresses()
    {
        $userId = $this->requireAuthApi();
        $this->jsonResponse([
            'success' => true,
            'addresses' => Address::getByUserId($userId),
        ]);
    }

    public function apiAddressCreate()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $result = Address::create($userId, $input);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiAddressUpdate()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه آدرس نامعتبر است.'], 400);
        }

        $result = Address::update($id, $userId, $input);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiAddressDelete()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه آدرس نامعتبر است.'], 400);
        }

        $result = Address::delete($id, $userId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiAddressSetDefault()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه آدرس نامعتبر است.'], 400);
        }

        $result = Address::setDefault($id, $userId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * API: خودروها
     * ========================================================= */

    public function apiVehicles()
    {
        $userId = $this->requireAuthApi();
        $this->jsonResponse([
            'success' => true,
            'vehicles' => Vehicle::getByUserId($userId),
        ]);
    }

    public function apiVehicleCreate()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $result = Vehicle::create($userId, $input);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiVehicleUpdate()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه خودرو نامعتبر است.'], 400);
        }

        $result = Vehicle::update($id, $userId, $input);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiVehicleDelete()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه خودرو نامعتبر است.'], 400);
        }

        $result = Vehicle::delete($id, $userId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiVehicleSetPrimary()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $id = (int) ($input['id'] ?? 0);

        if ($id <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه خودرو نامعتبر است.'], 400);
        }

        $result = Vehicle::setPrimary($id, $userId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * API: نشان‌شده‌ها
     * ========================================================= */

    public function apiWishlist()
    {
        $userId = $this->requireAuthApi();
        $this->jsonResponse([
            'success' => true,
            'items' => Wishlist::getByUserId($userId),
            'count' => Wishlist::count($userId),
        ]);
    }

    public function apiWishlistToggle()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $productId = (int) ($input['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه محصول نامعتبر است.'], 400);
        }

        $result = Wishlist::toggle($userId, $productId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiWishlistRemove()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $productId = (int) ($input['product_id'] ?? 0);

        if ($productId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه محصول نامعتبر است.'], 400);
        }

        $result = Wishlist::remove($userId, $productId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * API: کیف پول
     * ========================================================= */

    public function apiWallet()
    {
        $userId = $this->requireAuthApi();
        $this->jsonResponse([
            'success' => true,
            'balance' => Wallet::getBalance($userId),
            'transactions' => Wallet::getTransactions($userId, 30),
        ]);
    }

    public function apiWalletCharge()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $amount = (int) ($input['amount'] ?? 0);

        $result = Wallet::requestCharge($userId, $amount);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * API: تیکت پشتیبانی
     * ========================================================= */

    public function apiTickets()
    {
        $userId = $this->requireAuthApi();
        $tickets = Ticket::getByUserId($userId);
        $formatted = [];

        foreach ($tickets as $t) {
            $formatted[] = [
                'id' => (int) $t['id'],
                'subject' => $t['subject'],
                'status' => $t['status'],
                'status_label' => Ticket::statusLabel($t['status']),
                'priority' => $t['priority'],
                'last_message' => $t['last_message'] ?? '',
                'message_count' => (int) ($t['message_count'] ?? 0),
                'created_at' => $t['created_at'],
                'updated_at' => $t['updated_at'],
                'created_at_shamsi' => function_exists('toShamsi') ? toShamsi($t['created_at']) : $t['created_at'],
            ];
        }

        $this->jsonResponse([
            'success' => true,
            'tickets' => $formatted,
            'open_count' => Ticket::countOpen($userId),
        ]);
    }

    public function apiTicketCreate()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();

        $result = Ticket::create(
            $userId,
            (string) ($input['subject'] ?? ''),
            (string) ($input['message'] ?? ''),
            (string) ($input['priority'] ?? 'normal')
        );

        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiTicketDetail()
    {
        $userId = $this->requireAuthApi();
        $ticketId = (int) ($_GET['id'] ?? 0);

        if ($ticketId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه تیکت نامعتبر است.'], 400);
        }

        $ticket = Ticket::findById($ticketId, $userId);
        if (!$ticket) {
            $this->jsonResponse(['success' => false, 'message' => 'تیکت یافت نشد.'], 404);
        }

        $messages = Ticket::getMessages($ticketId, $userId);

        $this->jsonResponse([
            'success' => true,
            'ticket' => [
                'id' => (int) $ticket['id'],
                'subject' => $ticket['subject'],
                'status' => $ticket['status'],
                'status_label' => Ticket::statusLabel($ticket['status']),
                'priority' => $ticket['priority'],
                'created_at' => $ticket['created_at'],
                'created_at_shamsi' => function_exists('toShamsi') ? toShamsi($ticket['created_at']) : $ticket['created_at'],
            ],
            'messages' => array_map(function ($m) {
                return [
                    'id' => (int) $m['id'],
                    'sender_type' => $m['sender_type'],
                    'sender_name' => $m['sender_name'],
                    'message' => $m['message'],
                    'created_at' => $m['created_at'],
                    'created_at_shamsi' => function_exists('toShamsi') ? toShamsi($m['created_at']) : $m['created_at'],
                ];
            }, $messages),
        ]);
    }

    public function apiTicketReply()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $ticketId = (int) ($input['ticket_id'] ?? 0);

        if ($ticketId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه تیکت نامعتبر است.'], 400);
        }

        $result = Ticket::reply($ticketId, $userId, (string) ($input['message'] ?? ''));
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    public function apiTicketClose()
    {
        $userId = $this->requireAuthApi();
        $input = $this->jsonInput();
        $ticketId = (int) ($input['ticket_id'] ?? 0);

        if ($ticketId <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'شناسه تیکت نامعتبر است.'], 400);
        }

        $result = Ticket::close($ticketId, $userId);
        $this->jsonResponse($result, $result['success'] ? 200 : 400);
    }

    /* =========================================================
     * Helpers
     * ========================================================= */

    private function requireAuthApi(): int
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse(['success' => false, 'message' => 'لطفاً ابتدا وارد حساب کاربری شوید.'], 401);
        }

        return (int) $_SESSION['user_id'];
    }

    private function jsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '[]', true);
        return is_array($data) ? $data : [];
    }

    private function formatOrderSummary(array $o): array
    {
        return [
            'id' => (int) $o['id'],
            'tracking_code' => $o['tracking_code'],
            'status' => $o['status'],
            'status_label' => Order::statusLabel($o['status'] ?? ''),
            'status_color' => Order::statusColor($o['status'] ?? ''),
            'total_amount' => (float) ($o['total_amount'] ?? $o['total_price'] ?? 0),
            'items_count' => (int) ($o['items_count'] ?? 0),
            'recipient_name' => $o['recipient_name'] ?? '',
            'created_at' => $o['created_at'] ?? '',
            'created_at_shamsi' => function_exists('toShamsi') ? toShamsi($o['created_at'] ?? '') : ($o['created_at'] ?? ''),
        ];
    }

    private function formatOrderDetail(array $o): array
    {
        $summary = $this->formatOrderSummary($o);
        $summary['recipient_phone'] = $o['recipient_phone'] ?? '';
        $summary['shipping_address'] = $o['shipping_address'] ?? '';
        $summary['postal_code'] = $o['postal_code'] ?? '';
        $summary['user_notes'] = $o['user_notes'] ?? '';
        $summary['subtotal'] = (float) ($o['subtotal'] ?? 0);
        $summary['discount_amount'] = (float) ($o['discount_amount'] ?? 0);
        $summary['applied_coupon'] = $o['applied_coupon'] ?? null;
        $summary['items'] = $o['items'] ?? [];
        return $summary;
    }

    /**
     * اعتبارسنجی کد ملی ایرانی
     */
    private function isValidNationalId(string $code): bool
    {
        if (!preg_match('/^\d{10}$/', $code)) {
            return false;
        }

        // رد کردن اعداد تکراری مثل 0000000000
        if (preg_match('/^(\d)\1{9}$/', $code)) {
            return false;
        }

        $check = (int) $code[9];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $code[$i]) * (10 - $i);
        }
        $rem = $sum % 11;

        return ($rem < 2 && $check === $rem) || ($rem >= 2 && $check === (11 - $rem));
    }
}