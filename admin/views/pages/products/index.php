<?php use Admin\core\Auth; ?>

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
                <label class="fl">مدل خودرو</label>
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
                    <option value="1" <?= $stock === '1' ? 'selected' : '' ?>>موجود</option>
                    <option value="0" <?= $stock === '0' ? 'selected' : '' ?>>ناموجود</option>
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
                    <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>قدیمی‌ترین</option>
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

<form method="POST" action="<?= admin_url('products/bulk') ?>">
    <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
    <div class="card">
        <div class="card-head">
            <h3>لیست محصولات (<?= money($pg['total']) ?>)</h3>
            <div class="flex gap" style="flex-wrap:wrap">
                <select name="bulk_action" style="width:auto">
                    <option value="">عملیات گروهی…</option>
                    <option value="in_stock">موجود کردن</option>
                    <option value="out_stock">ناموجود کردن</option>
                    <option value="genuine">علامت‌گذاری اصل</option>
                    <option value="price_pct">تغییر درصدی قیمت</option>
                    <option value="delete">حذف</option>
                </select>
                <input type="number" name="price_pct" step="0.1" placeholder="٪" style="width:80px" title="درصد تغییر قیمت (مثلاً 10 یا 10-)">
                <button class="btn" type="submit" onclick="return confirm('عملیات گروهی روی موارد انتخابی اجرا شود؟')">اجرا</button>
                <a class="btn" href="<?= admin_url('products/export') ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
                <a class="btn btn-primary" href="<?= admin_url('products/create') ?>"><i data-lucide="plus" style="width:14px"></i> محصول جدید</a>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:34px"><input type="checkbox" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)"></th>
                        <th>تصویر</th>
                        <th>نام محصول</th>
                        <th>دسته</th>
                        <th>قیمت (تومان)</th>
                        <th>کد فنی</th>
                        <th>فروش</th>
                        <th>وضعیت</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="9" class="empty">محصولی با این فیلترها پیدا نشد.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><input class="rowchk" type="checkbox" name="ids[]" value="<?= (int) $p['id'] ?>"></td>
                            <td><img class="thumb" loading="lazy" src="<?= $p['telegram_photo_id'] ? '/image?id=' . urlencode($p['telegram_photo_id']) : '/assets/logo/logo.webp' ?>" alt=""></td>
                            <td>
                                <div style="font-weight:700;max-width:280px"><?= e($p['name']) ?></div>
                                <div class="hint mono"><?= e($p['brand'] ?: '—') ?> <?= $p['is_genuine'] ? '• اصل' : '' ?></div>
                            </td>
                            <td class="hint"><?= e($p['category_name'] ?? '—') ?></td>
                            <td style="font-weight:700"><?= money($p['price']) ?></td>
                            <td class="mono hint"><?= e($p['oem_code'] ?: '—') ?></td>
                            <td><?= (int) $p['sold'] ?></td>
                            <td>
                                <?php if ($p['in_stock']): ?>
                                    <span class="badge b-green">موجود</span>
                                <?php else: ?>
                                    <span class="badge b-red">ناموجود</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-left">
                                <div class="flex gap" style="justify-content:flex-end">
                                    <a class="btn btn-sm" href="<?= admin_url('products/toggleStock/' . $p['id']) ?>" title="تغییر موجودی"><i data-lucide="repeat" style="width:13px"></i></a>
                                    <a class="btn btn-sm" href="<?= admin_url('products/edit/' . $p['id']) ?>">ویرایش</a>
                                    <a class="btn btn-sm btn-danger" href="<?= admin_url('products/delete/' . $p['id']) ?>" onclick="return confirmDelete('حذف «<?= e($p['name']) ?>»؟')">حذف</a>
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
