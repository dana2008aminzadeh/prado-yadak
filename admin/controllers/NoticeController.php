<?php
namespace Admin\controllers;

use Admin\core\Model;

class NoticeController extends BaseController
{
    protected string $section = 'notices';

    protected array $permissions = [
        'save'   => 'notices.edit',
        'toggle' => 'notices.edit',
        'delete' => 'notices.edit',
    ];

    public function index($id = 0): void
    {
        $notices = Model::all('SELECT * FROM site_notices ORDER BY is_active DESC, priority DESC, id DESC');
        $editing = ($eid = (int) param('edit', 0)) ? Model::find('site_notices', $eid) : null;
        $this->view('notices/index', compact('notices', 'editing'), 'اطلاعیه‌های سایت', count($notices) . ' اطلاعیه');
    }

    public function save($id = 0): void
    {
        $title = trim((string) post('title'));
        if ($title === '') {
            flash('error', 'عنوان اطلاعیه الزامی است.');
            back(admin_url('notices'));
        }

        $data = [
            'page'      => trim((string) post('page')) ?: 'checkout',
            'type'      => in_array(post('type'), ['info', 'warning', 'danger'], true) ? (string) post('type') : 'info',
            'title'     => mb_substr($title, 0, 255),
            'message'   => (string) post('message'),
            'icon'      => trim((string) post('icon')) ?: 'info',
            'is_active' => post('is_active') ? 1 : 0,
            'priority'  => (int) post('priority', 0),
        ];

        $eid = (int) post('id', 0);
        if ($eid) {
            $old = Model::find('site_notices', $eid);
            Model::update('site_notices', $eid, $data);
            $this->audit('notice.update', 'notice', $eid, 'ویرایش اطلاعیه: ' . $title, $old, $data);
            flash('success', 'اطلاعیه به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('site_notices', $data);
            $this->audit('notice.update', 'notice', $newId, 'ایجاد اطلاعیه: ' . $title);
            flash('success', 'اطلاعیه ایجاد شد.');
        }
        redirect(admin_url('notices'));
    }

    public function toggle($id = 0): void
    {
        $nid = (int) post('notice_id', $id);
        Model::exec('UPDATE site_notices SET is_active = 1 - is_active WHERE id = ?', [$nid]);
        $this->audit('notice.update', 'notice', $nid, 'تغییر وضعیت نمایش اطلاعیه');
        flash('success', 'وضعیت اطلاعیه تغییر کرد.');
        redirect(admin_url('notices'));
    }

    public function delete($id = 0): void
    {
        $nid = (int) post('notice_id', $id);
        $n = Model::find('site_notices', $nid);
        if ($n) {
            Model::delete('site_notices', $nid);
            $this->audit('notice.update', 'notice', $nid, 'حذف اطلاعیه: ' . $n['title'], $n, null);
            flash('success', 'اطلاعیه حذف شد.');
        }
        redirect(admin_url('notices'));
    }
}
