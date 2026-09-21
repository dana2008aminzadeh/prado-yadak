<?php
namespace Admin\controllers;

use Admin\core\Model;

class CouponController extends BaseController
{
    protected string $section = 'coupons';

    public function index($id = 0): void
    {
        $coupons = Model::all('SELECT * FROM discount_coupons ORDER BY is_active DESC, id DESC');
        $editing = null;
        if ($eid = (int) param('edit', 0)) {
            $editing = Model::find('discount_coupons', $eid);
        }
        $usage = [];
        foreach (Model::all('SELECT applied_coupon AS c, COUNT(*) n, SUM(discount_amount) s FROM orders WHERE applied_coupon IS NOT NULL AND applied_coupon <> "" GROUP BY applied_coupon') as $r) {
            $usage[$r['c']] = $r;
        }
        $this->view('coupons/index', compact('coupons', 'editing', 'usage'), 'کدهای تخفیف', count($coupons) . ' کد ثبت‌شده');
    }

    public function save($id = 0): void
    {
        $code = strtoupper(trim((string) post('code')));
        if ($code === '') { flash('error', 'کد تخفیف الزامی است.'); redirect(admin_url('coupons')); }

        $data = [
            'code'         => $code,
            'type'         => post('type') === 'fixed' ? 'fixed' : 'percent',
            'value'        => (float) post('value', 0),
            'min_order'    => (float) post('min_order', 0),
            'max_discount' => post('max_discount') !== '' ? (float) post('max_discount') : null,
            'usage_limit'  => post('usage_limit') !== '' ? (int) post('usage_limit') : null,
            'expires_at'   => post('expires_at') ? str_replace('T', ' ', (string) post('expires_at')) . ':00' : null,
            'is_active'    => post('is_active') ? 1 : 0,
        ];

        $eid = (int) post('id', 0);
        if ($eid) {
            Model::update('discount_coupons', $eid, $data);
            flash('success', 'کد تخفیف به‌روزرسانی شد.');
        } else {
            if (Model::one('SELECT id FROM discount_coupons WHERE code = ?', [$code])) {
                flash('error', 'این کد تخفیف قبلاً ثبت شده است.');
                redirect(admin_url('coupons'));
            }
            Model::insert('discount_coupons', $data);
            flash('success', 'کد تخفیف جدید ایجاد شد.');
        }
        redirect(admin_url('coupons'));
    }

    public function toggle($id = 0): void
    {
        Model::exec('UPDATE discount_coupons SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        flash('success', 'وضعیت کد تخفیف تغییر کرد.');
        redirect(admin_url('coupons'));
    }

    public function reset($id = 0): void
    {
        Model::exec('UPDATE discount_coupons SET used_count = 0 WHERE id = ?', [(int) $id]);
        flash('success', 'شمارنده استفاده صفر شد.');
        redirect(admin_url('coupons'));
    }

    public function delete($id = 0): void
    {
        Model::delete('discount_coupons', (int) $id);
        flash('success', 'کد تخفیف حذف شد.');
        redirect(admin_url('coupons'));
    }
}
