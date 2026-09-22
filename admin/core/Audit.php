<?php
namespace Admin\core;

use Core\Database;
use PDO;
use Throwable;

/**
 * ثبت تاریخچه عملکرد مدیران (Audit Log)
 */
class Audit
{
    /** برچسب فارسی رویدادها */
    public const LABELS = [
        'auth.login'             => 'ورود به پنل',
        'auth.logout'            => 'خروج از پنل',
        'security.csrf_blocked'  => 'مسدودسازی درخواست مشکوک',
        'security.denied'        => 'تلاش برای دسترسی غیرمجاز',
        'product.create'         => 'ایجاد محصول',
        'product.update'         => 'ویرایش محصول',
        'product.delete'         => 'حذف محصول',
        'product.price'          => 'تغییر قیمت محصول',
        'product.stock'          => 'تغییر موجودی انبار',
        'product.bulk'           => 'عملیات گروهی محصولات',
        'product.image'          => 'تغییر تصاویر محصول',
        'order.status'           => 'تغییر وضعیت سفارش',
        'order.update'           => 'ویرایش سفارش',
        'order.delete'           => 'حذف سفارش',
        'order.invoice'          => 'صدور فاکتور',
        'order.shipping'         => 'ثبت بارنامه',
        'user.create'            => 'ایجاد کاربر',
        'user.update'            => 'ویرایش کاربر',
        'user.delete'            => 'حذف کاربر',
        'user.password'          => 'تغییر رمز عبور کاربر',
        'user.role'              => 'تغییر نقش کاربر',
        'wallet.adjust'          => 'تغییر دستی کیف پول',
        'wallet.approve'         => 'تأیید تراکنش کیف پول',
        'wallet.reject'          => 'رد تراکنش کیف پول',
        'wallet.delete'          => 'حذف تراکنش',
        'ticket.reply'           => 'پاسخ به تیکت',
        'ticket.update'          => 'تغییر وضعیت تیکت',
        'ticket.delete'          => 'حذف تیکت',
        'comment.moderate'       => 'بررسی دیدگاه',
        'comment.delete'         => 'حذف دیدگاه',
        'article.create'         => 'ایجاد مقاله',
        'article.update'         => 'ویرایش مقاله',
        'article.delete'         => 'حذف مقاله',
        'coupon.create'          => 'ایجاد کد تخفیف',
        'coupon.update'          => 'ویرایش کد تخفیف',
        'coupon.delete'          => 'حذف کد تخفیف',
        'catalog.update'         => 'ویرایش دسته/مدل خودرو',
        'catalog.delete'         => 'حذف دسته/مدل خودرو',
        'location.update'        => 'ویرایش استان/شهر',
        'notice.update'          => 'ویرایش اطلاعیه',
        'shipping.update'        => 'ویرایش روش ارسال',
        'settings.update'        => 'تغییر تنظیمات سایت',
        'role.update'            => 'تغییر نقش مدیریتی',
        'sms.send'               => 'ارسال پیامک',
        'sms.campaign'           => 'کمپین پیامکی',
        'backup.create'          => 'ایجاد نسخه پشتیبان',
        'backup.download'        => 'دانلود نسخه پشتیبان',
        'export.data'            => 'خروجی گرفتن از داده‌ها',
    ];

    /** رویدادهای حساس که در پیشخوان امنیتی برجسته می‌شوند */
    public const CRITICAL = [
        'security.csrf_blocked', 'security.denied', 'user.delete', 'user.role',
        'wallet.adjust', 'wallet.approve', 'order.delete', 'settings.update',
        'role.update', 'backup.download', 'product.delete',
    ];

    public static function label(string $action): string
    {
        return self::LABELS[$action] ?? $action;
    }

    /**
     * ثبت یک رویداد. هرگز استثنا پرتاب نمی‌کند تا جریان اصلی قطع نشود.
     */
    public static function log(
        string $action,
        ?string $entityType = null,
        $entityId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        try {
            $admin = $_SESSION['admin_user_id'] ?? null;
            $name = null;
            if ($admin) {
                // از کش Auth استفاده می‌کنیم اگر موجود باشد تا کوئری اضافه نزنیم
                $u = Auth::check() ? Auth::user() : null;
                $name = $u['full_name'] ?? null;
            }

            [$old, $new] = self::diff($oldValues, $newValues);

            $db = Database::getInstance();
            $st = $db->prepare(
                'INSERT INTO admin_audit_logs
                 (admin_id, admin_name, action, entity_type, entity_id, description,
                  old_values, new_values, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $st->execute([
                $admin ? (int) $admin : null,
                $name,
                $action,
                $entityType,
                $entityId !== null ? (string) $entityId : null,
                $description !== null ? mb_substr($description, 0, 500) : null,
                $old,
                $new,
                Auth::ip(),
                mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            // لاگ‌گیری نباید عملیات اصلی را بشکند
            @error_log('[audit] ' . $e->getMessage());
        }
    }

    /** فقط فیلدهای تغییریافته را نگه می‌دارد */
    private static function diff(?array $old, ?array $new): array
    {
        if ($old === null && $new === null) {
            return [null, null];
        }
        if ($old === null) {
            return [null, self::encode($new)];
        }
        if ($new === null) {
            return [self::encode($old), null];
        }

        $changedOld = [];
        $changedNew = [];
        foreach ($new as $k => $v) {
            $before = $old[$k] ?? null;
            if ((string) $before !== (string) $v) {
                $changedOld[$k] = $before;
                $changedNew[$k] = $v;
            }
        }
        if (!$changedNew) {
            return [null, null];
        }
        return [self::encode($changedOld), self::encode($changedNew)];
    }

    private static function encode(?array $data): ?string
    {
        if (!$data) return null;
        // پنهان‌سازی مقادیر حساس
        $masked = [];
        foreach ($data as $k => $v) {
            if (preg_match('/(password|token|api_key|secret|hash)/i', (string) $k)) {
                $masked[$k] = '••••••';
            } elseif (is_scalar($v) || $v === null) {
                $masked[$k] = is_string($v) ? mb_substr($v, 0, 300) : $v;
            } else {
                $masked[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
            }
        }
        return json_encode($masked, JSON_UNESCAPED_UNICODE) ?: null;
    }

    /** واکشی لاگ‌ها با فیلتر */
    public static function query(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = self::buildFilters($filters);
        $db = Database::getInstance();
        $st = $db->prepare("SELECT * FROM admin_audit_logs WHERE {$where}
                            ORDER BY created_at DESC, id DESC LIMIT {$limit} OFFSET {$offset}");
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function countAll(array $filters): int
    {
        [$where, $params] = self::buildFilters($filters);
        $db = Database::getInstance();
        $st = $db->prepare("SELECT COUNT(*) FROM admin_audit_logs WHERE {$where}");
        $st->execute($params);
        return (int) $st->fetchColumn();
    }

    private static function buildFilters(array $f): array
    {
        $where = ['1'];
        $params = [];
        if (!empty($f['admin_id'])) { $where[] = 'admin_id = ?'; $params[] = (int) $f['admin_id']; }
        if (!empty($f['action']))   { $where[] = 'action = ?'; $params[] = $f['action']; }
        if (!empty($f['entity']))   { $where[] = 'entity_type = ?'; $params[] = $f['entity']; }
        if (!empty($f['from']))     { $where[] = 'created_at >= ?'; $params[] = $f['from'] . ' 00:00:00'; }
        if (!empty($f['to']))       { $where[] = 'created_at <= ?'; $params[] = $f['to'] . ' 23:59:59'; }
        if (!empty($f['critical'])) {
            $in = implode(',', array_fill(0, count(self::CRITICAL), '?'));
            $where[] = "action IN ($in)";
            $params = array_merge($params, self::CRITICAL);
        }
        if (!empty($f['q'])) {
            $where[] = '(description LIKE ? OR admin_name LIKE ? OR entity_id LIKE ?)';
            $like = '%' . $f['q'] . '%';
            array_push($params, $like, $like, $like);
        }
        return [implode(' AND ', $where), $params];
    }
}
