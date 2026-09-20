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
                ':total_price' => $orderData['total_amount'],
                ':applied_coupon' => $orderData['applied_coupon'],
                ':recipient_name' => $orderData['recipient_name'],
                ':recipient_phone' => $orderData['recipient_phone'],
                ':shipping_address' => $orderData['shipping_address'],
                ':postal_code' => $orderData['postal_code'],
                ':user_notes' => $orderData['user_notes'] ?? null,
            ]);

            $orderId = $db->lastInsertId();

            $itemSql = "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $itemStmt = $db->prepare($itemSql);
            foreach ($items as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['quantity'],
                    $item['price'],
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
            return [
                'success' => true,
                'order_id' => $orderId,
                'tracking_code' => $orderData['tracking_code'],
            ];
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Order Creation Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * لیست سفارش‌های کاربر با فیلتر وضعیت
     */
    public static function getByUserId(int $userId, ?string $status = null, int $limit = 50): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(100, $limit));

        $sql = "SELECT o.*,
                       (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
                FROM orders o
                WHERE o.user_id = ?";
        $params = [$userId];

        if ($status && $status !== 'all') {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.id DESC LIMIT {$limit}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findByIdForUser(int $orderId, int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$orderId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getItems(int $orderId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT oi.quantity, oi.price,
                    p.id AS product_id, p.name, p.slug, p.oem_code, p.telegram_photo_id
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?"
        );
        $stmt->execute([$orderId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        foreach ($rows as $r) {
            $images = !empty($r['telegram_photo_id']) ? json_decode($r['telegram_photo_id'], true) : [];
            $items[] = [
                'product_id' => (int) ($r['product_id'] ?? 0),
                'name' => $r['name'] ?? 'قطعه حذف‌شده',
                'slug' => $r['slug'] ?? null,
                'oem' => $r['oem_code'] ?? null,
                'quantity' => (int) $r['quantity'],
                'price' => (float) $r['price'],
                'line_total' => (float) $r['price'] * (int) $r['quantity'],
                'images' => is_array($images) ? $images : [],
            ];
        }

        return $items;
    }

    /**
     * جزئیات کامل سفارش برای مودال/API
     */
    public static function getDetailForUser(int $orderId, int $userId): ?array
    {
        $order = self::findByIdForUser($orderId, $userId);
        if (!$order) {
            return null;
        }

        $order['items'] = self::getItems($orderId);
        $order['status_label'] = self::statusLabel($order['status'] ?? '');
        return $order;
    }

    public static function getStats(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) AS shipped,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
             FROM orders
             WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'processing' => (int) ($row['processing'] ?? 0),
            'shipped' => (int) ($row['shipped'] ?? 0),
            'delivered' => (int) ($row['delivered'] ?? 0),
            'cancelled' => (int) ($row['cancelled'] ?? 0),
        ];
    }

    public static function statusLabel(string $status): string
    {
        $map = [
            'processing' => 'در حال بررسی و آماده‌سازی',
            'shipped' => 'ارسال‌شده / در مسیر',
            'delivered' => 'تحویل شده',
            'cancelled' => 'لغو شده',
        ];
        return $map[$status] ?? 'نامشخص';
    }

    public static function statusColor(string $status): string
    {
        $map = [
            'processing' => 'amber',
            'shipped' => 'blue',
            'delivered' => 'emerald',
            'cancelled' => 'rose',
        ];
        return $map[$status] ?? 'gray';
    }
}
