<?php
namespace Admin\core;

use Core\Database;
use PDO;
use Throwable;

/**
 * مدیریت موجودی عددی انبار با ثبت حرکات
 */
class Inventory
{
    public const REASONS = [
        'order'      => 'کسر بابت سفارش',
        'return'     => 'بازگشت از سفارش',
        'manual'     => 'ثبت دستی',
        'correction' => 'اصلاح انبارگردانی',
        'purchase'   => 'خرید و ورود کالا',
        'damage'     => 'ضایعات',
    ];

    /**
     * تغییر موجودی یک محصول و ثبت حرکت.
     * مقدار منفی = خروج، مثبت = ورود.
     */
    public static function adjust(int $productId, int $change, string $reason = 'manual', ?string $referenceId = null, ?string $note = null): array
    {
        if ($change === 0) {
            return ['success' => false, 'message' => 'مقدار تغییر صفر است.'];
        }

        $db = Database::getInstance();
        $ownTransaction = !$db->inTransaction();

        try {
            if ($ownTransaction) $db->beginTransaction();

            $st = $db->prepare('SELECT id, name, stock_qty, track_stock, low_stock_threshold FROM products WHERE id = ? FOR UPDATE');
            $st->execute([$productId]);
            $p = $st->fetch(PDO::FETCH_ASSOC);

            if (!$p) {
                if ($ownTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'محصول یافت نشد.'];
            }

            $current = (int) $p['stock_qty'];
            $after = $current + $change;

            if ($after < 0) {
                if ($ownTransaction) $db->rollBack();
                return [
                    'success' => false,
                    'message' => 'موجودی کافی نیست. موجودی فعلی «' . $p['name'] . '»: ' . $current . ' عدد.',
                    'available' => $current,
                ];
            }

            // in_stock را با موجودی هماهنگ نگه می‌داریم
            $db->prepare('UPDATE products SET stock_qty = ?, in_stock = ? WHERE id = ?')
                ->execute([$after, $after > 0 ? 1 : 0, $productId]);

            $db->prepare(
                'INSERT INTO stock_movements (product_id, change_qty, qty_after, reason, reference_id, note, admin_id, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $productId, $change, $after, $reason, $referenceId,
                $note !== null ? mb_substr($note, 0, 255) : null,
                $_SESSION['admin_user_id'] ?? null,
            ]);

            if ($ownTransaction) $db->commit();

            return [
                'success'   => true,
                'qty_after' => $after,
                'low_stock' => $after > 0 && $after <= (int) $p['low_stock_threshold'],
                'out_of_stock' => $after === 0,
            ];
        } catch (Throwable $e) {
            if ($ownTransaction && $db->inTransaction()) $db->rollBack();
            return ['success' => false, 'message' => 'خطا در به‌روزرسانی انبار: ' . $e->getMessage()];
        }
    }

    /** تنظیم مستقیم موجودی روی یک عدد مشخص (انبارگردانی) */
    public static function setQuantity(int $productId, int $newQty, ?string $note = null): array
    {
        $current = (int) (Database::getInstance()
            ->query('SELECT stock_qty FROM products WHERE id = ' . (int) $productId)
            ->fetchColumn() ?: 0);
        $diff = $newQty - $current;
        if ($diff === 0) {
            return ['success' => true, 'qty_after' => $newQty, 'unchanged' => true];
        }
        return self::adjust($productId, $diff, 'correction', null, $note ?? 'تنظیم مستقیم موجودی');
    }

    /**
     * کسر موجودی همه اقلام یک سفارش (به‌صورت اتمیک).
     * اگر قبلاً کسر شده باشد، دوباره انجام نمی‌شود.
     */
    public static function deductOrder(int $orderId): array
    {
        $db = Database::getInstance();
        $ownTransaction = !$db->inTransaction();

        try {
            if ($ownTransaction) $db->beginTransaction();

            $st = $db->prepare('SELECT id, tracking_code, stock_deducted FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                if ($ownTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'سفارش یافت نشد.'];
            }
            if ((int) $order['stock_deducted'] === 1) {
                if ($ownTransaction) $db->commit();
                return ['success' => true, 'already' => true, 'message' => 'موجودی این سفارش قبلاً کسر شده است.'];
            }

            $items = $db->prepare('SELECT oi.product_id, oi.quantity, p.name, p.stock_qty, p.track_stock
                                   FROM order_items oi JOIN products p ON p.id = oi.product_id
                                   WHERE oi.order_id = ? FOR UPDATE');
            $items->execute([$orderId]);
            $rows = $items->fetchAll(PDO::FETCH_ASSOC);

            $warnings = [];
            foreach ($rows as $it) {
                if ((int) $it['track_stock'] !== 1) {
                    continue;
                }
                $qty = (int) $it['quantity'];
                $available = (int) $it['stock_qty'];
                $deduct = min($qty, $available);

                if ($deduct < $qty) {
                    $warnings[] = $it['name'] . ' (درخواست ' . $qty . '، موجود ' . $available . ')';
                }
                if ($deduct <= 0) {
                    continue;
                }

                $after = $available - $deduct;
                $db->prepare('UPDATE products SET stock_qty = ?, in_stock = ? WHERE id = ?')
                    ->execute([$after, $after > 0 ? 1 : 0, (int) $it['product_id']]);
                $db->prepare(
                    'INSERT INTO stock_movements (product_id, change_qty, qty_after, reason, reference_id, note, admin_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                )->execute([
                    (int) $it['product_id'], -$deduct, $after, 'order',
                    (string) $order['tracking_code'], 'کسر بابت سفارش ' . $order['tracking_code'],
                    $_SESSION['admin_user_id'] ?? null,
                ]);
            }

            $db->prepare('UPDATE orders SET stock_deducted = 1 WHERE id = ?')->execute([$orderId]);
            if ($ownTransaction) $db->commit();

            return ['success' => true, 'warnings' => $warnings];
        } catch (Throwable $e) {
            if ($ownTransaction && $db->inTransaction()) $db->rollBack();
            return ['success' => false, 'message' => 'خطا در کسر موجودی: ' . $e->getMessage()];
        }
    }

    /** بازگرداندن موجودی اقلام سفارش (لغو یا مرجوعی) */
    public static function restoreOrder(int $orderId): array
    {
        $db = Database::getInstance();
        $ownTransaction = !$db->inTransaction();

        try {
            if ($ownTransaction) $db->beginTransaction();

            $st = $db->prepare('SELECT id, tracking_code, stock_deducted FROM orders WHERE id = ? FOR UPDATE');
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                if ($ownTransaction) $db->rollBack();
                return ['success' => false, 'message' => 'سفارش یافت نشد.'];
            }
            if ((int) $order['stock_deducted'] !== 1) {
                if ($ownTransaction) $db->commit();
                return ['success' => true, 'already' => true, 'message' => 'موجودی این سفارش کسر نشده بود.'];
            }

            $items = $db->prepare('SELECT oi.product_id, oi.quantity, p.stock_qty, p.track_stock
                                   FROM order_items oi JOIN products p ON p.id = oi.product_id
                                   WHERE oi.order_id = ? FOR UPDATE');
            $items->execute([$orderId]);

            foreach ($items->fetchAll(PDO::FETCH_ASSOC) as $it) {
                if ((int) $it['track_stock'] !== 1) continue;
                $after = (int) $it['stock_qty'] + (int) $it['quantity'];
                $db->prepare('UPDATE products SET stock_qty = ?, in_stock = 1 WHERE id = ?')
                    ->execute([$after, (int) $it['product_id']]);
                $db->prepare(
                    'INSERT INTO stock_movements (product_id, change_qty, qty_after, reason, reference_id, note, admin_id, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                )->execute([
                    (int) $it['product_id'], (int) $it['quantity'], $after, 'return',
                    (string) $order['tracking_code'], 'بازگشت موجودی سفارش ' . $order['tracking_code'],
                    $_SESSION['admin_user_id'] ?? null,
                ]);
            }

            $db->prepare('UPDATE orders SET stock_deducted = 0 WHERE id = ?')->execute([$orderId]);
            if ($ownTransaction) $db->commit();

            return ['success' => true];
        } catch (Throwable $e) {
            if ($ownTransaction && $db->inTransaction()) $db->rollBack();
            return ['success' => false, 'message' => 'خطا در بازگشت موجودی: ' . $e->getMessage()];
        }
    }

    /** محصولات رو به اتمام */
    public static function lowStock(int $limit = 20): array
    {
        try {
            $st = Database::getInstance()->prepare(
                'SELECT id, name, stock_qty, low_stock_threshold, price FROM products
                 WHERE track_stock = 1 AND stock_qty <= low_stock_threshold
                 ORDER BY stock_qty ASC, id DESC LIMIT ' . (int) $limit
            );
            $st->execute();
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}
