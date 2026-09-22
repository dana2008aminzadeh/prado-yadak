<?php
namespace Admin\controllers;

use Admin\core\Model;

class CouponController extends BaseController
{
    protected string $section = 'coupons';

    protected array $permissions = [
        'save'   => 'coupons.edit',
        'toggle' => 'coupons.edit',
        'reset'  => 'coupons.edit',
        'delete' => 'coupons.edit',
    ];

    public function index($id = 0): void
    {
        $coupons = Model::all('SELECT dc.*, c.name AS category_name, cm.name AS car_model_name
                               FROM discount_coupons dc
                               LEFT JOIN categories c ON c.id = dc.category_id
                               LEFT JOIN car_models cm ON cm.id = dc.car_model_id
                               ORDER BY dc.is_active DESC, dc.id DESC');

        $editing = ($eid = (int) param('edit', 0)) ? Model::find('discount_coupons', $eid) : null;

        $usage = [];
        foreach (Model::all("SELECT applied_coupon AS c, COUNT(*) n, COALESCE(SUM(discount_amount),0) s
                             FROM orders WHERE applied_coupon IS NOT NULL AND applied_coupon <> ''
                             GROUP BY applied_coupon") as $r) {
            $usage[$r['c']] = $r;
        }

        $categories = Model::all('SELECT id, name FROM categories ORDER BY name');
        $carModels  = Model::all('SELECT id, name FROM car_models ORDER BY name');

        $this->view('coupons/index', compact('coupons', 'editing', 'usage', 'categories', 'carModels'),
            'کدهای تخفیف', count($coupons) . ' کد ثبت‌شده');
    }

    public function save($id = 0): void
    {
        $code = strtoupper(trim((string) post('code')));
        $code = preg_replace('/[^A-Z0-9_\-]/', '', $code) ?? '';

        if ($code === '') {
            flash('error', 'کد تخفیف الزامی است و فقط شامل حروف انگلیسی و عدد باشد.');
            back(admin_url('coupons'));
        }

        $type = post('type') === 'fixed' ? 'fixed' : 'percent';
        $value = (float) post('value', 0);

        if ($value <= 0) {
            flash('error', 'مقدار تخفیف باید بزرگ‌تر از صفر باشد.');
            back(admin_url('coupons'));
        }
        if ($type === 'percent' && $value > 100) {
            flash('error', 'درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.');
            back(admin_url('coupons'));
        }

        $startsAt  = $this->datePost('starts_at');
        $expiresAt = $this->datePost('expires_at');

        if ($startsAt && $expiresAt && strtotime($expiresAt) < strtotime($startsAt)) {
            flash('error', 'تاریخ انقضا نمی‌تواند قبل از تاریخ شروع باشد.');
            back(admin_url('coupons'));
        }

        $data = [
            'code'             => $code,
            'type'             => $type,
            'value'            => $value,
            'min_order'        => (float) post('min_order', 0),
            'max_discount'     => post('max_discount') !== '' ? (float) post('max_discount') : null,
            'category_id'      => post('category_id') !== '' ? (int) post('category_id') : null,
            'car_model_id'     => post('car_model_id') !== '' ? (int) post('car_model_id') : null,
            'first_order_only' => post('first_order_only') ? 1 : 0,
            'usage_limit'      => post('usage_limit') !== '' ? (int) post('usage_limit') : null,
            'per_user_limit'   => post('per_user_limit') !== '' ? (int) post('per_user_limit') : null,
            'starts_at'        => $startsAt ? $startsAt . ' 00:00:00' : null,
            'expires_at'       => $expiresAt ? $expiresAt . ' 23:59:59' : null,
            'is_active'        => post('is_active') ? 1 : 0,
        ];

        $eid = (int) post('id', 0);
        if ($eid) {
            $old = Model::find('discount_coupons', $eid);
            if (!$old) {
                flash('error', 'کد تخفیف یافت نشد.');
                redirect(admin_url('coupons'));
            }
            Model::update('discount_coupons', $eid, $data);
            $this->audit('coupon.update', 'coupon', $eid, 'ویرایش کد تخفیف ' . $code, $old, $data);
            flash('success', 'کد تخفیف به‌روزرسانی شد.');
        } else {
            if (Model::one('SELECT id FROM discount_coupons WHERE code = ?', [$code])) {
                flash('error', 'این کد تخفیف قبلاً ثبت شده است.');
                back(admin_url('coupons'));
            }
            $newId = Model::insert('discount_coupons', $data);
            $this->audit('coupon.create', 'coupon', $newId,
                'ایجاد کد تخفیف ' . $code . ' — ' . ($type === 'percent' ? $value . '٪' : money($value) . ' تومان'));
            flash('success', 'کد تخفیف جدید ایجاد شد.');
        }

        redirect(admin_url('coupons'));
    }

    public function toggle($id = 0): void
    {
        $cid = (int) post('coupon_id', $id);
        $c = Model::find('discount_coupons', $cid);
        if (!$c) {
            flash('error', 'کد تخفیف یافت نشد.');
            redirect(admin_url('coupons'));
        }
        Model::exec('UPDATE discount_coupons SET is_active = 1 - is_active WHERE id = ?', [$cid]);
        $this->audit('coupon.update', 'coupon', $cid,
            ((int) $c['is_active'] === 1 ? 'غیرفعال‌سازی' : 'فعال‌سازی') . ' کد تخفیف ' . $c['code']);
        flash('success', 'وضعیت کد تخفیف تغییر کرد.');
        redirect(admin_url('coupons'));
    }

    public function reset($id = 0): void
    {
        $cid = (int) post('coupon_id', $id);
        $c = Model::find('discount_coupons', $cid);
        if ($c) {
            Model::exec('UPDATE discount_coupons SET used_count = 0 WHERE id = ?', [$cid]);
            $this->audit('coupon.update', 'coupon', $cid, 'صفر کردن شمارنده استفاده کد ' . $c['code']);
            flash('success', 'شمارنده استفاده صفر شد.');
        }
        redirect(admin_url('coupons'));
    }

    public function delete($id = 0): void
    {
        $cid = (int) post('coupon_id', $id);
        $c = Model::find('discount_coupons', $cid);
        if ($c) {
            Model::delete('discount_coupons', $cid);
            $this->audit('coupon.delete', 'coupon', $cid, 'حذف کد تخفیف ' . $c['code'], $c, null);
            flash('success', 'کد تخفیف حذف شد.');
        }
        redirect(admin_url('coupons'));
    }
}
