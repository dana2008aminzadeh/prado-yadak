<?php
namespace Admin\controllers;

use Admin\core\Model;

class ShippingController extends BaseController
{
    protected string $section = 'shipping';

    public function index($id = 0): void
    {
        $methods = Model::all('SELECT * FROM shipping_methods ORDER BY sort_order ASC, id ASC');
        $editing = ($eid = (int) param('edit', 0)) ? Model::find('shipping_methods', $eid) : null;
        $this->view('shipping/index', compact('methods', 'editing'), 'شیوه‌های ارسال', count($methods) . ' روش تعریف‌شده');
    }

    public function save($id = 0): void
    {
        $title = trim((string) post('title'));
        if ($title === '') { flash('error', 'عنوان روش ارسال الزامی است.'); redirect(admin_url('shipping')); }
        $data = [
            'title'      => $title,
            'subtitle'   => trim((string) post('subtitle')) ?: null,
            'is_active'  => post('is_active') ? 1 : 0,
            'sort_order' => (int) post('sort_order', 0),
        ];
        $eid = (int) post('id', 0);
        if ($eid) { Model::update('shipping_methods', $eid, $data); flash('success', 'روش ارسال به‌روزرسانی شد.'); }
        else { Model::insert('shipping_methods', $data); flash('success', 'روش ارسال اضافه شد.'); }
        redirect(admin_url('shipping'));
    }

    public function toggle($id = 0): void
    {
        Model::exec('UPDATE shipping_methods SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        redirect(admin_url('shipping'));
    }

    public function delete($id = 0): void
    {
        Model::delete('shipping_methods', (int) $id);
        flash('success', 'روش ارسال حذف شد.');
        redirect(admin_url('shipping'));
    }
}
