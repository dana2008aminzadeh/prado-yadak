<?php use Admin\core\Auth; ?>

<form method="POST" action="<?= $product['id'] ? admin_url('products/edit/' . $product['id']) : admin_url('products/create') ?>">
    <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">

    <div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
        <div class="card">
            <div class="card-head"><h3>اطلاعات محصول</h3></div>
            <div class="card-body">
                <div class="field">
                    <label class="fl">نام محصول *</label>
                    <input type="text" name="name" value="<?= e($product['name']) ?>" required>
                </div>
                <div class="grid g2">
                    <div class="field">
                        <label class="fl">اسلاگ (نشانی یکتا)</label>
                        <input type="text" name="slug" class="mono" value="<?= e($product['slug']) ?>" placeholder="خودکار ساخته می‌شود">
                    </div>
                    <div class="field">
                        <label class="fl">قیمت (تومان) *</label>
                        <input type="number" name="price" value="<?= (int) $product['price'] ?>" min="0" step="1000" required>
                    </div>
                </div>
                <div class="grid g3">
                    <div class="field">
                        <label class="fl">دسته‌بندی</label>
                        <select name="category_id">
                            <option value="">بدون دسته</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>" <?= (int) $product['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">مدل خودرو</label>
                        <select name="car_model">
                            <option value="">عمومی</option>
                            <?php foreach ($carModels as $m): ?>
                                <option value="<?= e($m['slug']) ?>" <?= $product['car_model'] === $m['slug'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">برند</label>
                        <input type="text" name="brand" value="<?= e($product['brand']) ?>" placeholder="Toyota / Denso ...">
                    </div>
                </div>
                <div class="field">
                    <label class="fl">کد فنی (OEM)</label>
                    <input type="text" name="oem_code" class="mono" value="<?= e($product['oem_code']) ?>">
                </div>
                <div class="field">
                    <label class="fl">توضیحات</label>
                    <textarea name="description" rows="8"><?= e($product['description']) ?></textarea>
                    <div class="hint">می‌توانید از تگ‌های ساده HTML استفاده کنید.</div>
                </div>
            </div>
        </div>

        <div>
            <div class="card mb">
                <div class="card-head"><h3>انتشار</h3></div>
                <div class="card-body">
                    <label class="flex items-center gap" style="margin-bottom:12px;cursor:pointer">
                        <input type="checkbox" name="in_stock" value="1" style="width:auto" <?= $product['in_stock'] ? 'checked' : '' ?>>
                        <span style="font-size:13px;font-weight:700">موجود در انبار</span>
                    </label>
                    <label class="flex items-center gap" style="margin-bottom:16px;cursor:pointer">
                        <input type="checkbox" name="is_genuine" value="1" style="width:auto" <?= $product['is_genuine'] ? 'checked' : '' ?>>
                        <span style="font-size:13px;font-weight:700">قطعه اصل (Genuine)</span>
                    </label>
                    <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">
                        <i data-lucide="save" style="width:15px"></i> ذخیره محصول
                    </button>
                    <?php if ($product['id']): ?>
                        <a class="btn" style="width:100%;justify-content:center;margin-top:8px" target="_blank" href="/product/<?= e($product['slug']) ?>">مشاهده در سایت</a>
                        <a class="btn btn-danger" style="width:100%;justify-content:center;margin-top:8px"
                           href="<?= admin_url('products/delete/' . $product['id']) ?>" onclick="return confirmDelete()">حذف محصول</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>تصویر محصول</h3></div>
                <div class="card-body">
                    <img id="preview" class="thumb" style="width:100%;height:170px;margin-bottom:12px"
                         src="<?= $product['telegram_photo_id'] ? '/image?id=' . urlencode($product['telegram_photo_id']) : '/assets/logo/logo.webp' ?>" alt="">
                    <div class="field">
                        <label class="fl">Telegram file_id</label>
                        <input type="text" name="telegram_photo_id" id="tgid" class="mono" value="<?= e($product['telegram_photo_id']) ?>">
                        <div class="hint">شناسه فایل تلگرام؛ تصویر از مسیر <span class="mono">/image?id=</span> سرو می‌شود.</div>
                    </div>
                    <button type="button" class="btn btn-sm" onclick="document.getElementById('preview').src='/image?id='+encodeURIComponent(document.getElementById('tgid').value)">پیش‌نمایش</button>
                </div>
            </div>
        </div>
    </div>
</form>
