<?php
use Admin\core\Auth;
use Admin\core\Uploader;

$pid = (int) $product['id'];
$canEdit = can('products.edit');
$canPrice = can('products.price');
$canStock = can('products.stock');
?>

<form method="POST" action="<?= admin_url('products/save') ?>">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $pid ?>">

    <div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
        <div>
            <!-- دستیار هوش مصنوعی: پرامپت‌ها عمداً از داده‌های همین فرم ساخته می‌شوند -->
            <div class="card mb ai-product-tools">
                <div class="card-head"><h3><i data-lucide="sparkles" style="width:16px"></i> دستیار هوش مصنوعی محصول</h3></div>
                <div class="card-body">
                    <p class="hint">پرامپت‌ها ثابت نیستند؛ اطلاعات فعلی، فیلدهای قابل تکمیل و قالب JSON همین محصول داخل آن قرار می‌گیرد.</p>
                    <div class="flex gap wrap mt">
                        <button type="button" class="btn btn-sm" onclick="buildProductPrompt()">ساخت پرامپت اطلاعات محصول</button>
                        <button type="button" class="btn btn-sm" onclick="buildImagePrompt()">ساخت پرامپت عکس محصول</button>
                    </div>
                    <textarea id="ai-prompt-output" rows="8" class="mono mt" readonly placeholder="پرامپت تولیدشده اینجا نمایش داده می‌شود"></textarea>
                    <div class="flex gap wrap mt">
                        <button type="button" class="btn btn-sm" onclick="copyAiPrompt()">کپی پرامپت</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="applyAiJson()">اعمال خروجی JSON در فرم</button>
                    </div>
                    <textarea id="ai-json-input" rows="6" class="mono mt" placeholder="خروجی JSON هوش مصنوعی را اینجا پیست کنید"></textarea>
                    <div class="hint mt">فقط JSON معتبر وارد کنید؛ اسلاگ، متن جایگزین و فیلدهای قابل تولید خودکار تکمیل می‌شوند و موارد نامطمئن خالی می‌مانند.</div>
                </div>
            </div>

            <!-- اطلاعات پایه -->
            <div class="card mb">
                <div class="card-head"><h3>اطلاعات محصول</h3></div>
                <div class="card-body">
                    <div class="field">
                        <label class="fl">نام محصول *</label>
                        <input type="text" name="name" value="<?= e($product['name']) ?>" required <?= $canEdit ? '' : 'disabled' ?>>
                    </div>
                    <div class="grid g2">
                        <div class="field">
                            <label class="fl">اسلاگ (نشانی یکتا)</label>
                            <input type="text" name="slug" class="mono" value="<?= e($product['slug']) ?>" placeholder="خودکار ساخته می‌شود">
                        </div>
                        <div class="field">
                            <label class="fl">قیمت (تومان) *</label>
                            <input type="number" name="price" value="<?= (int) $product['price'] ?>" min="0" step="1000"
                                   required <?= $canPrice ? '' : 'readonly' ?>>
                            <?php if (!$canPrice): ?><div class="hint">شما اجازه تغییر قیمت را ندارید.</div><?php endif; ?>
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
                            <label class="fl">برند</label>
                            <input type="text" name="brand" value="<?= e($product['brand']) ?>" placeholder="Toyota / Denso ...">
                        </div>
                        <div class="field">
                            <label class="fl">کد فنی (OEM)</label>
                            <input type="text" name="oem_code" class="mono" value="<?= e($product['oem_code']) ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label class="fl">توضیحات</label>
                        <textarea name="description" rows="7"><?= e($product['description']) ?></textarea>
                        <div class="hint">می‌توانید از تگ‌های ساده HTML استفاده کنید.</div>
                    </div>
                </div>
            </div>

            <!-- سازگاری با خودروها -->
            <div class="card mb">
                <div class="card-head">
                    <h3>خودروهای سازگار</h3>
                    <button type="button" class="btn btn-sm" onclick="addVehicleRow()">
                        <i data-lucide="plus" style="width:13px"></i> افزودن خودرو
                    </button>
                </div>
                <div class="card-body">
                    <div class="hint mb">
                        یک قطعه می‌تواند با چند خودرو و چند بازه سال سازگار باشد. برای مثال «لنت جلو» هم روی
                        پرادو ۲۰۰۸ و هم لندکروزر ۲۰۰۶ نصب می‌شود.
                    </div>
                    <div id="vehicles-box">
                        <?php foreach ($vehicles as $v): ?>
                            <div class="row-repeat" style="grid-template-columns:2fr 1fr 1fr 1.4fr auto">
                                <select name="vehicle_model_id[]">
                                    <?php foreach ($carModels as $m): ?>
                                        <option value="<?= (int) $m['id'] ?>" <?= (int) $v['car_model_id'] === (int) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="vehicle_year_from[]" value="<?= e($v['year_from']) ?>" placeholder="از سال" min="1950" max="2100">
                                <input type="number" name="vehicle_year_to[]" value="<?= e($v['year_to']) ?>" placeholder="تا سال" min="1950" max="2100">
                                <input type="text" name="vehicle_trim[]" value="<?= e($v['trim_name']) ?>" placeholder="تیپ/موتور مثلا 1GR-FE">
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!$vehicles): ?>
                        <div class="empty" id="no-vehicles" style="padding:22px">هنوز خودرویی ثبت نشده است.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ویژگی‌های فنی -->
            <div class="card mb">
                <div class="card-head">
                    <h3>مشخصات فنی</h3>
                    <button type="button" class="btn btn-sm" onclick="addAttrRow()">
                        <i data-lucide="plus" style="width:13px"></i> افزودن مشخصه
                    </button>
                </div>
                <div class="card-body">
                    <div class="hint mb">مانند: کشور سازنده، وزن، ابعاد، سمت نصب (چپ/راست، جلو/عقب)، جنس، گارانتی.</div>
                    <div id="attrs-box">
                        <?php foreach ($attributes as $a): ?>
                            <div class="row-repeat" style="grid-template-columns:1fr 1.6fr auto">
                                <input type="text" name="attr_key[]" value="<?= e($a['attr_key']) ?>" list="attr-keys" placeholder="عنوان">
                                <input type="text" name="attr_value[]" value="<?= e($a['attr_value']) ?>" placeholder="مقدار">
                                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!$attributes): ?>
                        <div class="flex gap wrap mt">
                            <?php foreach (array_slice($presets, 0, 6) as $p): ?>
                                <button type="button" class="btn btn-sm" onclick="addAttrRow('<?= e($p['attr_key']) ?>')">
                                    + <?= e($p['attr_key']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <datalist id="attr-keys">
                        <?php foreach ($presets as $p): ?><option value="<?= e($p['attr_key']) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
            </div>

            <!-- ================= سئو و نمایش در گوگل ================= -->
            <?php
            $seoEntity = $product;
            $seoType = 'product';
            $seoUrlBase = '/product/';
            require ADMIN_PATH . '/views/partials/seo-box.php';
            ?>
        </div>

        <div>
            <!-- انتشار و انبار -->
            <div class="card mb">
                <div class="card-head"><h3>انتشار و انبار</h3></div>
                <div class="card-body">
                    <div class="field">
                        <label class="fl">موجودی انبار (عدد)</label>
                        <input type="number" name="stock_qty" value="<?= (int) $product['stock_qty'] ?>" min="0"
                               <?= $canStock ? '' : 'readonly' ?>>
                        <div class="hint">با ثبت سفارش، این عدد به‌صورت خودکار کسر می‌شود.</div>
                    </div>
                    <div class="field">
                        <label class="fl">حد هشدار کمبود موجودی</label>
                        <input type="number" name="low_stock_threshold" value="<?= (int) $product['low_stock_threshold'] ?>" min="0">
                    </div>
                    <label class="chk">
                        <input type="checkbox" name="track_stock" value="1" <?= (int) $product['track_stock'] === 1 ? 'checked' : '' ?>>
                        <span>کنترل خودکار موجودی عددی</span>
                    </label>
                    <label class="chk" id="manual-stock" style="<?= (int) $product['track_stock'] === 1 ? 'display:none' : '' ?>">
                        <input type="checkbox" name="in_stock" value="1" <?= (int) $product['in_stock'] === 1 ? 'checked' : '' ?>>
                        <span>موجود است (حالت دستی)</span>
                    </label>
                    <label class="chk">
                        <input type="checkbox" name="is_genuine" value="1" <?= (int) $product['is_genuine'] === 1 ? 'checked' : '' ?>>
                        <span>قطعه اصل (Genuine)</span>
                    </label>

                    <div class="field mt">
                        <label class="fl">سیاست چرخه‌عمر محصول</label>
                        <select name="lifecycle_status">
                            <option value="active" <?= ($product['lifecycle_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>فعال (ناموجودی موقت مجاز)</option>
                            <option value="out_of_stock" <?= ($product['lifecycle_status'] ?? '') === 'out_of_stock' ? 'selected' : '' ?>>ناموجود تا اطلاع بعدی</option>
                            <option value="discontinued" <?= ($product['lifecycle_status'] ?? '') === 'discontinued' ? 'selected' : '' ?>>توقف عرضه</option>
                        </select>
                        <div class="hint">ناموجودی موقت با OutOfStock در صفحه و Sitemap می‌ماند؛ توقف عرضه از Sitemap حذف می‌شود.</div>
                    </div>

                    <div class="field">
                        <label class="fl">محصول جایگزین (برای Redirect 301)</label>
                        <select name="replacement_product_id">
                            <option value="">بدون جایگزین</option>
                            <?php foreach ($replacementProducts as $rp): ?>
                                <option value="<?= (int) $rp['id'] ?>" <?= (int) ($product['replacement_product_id'] ?? 0) === (int) $rp['id'] ? 'selected' : '' ?>>
                                    <?= e($rp['name']) ?><?= !empty($rp['oem_code']) ? ' — ' . e($rp['oem_code']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">برای محصول متوقف/حذف‌شده، URL قدیمی دائماً به این محصول منتقل می‌شود.</div>
                    </div>

                    <div class="field">
                        <label class="fl">سیاست Sitemap</label>
                        <select name="sitemap_policy">
                            <option value="auto" <?= ($product['sitemap_policy'] ?? 'auto') === 'auto' ? 'selected' : '' ?>>خودکار (پیشنهادی)</option>
                            <option value="include" <?= ($product['sitemap_policy'] ?? '') === 'include' ? 'selected' : '' ?>>نگه‌داشتن</option>
                            <option value="exclude" <?= ($product['sitemap_policy'] ?? '') === 'exclude' ? 'selected' : '' ?>>حذف از Sitemap</option>
                        </select>
                    </div>

                    <div class="field mt">
                        <label class="fl">وضعیت انتشار</label>
                        <select name="publication_status">
                            <option value="draft" <?= ($product['publication_status'] ?? 'draft') !== 'published' ? 'selected' : '' ?>>پیش‌نویس (ذخیره بدون انتشار)</option>
                            <option value="published" <?= ($product['publication_status'] ?? '') === 'published' ? 'selected' : '' ?>>انتشار در سایت</option>
                        </select>
                        <div class="hint">محصول تازه همیشه ابتدا پیش‌نویس ذخیره می‌شود؛ بعد از افزودن تصاویر، انتشار را انتخاب کنید.</div>
                    </div>

                    <button class="btn btn-primary btn-block mt" type="submit" <?= $canEdit ? '' : 'disabled' ?>>
                        <i data-lucide="save" style="width:15px"></i> ذخیره محصول
                    </button>

                    <?php if ($pid): ?>
                        <a class="btn btn-block mt" target="_blank" rel="noopener" href="/product/<?= e($product['slug']) ?>">
                            مشاهده در سایت
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($pid && can('products.delete')): ?>
                <div class="card mb">
                    <div class="card-body">
                        <button class="btn btn-danger btn-block" type="submit" form="deleteProductForm">
                            <i data-lucide="trash-2" style="width:14px"></i> حذف محصول
                        </button>
                        <div class="hint mt">اگر محصول سابقه سفارش داشته باشد، حذف نمی‌شود. اگر بالاتر جایگزین ذخیره کرده باشید، محصول متوقف و Redirect 301 ثبت می‌شود؛ در غیر این صورت ناموجود باقی می‌ماند.</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($pid && $movements): ?>
                <div class="card">
                    <div class="card-head"><h3>آخرین حرکات انبار</h3></div>
                    <div class="card-body" style="max-height:260px;overflow-y:auto">
                        <?php foreach ($movements as $m): ?>
                            <div style="padding:6px 0;border-bottom:1px solid var(--line);font-size:11.5px">
                                <span style="font-weight:800;color:<?= (int) $m['change_qty'] > 0 ? 'var(--green)' : 'var(--red)' ?>">
                                    <?= (int) $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?>
                                </span>
                                <span class="hint">→ <?= (int) $m['qty_after'] ?> عدد</span>
                                <div class="hint"><?= e(\Admin\core\Inventory::REASONS[$m['reason']] ?? $m['reason']) ?>
                                    — <?= e(shamsiTime($m['created_at'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if ($pid && can('products.delete')): ?>
    <form id="deleteProductForm" method="POST" action="<?= admin_url('products/delete') ?>"
          onsubmit="return confirmDelete('حذف محصول «<?= e($product['name']) ?>»؟')" style="display:none">
        <?= Auth::csrfField() ?>
        <input type="hidden" name="product_id" value="<?= $pid ?>">
        <input type="hidden" name="replacement_product_id" value="<?= (int) ($product['replacement_product_id'] ?? 0) ?>">
    </form>
<?php endif; ?>

<!-- گالری تصاویر (فرم جداگانه برای آپلود) -->
<?php if ($pid): ?>
    <div class="card mt">
        <div class="card-head">
            <h3>گالری تصاویر (<?= count($images) ?>)</h3>
            <label class="chk" title="در حالت خودکار، نام محصول متن جایگزین تصویر می‌شود"><input type="checkbox" id="auto-alt-mode" checked> <span>متن جایگزین خودکار (نام محصول)</span></label>
            <span class="hint">اولین تصویر یا تصویر ستاره‌دار، شاخص محصول است.</span>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= admin_url('products/uploadImage') ?>" enctype="multipart/form-data" id="imgForm">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <div class="dropzone" id="dropzone" onclick="document.getElementById('imgInput').click()">
                    <i data-lucide="image-plus" style="width:30px;height:30px;color:var(--brand)"></i>
                    <div style="font-weight:800;font-size:13px;margin-top:8px">تصاویر را اینجا رها کنید یا کلیک کنید</div>
                    <div class="hint">JPG، PNG، WebP یا GIF — حداکثر ۸ مگابایت برای هر فایل — انتخاب چندتایی مجاز است</div>
                    <input type="file" name="images[]" id="imgInput" accept="image/*" multiple style="display:none"
                           onchange="document.getElementById('imgForm').submit()">
                </div>
            </form>

            <?php if ($images): ?>
                <!-- ویرایش متن جایگزین (alt) و نام فایل سئوی هر تصویر -->
                <form method="POST" action="<?= admin_url('products/saveImageMeta') ?>" id="imgMetaForm">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= $pid ?>">
                <div class="img-grid mt">
                    <?php foreach ($images as $imgIdx => $img):
                        $url = Uploader::url($img['telegram_file_id'] ?? null, $img['image_path'] ?? null);
                        $imgId = (int) $img['id'];
                        $suggestAlt = \Core\Seo::suggestAlt((string) $product['name'], null, $product['oem_code'] ?? null, (int) $imgIdx);
                        $suggestName = \Core\Seo::imageSlug((string) $product['name'], $product['oem_code'] ?? null, null, (int) $imgIdx);
                        ?>
                        <div class="img-cell">
                            <img src="<?= e($url) ?>" alt="<?= e($img['alt_text'] ?? '') ?>" loading="lazy">
                            <?php if ((int) $img['is_primary'] === 1): ?>
                                <span class="star">شاخص</span>
                            <?php endif; ?>
                            <div style="padding:8px">
                                <input type="text" name="image_alt[<?= $imgId ?>]"
                                       value="<?= e($img['alt_text'] ?? '') ?>"
                                       placeholder="<?= e($suggestAlt) ?>"
                                       style="font-size:11px;padding:6px 8px" title="متن جایگزین تصویر (alt)">
                                <input type="text" name="image_seo_name[<?= $imgId ?>]" class="mono"
                                       value="<?= e($img['seo_filename'] ?? '') ?>"
                                       placeholder="<?= e($suggestName) ?>"
                                       style="font-size:10.5px;padding:6px 8px;margin-top:5px"
                                       title="نام فایل سئوشده (در آدرس تصویر دیده می‌شود)">
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="flex gap wrap mt">
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i data-lucide="save" style="width:13px"></i> ذخیره متن جایگزین تصاویر
                    </button>
                    <span class="hint">خالی بگذارید تا مقدار پیشنهادی (خاکستری) به‌صورت خودکار استفاده شود.</span>
                </div>
                </form>

                <!-- عملیات حذف/شاخص کردن تصاویر (فرم‌های جدا تا تو در تو نشوند) -->
                <div class="flex gap wrap mt">
                    <?php foreach ($images as $img): ?>
                        <div class="flex gap" style="align-items:center;border:1px solid var(--line);border-radius:10px;padding:4px 8px">
                            <span class="hint mono">#<?= (int) $img['id'] ?></span>
                            <?php if ((int) $img['is_primary'] !== 1): ?>
                                <?= action_button(admin_url('products/primaryImage'), 'شاخص', [
                                    'class' => 'btn btn-sm', 'fields' => ['image_id' => (int) $img['id']],
                                ]) ?>
                            <?php else: ?>
                                <span class="badge b-green">شاخص</span>
                            <?php endif; ?>
                            <?= action_button(admin_url('products/deleteImage'), 'حذف', [
                                'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این تصویر؟',
                                'fields' => ['image_id' => (int) $img['id']],
                            ]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty">هنوز تصویری برای این محصول آپلود نشده است.</div>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card mt">
        <div class="card-body">
            <div class="empty">ابتدا محصول را ذخیره کنید تا بتوانید تصاویر آن را آپلود کنید.</div>
        </div>
    </div>
<?php endif; ?>

<script>
const CAR_MODELS = <?= json_encode(array_map(fn($m) => ['id' => (int) $m['id'], 'name' => $m['name']], $carModels), JSON_UNESCAPED_UNICODE) ?>;

function addVehicleRow() {
    document.getElementById('no-vehicles')?.remove();
    const box = document.getElementById('vehicles-box');
    const div = document.createElement('div');
    div.className = 'row-repeat';
    div.style.gridTemplateColumns = '2fr 1fr 1fr 1.4fr auto';
    div.innerHTML = `
        <select name="vehicle_model_id[]">
            ${CAR_MODELS.map(m => `<option value="${m.id}">${m.name}</option>`).join('')}
        </select>
        <input type="number" name="vehicle_year_from[]" placeholder="از سال" min="1950" max="2100">
        <input type="number" name="vehicle_year_to[]" placeholder="تا سال" min="1950" max="2100">
        <input type="text" name="vehicle_trim[]" placeholder="تیپ/موتور مثلا 1GR-FE">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>`;
    box.appendChild(div);
}

function addAttrRow(key) {
    const box = document.getElementById('attrs-box');
    const div = document.createElement('div');
    div.className = 'row-repeat';
    div.style.gridTemplateColumns = '1fr 1.6fr auto';
    div.innerHTML = `
        <input type="text" name="attr_key[]" value="${key || ''}" list="attr-keys" placeholder="عنوان">
        <input type="text" name="attr_value[]" placeholder="مقدار">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()">×</button>`;
    box.appendChild(div);
    if (key) div.querySelectorAll('input')[1].focus();
}

// نمایش/مخفی کردن سوییچ دستی موجودی
document.querySelector('input[name=track_stock]')?.addEventListener('change', function () {
    document.getElementById('manual-stock').style.display = this.checked ? 'none' : 'flex';
});

document.getElementById('auto-alt-mode')?.addEventListener('change', function(){ document.querySelectorAll('input[name^=\"image_alt\"]').forEach((x,i)=>{ if(this.checked){x.value=productFormValue('name')+(i?' - تصویر '+(i+1):'');x.readOnly=true;}else{x.readOnly=false;} }); });

// آپلود کشیدن و رها کردن
const dz = document.getElementById('dropzone');
if (dz) {
    ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault(); dz.classList.add('drag');
    }));
    ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
        e.preventDefault(); dz.classList.remove('drag');
    }));
    dz.addEventListener('drop', e => {
        const input = document.getElementById('imgInput');
        input.files = e.dataTransfer.files;
        if (input.files.length) document.getElementById('imgForm').submit();
    });
}
// انتخاب thumbnail همیشه با تصویر اصلی همگام است و متن جایگزین دو حالت دارد.
function productFormValue(name) { const el=document.querySelector('[name="'+name+'"]'); return el ? el.value.trim() : ''; }
function currentProductContext() {
 return {name:productFormValue('name'), category:productFormValue('category_id'), brand:productFormValue('brand'), oem_code:productFormValue('oem_code'), description:productFormValue('description'), price:productFormValue('price'), current_slug:productFormValue('slug'), vehicle_models:[...document.querySelectorAll('[name="vehicle_model_id[]"]')].map(x=>x.value), attributes:[...document.querySelectorAll('[name="attr_key[]"]')].map((x,i)=>({name:x.value,value:document.querySelectorAll('[name="attr_value[]"]')[i]?.value||''}))};
}
function buildProductPrompt() {
 const schema={name:'string required',slug:'lowercase latin url slug; generate from name',category_id:'number|null',brand:'string|null',oem_code:'string|null',price:'number',description:'safe Persian HTML/text',vehicles:[{model_id:'number',year_from:'number|null',year_to:'number|null',trim:'string|null'}],attributes:[{name:'string',value:'string'}],meta_title:'string|null',meta_description:'string|null',focus_keyword:'string|null',image_alt_mode:'auto|manual',images:[{alt:'string',seo_filename:'string'}]};
 document.getElementById('ai-prompt-output').value=`نقش شما کارشناس کاتالوگ قطعات خودرو هستید. با داده زمینه زیر فقط JSON معتبر و بدون markdown برگردان. اطلاعات را حدس نزن؛ موارد نامعلوم را null یا آرایه خالی بگذار. اسلاگ را هوشمند، یکتا، کوتاه و فقط با حروف لاتین کوچک و خط تیره بساز. خروجی باید دقیقاً همین کلیدها را داشته باشد و JSON اضافی ننویس. زمینه فعلی: ${JSON.stringify(currentProductContext())}\nقالب استاندارد خروجی: ${JSON.stringify(schema)}\nتمام اعداد را عدد واقعی بده و متن فارسی را UTF-8 نگه دار.`;
}
function buildImagePrompt() {
 const c=currentProductContext(); document.getElementById('ai-prompt-output').value=`یک تصویر محصول کاتالوگی حرفه‌ای و یکدست برای «${c.name||'نامشخص'}»${c.brand?' برند '+c.brand:''}${c.oem_code?' با کد فنی '+c.oem_code:''} بساز. قطعه دقیقاً در مرکز، نمای سه‌ربع و کامل، پس‌زمینه سفید یا خاکستری بسیار روشن، نور استودیویی نرم، سایه کنترل‌شده، بدون لوگو و نوشته و واترمارک، بدون دست و خودرو و بسته‌بندی، نسبت 1:1، کیفیت بالا، رنگ و جنس واقعی قطعه. تصویر مناسب فروشگاه قطعات خودرو و هماهنگ با سایر تصاویر کاتالوگ باشد.`;
}
function copyAiPrompt(){const x=document.getElementById('ai-prompt-output'); navigator.clipboard?.writeText(x.value);}
function applyAiJson(){try{const d=JSON.parse(document.getElementById('ai-json-input').value); const set=(n,v)=>{const x=document.querySelector('[name="'+n+'"]');if(x&&v!==null&&v!==undefined)x.value=v}; Object.keys(d).forEach(k=>{if(!['vehicles','attributes','images'].includes(k))set(k,d[k]);}); if(d.slug)set('slug',d.slug); if(d.attributes){document.getElementById('attrs-box').innerHTML='';d.attributes.forEach(a=>{addAttrRow(a.name);document.querySelectorAll('[name="attr_value[]"]')[document.querySelectorAll('[name="attr_value[]"]').length-1].value=a.value||'';});} if(d.image_alt_mode==='auto'){document.querySelectorAll('input[name^="image_alt"]').forEach(x=>{if(!x.value)x.value=productFormValue('name')});} alert('اطلاعات JSON با موفقیت در فرم قرار گرفت.');}catch(e){alert('JSON معتبر نیست: '+e.message)}}

</script>
