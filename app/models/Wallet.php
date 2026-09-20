<?php

namespace App\models;

use Core\Database;
use PDO;
use Exception;

class Wallet
{
    public static function getBalance(int $userId): float
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return (float) ($stmt->fetchColumn() ?: 0);
    }

    public static function getTransactions(int $userId, int $limit = 30): array
    {
        $db = Database::getInstance();
        $limit = max(1, min(100, $limit));
        $stmt = $db->prepare(
            "SELECT id, type, amount, balance_after, description, reference_code, status, created_at
             FROM wallet_transactions
             WHERE user_id = ?
             ORDER BY id DESC
             LIMIT {$limit}"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * ثبت درخواست شارژ کیف پول (درگاه واقعی بعداً متصل می‌شود)
     * فعلاً به‌صورت pending ثبت می‌شود تا ادمین تایید کند.
     */
    public static function requestCharge(int $userId, int $amount): array
    {
        $amount = (int) $amount;

        if ($amount < 50000) {
            return ['success' => false, 'message' => 'حداقل مبلغ شارژ ۵۰٬۰۰۰ تومان است.'];
        }

        if ($amount > 50000000) {
            return ['success' => false, 'message' => 'حداکثر مبلغ شارژ ۵۰٬۰۰۰٬۰۰۰ تومان است.'];
        }

        // گرد کردن به هزارتومان
        $amount = (int) (round($amount / 1000) * 1000);

        $db = Database::getInstance();
        $ref = 'WLT-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $userId;

        try {
            $stmt = $db->prepare(
                "INSERT INTO wallet_transactions
                    (user_id, type, amount, balance_after, description, reference_code, status, created_at)
                 VALUES (?, 'charge', ?, NULL, ?, ?, 'pending', NOW())"
            );
            $stmt->execute([
                $userId,
                $amount,
                'درخواست شارژ کیف پول',
                $ref,
            ]);

            return [
                'success' => true,
                'message' => 'درخواست شارژ ثبت شد. پس از تایید پرداخت، موجودی افزایش می‌یابد.',
                'reference' => $ref,
                'amount' => $amount,
                // در نسخه فعلی درگاه آنلاین متصل نیست؛ پیام راهنما برمی‌گردد
                'gateway' => false,
                'note' => 'فعلاً شارژ کیف پول پس از تماس با پشتیبانی و واریز دستی تایید می‌شود. کد پیگیری: ' . $ref,
            ];
        } catch (Exception $e) {
            error_log('Wallet charge error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت درخواست شارژ.'];
        }
    }

    /**
     * افزایش/کاهش موجودی به‌صورت اتمیک (برای ادمین یا سیستم)
     */
    public static function adjustBalance(int $userId, float $amount, string $type, string $description = '', ?string $ref = null): array
    {
        $allowedTypes = ['charge', 'purchase', 'refund', 'adjust'];
        if (!in_array($type, $allowedTypes, true)) {
            return ['success' => false, 'message' => 'نوع تراکنش نامعتبر است.'];
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $current = (float) $stmt->fetchColumn();

            $newBalance = $current + $amount;
            if ($newBalance < 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'موجودی کافی نیست.'];
            }

            $db->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?")
                ->execute([$newBalance, $userId]);

            $ins = $db->prepare(
                "INSERT INTO wallet_transactions
                    (user_id, type, amount, balance_after, description, reference_code, status, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'completed', NOW())"
            );
            $ins->execute([
                $userId,
                $type,
                $amount,
                $newBalance,
                $description !== '' ? $description : null,
                $ref,
            ]);

            $db->commit();
            return ['success' => true, 'balance' => $newBalance];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Wallet adjust error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در تغییر موجودی.'];
        }
    }
}
