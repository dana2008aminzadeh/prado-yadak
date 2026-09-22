<?php
namespace Admin\controllers;

use Admin\core\Model;

class CatalogController extends BaseController
{
    protected string $section = 'catalog';

    protected array $permissions = [
        'saveCategory'   => 'catalog.edit',
        'deleteCategory' => 'catalog.edit',
        'saveModel'      => 'catalog.edit',
        'deleteModel'    => 'catalog.edit',
    ];

    public function index($id = 0): void
    {
        $categories = Model::all('SELECT c.*, p.name AS parent_name,
                                         (SELECT COUNT(*) FROM products pr WHERE pr.category_id = c.id) AS products_count
                                  FROM categories c LEFT JOIN categories p ON p.id = c.parent_id
                                  ORDER BY COALESCE(c.parent_id, c.id), c.id');

        $carModels = Model::all('SELECT m.*,
                                        (SELECT COUNT(*) FROM product_vehicles pv WHERE pv.car_model_id = m.id) AS products_count
                                 FROM car_models m ORDER BY m.name');

        $editCat = ($cid = (int) param('cat', 0)) ? Model::find('categories', $cid) : null;
        $editModel = ($mid = (int) param('model', 0)) ? Model::find('car_models', $mid) : null;

        $this->view('catalog/index', compact('categories', 'carModels', 'editCat', 'editModel'),
            'دسته‌بندی‌ها و مدل‌های خودرو');
    }

    public function saveCategory($id = 0): void
    {
        $name = trim((string) post('name'));
        if ($name === '') {
            flash('error', 'نام دسته الزامی است.');
            back(admin_url('catalog'));
        }

        $eid = (int) post('id', 0);
        $parentId = post('parent_id') !== '' ? (int) post('parent_id') : null;
        if ($eid && $parentId === $eid) $parentId = null;

        // جلوگیری از حلقه در درخت دسته‌ها
        if ($eid && $parentId) {
            $cursor = $parentId;
            $depth = 0;
            while ($cursor && $depth++ < 10) {
                if ($cursor === $eid) { $parentId = null; break; }
                $cursor = (int) (Model::one('SELECT parent_id FROM categories WHERE id = ?', [$cursor])['parent_id'] ?? 0);
            }
        }

        $data = [
            'name'        => mb_substr($name, 0, 100),
            'slug'        => trim((string) post('slug')) ?: make_slug($name),
            'description' => trim((string) post('description')) ?: null,
            'tags'        => trim((string) post('tags')) ?: null,
            'icon_svg'    => trim((string) post('icon_svg')) ?: null,
            'parent_id'   => $parentId,
        ];

        if ($eid) {
            $old = Model::find('categories', $eid);
            Model::update('categories', $eid, $data);
            $this->audit('catalog.update', 'category', $eid, 'ویرایش دسته‌بندی: ' . $name, $old, $data);
            flash('success', 'دسته‌بندی به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('categories', $data);
            $this->audit('catalog.update', 'category', $newId, 'ایجاد دسته‌بندی: ' . $name);
            flash('success', 'دسته‌بندی جدید اضافه شد.');
        }
        redirect(admin_url('catalog'));
    }

    public function deleteCategory($id = 0): void
    {
        $cid = (int) post('category_id', $id);
        $c = Model::find('categories', $cid);
        if (!$c) {
            flash('error', 'دسته یافت نشد.');
            redirect(admin_url('catalog'));
        }
        $count = Model::count('products', 'category_id = ?', [$cid]);
        if ($count > 0) {
            flash('error', "این دسته {$count} محصول دارد؛ ابتدا محصولات را جابه‌جا کنید.");
            redirect(admin_url('catalog'));
        }

        Model::exec('UPDATE categories SET parent_id = NULL WHERE parent_id = ?', [$cid]);
        Model::delete('categories', $cid);
        $this->audit('catalog.delete', 'category', $cid, 'حذف دسته‌بندی: ' . $c['name'], $c, null);
        flash('success', 'دسته‌بندی حذف شد.');
        redirect(admin_url('catalog'));
    }

    public function saveModel($id = 0): void
    {
        $name = trim((string) post('name'));
        if ($name === '') {
            flash('error', 'نام مدل الزامی است.');
            back(admin_url('catalog'));
        }

        $data = [
            'name'     => mb_substr($name, 0, 100),
            'slug'     => trim((string) post('slug')) ?: make_slug($name),
            'logo_svg' => trim((string) post('logo_svg')) ?: null,
        ];

        $eid = (int) post('id', 0);
        if ($eid) {
            $old = Model::find('car_models', $eid);
            Model::update('car_models', $eid, $data);
            $this->audit('catalog.update', 'car_model', $eid, 'ویرایش مدل خودرو: ' . $name, $old, $data);
            flash('success', 'مدل خودرو به‌روزرسانی شد.');
        } else {
            $newId = Model::insert('car_models', $data);
            $this->audit('catalog.update', 'car_model', $newId, 'افزودن مدل خودرو: ' . $name);
            flash('success', 'مدل خودرو اضافه شد.');
        }
        redirect(admin_url('catalog'));
    }

    public function deleteModel($id = 0): void
    {
        $mid = (int) post('model_id', $id);
        $m = Model::find('car_models', $mid);
        if (!$m) {
            flash('error', 'مدل یافت نشد.');
            redirect(admin_url('catalog'));
        }
        $count = Model::count('product_vehicles', 'car_model_id = ?', [$mid]);
        if ($count > 0) {
            flash('error', "{$count} محصول به این مدل متصل‌اند؛ ابتدا آن‌ها را تغییر دهید.");
            redirect(admin_url('catalog'));
        }

        Model::delete('car_models', $mid);
        $this->audit('catalog.delete', 'car_model', $mid, 'حذف مدل خودرو: ' . $m['name'], $m, null);
        flash('success', 'مدل خودرو حذف شد.');
        redirect(admin_url('catalog'));
    }
}
