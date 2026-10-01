-- -----------------------------------------------------------------------------
-- اعلان‌های پیامکی سفارش و مشخصات حساب sms.ir
-- ۱) تنظیمات جدید گروه «اطلاع‌رسانی پیامکی رویدادها» و شناسه قالب ثبت سفارش
-- ۲) قالب‌های پیش‌فرض اعلان مدیر در sms_templates (قابل ویرایش در پنل پیامک)
-- درج‌ها با گارد NOT EXISTS انجام می‌شوند تا مقادیر سفارشی موجود بازنویسی نشوند.
-- (الگوی select-from-derived-table برای سازگاری بدون نیاز به UNIQUE INDEX)
-- -----------------------------------------------------------------------------

-- ===== تنظیمات =====
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'smsir_template_id', '597624', 'شناسه قالب ورود با رمز یکبارمصرف (OTP) در sms.ir'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'smsir_template_id');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'smsir_order_template_id', '989878', 'شناسه قالب تأیید ثبت سفارش (verify) در sms.ir — پارامترها: NAME و CODE'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'smsir_order_template_id');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'sms_notify_order_customer', '1', 'ارسال پیامک تأیید سفارش به مشتری همزمان با ثبت رسید بانکی'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'sms_notify_order_customer');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'sms_notify_new_order_admin', '1', 'ارسال اعلان پیامکی به مدیران هنگام ثبت سفارش/رسید جدید'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'sms_notify_new_order_admin');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'sms_notify_status_admin', '1', 'ارسال اعلان پیامکی به مدیران هنگام تغییر وضعیت سفارش و ثبت بارنامه'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'sms_notify_status_admin');

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`)
SELECT 'sms_admin_phones', '', 'موبایل مدیران گیرنده اعلان‌های پیامکی — در هر خط یک شماره یا جداشده با ویرگول (مانند 09120000000)'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `settings`) AS t WHERE t.setting_key = 'sms_admin_phones');

-- ===== قالب‌های پیش‌فرض اعلان مدیر (قابل ویرایش از پنل پیامک ← قالب‌های خودکار) =====
-- متغیرهای قابل استفاده: {order} {name} {customer_phone} {amount} {tracking} {carrier} {status} {from} {to}
INSERT INTO `sms_templates` (`template_key`, `title`, `body`, `is_active`, `auto_send`)
SELECT 'admin_order_new', 'اعلان سفارش جدید به مدیر',
'سفارش جدید ثبت شد
کد رهگیری: {order}
مشتری: {name}
موبایل: {customer_phone}
مبلغ: {amount} تومان
رسید بانکی ثبت شد و نیازمند بررسی است.',
1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `sms_templates`) AS t WHERE t.template_key = 'admin_order_new');

INSERT INTO `sms_templates` (`template_key`, `title`, `body`, `is_active`, `auto_send`)
SELECT 'admin_order_status', 'اعلان تغییر وضعیت سفارش به مدیر',
'وضعیت سفارش {order} تغییر کرد
از «{from}» به «{to}»
مشتری: {name}
موبایل: {customer_phone}
مبلغ: {amount} تومان',
1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `sms_templates`) AS t WHERE t.template_key = 'admin_order_status');

INSERT INTO `sms_templates` (`template_key`, `title`, `body`, `is_active`, `auto_send`)
SELECT 'admin_order_shipping', 'اعلان ثبت بارنامه به مدیر',
'بارنامه سفارش {order} ثبت شد
حمل: {carrier} — کد رهگیری: {tracking}
مشتری: {name}
موبایل: {customer_phone}',
1, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM `sms_templates`) AS t WHERE t.template_key = 'admin_order_shipping');
