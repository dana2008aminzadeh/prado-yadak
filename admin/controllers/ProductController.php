<?php
namespace Admin\controllers;

use Admin\core\Model;

class ProductController extends BaseController
{
    protected string $section = 'products';

    public function index($id = 0): void
    {
        $q        = trim((string) param('q', ''));
        $cat      = param('category', '');
        $model    = param('car_model', '');
        $stock    = param('stock', '');
        $genuine  = param('genuine', '');
        $sort     = param('sort', 'new');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.oem_code LIKE ? OR p.brand LIKE ? OR p.slug LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($cat !== '')     { $where[] = 'p.category_id = ?'; $params[] = (int) $cat; }
        if ($model !== '')   { $where[] = 'p.car_model = ?'; $params[] = $model; }
        if ($stock !== '')   { $where[] = 'p.in_stock = ?'; $params[] = (int) $stock; }
        if ($genuine !== '') { $where[] = 'p.is_genuine = ?'; $params[] = (int) $genuine; }
        $w = implode(' AND ', $where);

        $orderBy = match ($sort) {
            'old'        => 'p.created_at ASC',
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC',
            default      => 'p.created_at DESC',
        };

        $total = (int) Model::scalar("SELECT COUNT(*) FROM products p WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $products = Model::all(
            "SELECT p.*, c.name AS category_name,
                    (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id = p.id) AS sold
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE $w ORDER BY $orderBy LIMIT {$this->perPage} OFFSET {$pg['offset']}",
            $params
        );

        $categories = Model::all('SELECT id, name FROM categories ORDER BY name');
        $carModels  = Model::all('SELECT slug, name FROM car_models ORDER BY name');

        $this->view('products/index', compact('products', 'categories', 'carModels', 'pg', 'q', 'cat', 'model', 'stock', 'genuine', 'sort'),
            'مدیریت محصولات', money($total) . ' محصول ثبت شده');
    }

    public function create($id = 0): void
    {
        if ($this->isPost()) {
            $this->save(0);
        }
        $product = ['id' => 0, 'name' => '', 'slug' => '', 'category_id' => null, 'price' => 0, 'oem_code' => '',
            'car_model' => '', 'brand' => '', 'is_genuine' => 0, 'in_stock' => 1, 'description' => '', 'telegram_photo_id' => ''];
        $this->form($product, 'افزودن محصول جدید');
    }

    public function edit($id = 0): void
    {
        $product = Model::find('products', (int) $id);
        if (!$product) {
            flash('error', 'محصول یافت نشد.');
            redirect(admin_url('products'));
        }
        if ($this->isPost()) {
            $this->save((int) $id);
        }
        $this->form($product, 'ویرایش محصول: ' . $product['name']);
    }

    private function form(array $product, string $title): void
    {
        $categories = Model::all('SELECT id, name FROM categories ORDER BY name');
        $carModels  = Model::all('SELECT slug, name FROM car_models ORDER BY name');
        $this->view('products/form', compact('product', 'categories', 'carModels'), $title);
    }

    private function save(int $id): void
    {
        $name = trim((string) post('name'));
        if ($name === '') {
            flash('error', 'نام محصول الزامی است.');
            redirect($id ? admin_url('products/edit/' . $id) : admin_url('products/create'));
        }
        $slug = trim((string) post('slug')) ?: make_slug($name);

        $data = [
            'name'              => $name,
            'slug'              => $slug,
            'category_id'       => post('category_id') !== '' ? (int) post('category_id') : null,
            'price'             => (float) str_replace(',', '', (string) post('price', 0)),
            'oem_code'          => trim((string) post('oem_code')) ?: null,
            'car_model'         => trim((string) post('car_model')) ?: null,
            'brand'             => trim((string) post('brand')) ?: null,
            'is_genuine'        => post('is_genuine') ? 1 : 0,
            'in_stock'          => post('in_stock') ? 1 : 0,
            'description'       => (string) post('description'),
            'telegram_photo_id' => trim((string) post('telegram_photo_id')) ?: null,
        ];

        // یکتا بودن اسلاگ
        $exists = Model::one('SELECT id FROM products WHERE slug = ? AND id <> ?', [$data['slug'], $id]);
        if ($exists) {
            $data['slug'] .= '-' . random_int(100, 999);
        }

        if ($id) {
            Model::update('products', $id, $data);
            flash('success', 'محصول با موفقیت به‌روزرسانی شد.');
        } else {
            $id = Model::insert('products', $data);
            flash('success', 'محصول جدید با موفقیت اضافه شد.');
        }
        redirect(admin_url('products/edit/' . $id));
    }

    public function toggleStock($id = 0): void
    {
        Model::exec('UPDATE products SET in_stock = 1 - in_stock WHERE id = ?', [(int) $id]);
        flash('success', 'وضعیت موجودی تغییر کرد.');
        redirect($_SERVER['HTTP_REFERER'] ?? admin_url('products'));
    }

    public function delete($id = 0): void
    {
        $used = Model::count('order_items', 'product_id = ?', [(int) $id]);
        if ($used > 0) {
            Model::exec('UPDATE products SET in_stock = 0 WHERE id = ?', [(int) $id]);
            flash('info', 'این محصول در سفارش‌ها استفاده شده؛ به‌جای حذف، ناموجود شد.');
        } else {
            Model::exec('DELETE FROM wishlists WHERE product_id = ?', [(int) $id]);
            Model::exec('DELETE FROM cart_items WHERE product_id = ?', [(int) $id]);
            Model::exec('DELETE FROM product_comments WHERE product_id = ?', [(int) $id]);
            Model::delete('products', (int) $id);
            flash('success', 'محصول حذف شد.');
        }
        redirect(admin_url('products'));
    }

    /** عملیات گروهی */
    public function bulk($id = 0): void
    {
        $ids = array_map('intval', (array) post('ids', []));
        $act = (string) post('bulk_action');
        if (!$ids) {
            flash('error', 'موردی انتخاب نشده است.');
            redirect(admin_url('products'));
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        switch ($act) {
            case 'in_stock':
                Model::exec("UPDATE products SET in_stock = 1 WHERE id IN ($in)", $ids);
                flash('success', count($ids) . ' محصول موجود شد.');
                break;
            case 'out_stock':
                Model::exec("UPDATE products SET in_stock = 0 WHERE id IN ($in)", $ids);
                flash('success', count($ids) . ' محصول ناموجود شد.');
                break;
            case 'genuine':
                Model::exec("UPDATE products SET is_genuine = 1 WHERE id IN ($in)", $ids);
                flash('success', 'محصولات به‌عنوان اصل علامت‌گذاری شدند.');
                break;
            case 'price_pct':
                $pct = (float) post('price_pct', 0);
                Model::exec("UPDATE products SET price = ROUND(price * (1 + ? / 100)) WHERE id IN ($in)", [$pct, ...$ids]);
                flash('success', 'قیمت محصولات انتخابی ' . $pct . '٪ تغییر کرد.');
                break;
            case 'delete':
                Model::exec("DELETE FROM products WHERE id IN ($in) AND id NOT IN (SELECT product_id FROM order_items)", $ids);
                flash('success', 'محصولات انتخابی (بدون سابقه سفارش) حذف شدند.');
                break;
            default:
                flash('error', 'عملیات نامعتبر.');
        }
        redirect(admin_url('products'));
    }

    /** خروجی CSV */
    public function export($id = 0): void
    {
        $rows = Model::all('SELECT p.id, p.name, p.slug, c.name AS category, p.price, p.oem_code, p.car_model, p.brand, p.is_genuine, p.in_stock
                            FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['شناسه', 'نام', 'اسلاگ', 'دسته', 'قیمت', 'کد فنی', 'مدل خودرو', 'برند', 'اصل', 'موجود']);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out);
        exit;
    }
}
