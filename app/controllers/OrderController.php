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
        header('X-Robots-Tag: noindex, nofollow', true);

        $userId = (int) $_SESSION['user_id'];
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $pageTitle = 'تسویه حساب و پرداخت نهایی | ' . $siteName;

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

        // ۱. جلوگیری از اسپم فاکتور (حداقل ۳۰ ثانیه فاصله بین ثبت فاکتورهای متوالی)
        $now = time();
        if (isset($_SESSION['last_checkout_time']) && ($now - (int) $_SESSION['last_checkout_time']) < 30) {
            $_SESSION['checkout_error'] = 'یک سفارش از سمت شما در حال پردازش است. لطفاً چند لحظه صبر کنید.';
            header('Location: /checkout');
            exit;
        }

        // ۲. دریافت و پالایش امن ورودی‌های متنی با سقف طول مجاز
        $recipientName = mb_substr(trim(strip_tags($_POST['recipient_name'] ?? '')), 0, 100, 'UTF-8');
        $recipientPhone = trim($_POST['recipient_phone'] ?? '');
        $province = mb_substr(trim(strip_tags($_POST['province'] ?? '')), 0, 50, 'UTF-8');
        $city = mb_substr(trim(strip_tags($_POST['city'] ?? '')), 0, 50, 'UTF-8');
        $addressDetail = mb_substr(trim(strip_tags($_POST['address_detail'] ?? '')), 0, 300, 'UTF-8');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $userNotes = mb_substr(trim(strip_tags($_POST['user_notes'] ?? '')), 0, 500, 'UTF-8');
        $couponCode = trim($_POST['applied_discount_code'] ?? '');
        $rawCartData = $_POST['cart_data'] ?? '';

        $provinceCity = $province . ' - ' . $city;
        $shippingAddress = $province . '، ' . $city . '، ' . $addressDetail;

        if (empty($recipientName) || empty($recipientPhone) || empty($province) || empty($city) || empty($addressDetail) || empty($postalCode)) {
            $_SESSION['checkout_error'] = 'لطفاً تمامی فیلدهای الزامی شامل مشخصات تحویل‌گیرنده، آدرس و کد پستی را تکمیل کنید.';
            header('Location: /checkout');
            exit;
        }

        if (!preg_match('/^09[0-9]{9}$/', $recipientPhone)) {
            $_SESSION['checkout_error'] = 'شماره همراه تحویل‌گیرنده نامعتبر است (مثال: 09189998852).';
            header('Location: /checkout');
            exit;
        }

        if (!preg_match('/^[0-9]{10}$/', $postalCode)) {
            $_SESSION['checkout_error'] = 'کد پستی نامعتبر است (باید دقیقاً یک عدد ۱۰ رقمی باشد).';
            header('Location: /checkout');
            exit;
        }

        // بررسی اعتبار استان و شهر در پایگاه داده
        if (!Location::validateProvinceAndCity($province, $city)) {
            $_SESSION['checkout_error'] = 'استان یا شهر انتخاب‌شده معتبر نیست.';
            header('Location: /checkout');
            exit;
        }

        // ۳. بررسی قیمت، تعداد و موجودی واقعی انبار
        $clientItems = json_decode($rawCartData, true) ?: [];
        $validatedItems = [];
        $subtotal = 0.0;

        if (!empty($clientItems)) {
            foreach ($clientItems as $ci) {
                $pid = (int) ($ci['product']['id'] ?? $ci['id'] ?? 0);
                // محدود کردن بازه مجاز تعداد (بین ۱ تا ۱۰ عدد برای هر قطعه جهت جلوگیری از سرریز قیمت)
                $qty = max(1, min(10, (int) ($ci['quantity'] ?? 1)));

                if ($pid > 0) {
                    $prod = Product::findById($pid);
                    if ($prod) {
                        // کنترل وضعیت موجودی در لحظه تسویه
                        if (!$prod['inStock']) {
                            $_SESSION['checkout_error'] = "متأسفانه قطعه «{$prod['name']}» در انبار ناموجود است.";
                            header('Location: /checkout');
                            exit;
                        }

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

        if (empty($validatedItems) || $subtotal <= 0) {
            $_SESSION['checkout_error'] = 'سبد خرید شما خالی است.';
            header('Location: /checkout');
            exit;
        }

        // ۴. بررسی تخفیف
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

        // ۵. اعتبارسنجی چندلایه فایل رسید بانکی
        if (!isset($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['checkout_error'] = 'آپلود تصویر یا فایل رسید بانکی الزامی است.';
            header('Location: /checkout');
            exit;
        }

        $file = $_FILES['receipt_image'];

        // الف) بررسی حجم فایل (حداکثر ۵ مگابایت)
        $maxSizeBytes = 5 * 1024 * 1024;
        if ($file['size'] > $maxSizeBytes || $file['size'] < 1024) {
            $_SESSION['checkout_error'] = 'حجم فایل رسید نامعتبر است (باید بین ۱ کیلوبایت تا ۵ مگابایت باشد).';
            header('Location: /checkout');
            exit;
        }

        // ب) تشخیص MIME واقعی از هدرهای درونی فایل
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf'
        ];

        if (!array_key_exists($mimeType, $allowedMimes)) {
            $_SESSION['checkout_error'] = 'فرمت رسید نامعتبر است (تنها JPG, PNG, WEBP و PDF مجاز است).';
            header('Location: /checkout');
            exit;
        }

        // ج) بررسی بایت‌های جادویی (Magic Bytes) و ساختار باینری
        if ($mimeType === 'application/pdf') {
            $header = file_get_contents($file['tmp_name'], false, null, 0, 5);
            if (strncmp($header, '%PDF-', 5) !== 0) {
                $_SESSION['checkout_error'] = 'فایل PDF بارگذاری‌شده معتبر نیست.';
                header('Location: /checkout');
                exit;
            }
        } else {
            // برای فایل‌های تصویری، اعتبارسنجی از طریق موتور GD انجام می‌شود
            $imgInfo = @getimagesize($file['tmp_name']);
            if ($imgInfo === false) {
                $_SESSION['checkout_error'] = 'تصویر رسید بانکی مخدوش یا دستکاری شده است.';
                header('Location: /checkout');
                exit;
            }
        }

        // د) ذخیره‌سازی با نام کاملاً تصادفی و پسوند کنترل‌شده
        $extension = $allowedMimes[$mimeType];
        $uploadDir = BASE_PATH . '/assets/uploads/receipts';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $uniqueFileName = 'rcpt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $uploadDir . '/' . $uniqueFileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $_SESSION['checkout_error'] = 'خطا در ذخیره‌سازی فایل رسید.';
            header('Location: /checkout');
            exit;
        }

        // تغییر مجوز دسترسی فایل آپلود شده به حالت غیرقابل اجرا (Read-Only برای وب‌سرور)
        @chmod($destination, 0644);

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
            @unlink($destination); // حذف فایل آپلود شده در صورت شکست دیتابیس
            $_SESSION['checkout_error'] = 'خطا در ثبت نهایی فاکتور در پایگاه داده.';
            header('Location: /checkout');
            exit;
        }

        // ثبت زمان موفق جهت اعمال Rate Limit
        $_SESSION['last_checkout_time'] = time();

        // ذخیره نشانی در دفترچه کاربر
        Address::saveIfNotExists($userId, $provinceCity, $addressDetail, $postalCode);

        header('Location: /order/success?code=' . urlencode($trackingCode));
        exit;
    }

    public function orderSuccess()
    {
        header('X-Robots-Tag: noindex, nofollow', true);

        $code = trim($_GET['code'] ?? '');
        if (empty($code)) {
            header('Location: /profile');
            exit;
        }

        $order = Order::findByTrackingCode($code);

        // جلوگیری از IDOR با بررسی سخت‌گیرانه نوع داده و شناسه کاربر
        if (!$order || (int) $order['user_id'] !== (int) $_SESSION['user_id']) {
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

        // محدودسازی درخواست‌های پیگیری جهت جلوگیری از بروت‌فورس کدهای رهگیری (حداکثر ۲۰ درخواست در ۵ دقیقه)
        if (!isset($_SESSION['track_attempts'])) {
            $_SESSION['track_attempts'] = ['count' => 0, 'first_attempt' => time()];
        }

        if (time() - $_SESSION['track_attempts']['first_attempt'] > 300) {
            $_SESSION['track_attempts'] = ['count' => 1, 'first_attempt' => time()];
        } else {
            $_SESSION['track_attempts']['count']++;
            if ($_SESSION['track_attempts']['count'] > 20) {
                http_response_code(429);
                echo json_encode(['status' => 'error', 'message' => 'تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً ۵ دقیقه دیگر تلاش کنید.']);
                exit;
            }
        }

        $code = trim($_POST['code'] ?? '');
        if (empty($code) || !preg_match('/^[a-zA-Z0-9_-]{6,20}$/', $code)) {
            echo json_encode(['status' => 'error', 'message' => 'کد رهگیری وارد شده نامعتبر است.']);
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