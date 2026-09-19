<?php
namespace App\models;

use Core\Database;
use PDO;
use Exception;

class Order
{
    public static function findByTrackingCode($code)
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM orders WHERE tracking_code = ? LIMIT 1");
        $stmt->execute([trim($code)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function generateUniqueTrackingCode(): string
    {
        $db = Database::getInstance();
        do {
            $code = 'PRD-' . random_int(100000, 999999);
            $stmt = $db->prepare("SELECT id FROM orders WHERE tracking_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());

        return $code;
    }

    public static function createOrder(array $orderData, array $items): array
    {
        $db = Database::getInstance();
        try {
            $db->beginTransaction();

            $sql = "INSERT INTO orders (
                    user_id, tracking_code, payer_name, bank_reference,
                    receipt_path, subtotal, discount_amount, total_amount, total_price,
                    applied_coupon, recipient_name, recipient_phone,
                    shipping_address, postal_code, user_notes, status, created_at
                ) VALUES (
                    :user_id, :tracking_code, :payer_name, :bank_reference,
                    :receipt_path, :subtotal, :discount_amount, :total_amount, :total_price,
                    :applied_coupon, :recipient_name, :recipient_phone,
                    :shipping_address, :postal_code, :user_notes, 'processing', NOW()
                )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':user_id' => $orderData['user_id'],
                ':tracking_code' => $orderData['tracking_code'],
                ':payer_name' => $orderData['payer_name'] ?? null,
                ':bank_reference' => $orderData['bank_reference'] ?? null,
                ':receipt_path' => $orderData['receipt_path'],
                ':subtotal' => $orderData['subtotal'],
                ':discount_amount' => $orderData['discount_amount'],
                ':total_amount' => $orderData['total_amount'],
                ':total_price' => $orderData['total_amount'], // مقداردهی به ستون اجباری دیتابیس
                ':applied_coupon' => $orderData['applied_coupon'],
                ':recipient_name' => $orderData['recipient_name'],
                ':recipient_phone' => $orderData['recipient_phone'],
                ':shipping_address' => $orderData['shipping_address'],
                ':postal_code' => $orderData['postal_code'],
                ':user_notes' => $orderData['user_notes'] ?? null
            ]);

            $orderId = $db->lastInsertId();

            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $itemStmt = $db->prepare($itemSql);
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$orderData['user_id']]);

            if (!empty($orderData['applied_coupon'])) {
                $consumed = Coupon::consumeCouponAtomic($orderData['applied_coupon'], $db);
                if (!$consumed) {
                    throw new Exception("ظرفیت استفاده از کد تخفیف انتخابی تکمیل شده است.");
                }
            }

            $db->commit();
            return ['success' => true, 'order_id' => $orderId, 'tracking_code' => $orderData['tracking_code']];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Order Creation Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}