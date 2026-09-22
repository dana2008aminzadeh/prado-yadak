<?php
namespace Admin\controllers;

use Admin\core\Model;

class LocationController extends BaseController
{
    protected string $section = 'locations';
    protected int $perPage = 50;

    protected array $permissions = [
        'saveProvince'   => 'locations.edit',
        'saveCity'       => 'locations.edit',
        'toggleProvince' => 'locations.edit',
        'toggleCity'     => 'locations.edit',
        'deleteProvince' => 'locations.edit',
        'deleteCity'     => 'locations.edit',
    ];

    public function index($id = 0): void
    {
        $provinceId = (int) param('province', 0);
        $q = trim((string) param('q', ''));

        $provinces = Model::all('SELECT p.*, (SELECT COUNT(*) FROM cities c WHERE c.province_id = p.id) AS cities_count
                                 FROM provinces p ORDER BY p.name');

        $where = ['1'];
        $params = [];
        if ($provinceId) { $where[] = 'c.province_id = ?'; $params[] = $provinceId; }
        if ($q !== '')   { $where[] = 'c.name LIKE ?'; $params[] = "%$q%"; }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM cities c WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $cities = Model::all("SELECT c.*, p.name AS province_name FROM cities c
                              LEFT JOIN provinces p ON p.id = c.province_id
                              WHERE $w ORDER BY p.name, c.name LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $this->view('locations/index', compact('provinces', 'cities', 'pg', 'provinceId', 'q'),
            'استان‌ها و شهرها', count($provinces) . ' استان — ' . money($total) . ' شهر');
    }

    public function saveProvince($id = 0): void
    {
        $name = trim((string) post('name'));
        if ($name === '') {
            flash('error', 'نام استان الزامی است.');
            back(admin_url('locations'));
        }
        $data = ['name' => mb_substr($name, 0, 100), 'slug' => trim((string) post('slug')) ?: make_slug($name),
                 'is_active' => post('is_active') ? 1 : 0];

        $eid = (int) post('id', 0);
        if ($eid) {
            Model::update('provinces', $eid, $data);
            $this->audit('location.update', 'province', $eid, 'ویرایش استان: ' . $name);
            flash('success', 'استان به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('provinces', $data);
            $this->audit('location.update', 'province', $newId, 'افزودن استان: ' . $name);
            flash('success', 'استان اضافه شد.');
        }
        redirect(admin_url('locations'));
    }

    public function saveCity($id = 0): void
    {
        $name = trim((string) post('name'));
        $pid = (int) post('province_id');
        if ($name === '' || !$pid) {
            flash('error', 'نام شهر و استان الزامی است.');
            back(admin_url('locations'));
        }
        $data = ['province_id' => $pid, 'name' => mb_substr($name, 0, 100),
                 'slug' => trim((string) post('slug')) ?: make_slug($name),
                 'is_active' => post('is_active') ? 1 : 0];

        $eid = (int) post('id', 0);
        if ($eid) {
            Model::update('cities', $eid, $data);
            $this->audit('location.update', 'city', $eid, 'ویرایش شهر: ' . $name);
            flash('success', 'شهر به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('cities', $data);
            $this->audit('location.update', 'city', $newId, 'افزودن شهر: ' . $name);
            flash('success', 'شهر اضافه شد.');
        }
        redirect(admin_url('locations', ['province' => $pid]));
    }

    public function toggleProvince($id = 0): void
    {
        $pid = (int) post('province_id', $id);
        Model::exec('UPDATE provinces SET is_active = 1 - is_active WHERE id = ?', [$pid]);
        $this->audit('location.update', 'province', $pid, 'تغییر وضعیت فعال بودن استان');
        back(admin_url('locations'));
    }

    public function toggleCity($id = 0): void
    {
        $cid = (int) post('city_id', $id);
        Model::exec('UPDATE cities SET is_active = 1 - is_active WHERE id = ?', [$cid]);
        $this->audit('location.update', 'city', $cid, 'تغییر وضعیت فعال بودن شهر');
        back(admin_url('locations'));
    }

    public function deleteProvince($id = 0): void
    {
        $pid = (int) post('province_id', $id);
        $p = Model::find('provinces', $pid);
        if ($p) {
            Model::exec('DELETE FROM cities WHERE province_id = ?', [$pid]);
            Model::delete('provinces', $pid);
            $this->audit('location.update', 'province', $pid, 'حذف استان «' . $p['name'] . '» و شهرهای آن', $p, null);
            flash('success', 'استان و شهرهای آن حذف شدند.');
        }
        redirect(admin_url('locations'));
    }

    public function deleteCity($id = 0): void
    {
        $cid = (int) post('city_id', $id);
        $c = Model::find('cities', $cid);
        if ($c) {
            Model::delete('cities', $cid);
            $this->audit('location.update', 'city', $cid, 'حذف شهر: ' . $c['name'], $c, null);
            flash('success', 'شهر حذف شد.');
        }
        back(admin_url('locations'));
    }
}
