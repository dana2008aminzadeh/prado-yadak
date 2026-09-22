<?php
namespace Admin\controllers;

use Admin\core\Auth;
use Admin\core\Model;
use Admin\core\Permission;

class RoleController extends BaseController
{
    protected string $section = 'roles';

    protected array $permissions = [
        'save'       => 'roles.manage',
        'delete'     => 'roles.manage',
        'assign'     => 'users.roles',
        'revoke'     => 'users.roles',
        'toggleUser' => 'users.roles',
    ];

    public function index($id = 0): void
    {
        $roles = Model::all('SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.admin_role_id = r.id) AS admins_count
                             FROM admin_roles r ORDER BY r.is_system DESC, r.id');

        $admins = Model::all("SELECT u.id, u.full_name, u.phone, u.email, COALESCE(u.is_active,1) AS is_active,
                                     r.name AS role_name, r.slug AS role_slug, u.admin_role_id, u.created_at
                              FROM users u LEFT JOIN admin_roles r ON r.id = u.admin_role_id
                              WHERE u.role = 'admin' ORDER BY u.id");

        $editing = ($eid = (int) param('edit', 0)) ? Model::find('admin_roles', $eid) : null;

        $tree = Permission::TREE;
        $meId = $this->adminId();

        $this->view('roles/index', compact('roles', 'admins', 'editing', 'tree', 'meId'),
            'نقش‌ها و سطوح دسترسی', count($roles) . ' نقش — ' . count($admins) . ' مدیر');
    }

    public function save($id = 0): void
    {
        $rid = (int) post('id', 0);
        $name = trim((string) post('name'));
        $slug = preg_replace('/[^a-z0-9_]/i', '', (string) post('slug')) ?: '';

        if ($name === '') {
            flash('error', 'نام نقش الزامی است.');
            back(admin_url('roles'));
        }

        $selected = array_values(array_intersect(
            array_map('strval', (array) post('permissions', [])),
            array_keys(Permission::all())
        ));

        if (!$selected) {
            flash('error', 'حداقل یک دسترسی باید انتخاب شود.');
            back(admin_url('roles'));
        }

        $existing = $rid ? Model::find('admin_roles', $rid) : null;

        // نقش «مدیر کل» قابل محدود کردن نیست
        if ($existing && $existing['slug'] === 'super_admin') {
            flash('error', 'دسترسی‌های نقش «مدیر کل» قابل تغییر نیست.');
            back(admin_url('roles'));
        }

        $data = [
            'name'        => mb_substr($name, 0, 100),
            'permissions' => implode(',', $selected),
        ];

        if ($existing) {
            Model::update('admin_roles', $rid, $data);
            $this->audit('role.update', 'role', $rid, 'ویرایش نقش «' . $name . '» با ' . count($selected) . ' دسترسی',
                $existing, $data);
            flash('success', 'نقش به‌روزرسانی شد.');
        } else {
            if ($slug === '') $slug = 'role_' . substr(md5($name . microtime()), 0, 6);
            if (Model::one('SELECT id FROM admin_roles WHERE slug = ?', [$slug])) {
                $slug .= '_' . random_int(10, 99);
            }
            $data['slug'] = $slug;
            $data['is_system'] = 0;
            $newId = Model::insert('admin_roles', $data);
            $this->audit('role.update', 'role', $newId, 'ایجاد نقش جدید «' . $name . '»');
            flash('success', 'نقش جدید ایجاد شد.');
        }

        redirect(admin_url('roles'));
    }

    public function delete($id = 0): void
    {
        $rid = (int) post('role_id', $id);
        $role = Model::find('admin_roles', $rid);

        if (!$role) {
            flash('error', 'نقش یافت نشد.');
            redirect(admin_url('roles'));
        }
        if ((int) $role['is_system'] === 1) {
            flash('error', 'نقش‌های پیش‌فرض سیستم قابل حذف نیستند.');
            redirect(admin_url('roles'));
        }
        $inUse = Model::count('users', 'admin_role_id = ?', [$rid]);
        if ($inUse > 0) {
            flash('error', "این نقش به {$inUse} مدیر تخصیص یافته است. ابتدا نقش آن‌ها را تغییر دهید.");
            redirect(admin_url('roles'));
        }

        Model::delete('admin_roles', $rid);
        $this->audit('role.update', 'role', $rid, 'حذف نقش «' . $role['name'] . '»', $role, null);
        flash('success', 'نقش حذف شد.');
        redirect(admin_url('roles'));
    }

    /** تخصیص نقش مدیریتی به یک کاربر */
    public function assign($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        $roleId = post('role_id') !== '' ? (int) post('role_id') : null;

        $user = Model::find('users', $uid);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('roles'));
        }

        if ($roleId !== null && !Model::find('admin_roles', $roleId)) {
            flash('error', 'نقش انتخابی معتبر نیست.');
            redirect(admin_url('roles'));
        }

        // محافظت: مدیر کل نمی‌تواند نقش خودش را تنزل دهد و آخرین مدیر کل باید باقی بماند
        if ($uid === $this->adminId()) {
            $superId = (int) (Model::one("SELECT id FROM admin_roles WHERE slug = 'super_admin'")['id'] ?? 0);
            if ($roleId !== $superId) {
                flash('error', 'نمی‌توانید سطح دسترسی خودتان را کاهش دهید.');
                redirect(admin_url('roles'));
            }
        }
        if (!$this->ensureSuperAdminRemains($uid, $roleId)) {
            flash('error', 'حداقل یک «مدیر کل» فعال باید در سیستم باقی بماند.');
            redirect(admin_url('roles'));
        }

        Model::exec("UPDATE users SET role = 'admin', admin_role_id = ? WHERE id = ?", [$roleId, $uid]);

        $roleName = $roleId ? (Model::find('admin_roles', $roleId)['name'] ?? '?') : 'بدون نقش';
        $this->audit('user.role', 'user', $uid,
            'تخصیص نقش «' . $roleName . '» به ' . ($user['full_name'] ?: $user['phone']));
        flash('success', 'نقش «' . $roleName . '» به کاربر تخصیص یافت.');
        redirect(admin_url('roles'));
    }

    /** سلب دسترسی مدیریتی */
    public function revoke($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        if ($uid === $this->adminId()) {
            flash('error', 'نمی‌توانید دسترسی مدیریتی خودتان را حذف کنید.');
            redirect(admin_url('roles'));
        }
        if (!$this->ensureSuperAdminRemains($uid, null, true)) {
            flash('error', 'حداقل یک «مدیر کل» فعال باید باقی بماند.');
            redirect(admin_url('roles'));
        }

        $user = Model::find('users', $uid);
        Model::exec("UPDATE users SET role = 'user', admin_role_id = NULL WHERE id = ?", [$uid]);

        $this->audit('user.role', 'user', $uid,
            'سلب دسترسی مدیریتی از ' . ($user['full_name'] ?? $uid));
        flash('success', 'دسترسی مدیریتی کاربر حذف شد.');
        redirect(admin_url('roles'));
    }

    /** فعال یا غیرفعال کردن یک حساب مدیریتی */
    public function toggleUser($id = 0): void
    {
        $uid = (int) post('user_id', $id);
        if ($uid === $this->adminId()) {
            flash('error', 'نمی‌توانید حساب خودتان را غیرفعال کنید.');
            redirect(admin_url('roles'));
        }

        $user = Model::find('users', $uid);
        if (!$user) {
            flash('error', 'کاربر یافت نشد.');
            redirect(admin_url('roles'));
        }

        $newState = (int) ($user['is_active'] ?? 1) === 1 ? 0 : 1;
        if ($newState === 0 && !$this->ensureSuperAdminRemains($uid, null, true)) {
            flash('error', 'حداقل یک «مدیر کل» فعال باید باقی بماند.');
            redirect(admin_url('roles'));
        }

        Model::exec('UPDATE users SET is_active = ? WHERE id = ?', [$newState, $uid]);
        $this->audit('user.update', 'user', $uid,
            ($newState ? 'فعال‌سازی' : 'غیرفعال‌سازی') . ' حساب مدیریتی ' . ($user['full_name'] ?: $uid));
        flash('success', $newState ? 'حساب مدیریتی فعال شد.' : 'حساب مدیریتی غیرفعال شد.');
        redirect(admin_url('roles'));
    }

    /**
     * اطمینان از باقی ماندن حداقل یک مدیر کل فعال
     * @param int  $changingUserId کاربری که قرار است تغییر کند
     * @param ?int $newRoleId      نقش جدید او
     * @param bool $removing       آیا کلاً دسترسی سلب می‌شود
     */
    private function ensureSuperAdminRemains(int $changingUserId, ?int $newRoleId, bool $removing = false): bool
    {
        $superId = (int) (Model::one("SELECT id FROM admin_roles WHERE slug = 'super_admin'")['id'] ?? 0);
        if (!$superId) return true;

        $current = Model::find('users', $changingUserId);
        $wasSuper = (int) ($current['admin_role_id'] ?? 0) === $superId;

        if (!$wasSuper) return true;
        if (!$removing && $newRoleId === $superId) return true;

        $remaining = (int) Model::scalar(
            "SELECT COUNT(*) FROM users WHERE role = 'admin' AND admin_role_id = ?
             AND COALESCE(is_active,1) = 1 AND id <> ?",
            [$superId, $changingUserId]
        );
        return $remaining > 0;
    }
}
