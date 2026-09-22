<?php
use Admin\core\Auth;
use Admin\core\Uploader;
?>

<div class="grid g3 mb">
    <a class="stat" href="<?= admin_url('products') ?>">
        <span class="lbl">کل محصولات</span><div class="val"><?= money($counts['all']) ?></div>
    </a>
    <a class="stat warn" href="<?= admin_url('products', ['stock' => 'low']) ?>">
        <div class="ic-box"><i data-lucide="alert-triangle" style="width:17px"></i></div>
        <span class="lbl">رو به اتمام</span><div class="val"><?= money($counts['low']) ?></div>
    </a>
    <a class="stat danger" href="<?= admin_url('products', ['stock' => 'out']) ?>">
        <div class="ic-box"><i data-lucide="package-x" style="width:17px"></i></div>
        <span class="lbl">ناموجود</span><div class="val"><?= money($counts['out']) ?></div>
    </a>
</div>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('products') ?>" class="filters">
            <div class="f" style="flex:2">
                <label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="نام، کد فنی، برند...">
            </div>
            <div class="f">
                <label class="fl">دسته‌بندی</label>
                <select name="category">
                    <option value="">همه</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (string) $cat === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f">
                <label class="fl">خودرو سازگار</label>
                <select name="car_model">
                    <option value="">همه</option>
                    <?php foreach ($carModels as $m): ?>
                        <option value="<?= e($m['slug']) ?>" <?= $model === $m['slug'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f">
                <label class="fl">موجودی</label>
                <select name="stock">
                    <option value="">همه</option>
                    <option value="in"  <?= $stock === 'in' ? 'selected' : '' ?>>موجود</option>
                    <option value="low" <?= $stock === 'low' ? 'selected' : '' ?>>رو به اتمام</option>
                    <option value="out" <?= $stock === 'out' ? 'selected' : '' ?>>ناموجود</option>
                </select>
            </div>
            <div class="f">
                <label class="fl">اصالت</label>
                <select name="genuine">
                    <option value="">همه</option>
                    <option value="1" <?= $genuine === '1' ? 'selected' : '' ?>>اصل</option>
                    <option value="0" <?= $genuine === '0' ? 'selected' : '' ?>>متفرقه</option>
                </select>
            </div>
            <div class="f">
                <label class="fl">مرتب‌سازی</label>
                <select name="sort">
                    <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>جدیدترین</option>
                    <option value="best" <?= $sort === 'best' ? 'selected' : '' ?>>پرفروش‌ترین</option>
                    <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>کم‌موجودی‌ترین</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>گران‌ترین</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>ارزان‌ترین</option>
                    <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>نام (الفبا)</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i data-lucide="search" style="width:14px"></i> فیلتر</button>
            <a class="btn" href="<?= admin_url('products') ?>">حذف فیلتر</a>
        </form>
    </div>
</div>

<form method="POST" action="<?= admin_url('products/bulk') ?>" onsubmit="return confirmBulk()">
    <?= Auth::csrfField() ?>
    <div class="card">
        <div class="card-head">
            <h3>لیست محصولات (<?= money($pg['total']) ?>)</h3>
            <div class="flex gap wrap">
                <?php if (can('products.edit')): ?>
                    <select name="bulk_action" id="bulkAction" style="width:auto" onchange="toggleBulkExtras()">
                        <option value="">عملیات گروهی…</option>
                        <option value="in_stock">موجود کردن</option>
                        <option value="out_stock">ناموجود کردن</option>
                        <option value="genuine">علامت‌گذاری اصل</option>
                        <option value="not_genuine">حذف برچسب اصل</option>
                        <?php if (can('products.price')): ?><option value="price_pct">تغییر درصدی قیمت</option><?php endif; ?>
                        <option value="category">تغییر دسته‌بندی</option>
                        <?php if (can('products.delete')): ?><option value="delete">حذف</option><?php endif; ?>
                    </select>
                    <input type="number" name="price_pct" id="pctInput" step="0.1" placeholder="٪"
                           style="width:80px;display:none" title="درصد تغییر قیمت (مثلاً 10 یا 10-)">
                    <select name="bulk_category" id="catInput" style="width:auto;display:none">
                        <option value="">بدون دسته</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn" type="submit">اجرا</button>
                <?php endif; ?>
                <?php if (can('products.stock')): ?>
                    <a class="btn" href="<?= admin_url('products/stock') ?>"><i data-lucide="boxes" style="width:14px"></i> انبارداری</a>
                <?php endif; ?>
                <a class="btn" href="<?= admin_url('products/export') ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
                <?php if (can('products.edit')): ?>
                    <a class="btn btn-primary" href="<?= admin_url('products/create') ?>"><i data-lucide="plus" style="width:14px"></i> محصول جدید</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:32px"><input type="checkbox" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)"></th>
                        <th>تصویر</th>
                        <th>نام محصول</th>
                        <th>دسته</th>
                        <th>خودروهای سازگار</th>
                        <th>قیمت</th>
                        <th>موجودی</th>
                        <th>فروش</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="9" class="empty">محصولی با این فیلترها پیدا نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p):
                        $img = Uploader::url($p['img_tg'] ?? null, $p['img_path'] ?? null);
                        $qty = (int) $p['stock_qty'];
                        $tracks = (int) $p['track_stock'] === 1;
                        $low = $tracks && $qty > 0 && $qty <= (int) $p['low_stock_threshold'];
                        ?>
                        <tr>
                            <td><input class="rowchk" type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>"></td>
                            <td style="position:relative">
                                <img class="thumb" loading="lazy" src="<?= e($img) ?>" alt="">
                                <?php if ((int) $p['images_count'] > 1): ?>
                                    <span class="badge b-gray" style="position:absolute;bottom:6px;left:6px;font-size:9px;padding:1px 5px">
                                        <?= (int) $p['images_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:700;max-width:250px"><?= e($p['name']) ?></div>
                                <div class="hint mono">
                                    <?= e($p['oem_code'] ?: '—') ?>
                                    <?= $p['is_genuine'] ? ' • <span style="color:var(--green)">اصل</span>' : '' ?>
                                </div>
                            </td>
                            <td class="hint"><?= e($p['category_name'] ?? '—') ?></td>
                            <td class="hint" style="max-width:160px;font-size:11px"><?= e(excerpt($p['vehicles'] ?? '—', 42)) ?></td>
                            <td style="font-weight:700;white-space:nowrap"><?= money($p['price']) ?></td>
                            <td>
                                <?php if (!$tracks): ?>
                                    <span class="badge <?= $p['in_stock'] ? 'b-green' : 'b-red' ?>"><?= $p['in_stock'] ? 'موجود' : 'ناموجود' ?></span>
                                    <div class="hint">دستی</div>
                                <?php elseif ($qty <= 0): ?>
                                    <span class="badge b-red">ناموجود</span>
                                <?php elseif ($low): ?>
                                    <span class="badge b-amber"><?= $qty ?> عدد</span>
                                    <div class="hint">رو به اتمام</div>
                                <?php else: ?>
                                    <span class="badge b-green"><?= $qty ?> عدد</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int) $p['sold'] ?></td>
                            <td class="text-left">
                                <div class="flex gap" style="justify-content:flex-end">
                                    <?php if (can('products.stock')): ?>
                                        <?= action_button(admin_url('products/toggleStock'), '', [
                                            'class' => 'btn btn-sm', 'icon' => 'repeat', 'title' => 'تغییر سریع موجودی',
                                            'fields' => ['product_id' => $p['id']],
                                        ]) ?>
                                    <?php endif; ?>
                                    <a class="btn btn-sm" href="<?= admin_url('products/edit/' . $p['id']) ?>">ویرایش</a>
                                    <?php if (can('products.delete')): ?>
                                        <?= action_button(admin_url('products/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger',
                                            'confirm' => 'حذف «' . $p['name'] . '»؟',
                                            'fields' => ['product_id' => $p['id']],
                                        ]) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
    </div>
</form>

<script>
function toggleBulkExtras() {
    const v = document.getElementById('bulkAction').value;
    document.getElementById('pctInput').style.display = v === 'price_pct' ? 'block' : 'none';
    document.getElementById('catInput').style.display = v === 'category' ? 'block' : 'none';
}
function confirmBulk() {
    const action = document.getElementById('bulkAction');
    if (!action || !action.value) { alert('یک عملیات گروهی انتخاب کنید.'); return false; }
    const n = document.querySelectorAll('.rowchk:checked').length;
    if (!n) { alert('هیچ محصولی انتخاب نشده است.'); return false; }
    return confirm(`عملیات انتخابی روی ${n} محصول اجرا شود؟`);
}
</script>
