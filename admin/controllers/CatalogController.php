<?php
namespace Admin\controllers;

use Admin\core\Model;

/** مدیریت دسته‌بندی‌ها و مدل‌های خودرو */
class CatalogController extends BaseController
{
    protected string $section = 'catalog';

    public function index($id = 0): void
    {
        $categories = Model::all('SELECT c.*, p.name AS parent_name,
                                         (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id) AS products_count
                                  FROM categories c LEFT JOIN categories p ON p.id = c.parent_id
                                  ORDER BY COALESCE(c.parent_id, c.id), c.id');
        $carModels = Model::all('SELECT m.*, (SELECT COUNT(*) FROM products pr WHERE pr.car_model = m.slug) AS products_count
                                 FROM car_models m ORDER BY m.name');
        $editCat = ($cid = (int) param('cat', 0)) ? Model::find('categories', $cid) : null;
        $editModel = ($mid = (int) param('model', 0)) ? Model::find('car_models', $mid) : null;

        $this->view('catalog/index', compact('categories', 'carModels', 'editCat', 'editModel'),
            'دسته‌بندی‌ها و مدل‌های خودرو');
    }

    public function saveCategory($id = 0): void
    {
        $name = trim((string) post('name'));
        if ($name === '') { flash('error', 'نام دسته الزامی است.'); redirect(admin_url('catalog')); }
        $data = [
            'name'        => $name,
            'slug'        => trim((string) post('slug')) ?: make_slug($name),
            'description' => trim((string) post('description')) ?: null,
            'tags'        => trim((string) post('tags')) ?: null,
            'icon_svg'    => trim((string) post('icon_svg')) ?: null,
            'parent_id'   => post('parent_id') !== '' ? (int) post('parent_id') : null,
        ];
        $eid = (int) post('id', 0);
        if ($eid) {
            if ($data['parent_id'] === $eid) $data['parent_id'] = null;
            Model::update('categories', $eid, $data);
            flash('success', 'دسته‌بندی به‌روزرسانی شد.');
        } else {
            Model::insert('categories', $data);
            flash('success', 'دسته‌بندی جدید اضافه شد.');
        }
        redirect(admin_url('catalog'));
    }

    public function deleteCategory($id = 0): void
    {
        if (Model::count('products', 'category_id = ?', [(int) $id]) > 0) {
            flash('error', 'این دسته محصول دارد؛ ابتدا محصولات را جابه‌جا کنید.');
            redirect(admin_url('catalog'));
        }
        Model::exec('UPDATE categories SET parent_id = NULL WHERE parent_id = ?', [(int) $id]);
        Model::delete('categories', (int) $id);
        flash('success', 'دسته‌بندی حذف شد.');
        redirect(admin_url('catalog'));
    }

    public function saveModel($id = 0): void
    {
        $name = trim((string) post('name'));
        if ($name === '') { flash('error', 'نام مدل الزامی است.'); redirect(admin_url('catalog')); }
        $data = [
            'name'     => $name,
            'slug'     => trim((string) post('slug')) ?: make_slug($name),
            'logo_svg' => trim((string) post('logo_svg')) ?: null,
        ];
        $eid = (int) post('id', 0);
        if ($eid) {
            Model::update('car_models', $eid, $data);
            flash('success', 'مدل خودرو به‌روزرسانی شد.');
        } else {
            Model::insert('car_models', $data);
            flash('success', 'مدل خودرو اضافه شد.');
        }
        redirect(admin_url('catalog'));
    }

    public function deleteModel($id = 0): void
    {
        $m = Model::find('car_models', (int) $id);
        if ($m && Model::count('products', 'car_model = ?', [$m['slug']]) > 0) {
            flash('error', 'محصولاتی به این مدل متصل‌اند؛ ابتدا آن‌ها را تغییر دهید.');
            redirect(admin_url('catalog'));
        }
        Model::delete('car_models', (int) $id);
        flash('success', 'مدل خودرو حذف شد.');
        redirect(admin_url('catalog'));
    }
}
