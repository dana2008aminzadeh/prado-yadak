<?php
namespace Admin\controllers;

use Admin\core\Model;

class ShippingController extends BaseController
{
    protected string $section = 'shipping';

    protected array $permissions = [
        'save'   => 'shipping.edit',
        'toggle' => 'shipping.edit',
        'delete' => 'shipping.edit',
    ];

    public function index($id = 0): void
    {
        $methods = Model::all('SELECT * FROM shipping_methods ORDER BY sort_order ASC, id ASC');
        $editing = ($eid = (int) param('edit', 0)) ? Model::find('shipping_methods', $eid) : null;
        $carriers = OrderController::CARRIERS;
        $this->view('shipping/index', compact('methods', 'editing', 'carriers'),
            'شیوه‌های ارسال', count($methods) . ' روش تعریف‌شده');
    }

    public function save($id = 0): void
    {
        $title = trim((string) post('title'));
        if ($title === '') {
            flash('error', 'عنوان روش ارسال الزامی است.');
            back(admin_url('shipping'));
        }

        $carrier = (string) post('carrier_slug');
        $data = [
            'title'        => mb_substr($title, 0, 100),
            'subtitle'     => trim((string) post('subtitle')) ?: null,
            'cost'         => (float) preg_replace('/[^\d.]/', '', (string) post('cost', 0)),
            'free_above'   => post('free_above') !== '' ? (float) post('free_above') : null,
            'carrier_slug' => isset(OrderController::CARRIERS[$carrier]) ? $carrier : null,
            'is_active'    => post('is_active') ? 1 : 0,
            'sort_order'   => (int) post('sort_order', 0),
        ];

        $eid = (int) post('id', 0);
        if ($eid) {
            $old = Model::find('shipping_methods', $eid);
            Model::update('shipping_methods', $eid, $data);
            $this->audit('shipping.update', 'shipping', $eid, 'ویرایش روش ارسال: ' . $title, $old, $data);
            flash('success', 'روش ارسال به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('shipping_methods', $data);
            $this->audit('shipping.update', 'shipping', $newId, 'افزودن روش ارسال: ' . $title);
            flash('success', 'روش ارسال اضافه شد.');
        }
        redirect(admin_url('shipping'));
    }

    public function toggle($id = 0): void
    {
        $sid = (int) post('method_id', $id);
        Model::exec('UPDATE shipping_methods SET is_active = 1 - is_active WHERE id = ?', [$sid]);
        $this->audit('shipping.update', 'shipping', $sid, 'تغییر وضعیت روش ارسال');
        redirect(admin_url('shipping'));
    }

    public function delete($id = 0): void
    {
        $sid = (int) post('method_id', $id);
        $m = Model::find('shipping_methods', $sid);
        if ($m) {
            Model::delete('shipping_methods', $sid);
            $this->audit('shipping.update', 'shipping', $sid, 'حذف روش ارسال: ' . $m['title'], $m, null);
            flash('success', 'روش ارسال حذف شد.');
        }
        redirect(admin_url('shipping'));
    }
}
