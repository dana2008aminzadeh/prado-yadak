<?php
namespace Admin\controllers;

use Admin\core\Model;

class LocationController extends BaseController
{
    protected string $section = 'locations';
    protected int $perPage = 50;

    public function index($id = 0): void
    {
        $provinceId = (int) param('province', 0);
        $q = trim((string) param('q', ''));

        $provinces = Model::all('SELECT p.*, (SELECT COUNT(*) FROM cities c WHERE c.province_id = p.id) AS cities_count
                                 FROM provinces p ORDER BY p.name');

        $where = ['1']; $params = [];
        if ($provinceId) { $where[] = 'c.province_id = ?'; $params[] = $provinceId; }
        if ($q !== '') { $where[] = 'c.name LIKE ?'; $params[] = "%$q%"; }
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
        if ($name === '') { flash('error', 'نام استان الزامی است.'); redirect(admin_url('locations')); }
        $data = ['name' => $name, 'slug' => trim((string) post('slug')) ?: make_slug($name), 'is_active' => post('is_active') ? 1 : 0];
        $eid = (int) post('id', 0);
        if ($eid) { Model::update('provinces', $eid, $data); flash('success', 'استان به‌روزرسانی شد.'); }
        else { Model::insert('provinces', $data); flash('success', 'استان اضافه شد.'); }
        redirect(admin_url('locations'));
    }

    public function saveCity($id = 0): void
    {
        $name = trim((string) post('name'));
        $pid = (int) post('province_id');
        if ($name === '' || !$pid) { flash('error', 'نام شهر و استان الزامی است.'); redirect(admin_url('locations')); }
        $data = ['province_id' => $pid, 'name' => $name, 'slug' => trim((string) post('slug')) ?: make_slug($name), 'is_active' => post('is_active') ? 1 : 0];
        $eid = (int) post('id', 0);
        if ($eid) { Model::update('cities', $eid, $data); flash('success', 'شهر به‌روزرسانی شد.'); }
        else { Model::insert('cities', $data); flash('success', 'شهر اضافه شد.'); }
        redirect(admin_url('locations', $pid ? ['province' => $pid] : []));
    }

    public function toggleProvince($id = 0): void
    {
        Model::exec('UPDATE provinces SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('locations'));
    }

    public function toggleCity($id = 0): void
    {
        Model::exec('UPDATE cities SET is_active = 1 - is_active WHERE id = ?', [(int) $id]);
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('locations'));
    }

    public function deleteProvince($id = 0): void
    {
        Model::exec('DELETE FROM cities WHERE province_id = ?', [(int) $id]);
        Model::delete('provinces', (int) $id);
        flash('success', 'استان و شهرهای آن حذف شدند.');
        redirect(admin_url('locations'));
    }

    public function deleteCity($id = 0): void
    {
        Model::delete('cities', (int) $id);
        flash('success', 'شهر حذف شد.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('locations'));
    }
}
