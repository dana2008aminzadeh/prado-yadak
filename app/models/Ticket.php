<?php

namespace App\models;

use Core\Database;
use PDO;
use Exception;

class Ticket
{
    public static function getByUserId(int $userId): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT t.id, t.subject, t.status, t.priority, t.created_at, t.updated_at,
                    (SELECT tm.message FROM ticket_messages tm WHERE tm.ticket_id = t.id ORDER BY tm.id DESC LIMIT 1) AS last_message,
                    (SELECT COUNT(*) FROM ticket_messages tm2 WHERE tm2.ticket_id = t.id) AS message_count
             FROM support_tickets t
             WHERE t.user_id = ?
             ORDER BY t.updated_at DESC, t.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function findById(int $id, int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT * FROM support_tickets WHERE id = ? AND user_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getMessages(int $ticketId, int $userId): array
    {
        $ticket = self::findById($ticketId, $userId);
        if (!$ticket) {
            return [];
        }

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT id, sender_type, sender_name, message, created_at
             FROM ticket_messages
             WHERE ticket_id = ?
             ORDER BY id ASC"
        );
        $stmt->execute([$ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function create(int $userId, string $subject, string $message, string $priority = 'normal'): array
    {
        $subject = mb_substr(trim(strip_tags($subject)), 0, 150, 'UTF-8');
        $message = mb_substr(trim(strip_tags($message)), 0, 2000, 'UTF-8');
        $priority = in_array($priority, ['low', 'normal', 'high'], true) ? $priority : 'normal';

        if (mb_strlen($subject, 'UTF-8') < 5) {
            return ['success' => false, 'message' => 'موضوع تیکت باید حداقل ۵ کاراکتر باشد.'];
        }

        if (mb_strlen($message, 'UTF-8') < 10) {
            return ['success' => false, 'message' => 'متن پیام باید حداقل ۱۰ کاراکتر باشد.'];
        }

        $db = Database::getInstance();

        // محدودیت: حداکثر ۳ تیکت باز
        $openStmt = $db->prepare(
            "SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status IN ('open', 'answered', 'pending')"
        );
        $openStmt->execute([$userId]);
        if ((int) $openStmt->fetchColumn() >= 5) {
            return ['success' => false, 'message' => 'حداکثر ۵ تیکت باز مجاز است. ابتدا تیکت‌های قبلی را ببندید.'];
        }

        $user = User::findById($userId);
        $senderName = $user['full_name'] ?? 'کاربر';

        try {
            $db->beginTransaction();

            $stmt = $db->prepare(
                "INSERT INTO support_tickets (user_id, subject, status, priority, created_at, updated_at)
                 VALUES (?, ?, 'open', ?, NOW(), NOW())"
            );
            $stmt->execute([$userId, $subject, $priority]);
            $ticketId = (int) $db->lastInsertId();

            $msg = $db->prepare(
                "INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message, created_at)
                 VALUES (?, 'user', ?, ?, NOW())"
            );
            $msg->execute([$ticketId, $senderName, $message]);

            $db->commit();

            return [
                'success' => true,
                'message' => 'تیکت با موفقیت ثبت شد. پشتیبانی به‌زودی پاسخ می‌دهد.',
                'ticket_id' => $ticketId,
            ];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Ticket create error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'خطا در ثبت تیکت.'];
        }
    }

    public static function reply(int $ticketId, int $userId, string $message): array
    {
        $ticket = self::findById($ticketId, $userId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'تیکت یافت نشد.'];
        }

        if ($ticket['status'] === 'closed') {
            return ['success' => false, 'message' => 'این تیکت بسته شده است.'];
        }

        $message = mb_substr(trim(strip_tags($message)), 0, 2000, 'UTF-8');
        if (mb_strlen($message, 'UTF-8') < 5) {
            return ['success' => false, 'message' => 'متن پاسخ باید حداقل ۵ کاراکتر باشد.'];
        }

        $user = User::findById($userId);
        $senderName = $user['full_name'] ?? 'کاربر';
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $msg = $db->prepare(
                "INSERT INTO ticket_messages (ticket_id, sender_type, sender_name, message, created_at)
                 VALUES (?, 'user', ?, ?, NOW())"
            );
            $msg->execute([$ticketId, $senderName, $message]);

            $db->prepare(
                "UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?"
            )->execute([$ticketId]);

            $db->commit();
            return ['success' => true, 'message' => 'پاسخ شما ثبت شد.'];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['success' => false, 'message' => 'خطا در ثبت پاسخ.'];
        }
    }

    public static function close(int $ticketId, int $userId): array
    {
        $ticket = self::findById($ticketId, $userId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'تیکت یافت نشد.'];
        }

        if ($ticket['status'] === 'closed') {
            return ['success' => true, 'message' => 'تیکت قبلاً بسته شده است.'];
        }

        $db = Database::getInstance();
        $db->prepare(
            "UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ? AND user_id = ?"
        )->execute([$ticketId, $userId]);

        return ['success' => true, 'message' => 'تیکت بسته شد.'];
    }

    public static function countOpen(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM support_tickets WHERE user_id = ? AND status IN ('open', 'answered', 'pending')"
        );
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function statusLabel(string $status): string
    {
        $map = [
            'open' => 'باز / در انتظار پاسخ',
            'answered' => 'پاسخ داده شده',
            'pending' => 'در حال بررسی',
            'closed' => 'بسته شده',
        ];
        return $map[$status] ?? $status;
    }
}
