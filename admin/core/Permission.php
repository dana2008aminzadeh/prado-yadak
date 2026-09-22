<?php
namespace Admin\core;

/**
 * تعریف و بررسی سطوح دسترسی (RBAC)
 */
class Permission
{
    /** درخت دسترسی‌ها برای نمایش در فرم نقش‌ها */
    public const TREE = [
        'پیشخوان و گزارش' => [
            'dashboard.view'  => 'مشاهده پیشخوان',
            'reports.view'    => 'مشاهده گزارش‌ها',
            'reports.export'  => 'خروجی گرفتن از گزارش‌ها',
            'audit.view'      => 'مشاهده لاگ رویدادهای مدیران',
        ],
        'محصولات و انبار' => [
            'products.view'   => 'مشاهده محصولات',
            'products.edit'   => 'افزودن و ویرایش محصول',
            'products.delete' => 'حذف محصول',
            'products.stock'  => 'مدیریت موجودی انبار',
            'products.price'  => 'تغییر قیمت',
            'catalog.view'    => 'مشاهده دسته‌بندی و مدل خودرو',
            'catalog.edit'    => 'ویرایش دسته‌بندی و مدل خودرو',
        ],
        'سفارش‌ها' => [
            'orders.view'    => 'مشاهده سفارش‌ها',
            'orders.status'  => 'تغییر وضعیت و ثبت بارنامه',
            'orders.edit'    => 'ویرایش اطلاعات سفارش',
            'orders.delete'  => 'حذف سفارش',
            'orders.invoice' => 'صدور فاکتور رسمی',
        ],
        'کاربران و مالی' => [
            'users.view'     => 'مشاهده کاربران',
            'users.edit'     => 'ویرایش کاربر',
            'users.delete'   => 'حذف کاربر',
            'users.roles'    => 'تغییر نقش و سطح دسترسی',
            'wallet.view'    => 'مشاهده تراکنش‌های کیف پول',
            'wallet.approve' => 'تأیید و رد تراکنش',
            'wallet.adjust'  => 'شارژ و کسر دستی کیف پول',
        ],
        'پشتیبانی و محتوا' => [
            'tickets.view'     => 'مشاهده تیکت‌ها',
            'tickets.reply'    => 'پاسخ به تیکت',
            'tickets.delete'   => 'حذف تیکت',
            'comments.view'    => 'مشاهده دیدگاه‌ها',
            'comments.moderate' => 'تأیید و رد دیدگاه',
            'articles.view'    => 'مشاهده مقالات',
            'articles.edit'    => 'نگارش و ویرایش مقاله',
            'notices.view'     => 'مشاهده اطلاعیه‌ها',
            'notices.edit'     => 'ویرایش اطلاعیه‌ها',
        ],
        'بازاریابی' => [
            'coupons.view' => 'مشاهده کدهای تخفیف',
            'coupons.edit' => 'ساخت و ویرایش کد تخفیف',
            'sms.view'     => 'مشاهده گزارش پیامک',
            'sms.send'     => 'ارسال پیامک',
        ],
        'پیکربندی سیستم' => [
            'shipping.view'  => 'مشاهده روش‌های ارسال',
            'shipping.edit'  => 'ویرایش روش‌های ارسال',
            'locations.view' => 'مشاهده استان و شهر',
            'locations.edit' => 'ویرایش استان و شهر',
            'settings.view'  => 'مشاهده تنظیمات',
            'settings.edit'  => 'تغییر تنظیمات سایت',
            'roles.manage'   => 'مدیریت نقش‌های مدیریتی',
            'tools.backup'   => 'پشتیبان‌گیری از پایگاه داده',
        ],
    ];

    public static function all(): array
    {
        $out = [];
        foreach (self::TREE as $group) {
            foreach ($group as $key => $label) $out[$key] = $label;
        }
        return $out;
    }

    public static function label(string $key): string
    {
        return self::all()[$key] ?? $key;
    }

    /** آیا مجموعه دسترسی‌های داده‌شده اجازه این کار را می‌دهد؟ */
    public static function granted(array $permissions, string $needle): bool
    {
        if (in_array('*', $permissions, true)) {
            return true;
        }
        if (in_array($needle, $permissions, true)) {
            return true;
        }
        // پشتیبانی از الگوی گروهی مثل products.*
        $group = explode('.', $needle)[0] ?? '';
        return $group !== '' && in_array($group . '.*', $permissions, true);
    }
}
