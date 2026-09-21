<?php
namespace Admin\controllers;

use Admin\core\Model;

class SettingController extends BaseController
{
    protected string $section = 'settings';

    /** گروه‌بندی و برچسب کلیدهای شناخته‌شده */
    public const GROUPS = [
        'عمومی سایت' => [
            'site_title'    => ['عنوان سایت', 'text'],
            'site_subtitle' => ['زیرعنوان (انگلیسی)', 'text'],
            'phone_number'  => ['شماره تماس', 'text'],
            'address'       => ['آدرس', 'textarea'],
            'work_hours'    => ['ساعات کاری', 'textarea'],
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
        'سرویس‌ها و کلیدها' => [
            'telegram_bot_token' => ['توکن ربات تلگرام', 'password'],
            'smsir_api_key'      => ['کلید API سرویس SMS.ir', 'password'],
            'smsir_template_id'  => ['شناسه قالب پیامک OTP', 'text'],
        ],
    ];

    public function index($id = 0): void
    {
        $rows = Model::all('SELECT * FROM settings ORDER BY id');
        $values = [];
        $descs = [];
        foreach ($rows as $r) {
            $values[$r['setting_key']] = $r['setting_value'];
            $descs[$r['setting_key']] = $r['description'];
        }
        $known = [];
        foreach (self::GROUPS as $g) $known = array_merge($known, array_keys($g));
        $extra = array_diff(array_keys($values), $known);

        $groups = self::GROUPS;
        $this->view('settings', compact('values', 'descs', 'groups', 'extra'), 'تنظیمات سایت', 'مقادیر پیکربندی فروشگاه');
    }

    public function save($id = 0): void
    {
        $data = (array) post('settings', []);
        foreach ($data as $key => $value) {
            $key = trim((string) $key);
            if ($key === '') continue;
            $exists = Model::one('SELECT id FROM settings WHERE setting_key = ?', [$key]);
            if ($exists) {
                Model::exec('UPDATE settings SET setting_value = ? WHERE setting_key = ?', [(string) $value, $key]);
            } else {
                Model::insert('settings', ['setting_key' => $key, 'setting_value' => (string) $value]);
            }
        }
        flash('success', 'تنظیمات با موفقیت ذخیره شد.');
        redirect(admin_url('settings'));
    }

    public function add($id = 0): void
    {
        $key = trim((string) post('setting_key'));
        if ($key === '') { flash('error', 'کلید تنظیم الزامی است.'); redirect(admin_url('settings')); }
        if (Model::one('SELECT id FROM settings WHERE setting_key = ?', [$key])) {
            flash('error', 'این کلید قبلاً وجود دارد.');
            redirect(admin_url('settings'));
        }
        Model::insert('settings', [
            'setting_key'   => $key,
            'setting_value' => (string) post('setting_value'),
            'description'   => trim((string) post('description')) ?: null,
        ]);
        flash('success', 'تنظیم جدید اضافه شد.');
        redirect(admin_url('settings'));
    }

    public function delete($id = 0): void
    {
        Model::delete('settings', (int) $id);
        flash('success', 'تنظیم حذف شد.');
        redirect(admin_url('settings'));
    }
}
