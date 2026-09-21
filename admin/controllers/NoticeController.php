<?php
namespace Admin\controllers;

use Admin\core\Model;

class NoticeController extends BaseController
{
    protected string $section = 'notices';

    public function index($id = 0): void
    {
        $notices = Model::all('SELECT * FROM site_notices ORDER BY is_active DESC, priority DESC, id DESC');
        $editing = ($eid = (int) param('edit', 0)) ? Model::find('site_notices', $eid) : null;
        $this->view('notices/index', compact('notices', 'editing'), 'اطلاعیه‌های سایت', count($notices) . ' اطلاعیه');
    }

    public function save($id = 0): void
    {
        $title = trim((string) post('title'));
        if ($title === '') { flash('error', 'عنوان اطلاعیه الزامی است.'); redirect(admin_url('notices')); }
        $data = [
            'page'      => trim((string) post('page')) ?: 'checkout',
            'type'      => in_array(post('type'), ['info', 'warning', 'danger'], true) ? (string) post('type') : 'info',
            'title'     => $title,
            'message'   => (string) post('message'),
            'icon'      => trim((string) post('icon')) ?: 'info',
            'is_active' => post('is_active') ? 1 : 0,
            'priority'  => (int) post('priority', 0),
        ];
        $eid = (int) post('id', 0);
        if ($eid) { Model::update('site_notices', $eid, $data); flash('success', 'اطلاعیه به‌روزرسانی شد.'); }
        else { Model::insert('site_notices', $data); flash('success', 'اطلاعیه ایجاد شد.'); }
        redirect(admin_url('notices'));
    }

    public function toggle($id = 0): void
    {
        Model::exec('UPDATE site_notices SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        flash('success', 'وضعیت اطلاعیه تغییر کرد.');
        redirect(admin_url('notices'));
    }

    public function delete($id = 0): void
    {
        Model::delete('site_notices', (int) $id);
        flash('success', 'اطلاعیه حذف شد.');
        redirect(admin_url('notices'));
    }
}
