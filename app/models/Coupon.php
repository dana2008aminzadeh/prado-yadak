<?php
namespace App\models;

use Core\Database;
use PDO;

class Coupon
{
    public static function validate(string $code, float $cartTotal): array
    {
        $code = trim(strtoupper($code));
        if (empty($code)) {
            return ['valid' => false, 'message' => 'کد تخفیف را وارد کنید.'];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM discount_coupons WHERE BINARY code = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$coupon) {
            return ['valid' => false, 'message' => 'کد تخفیف وارد شده معتبر نیست.'];
        }

        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'مهلت استفاده از این کد تخفیف به پایان رسیده است.'];
        }

        if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'message' => 'ظرفیت استفاده از این کد تخفیف تکمیل شده است.'];
        }

        if ($cartTotal < (float) $coupon['min_order']) {
            return [
                'valid' => false,
                'message' => 'حداقل مبلغ سفارش برای استفاده از این تخفیف ' . number_format($coupon['min_order']) . ' تومان است.'
            ];
        }

        $discount = 0;
        if ($coupon['type'] === 'percent') {
            $discount = ($cartTotal * (float) $coupon['value']) / 100;
            if (!empty($coupon['max_discount']) && $discount > (float) $coupon['max_discount']) {
                $discount = (float) $coupon['max_discount'];
            }
        } else {
            $discount = (float) $coupon['value'];
        }

        $discount = min($discount, $cartTotal);

        return [
            'valid' => true,
            'code' => $coupon['code'],
            'discount' => $discount,
            'message' => 'کد تخفیف با موفقیت اعمال شد (' . number_format($discount) . ' تومان کسر شد).'
        ];
    }

    public static function incrementUsage(string $code): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE discount_coupons SET used_count = used_count + 1 WHERE code = ?");
        $stmt->execute([$code]);
    }
}