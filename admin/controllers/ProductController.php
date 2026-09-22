<?php
namespace Admin\controllers;

use Admin\core\Inventory;
use Admin\core\Model;
use Admin\core\Uploader;

class ProductController extends BaseController
{
    protected string $section = 'products';

    protected array $permissions = [
        'create'       => 'products.edit',
        'edit'         => 'products.view',
        'save'         => 'products.edit',
        'delete'       => 'products.delete',
        'bulk'         => 'products.edit',
        'toggleStock'  => 'products.stock',
        'setStock'     => 'products.stock',
        'uploadImage'  => 'products.edit',
        'deleteImage'  => 'products.edit',
        'primaryImage' => 'products.edit',
        'export'       => 'products.view',
        'stock'        => 'products.stock',
    ];

    // ---------------------------------------------------------------- لیست

    public function index($id = 0): void
    {
        $q       = trim((string) param('q', ''));
        $cat     = param('category', '');
        $model   = param('car_model', '');
        $stock   = param('stock', '');
        $genuine = param('genuine', '');
        $sort    = param('sort', 'new');

        $where = ['1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(p.name LIKE ? OR p.oem_code LIKE ? OR p.brand LIKE ? OR p.slug LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%", "%$q%");
        }
        if ($cat !== '')   { $where[] = 'p.category_id = ?'; $params[] = (int) $cat; }
        if ($model !== '') {
            $where[] = 'EXISTS (SELECT 1 FROM product_vehicles pv JOIN car_models cm ON cm.id = pv.car_model_id
                                WHERE pv.product_id = p.id AND cm.slug = ?)';
            $params[] = $model;
        }
        if ($genuine !== '') { $where[] = 'p.is_genuine = ?'; $params[] = (int) $genuine; }

        if ($stock === 'in')       { $where[] = 'p.stock_qty > 0'; }
        elseif ($stock === 'out')  { $where[] = 'p.stock_qty <= 0'; }
        elseif ($stock === 'low')  { $where[] = 'p.track_stock = 1 AND p.stock_qty > 0 AND p.stock_qty <= p.low_stock_threshold'; }

        $w = implode(' AND ', $where);

        $orderBy = match ($sort) {
            'old'        => 'p.created_at ASC',
            'price_asc'  => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            'name'       => 'p.name ASC',
            'stock_asc'  => 'p.stock_qty ASC',
            'best'       => 'sold DESC',
            default      => 'p.created_at DESC',
        };

        $total = (int) Model::scalar("SELECT COUNT(*) FROM products p WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);

        $products = Model::all(
            "SELECT p.*, c.name AS category_name,
                    (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id = p.id) AS sold,
                    (SELECT pi.telegram_file_id FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS img_tg,
                    (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.is_primary DESC, pi.sort_order ASC LIMIT 1) AS img_path,
                    (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images_count,
                    (SELECT GROUP_CONCAT(cm.name SEPARATOR '، ') FROM product_vehicles pv
                     JOIN car_models cm ON cm.id = pv.car_model_id WHERE pv.product_id = p.id) AS vehicles
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE $w ORDER BY $orderBy LIMIT {$this->perPage} OFFSET {$pg['offset']}",
            $params
        );

        $categories = Model::all('SELECT id, name FROM categories ORDER BY name');
        $carModels  = Model::all('SELECT slug, name FROM car_models ORDER BY name');

        $counts = [
            'all' => Model::count('products'),
            'out' => Model::count('products', 'stock_qty <= 0'),
            'low' => Model::count('products', 'track_stock = 1 AND stock_qty > 0 AND stock_qty <= low_stock_threshold'),
        ];

        $this->view('products/index',
            compact('products', 'categories', 'carModels', 'pg', 'q', 'cat', 'model', 'stock', 'genuine', 'sort', 'counts'),
            'مدیریت محصولات', money($total) . ' محصول یافت شد');
    }

    // ---------------------------------------------------------------- فرم

    public function create($id = 0): void
    {
        $this->need('products.edit');
        $product = [
            'id' => 0, 'name' => '', 'slug' => '', 'category_id' => null, 'price' => 0, 'oem_code' => '',
            'car_model' => '', 'brand' => '', 'is_genuine' => 0, 'in_stock' => 1, 'description' => '',
            'telegram_photo_id' => '', 'stock_qty' => 0, 'low_stock_threshold' => 3, 'track_stock' => 1,
        ];
        $this->renderForm($product, 'افزودن محصول جدید');
    }

    public function edit($id = 0): void
    {
        $product = Model::find('products', (int) $id);
        if (!$product) {
            flash('error', 'محصول یافت نشد.');
            redirect(admin_url('products'));
        }
        $this->renderForm($product, 'ویرایش محصول: ' . $product['name']);
    }

    private function renderForm(array $product, string $title): void
    {
        $pid = (int) $product['id'];

        $categories = Model::all('SELECT id, name FROM categories ORDER BY name');
        $carModels  = Model::all('SELECT id, slug, name FROM car_models ORDER BY name');
        $images     = $pid ? Model::all('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC', [$pid]) : [];
        $attributes = $pid ? Model::all('SELECT * FROM product_attributes WHERE product_id = ? ORDER BY sort_order, id', [$pid]) : [];
        $vehicles   = $pid ? Model::all('SELECT pv.*, cm.name AS model_name FROM product_vehicles pv
                                         JOIN car_models cm ON cm.id = pv.car_model_id
                                         WHERE pv.product_id = ? ORDER BY cm.name', [$pid]) : [];
        $presets    = Model::all('SELECT * FROM attribute_presets ORDER BY sort_order, id');
        $movements  = $pid ? Model::all('SELECT * FROM stock_movements WHERE product_id = ? ORDER BY created_at DESC LIMIT 15', [$pid]) : [];

        $this->view('products/form',
            compact('product', 'categories', 'carModels', 'images', 'attributes', 'vehicles', 'presets', 'movements'),
            $title);
    }

    // ---------------------------------------------------------------- ذخیره

    public function save($id = 0): void
    {
        $pid = (int) post('id', $id);
        $name = trim((string) post('name'));

        if ($name === '') {
            flash('error', 'نام محصول الزامی است.');
            back(admin_url('products'));
        }

        $old = $pid ? Model::find('products', $pid) : null;
        if ($pid && !$old) {
            flash('error', 'محصول یافت نشد.');
            redirect(admin_url('products'));
        }

        $price = (float) preg_replace('/[^\d.]/', '', (string) post('price', 0));
        if ($old && (float) $old['price'] !== $price && !can('products.price')) {
            flash('error', 'شما اجازه تغییر قیمت را ندارید.');
            back();
        }

        $slug = trim((string) post('slug')) ?: make_slug($name);
        if (Model::one('SELECT id FROM products WHERE slug = ? AND id <> ?', [$slug, $pid])) {
            $slug .= '-' . random_int(100, 999);
        }

        $trackStock = post('track_stock') ? 1 : 0;
        $stockQty = max(0, (int) post('stock_qty', 0));

        $data = [
            'name'                => $name,
            'slug'                => $slug,
            'category_id'         => post('category_id') !== '' ? (int) post('category_id') : null,
            'price'               => $price,
            'oem_code'            => trim((string) post('oem_code')) ?: null,
            'brand'               => trim((string) post('brand')) ?: null,
            'is_genuine'          => post('is_genuine') ? 1 : 0,
            'description'         => (string) post('description'),
            'low_stock_threshold' => max(0, (int) post('low_stock_threshold', 3)),
            'track_stock'         => $trackStock,
        ];

        // موجودی: اگر ردیابی خاموش است، سوییچ دستی موجود/ناموجود
        if (!$trackStock) {
            $data['in_stock'] = post('in_stock') ? 1 : 0;
        }

        $db = Model::db();
        $db->beginTransaction();
        try {
            if ($pid) {
                Model::update('products', $pid, $data);
            } else {
                $pid = Model::insert('products', $data);
            }

            // --- موجودی عددی از طریق Inventory تا حرکت انبار ثبت شود ---
            if ($trackStock && can('products.stock')) {
                $currentQty = (int) ($old['stock_qty'] ?? 0);
                if ($stockQty !== $currentQty) {
                    $diff = $stockQty - $currentQty;
                    Inventory::adjust($pid, $diff, $old ? 'correction' : 'purchase', null,
                        $old ? 'اصلاح از فرم ویرایش محصول' : 'موجودی اولیه هنگام ایجاد محصول');
                } else {
                    Model::exec('UPDATE products SET in_stock = ? WHERE id = ?', [$stockQty > 0 ? 1 : 0, $pid]);
                }
            }

            // --- سازگاری با مدل‌های خودرو (چندبه‌چند) ---
            $this->syncVehicles($pid);

            // --- ویژگی‌های فنی ---
            $this->syncAttributes($pid);

            // --- همگام‌سازی فیلد قدیمی car_model و telegram_photo_id برای سازگاری با فرانت ---
            $this->syncLegacyFields($pid);

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            flash('error', 'خطا در ذخیره محصول: ' . $e->getMessage());
            back();
        }

        $new = Model::find('products', $pid);
        $this->audit($old ? 'product.update' : 'product.create', 'product', $pid,
            ($old ? 'ویرایش' : 'ایجاد') . ' محصول: ' . $name, $old, $new);

        if ($old && (float) $old['price'] !== $price) {
            $this->audit('product.price', 'product', $pid,
                'تغییر قیمت «' . $name . '» از ' . money($old['price']) . ' به ' . money($price) . ' تومان');
        }

        flash('success', $old ? 'محصول با موفقیت به‌روزرسانی شد.' : 'محصول جدید ایجاد شد.');
        redirect(admin_url('products/edit/' . $pid));
    }

    /** ثبت مدل‌های خودرو سازگار */
    private function syncVehicles(int $pid): void
    {
        $modelIds = array_map('intval', (array) post('vehicle_model_id', []));
        $yearFrom = (array) post('vehicle_year_from', []);
        $yearTo   = (array) post('vehicle_year_to', []);
        $trims    = (array) post('vehicle_trim', []);
        $notes    = (array) post('vehicle_note', []);

        Model::exec('DELETE FROM product_vehicles WHERE product_id = ?', [$pid]);

        $seen = [];
        foreach ($modelIds as $i => $mid) {
            if ($mid <= 0) continue;
            $yf = isset($yearFrom[$i]) && $yearFrom[$i] !== '' ? (int) $yearFrom[$i] : null;
            $yt = isset($yearTo[$i]) && $yearTo[$i] !== '' ? (int) $yearTo[$i] : null;
            $tr = isset($trims[$i]) ? trim((string) $trims[$i]) : '';
            $key = $mid . '|' . $yf . '|' . $yt . '|' . $tr;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            Model::insert('product_vehicles', [
                'product_id'   => $pid,
                'car_model_id' => $mid,
                'year_from'    => $yf,
                'year_to'      => $yt,
                'trim_name'    => $tr ?: null,
                'note'         => isset($notes[$i]) && trim((string) $notes[$i]) !== '' ? mb_substr(trim((string) $notes[$i]), 0, 255) : null,
            ]);
        }
    }

    /** ثبت ویژگی‌های فنی */
    private function syncAttributes(int $pid): void
    {
        $keys   = (array) post('attr_key', []);
        $values = (array) post('attr_value', []);

        Model::exec('DELETE FROM product_attributes WHERE product_id = ?', [$pid]);

        $order = 0;
        foreach ($keys as $i => $k) {
            $k = trim((string) $k);
            $v = trim((string) ($values[$i] ?? ''));
            if ($k === '' || $v === '') continue;
            Model::insert('product_attributes', [
                'product_id' => $pid,
                'attr_key'   => mb_substr($k, 0, 100),
                'attr_value' => mb_substr($v, 0, 255),
                'sort_order' => $order++,
            ]);
        }
    }

    /** نگه‌داشتن ستون‌های قدیمی همگام تا فرانت‌اند فعلی نشکند */
    private function syncLegacyFields(int $pid): void
    {
        $firstModel = Model::one('SELECT cm.slug FROM product_vehicles pv
                                  JOIN car_models cm ON cm.id = pv.car_model_id
                                  WHERE pv.product_id = ? ORDER BY pv.id LIMIT 1', [$pid]);
        $primary = Model::one('SELECT telegram_file_id, image_path FROM product_images
                               WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC LIMIT 1', [$pid]);

        Model::exec('UPDATE products SET car_model = ?, telegram_photo_id = ? WHERE id = ?', [
            $firstModel['slug'] ?? null,
            $primary['telegram_file_id'] ?? ($primary['image_path'] ?? null),
            $pid,
        ]);
    }

    // ---------------------------------------------------------------- تصاویر

    /** آپلود یک یا چند تصویر (AJAX یا فرم معمولی) */
    public function uploadImage($id = 0): void
    {
        $pid = (int) post('product_id', $id);
        if (!$pid || !Model::find('products', $pid)) {
            $this->respond(false, 'محصول یافت نشد.');
        }

        $files = $_FILES['images'] ?? null;
        if (!$files || !isset($files['name'])) {
            $this->respond(false, 'فایلی انتخاب نشده است.');
        }

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $count = count($names);
        $saved = 0;
        $errors = [];

        $maxOrder = (int) Model::scalar('SELECT COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?', [$pid]);
        $hasPrimary = (int) Model::scalar('SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_primary = 1', [$pid]) > 0;

        for ($i = 0; $i < $count; $i++) {
            $one = is_array($files['name'])
                ? ['name' => $files['name'][$i], 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                   'error' => $files['error'][$i], 'size' => $files['size'][$i]]
                : $files;

            if ((int) $one['error'] === UPLOAD_ERR_NO_FILE) continue;

            $res = Uploader::image($one, 'products');
            if (!$res['success']) {
                $errors[] = ($one['name'] ?? 'فایل') . ': ' . $res['message'];
                continue;
            }

            Model::insert('product_images', [
                'product_id'       => $pid,
                'telegram_file_id' => $res['telegram_file_id'] ?? null,
                'image_path'       => $res['path'] ?? null,
                'alt_text'         => null,
                'is_primary'       => (!$hasPrimary && $saved === 0) ? 1 : 0,
                'sort_order'       => ++$maxOrder,
            ]);
            $saved++;
        }

        $this->syncLegacyFields($pid);
        $this->audit('product.image', 'product', $pid, $saved . ' تصویر به محصول اضافه شد.');

        $msg = $saved > 0 ? "{$saved} تصویر با موفقیت آپلود شد." : 'هیچ تصویری آپلود نشد.';
        if ($errors) $msg .= ' خطاها: ' . implode(' | ', array_slice($errors, 0, 3));

        $this->respond($saved > 0, $msg, ['product_id' => $pid]);
    }

    public function deleteImage($id = 0): void
    {
        $imgId = (int) post('image_id', $id);
        $img = Model::find('product_images', $imgId);
        if (!$img) {
            $this->respond(false, 'تصویر یافت نشد.');
        }
        $pid = (int) $img['product_id'];

        Uploader::deleteFile($img['image_path'] ?? null);
        Model::delete('product_images', $imgId);

        // اگر تصویر شاخص حذف شد، اولین تصویر باقی‌مانده شاخص می‌شود
        if ((int) $img['is_primary'] === 1) {
            $next = Model::one('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id LIMIT 1', [$pid]);
            if ($next) {
                Model::exec('UPDATE product_images SET is_primary = 1 WHERE id = ?', [(int) $next['id']]);
            }
        }

        $this->syncLegacyFields($pid);
        $this->audit('product.image', 'product', $pid, 'حذف یک تصویر از محصول');
        $this->respond(true, 'تصویر حذف شد.', ['product_id' => $pid]);
    }

    public function primaryImage($id = 0): void
    {
        $imgId = (int) post('image_id', $id);
        $img = Model::find('product_images', $imgId);
        if (!$img) {
            $this->respond(false, 'تصویر یافت نشد.');
        }
        $pid = (int) $img['product_id'];

        Model::exec('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [$pid]);
        Model::exec('UPDATE product_images SET is_primary = 1 WHERE id = ?', [$imgId]);

        $this->syncLegacyFields($pid);
        $this->audit('product.image', 'product', $pid, 'تغییر تصویر شاخص محصول');
        $this->respond(true, 'تصویر شاخص تنظیم شد.', ['product_id' => $pid]);
    }

    private function respond(bool $ok, string $message, array $extra = []): void
    {
        if (\Admin\core\Auth::wantsJson()) {
            $this->json(array_merge(['success' => $ok, 'message' => $message], $extra), $ok ? 200 : 400);
        }
        flash($ok ? 'success' : 'error', $message);
        $pid = $extra['product_id'] ?? 0;
        $pid ? redirect(admin_url('products/edit/' . $pid)) : back(admin_url('products'));
    }

    // ---------------------------------------------------------------- انبار

    /** صفحه انبارداری */
    public function stock($id = 0): void
    {
        $this->need('products.stock');

        $filter = param('filter', 'low');
        $q = trim((string) param('q', ''));

        $where = ['p.track_stock = 1'];
        $params = [];
        if ($filter === 'low')      { $where[] = 'p.stock_qty > 0 AND p.stock_qty <= p.low_stock_threshold'; }
        elseif ($filter === 'out')  { $where[] = 'p.stock_qty <= 0'; }
        if ($q !== '') { $where[] = '(p.name LIKE ? OR p.oem_code LIKE ?)'; array_push($params, "%$q%", "%$q%"); }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM products p WHERE $w", $params);
        $pg = paginate($total, $this->page(), 30);

        $products = Model::all("SELECT p.id, p.name, p.oem_code, p.price, p.stock_qty, p.low_stock_threshold
                                FROM products p WHERE $w ORDER BY p.stock_qty ASC, p.name ASC
                                LIMIT 30 OFFSET {$pg['offset']}", $params);

        $movements = Model::all('SELECT sm.*, p.name AS product_name, u.full_name AS admin_name
                                 FROM stock_movements sm
                                 LEFT JOIN products p ON p.id = sm.product_id
                                 LEFT JOIN users u ON u.id = sm.admin_id
                                 ORDER BY sm.created_at DESC LIMIT 25');

        $stats = [
            'total_units' => (int) Model::scalar('SELECT COALESCE(SUM(stock_qty),0) FROM products WHERE track_stock = 1'),
            'value'       => (float) Model::scalar('SELECT COALESCE(SUM(stock_qty * price),0) FROM products WHERE track_stock = 1'),
            'out'         => Model::count('products', 'track_stock = 1 AND stock_qty <= 0'),
            'low'         => Model::count('products', 'track_stock = 1 AND stock_qty > 0 AND stock_qty <= low_stock_threshold'),
        ];

        $reasons = Inventory::REASONS;

        $this->view('products/stock', compact('products', 'movements', 'stats', 'pg', 'filter', 'q', 'reasons'),
            'انبارداری', 'مدیریت موجودی عددی محصولات');
    }

    public function setStock($id = 0): void
    {
        $pid = (int) post('product_id', $id);
        $mode = (string) post('mode', 'set');
        $value = (int) post('value', 0);
        $note = trim((string) post('note')) ?: null;
        $reason = (string) post('reason', 'manual');
        if (!array_key_exists($reason, Inventory::REASONS)) $reason = 'manual';

        $product = Model::find('products', $pid);
        if (!$product) {
            flash('error', 'محصول یافت نشد.');
            back(admin_url('products/stock'));
        }

        $res = $mode === 'adjust'
            ? Inventory::adjust($pid, $value, $reason, null, $note)
            : Inventory::setQuantity($pid, max(0, $value), $note);

        if (!empty($res['success'])) {
            $this->audit('product.stock', 'product', $pid,
                'موجودی «' . $product['name'] . '» به ' . ($res['qty_after'] ?? '?') . ' عدد تغییر کرد.'
                . ($note ? ' یادداشت: ' . $note : ''));
            flash('success', 'موجودی «' . $product['name'] . '» به ' . money($res['qty_after'] ?? 0) . ' عدد تنظیم شد.');
        } else {
            flash('error', $res['message'] ?? 'خطا در تغییر موجودی.');
        }
        back(admin_url('products/stock'));
    }

    public function toggleStock($id = 0): void
    {
        $pid = (int) post('product_id', $id);
        $p = Model::find('products', $pid);
        if (!$p) {
            flash('error', 'محصول یافت نشد.');
            back(admin_url('products'));
        }

        if ((int) $p['track_stock'] === 1) {
            // در حالت ردیابی عددی: صفر کردن یا بازگرداندن به ۱
            $target = (int) $p['stock_qty'] > 0 ? 0 : 1;
            Inventory::setQuantity($pid, $target, 'تغییر سریع وضعیت موجودی از لیست محصولات');
        } else {
            Model::exec('UPDATE products SET in_stock = 1 - in_stock WHERE id = ?', [$pid]);
        }

        $this->audit('product.stock', 'product', $pid, 'تغییر سریع وضعیت موجودی: ' . $p['name']);
        flash('success', 'وضعیت موجودی «' . $p['name'] . '» تغییر کرد.');
        back(admin_url('products'));
    }

    // ---------------------------------------------------------------- حذف و گروهی

    public function delete($id = 0): void
    {
        $pid = (int) post('product_id', $id);
        $p = Model::find('products', $pid);
        if (!$p) {
            flash('error', 'محصول یافت نشد.');
            redirect(admin_url('products'));
        }

        $used = Model::count('order_items', 'product_id = ?', [$pid]);
        if ($used > 0) {
            Model::exec('UPDATE products SET in_stock = 0, stock_qty = 0 WHERE id = ?', [$pid]);
            $this->audit('product.update', 'product', $pid, 'محصول دارای سابقه سفارش ناموجود شد (به‌جای حذف): ' . $p['name']);
            flash('info', 'این محصول در ' . $used . ' سفارش استفاده شده؛ به‌جای حذف، ناموجود شد.');
            redirect(admin_url('products'));
        }

        foreach (Model::all('SELECT image_path FROM product_images WHERE product_id = ?', [$pid]) as $img) {
            Uploader::deleteFile($img['image_path'] ?? null);
        }
        foreach (['wishlists', 'cart_items', 'product_comments', 'product_images', 'product_attributes', 'product_vehicles', 'stock_movements'] as $t) {
            Model::exec("DELETE FROM `$t` WHERE product_id = ?", [$pid]);
        }
        Model::delete('products', $pid);

        $this->audit('product.delete', 'product', $pid, 'حذف کامل محصول: ' . $p['name'], $p, null);
        flash('success', 'محصول «' . $p['name'] . '» حذف شد.');
        redirect(admin_url('products'));
    }

    public function bulk($id = 0): void
    {
        $ids = array_values(array_filter(array_map('intval', (array) post('ids', []))));
        $act = (string) post('bulk_action');

        if (!$ids) {
            flash('error', 'هیچ محصولی انتخاب نشده است.');
            back(admin_url('products'));
        }
        $in = implode(',', array_fill(0, count($ids), '?'));

        switch ($act) {
            case 'in_stock':
                foreach ($ids as $pid) Inventory::setQuantity($pid, max(1, (int) Model::scalar('SELECT stock_qty FROM products WHERE id = ?', [$pid])), 'عملیات گروهی');
                Model::exec("UPDATE products SET in_stock = 1 WHERE id IN ($in) AND track_stock = 0", $ids);
                flash('success', count($ids) . ' محصول موجود شد.');
                break;

            case 'out_stock':
                foreach ($ids as $pid) Inventory::setQuantity($pid, 0, 'عملیات گروهی: ناموجود کردن');
                Model::exec("UPDATE products SET in_stock = 0 WHERE id IN ($in) AND track_stock = 0", $ids);
                flash('success', count($ids) . ' محصول ناموجود شد.');
                break;

            case 'genuine':
                Model::exec("UPDATE products SET is_genuine = 1 WHERE id IN ($in)", $ids);
                flash('success', 'محصولات انتخابی به‌عنوان «اصل» علامت‌گذاری شدند.');
                break;

            case 'not_genuine':
                Model::exec("UPDATE products SET is_genuine = 0 WHERE id IN ($in)", $ids);
                flash('success', 'برچسب «اصل» از محصولات انتخابی برداشته شد.');
                break;

            case 'price_pct':
                if (!can('products.price')) {
                    flash('error', 'شما اجازه تغییر قیمت را ندارید.');
                    back(admin_url('products'));
                }
                $pct = (float) post('price_pct', 0);
                if ($pct === 0.0) {
                    flash('error', 'درصد تغییر قیمت را وارد کنید.');
                    back(admin_url('products'));
                }
                Model::exec("UPDATE products SET price = GREATEST(0, ROUND(price * (1 + ? / 100))) WHERE id IN ($in)", [$pct, ...$ids]);
                $this->audit('product.price', 'product', implode(',', $ids), 'تغییر گروهی قیمت ' . count($ids) . ' محصول به میزان ' . $pct . '٪');
                flash('success', 'قیمت ' . count($ids) . ' محصول ' . $pct . '٪ تغییر کرد.');
                break;

            case 'category':
                $catId = post('bulk_category') !== '' ? (int) post('bulk_category') : null;
                Model::exec("UPDATE products SET category_id = ? WHERE id IN ($in)", [$catId, ...$ids]);
                flash('success', 'دسته‌بندی محصولات انتخابی تغییر کرد.');
                break;

            case 'delete':
                if (!can('products.delete')) {
                    flash('error', 'شما اجازه حذف محصول را ندارید.');
                    back(admin_url('products'));
                }
                $deletable = Model::all("SELECT id FROM products WHERE id IN ($in)
                                         AND id NOT IN (SELECT DISTINCT product_id FROM order_items)", $ids);
                $delIds = array_column($deletable, 'id');
                if ($delIds) {
                    $din = implode(',', array_fill(0, count($delIds), '?'));
                    foreach (['wishlists', 'cart_items', 'product_comments', 'product_images', 'product_attributes', 'product_vehicles'] as $t) {
                        Model::exec("DELETE FROM `$t` WHERE product_id IN ($din)", $delIds);
                    }
                    Model::exec("DELETE FROM products WHERE id IN ($din)", $delIds);
                }
                $skipped = count($ids) - count($delIds);
                flash('success', count($delIds) . ' محصول حذف شد.' . ($skipped ? " {$skipped} محصول به دلیل سابقه سفارش حذف نشد." : ''));
                break;

            default:
                flash('error', 'عملیات انتخابی نامعتبر است.');
        }

        $this->audit('product.bulk', 'product', implode(',', array_slice($ids, 0, 50)),
            'عملیات گروهی «' . $act . '» روی ' . count($ids) . ' محصول');
        back(admin_url('products'));
    }

    // ---------------------------------------------------------------- خروجی

    public function export($id = 0): void
    {
        $rows = Model::all("SELECT p.id, p.name, p.slug, c.name AS category, p.price, p.oem_code, p.brand,
                                   p.is_genuine, p.stock_qty, p.low_stock_threshold,
                                   (SELECT GROUP_CONCAT(cm.name SEPARATOR ' | ') FROM product_vehicles pv
                                    JOIN car_models cm ON cm.id = pv.car_model_id WHERE pv.product_id = p.id) AS vehicles,
                                   (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.product_id = p.id) AS sold
                            FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id");

        $data = array_map(fn($r) => [
            $r['id'], $r['name'], $r['slug'], $r['category'] ?? '—', $r['price'], $r['oem_code'] ?? '',
            $r['brand'] ?? '', $r['is_genuine'] ? 'اصل' : 'متفرقه', $r['stock_qty'],
            $r['low_stock_threshold'], $r['vehicles'] ?? '', $r['sold'],
        ], $rows);

        $this->streamCsv('products-' . date('Y-m-d') . '.csv',
            ['شناسه', 'نام', 'اسلاگ', 'دسته', 'قیمت', 'کد فنی', 'برند', 'اصالت', 'موجودی', 'حد هشدار', 'خودروهای سازگار', 'فروش'],
            $data);
    }
}
