<?php
namespace App\controllers;

class OrderController extends Controller
{
    public function checkout()
    {
        require_once VIEWS_PATH . '/checkout.php';
    }

    public function trackOrder()
    {
        header('Content-Type: application/json; charset=utf-8');
        $code = trim($_POST['code'] ?? '');

        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'کد رهگیری را وارد کنید.']);
            exit;
        }

        $order = \App\models\Order::findByTrackingCode($code);

        if ($order) {
            $statusMap = [
                'processing' => 'در حال پردازش و بسته‌بندی در انبار',
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