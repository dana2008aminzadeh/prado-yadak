<?php

namespace App\controllers;

use App\models\Order;
use App\models\Cart;
use App\models\Product;
use App\models\Coupon;
use App\models\Notice;
use App\models\Location;
use App\models\Address;
use App\models\User;

class OrderController extends Controller
{
    public function checkout()
    {
        $userId = (int) $_SESSION['user_id'];
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $pageTitle = 'تسویه حساب و پرداخت نهایی | ' . $siteName;

        // تمام فراخوانی‌ها فقط از طریق مدل‌ها انجام می‌شود
        $notices = Notice::getForPage('checkout');
        $provinces = Location::getActiveProvinces();
        $savedAddresses = Address::getByUserId($userId);
        $currentUser = User::findById($userId);

        require_once VIEWS_PATH . '/checkout.php';
    }

    public function apiValidateCoupon()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $code = trim($input['code'] ?? '');
        $cartSubtotal = (float) ($input['subtotal'] ?? 0);

        $result = Coupon::validate($code, $cartSubtotal);
        $this->jsonResponse($result, $result['valid'] ? 200 : 400);
    }

    public function processCheckout()
    {
        $userId = (int) $_SESSION['user_id'];
        $recipientName = trim($_POST['recipient_name'] ?? '');
        $recipientPhone = trim($_POST['recipient_phone'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $addressDetail = trim($_POST['address_detail'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $userNotes = trim($_POST['user_notes'] ?? '');
        $couponCode = trim($_POST['applied_discount_code'] ?? '');
        $rawCartData = $_POST['cart_data'] ?? '';

        $provinceCity = $province . ' - ' . $city;
        $shippingAddress = $province . '، ' . $city . '، ' . $addressDetail;

        if (empty($recipientName) || empty($recipientPhone) || empty($province) || empty($city) || empty($addressDetail)) {
            $_SESSION['checkout_error'] = 'لطفاً تمامی فیلدهای الزامی مشخصات تحویل‌گیرنده و آدرس را تکمیل کنید.';
            header('Location: /checkout');
            exit;
        }

        if (!preg_match('/^09[0-9]{9}$/', $recipientPhone)) {
            $_SESSION['checkout_error'] = 'شماره همراه تحویل‌گیرنده نامعتبر است (مثال: 09189998852).';
            header('Location: /checkout');
            exit;
        }

        // اعتبارسنجی تطابق استان و شهر در مدل Location
        if (!Location::validateProvinceAndCity($province, $city)) {
            $_SESSION['checkout_error'] = 'استان یا شهر انتخاب‌شده معتبر نیست.';
            header('Location: /checkout');
            exit;
        }

        $clientItems = json_decode($rawCartData, true) ?: [];
        $validatedItems = [];
        $subtotal = 0.0;

        if (!empty($clientItems)) {
            foreach ($clientItems as $ci) {
                $pid = (int) ($ci['product']['id'] ?? $ci['id'] ?? 0);
                $qty = max(1, (int) ($ci['quantity'] ?? 1));
                if ($pid > 0) {
                    $prod = Product::findById($pid);
                    if ($prod) {
                        $itemPrice = (float) $prod['price'];
                        $subtotal += ($itemPrice * $qty);
                        $validatedItems[] = [
                            'id' => $prod['id'],
                            'name' => $prod['name'],
                            'price' => $itemPrice,
                            'quantity' => $qty
                        ];
                    }
                }
            }
        }

        if (empty($validatedItems)) {
            $dbCart = Cart::get($userId);
            foreach ($dbCart as $ci) {
                $itemPrice = (float) $ci['price'];
                $qty = (int) $ci['quantity'];
                $subtotal += ($itemPrice * $qty);
                $validatedItems[] = [
                    'id' => (int) $ci['id'],
                    'name' => $ci['name'],
                    'price' => $itemPrice,
                    'quantity' => $qty
                ];
            }
        }

        if (empty($validatedItems) || $subtotal <= 0) {
            $_SESSION['checkout_error'] = 'سبد خرید شما خالی است.';
            header('Location: /checkout');
            exit;
        }

        $discountAmount = 0.0;
        $appliedCouponCode = null;
        if (!empty($couponCode)) {
            $couponCheck = Coupon::validate($couponCode, $subtotal);
            if ($couponCheck['valid']) {
                $discountAmount = (float) $couponCheck['discount'];
                $appliedCouponCode = $couponCheck['code'];
            }
        }

        $totalAmount = max(0, $subtotal - $discountAmount);

        if (!isset($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['checkout_error'] = 'آپلود تصویر یا فایل رسید بانکی الزامی است.';
            header('Location: /checkout');
            exit;
        }

        $file = $_FILES['receipt_image'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mimeType, $allowedMimes)) {
            $_SESSION['checkout_error'] = 'فرمت رسید نامعتبر است (فقط JPG, PNG, WEBP و PDF مجاز است).';
            header('Location: /checkout');
            exit;
        }

        $extension = $allowedMimes[$mimeType];
        $uploadDir = BASE_PATH . '/assets/uploads/receipts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueFileName = 'rcpt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadDir . '/' . $uniqueFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['checkout_error'] = 'خطا در ذخیره‌سازی فایل رسید روی هاست.';
            header('Location: /checkout');
            exit;
        }

        $receiptRelativePath = '/assets/uploads/receipts/' . $uniqueFileName;
        $trackingCode = Order::generateUniqueTrackingCode();

        $orderPayload = [
            'user_id' => $userId,
            'tracking_code' => $trackingCode,
            'payer_name' => null,
            'bank_reference' => null,
            'receipt_path' => $receiptRelativePath,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'applied_coupon' => $appliedCouponCode,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'shipping_address' => $shippingAddress,
            'postal_code' => $postalCode,
            'user_notes' => $userNotes
        ];

        $orderResult = Order::createOrder($orderPayload, $validatedItems);

        if (!$orderResult['success']) {
            @unlink($destination);
            $_SESSION['checkout_error'] = 'خطا در ثبت نهایی فاکتور در دیتابیس.';
            header('Location: /checkout');
            exit;
        }

        // ذخیره آدرس از طریق مدل Address بدون درج مستقیم SQL در کنترلر
        Address::saveIfNotExists($userId, $provinceCity, $addressDetail, $postalCode);

        header('Location: /order/success?code=' . urlencode($trackingCode));
        exit;
    }

    public function orderSuccess()
    {
        $code = trim($_GET['code'] ?? '');
        if (empty($code)) {
            header('Location: /profile');
            exit;
        }

        $order = Order::findByTrackingCode($code);
        if (!$order || $order['user_id'] != $_SESSION['user_id']) {
            header('Location: /404');
            exit;
        }

        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $pageTitle = 'سفارش شما با موفقیت ثبت شد | ' . $siteName;

        require_once VIEWS_PATH . '/order-success.php';
    }

    public function trackOrder()
    {
        header('Content-Type: application/json; charset=utf-8');
        $code = trim($_POST['code'] ?? '');
        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'کد رهگیری را وارد کنید.']);
            exit;
        }

        $order = Order::findByTrackingCode($code);
        if ($order) {
            $statusMap = [
                'processing' => 'در حال بررسی فیش و آماده‌سازی قطعات در انبار',
                'shipped' => 'تحویل شده به شرکت پست / تیپاکس',
                'delivered' => 'با موفقیت تحویل مشتری شده است',
                'cancelled' => 'لغو شده'
            ];
            $msg = $statusMap[$order['status']] ?? 'وضعیت نامشخص';
            $color = $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'error' : 'warning');
            echo json_encode(['status' => $color, 'message' => "وضعیت سفارش شما: " . $msg]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'سفارشی با این کد رهگیری در سیستم یافت نشد.']);
        }
        exit;
    }
}