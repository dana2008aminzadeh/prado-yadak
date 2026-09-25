<?php
namespace Admin\controllers;

use Admin\core\Model;
use Admin\core\Settings;

class SettingController extends BaseController
{
    protected string $section = 'settings';

    protected array $permissions = [
        'save'   => 'settings.edit',
        'add'    => 'settings.edit',
        'delete' => 'settings.edit',
    ];

    public const GROUPS = [
        'عمومی سایت' => [
            'site_title'    => ['عنوان سایت', 'text'],
            'site_subtitle' => ['زیرعنوان (انگلیسی)', 'text'],
            'phone_number'  => ['شماره تماس', 'text'],
            'address'       => ['آدرس نمایشی', 'textarea'],
            'work_hours'    => ['ساعات کاری', 'textarea'],
        ],
        'اطلاعات رسمی (فاکتور مالیاتی)' => [
            'company_legal_name'      => ['نام رسمی ثبت‌شده', 'text'],
            'company_national_id'     => ['شناسه ملی', 'text'],
            'company_economic_code'   => ['کد اقتصادی', 'text'],
            'company_registration_no' => ['شماره ثبت', 'text'],
            'company_full_address'    => ['آدرس کامل قانونی', 'textarea'],
            'company_postal_code'     => ['کد پستی', 'text'],
            'company_phone'           => ['تلفن ثابت', 'text'],
            'invoice_vat_percent'     => ['درصد مالیات بر ارزش افزوده', 'text'],
            'invoice_footer_note'     => ['یادداشت پایانی فاکتور', 'textarea'],
        ],
        'شبکه‌های اجتماعی' => [
            'whatsapp_link'  => ['لینک واتساپ', 'text'],
            'telegram_link'  => ['لینک تلگرام', 'text'],
            'instagram_link' => ['لینک اینستاگرام', 'text'],
        ],
        'اطلاعات بانکی' => [
            'bank_name'          => ['نام بانک', 'text'],
            'bank_account_owner' => ['صاحب حساب', 'text'],
            'bank_card_number'   => ['شماره کارت', 'text'],
            'bank_sheba'         => ['شماره شبا', 'text'],
        ],
        'سرویس پیامک (SMS.ir)' => [
            'sms_enabled'       => ['فعال بودن سرویس پیامک', 'bool'],
            'smsir_api_key'     => ['کلید API', 'secret'],
            'smsir_line_number' => ['شماره خط ارسال', 'text'],
            'smsir_template_id' => ['شناسه قالب OTP', 'text'],
        ],
        'تلگرام و پنل' => [
            'telegram_bot_token'   => ['توکن ربات تلگرام', 'secret'],
            'telegram_chat_id'     => ['شناسه چت میزبان تصاویر', 'text'],
            'admin_notify_sound'   => ['پخش صدا هنگام رویداد جدید', 'bool'],
            'admin_poll_interval'  => ['فاصله بررسی اعلان‌ها (ثانیه)', 'text'],
        ],
        'پیشخوان و هشدارها' => [
            'content_alert_days' => ['بیشینه فاصله مجاز بین انتشار مقاله‌ها (روز)', 'text'],
        ],
        'Search Console و مانیتورینگ' => [
            'gsc_verification_content' => ['کد تایید Google Search Console (فقط محتوای content=)', 'text'],
            'bing_verification_content' => ['کد تایید Bing Webmaster Tools (فقط محتوای content=)', 'text'],
            'ga4_measurement_id'        => ['شناسه Google Analytics 4 (مثل G-XXXXXXX)', 'text'],
            'gtm_container_id'          => ['شناسه Google Tag Manager (مثل GTM-XXXXXXX)', 'text'],
        ],
    ];

    public function index($id = 0): void
    {
        $rows = Model::all('SELECT * FROM settings ORDER BY id');
        $values = [];
        $descs = [];
        $ids = [];
        foreach ($rows as $r) {
            $values[$r['setting_key']] = $r['setting_value'];
            $descs[$r['setting_key']] = $r['description'];
            $ids[$r['setting_key']] = $r['id'];
        }

        $known = [];
        foreach (self::GROUPS as $g) $known = array_merge($known, array_keys($g));
        $extra = array_values(array_diff(array_keys($values), $known));

        $groups = self::GROUPS;
        $this->view('settings', compact('values', 'descs', 'ids', 'groups', 'extra'),
            'تنظیمات سایت', 'مقادیر پیکربندی فروشگاه');
    }

    public function save($id = 0): void
    {
        $data = (array) post('settings', []);
        $old = [];
        foreach (Model::all('SELECT setting_key, setting_value FROM settings') as $r) {
            $old[$r['setting_key']] = $r['setting_value'];
        }

        // چک‌باکس‌های خاموش در POST نمی‌آیند؛ آن‌ها را صفر می‌کنیم
        $boolKeys = [];
        foreach (self::GROUPS as $fields) {
            foreach ($fields as $k => $meta) {
                if (($meta[1] ?? '') === 'bool') $boolKeys[] = $k;
            }
        }
        foreach ($boolKeys as $bk) {
            if (!array_key_exists($bk, $data)) $data[$bk] = '0';
        }

        $changed = [];
        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || !preg_match('/^[a-z0-9_\-]+$/i', $key)) continue;
            $value = is_array($value) ? implode(',', $value) : (string) $value;
            if (($old[$key] ?? null) === $value) continue;

            Settings::set($key, $value);
            $changed[$key] = preg_match('/(token|api_key|secret|password)/i', $key) ? '••••••' : $value;
        }

        if ($changed) {
            $this->audit('settings.update', 'settings', null,
                'تغییر ' . count($changed) . ' تنظیم: ' . implode('، ', array_slice(array_keys($changed), 0, 8)),
                array_intersect_key($old, $changed), $changed);
            flash('success', count($changed) . ' تنظیم با موفقیت ذخیره شد.');
        } else {
            flash('info', 'تغییری اعمال نشد.');
        }

        Settings::flush();
        redirect(admin_url('settings'));
    }

    public function add($id = 0): void
    {
        $key = trim((string) post('setting_key'));
        if ($key === '' || !preg_match('/^[a-z0-9_\-]+$/i', $key)) {
            flash('error', 'کلید تنظیم باید فقط شامل حروف انگلیسی، عدد و زیرخط باشد.');
            back(admin_url('settings'));
        }
        if (Model::one('SELECT id FROM settings WHERE setting_key = ?', [$key])) {
            flash('error', 'این کلید قبلاً وجود دارد.');
            back(admin_url('settings'));
        }

        Model::insert('settings', [
            'setting_key'   => $key,
            'setting_value' => (string) post('setting_value'),
            'description'   => trim((string) post('description')) ?: null,
        ]);

        Settings::flush();
        $this->audit('settings.update', 'settings', $key, 'افزودن تنظیم سفارشی: ' . $key);
        flash('success', 'تنظیم جدید اضافه شد.');
        redirect(admin_url('settings'));
    }

    public function delete($id = 0): void
    {
        $sid = (int) post('setting_id', $id);
        $s = Model::find('settings', $sid);
        if ($s) {
            Model::delete('settings', $sid);
            Settings::flush();
            $this->audit('settings.update', 'settings', $s['setting_key'], 'حذف تنظیم: ' . $s['setting_key'], $s, null);
            flash('success', 'تنظیم حذف شد.');
        }
        redirect(admin_url('settings'));
    }
}
