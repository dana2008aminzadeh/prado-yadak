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
                    <p class="hint">
                        <b>۱)</b> پرامپت موردنظر را بسازید و کپی کنید.
                        <b>۲)</b> آن را در ChatGPT / Gemini / Claude پیست کنید.
                        <b>۳)</b> JSON پاسخ را در کادر پایین برگردانید و «اعمال» بزنید.
                    </p>
                    <div class="flex gap wrap mt">
                        <button type="button" class="btn btn-sm" onclick="buildProductPrompt()">
                            <i data-lucide="file-text" style="width:13px"></i> پرامپت تکمیل اطلاعات (JSON)
                        </button>
                        <button type="button" class="btn btn-sm" onclick="buildImagePrompt()">
                            <i data-lucide="image" style="width:13px"></i> پرامپت عکس محصول (انگلیسی)
                        </button>
                    </div>
                    <textarea id="ai-prompt-output" rows="10" class="mono mt" readonly placeholder="پرامپت تولیدشده اینجا نمایش داده می‌شود"></textarea>
                    <div class="flex gap wrap mt">
                        <button type="button" class="btn btn-sm" onclick="copyAiPrompt()">
                            <i data-lucide="copy" style="width:13px"></i> کپی پرامپت
                        </button>
                    </div>
                    <textarea id="ai-json-input" rows="6" class="mono mt" placeholder="خروجی JSON هوش مصنوعی را اینجا پیست کنید (اگر داخل ```json بود، خودش تشخیص داده می‌شود)"></textarea>
                    <div class="flex gap wrap mt">
                        <button type="button" class="btn btn-sm btn-primary" onclick="applyAiJson()">
                            <i data-lucide="check-check" style="width:13px"></i> اعمال خروجی JSON در فرم
                        </button>
                    </div>
                    <div class="hint mt">
                        پرامپت عکس به انگلیسی ساخته می‌شود چون مدل‌های تصویرساز انگلیسی را دقیق‌تر می‌فهمند؛ نام و کد فنی قطعه داخل آن حفظ می‌شود.
                        اعمال JSON فقط فیلدهای معتبر را به‌روز می‌کند — قیمت، موجودی و وضعیت انتشار فقط با دست خودتان تغییر می‌کند.
                    </div>
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
// فهرست‌های مجاز برای پرامپت هوش مصنوعی: مدل فقط شناسه‌های واقعی سیستم را برمی‌گرداند
const AI_CATEGORIES = <?= json_encode(array_map(fn($c) => ['id' => (int) $c['id'], 'name' => (string) $c['name']], $categories), JSON_UNESCAPED_UNICODE) ?>;
const AI_PRESET_KEYS = <?= json_encode(array_values(array_map(fn($p) => (string) $p['attr_key'], $presets)), JSON_UNESCAPED_UNICODE) ?>;

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
// ================= دستیار هوش مصنوعی =================
// انتخاب thumbnail همیشه با تصویر اصلی همگام است و متن جایگزین دو حالت دارد.
function productFormValue(name) { const el = document.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; }

function aiChecked(name) {
    const el = document.querySelector('[name="' + name + '"]');
    return !!(el && el.checked);
}

/** وضعیت فعلی فرم به‌همراه نام‌ها (نه فقط شناسه‌ها) تا مدل بدون حدس، فیلدها را تکمیل کند */
function aiCurrentContext() {
    const catId = parseInt(productFormValue('category_id'), 10) || null;
    const cat = AI_CATEGORIES.find(c => c.id === catId) || null;
    const attrKeys = [...document.querySelectorAll('[name="attr_key[]"]')];
    const attrVals = [...document.querySelectorAll('[name="attr_value[]"]')];
    return {
        name: productFormValue('name'),
        brand: productFormValue('brand'),
        oem_code: productFormValue('oem_code'),
        current_slug: productFormValue('slug'),
        category_id: catId,
        category_name: cat ? cat.name : null,
        is_genuine: aiChecked('is_genuine'),
        description: productFormValue('description'),
        vehicles: [...document.querySelectorAll('[name="vehicle_model_id[]"]')].map(sel => {
            const mid = parseInt(sel.value, 10) || null;
            const m = CAR_MODELS.find(x => x.id === mid);
            const row = sel.closest('.row-repeat');
            const yf = row ? row.querySelector('[name="vehicle_year_from[]"]') : null;
            const yt = row ? row.querySelector('[name="vehicle_year_to[]"]') : null;
            const tr = row ? row.querySelector('[name="vehicle_trim[]"]') : null;
            return {
                model_id: mid,
                model_name: m ? m.name : null,
                year_from: yf && yf.value ? parseInt(yf.value, 10) : null,
                year_to: yt && yt.value ? parseInt(yt.value, 10) : null,
                trim: tr ? tr.value.trim() : null
            };
        }),
        attributes: attrKeys.map((k, i) => ({ name: k.value.trim(), value: (attrVals[i] ? attrVals[i].value : '').trim() }))
    };
}

/** پرامپت تکمیل اطلاعات محصول: زمینه + فهرست‌های مجاز + قالب دقیق خروجی */
function buildProductPrompt() {
    const schema = {
        name: 'نام کامل و استاندارد قطعه (فارسی؛ جلو/عقب/چپ/راست اگر مشخص است)',
        slug: 'اسلاگ لاتین کوچک با خط تیره، حداکثر ۵ کلمه، ساخته‌شده از نام قطعه',
        category_id: 'عدد — فقط یکی از idهای «فهرست دسته‌های مجاز»؛ نامعلوم = null',
        brand: 'برند واقعی قطعه یا null',
        oem_code: 'کد فنی (OEM) یا null — فقط اگر قطعی است',
        description: 'HTML فارسی سئوشده فقط با تگ‌های مجاز <p> <strong> <ul> <li> <br>',
        vehicles: [{ model_id: 'عدد — فقط از «فهرست خودروهای مجاز»', year_from: 'عدد میلادی یا null', year_to: 'عدد میلادی یا null', trim: 'تیپ/موتور مثل 1GR-FE یا null' }],
        attributes: [{ name: 'عنوان مشخصه — ترجیحاً از «کلیدهای پیشنهادی مشخصات»', value: 'مقدار درست و بدون حدس' }],
        meta_title: 'حداکثر ۶۰ کاراکتر، شامل نام قطعه',
        meta_description: 'بین ۱۲۰ تا ۱۵۵ کاراکتر، ترغیب‌کننده کلیک',
        focus_keyword: 'عبارتی که خریدار واقعاً جستجو می‌کند',
        image_alt_mode: "اگر متن جایگزین تصاویر از نام محصول ساخته شود 'auto' وگرنه 'manual'",
        images: [{ alt: 'متن جایگزین گویا برای هر تصویر', seo_filename: 'نام فایل لاتین کوچک با خط تیره' }]
    };
    const rules = [
        'قواعد الزامی:',
        '۱) هیچ اطلاعات فنی، سازگاری، سال یا کد فنی را از خودت نساز؛ هر چیزی که در داده‌ها نیست را null یا آرایه خالی بگذار.',
        '۲) category_id فقط از «فهرست دسته‌های مجاز» و model_id فقط از «فهرست خودروهای مجاز» انتخاب کن؛ اگر مورد مناسبی نیست، null یا [] بگذار.',
        '۳) اسلاگ فقط با حروف لاتین کوچک، عدد و خط تیره باشد (مثال: prado-front-brake-pad-set).',
        '۴) description فارسی روان و یکتا باشد: ۲ تا ۴ پاراگراف شامل کاربرد قطعه، خودروهای سازگار، نکته کیفیت/نصب و مزیت خرید؛ از جمله‌های کلی و تکراری بپرهیز.',
        '۵) در attributes فقط مشخصات استاندارد و درست برای این نوع قطعه را بنویس (جنس، کشور سازنده، سمت نصب و...)؛ مقدار نامعلوم را حذف کن، حدس نزن.',
        '۶) meta_title حداکثر ۶۰ کاراکتر و meta_description بین ۱۲۰ تا ۱۵۵ کاراکتر باشد.',
        '۷) همه اعداد به‌صورت عدد JSON (بدون گیومه و جداکننده هزارگان) و متن فارسی UTF-8 باشد.',
        '۸) خروجی فقط و فقط یک JSON معتبر باشد: بدون markdown، بدون ```، بدون هیچ توضیحی قبل و بعد.'
    ].join('\n');
    const prompt = [
        'تو یک کارشناس ارشد کاتالوگ قطعات خودرو (تویوتا، لندکروزر، پرادو) و متخصص سئوی فارسی هستی.',
        'وظیفه: داده‌های ناقص محصول زیر را طبق قواعد تکمیل و اصلاح کن و خروجی را دقیقاً با ساختار «قالب خروجی» برگردان.',
        '',
        rules,
        '',
        'فهرست دسته‌های مجاز (id و نام): ' + JSON.stringify(AI_CATEGORIES),
        'فهرست خودروهای مجاز (id و نام): ' + JSON.stringify(CAR_MODELS),
        'کلیدهای پیشنهادی مشخصات فنی: ' + JSON.stringify(AI_PRESET_KEYS),
        '',
        'داده‌های فعلی محصول: ' + JSON.stringify(aiCurrentContext()),
        '',
        'قالب خروجی (دقیقاً همین کلیدها، هیچ کلید دیگری اضافه نکن):',
        JSON.stringify(schema, null, 2)
    ].join('\n');
    document.getElementById('ai-prompt-output').value = prompt;
}

// واژه‌نامه قطعات پرتکرار برای ساخت پرامپت انگلیسی تصویر (طولانی‌ترین تطبیق برنده است)
const AI_PART_EN = [
    ['لنت ترمز جلو', 'front brake pad set'], ['لنت ترمز عقب', 'rear brake pad set'], ['لنت ترمز', 'brake pad set'],
    ['لنت', 'brake pad'], ['دیسک ترمز', 'brake rotor disc'], ['کاسه ترمز', 'brake drum'],
    ['سیلندر ترمز', 'brake wheel cylinder'], ['شیلنگ ترمز', 'brake hose'], ['ترمز دست', 'parking brake lever'],
    ['فیلتر روغن', 'engine oil filter'], ['فیلتر هوا', 'engine air filter'], ['فیلتر کابین', 'cabin air filter'],
    ['فیلتر اتاق', 'cabin air filter'], ['فیلتر بنزین', 'fuel filter'], ['فیلتر گازوئیل', 'diesel fuel filter'],
    ['فیلتر', 'filter'], ['شمع', 'spark plug'], ['سیم شمع', 'spark plug wire set'], ['کویل', 'ignition coil'],
    ['دینام', 'alternator'], ['استارت', 'starter motor'], ['باتری', 'car battery'], ['رله', 'relay'],
    ['سنسور اکسیژن', 'oxygen sensor'], ['حسگر', 'sensor'], ['سنسور', 'sensor'], ['لامپ', 'bulb'],
    ['برف پاک کن', 'wiper blade'], ['کمک فنر جلو', 'front shock absorber'], ['کمک فنر عقب', 'rear shock absorber'],
    ['کمک فنر', 'shock absorber'], ['کمک', 'shock absorber'], ['فنر', 'coil spring'], ['طبق', 'control arm'],
    ['سیبک', 'ball joint'], ['جعبه فرمان', 'steering gearbox'], ['میل تعادل', 'stabilizer bar link'],
    ['بوش', 'bushing'], ['بلبرینگ', 'wheel bearing'], ['گلگیر', 'fender'], ['سپر', 'bumper'], ['آینه', 'side mirror'],
    ['رادیاتور کولر', 'AC condenser'], ['رادیاتور', 'radiator'], ['فن رادیاتور', 'radiator cooling fan'],
    ['ترموستات', 'thermostat'], ['واتر پمپ', 'water pump'], ['واترپمپ', 'water pump'], ['تسمه تایم', 'timing belt'],
    ['زنجیر تایم', 'timing chain'], ['تسمه دینام', 'serpentine belt'], ['تسمه', 'belt'],
    ['واشر سرسیلندر', 'cylinder head gasket'], ['واشر', 'gasket'], ['پمپ بنزین', 'fuel pump'],
    ['انژکتور', 'fuel injector'], ['کاربراتور', 'carburetor'], ['دستگاه کلاچ', 'clutch kit'],
    ['دیسک کلاچ', 'clutch disc'], ['پلوس', 'drive axle shaft'], ['گاردان', 'propeller shaft'],
    ['دیفرانسیل', 'differential'], ['چراغ جلو', 'headlight assembly'], ['چراغ عقب', 'tail light assembly'],
    ['چراغ', 'light assembly'], ['دریچه باک', 'fuel filler door'], ['کاپوت', 'hood'],
    ['روغن موتور', 'engine oil'], ['مایع ترمز', 'brake fluid'], ['ضدیخ', 'antifreeze coolant']
];
const AI_BRAND_EN = { 'تویوتا': 'Toyota', 'دنسو': 'Denso', 'ایسین': 'Aisin', 'بوش': 'Bosch', 'نیپوندنسو': 'Nippon Denso' };

function aiEnglishPartName(c) {
    const hay = ((c.category_name || '') + ' ' + (c.name || '')).replace(/\u200c/g, ' ');
    let best = null;
    AI_PART_EN.forEach(row => {
        if (hay.indexOf(row[0]) !== -1 && (!best || row[0].length > best[0].length)) best = row;
    });
    return best ? best[1] : '';
}

function aiEnglishBrand(c) {
    const b = (c.brand || '').trim();
    if (!b) return '';
    if (/[A-Za-z]/.test(b)) return b;
    return AI_BRAND_EN[b] || '';
}

/** پرامپت عکس: انگلیسی (مدل‌های تصویرساز انگلیسی را دقیق‌تر می‌فهمند) + حفظ نام و کد فنی قطعه */
function buildImagePrompt() {
    const c = aiCurrentContext();
    const partEn = aiEnglishPartName(c);
    const brandEn = aiEnglishBrand(c);
    const subject = [
        partEn
            ? 'Professional e-commerce product photo of a ' + partEn + ' automotive spare part'
            : 'Professional e-commerce product photo of an automotive spare part (identify the exact part type from the Persian name below first)',
        c.is_genuine ? 'genuine original equipment (OEM) part' : 'quality OEM replacement part',
        brandEn ? ('brand: ' + brandEn) : '',
        c.oem_code ? ('manufacturer part number: ' + c.oem_code) : '',
        c.name ? ('Persian part name: ' + c.name) : ''
    ].filter(Boolean).join(', ');
    const style = 'the single part is perfectly centered in a three-quarter view, fully visible with nothing cropped, pure white seamless studio background, soft diffused studio lighting, subtle soft contact shadow under the part, photorealistic, ultra sharp focus, accurate true-to-life colors and material texture (metal, plastic or rubber as applicable), clean minimal catalog composition matching a professional auto parts store';
    const negative = 'strictly avoid: any text or writing on the image, watermarks, overlaid brand logos, hands or people, tools, the whole car or its interior, workshop or garage background, packaging boxes, harsh shadows, busy background, cropped or cut-off part edges';
    document.getElementById('ai-prompt-output').value =
        subject + '.\n\n' + style + '.\n\n' + negative + '.\n\nAspect ratio: 1:1 (square).';
}

function copyAiPrompt() {
    const box = document.getElementById('ai-prompt-output');
    if (!box.value.trim()) { alert('ابتدا یکی از دکمه‌های ساخت پرامپت را بزنید.'); return; }
    const done = () => alert('پرامپت کپی شد؛ آن را در ابزار هوش مصنوعی پیست کنید.');
    const fallback = () => {
        const ta = document.createElement('textarea');
        ta.value = box.value;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        let ok = false;
        try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        ta.remove();
        if (ok) done(); else alert('کپی خودکار ممکن نشد؛ متن پرامپت را دستی انتخاب و کپی کنید.');
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(box.value).then(done, fallback);
    } else {
        fallback();
    }
}

/** پاسخ مدل حتی داخل ```json یا همراه متن اضافه هم قابل استخراج است */
function aiExtractJson(raw) {
    let t = String(raw || '').trim();
    const fence = t.match(/```(?:json)?\s*([\s\S]*?)```/i);
    if (fence) t = fence[1].trim();
    const s = t.indexOf('{');
    const e = t.lastIndexOf('}');
    if (s !== -1 && e > s) t = t.slice(s, e + 1);
    return t;
}

function aiSetField(name, value) {
    const el = document.querySelector('[name="' + name + '"]');
    if (!el || value === null || value === undefined || String(value) === '') return false;
    el.value = String(value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    return true;
}

function applyAiJson() {
    let d;
    try {
        d = JSON.parse(aiExtractJson(document.getElementById('ai-json-input').value));
    } catch (err) {
        alert('خروجی قابل خواندن نبود:\n' + err.message + '\n\nکل پاسخ هوش مصنوعی را (حتی اگر داخل ```json است) پیست کنید.');
        return;
    }
    if (!d || typeof d !== 'object' || Array.isArray(d)) {
        alert('ساختار پاسخ درست نیست؛ باید یک شیء JSON با کلیدهای قالب پرامپت باشد.');
        return;
    }
    // بعضی مدل‌ها خروجی را داخل product یا data می‌پیچند
    const hasKnown = Object.keys(d).some(k => ['name', 'slug', 'category_id', 'brand', 'oem_code', 'description', 'vehicles', 'attributes', 'meta_title'].indexOf(k) !== -1);
    if (!hasKnown && d.product && typeof d.product === 'object') d = d.product;

    const applied = [], skipped = [];

    if (aiSetField('name', d.name)) applied.push('نام');
    if (aiSetField('slug', d.slug)) applied.push('اسلاگ');
    if (aiSetField('brand', d.brand)) applied.push('برند');
    if (aiSetField('oem_code', d.oem_code)) applied.push('کد فنی');
    if (aiSetField('description', d.description)) applied.push('توضیحات');
    if (aiSetField('meta_title', d.meta_title)) applied.push('عنوان سئو');
    if (aiSetField('meta_description', d.meta_description)) applied.push('توضیحات متا');
    if (aiSetField('focus_keyword', d.focus_keyword)) applied.push('کلمه کلیدی');

    // دسته‌بندی: فقط شناسه‌ای که واقعاً در سیستم وجود دارد
    if (d.category_id !== undefined && d.category_id !== null && d.category_id !== '') {
        const cid = parseInt(d.category_id, 10);
        if (cid && AI_CATEGORIES.some(c => c.id === cid)) {
            if (aiSetField('category_id', cid)) applied.push('دسته‌بندی');
        } else {
            skipped.push('دسته‌بندی (شناسه در فهرست نیست)');
        }
    }

    // خودروهای سازگار: ردیف‌ها بازسازی می‌شوند
    if (Array.isArray(d.vehicles)) {
        const box = document.getElementById('vehicles-box');
        const valid = d.vehicles.filter(v => v && CAR_MODELS.some(m => m.id === parseInt(v.model_id, 10)));
        if (box && valid.length) {
            box.innerHTML = '';
            document.getElementById('no-vehicles')?.remove();
            valid.forEach(() => addVehicleRow());
            const rows = [...box.querySelectorAll('.row-repeat')];
            valid.forEach((v, i) => {
                const row = rows[i];
                if (!row) return;
                row.querySelector('[name="vehicle_model_id[]"]').value = String(parseInt(v.model_id, 10));
                row.querySelector('[name="vehicle_year_from[]"]').value = v.year_from ? String(parseInt(v.year_from, 10)) : '';
                row.querySelector('[name="vehicle_year_to[]"]').value = v.year_to ? String(parseInt(v.year_to, 10)) : '';
                row.querySelector('[name="vehicle_trim[]"]').value = v.trim || '';
            });
            applied.push('خودروهای سازگار (' + valid.length + ' ردیف)');
            if (d.vehicles.length > valid.length) skipped.push((d.vehicles.length - valid.length) + ' خودرو با model_id نامعتبر');
        } else if (d.vehicles.length) {
            skipped.push('خودروهای سازگار (model_id نامعتبر)');
        }
    }

    // مشخصات فنی
    if (Array.isArray(d.attributes)) {
        const box = document.getElementById('attrs-box');
        const valid = d.attributes.filter(a => a && String(a.name || '').trim());
        if (box && valid.length) {
            box.innerHTML = '';
            valid.forEach(a => {
                addAttrRow(String(a.name).trim());
                [...box.querySelectorAll('[name="attr_value[]"]')].pop().value = String(a.value || '').trim();
            });
            applied.push('مشخصات فنی (' + valid.length + ' ردیف)');
        }
    }

    // حالت متن جایگزین خودکار
    if (d.image_alt_mode === 'auto' || d.image_alt_mode === 'manual') {
        const cb = document.getElementById('auto-alt-mode');
        if (cb) {
            cb.checked = d.image_alt_mode === 'auto';
            cb.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // تصاویر: متن جایگزین و نام فایل سئو (بعد از حالت خودکار تا مقدار اختصاصی AI جایگزین شود)
    if (Array.isArray(d.images)) {
        const alts = [...document.querySelectorAll('input[name^="image_alt"]')];
        const seos = [...document.querySelectorAll('input[name^="image_seo_name"]')];
        let n = 0;
        d.images.forEach((im, i) => {
            if (!im) return;
            if (alts[i] && im.alt) { alts[i].value = String(im.alt); n++; }
            if (seos[i] && im.seo_filename) seos[i].value = String(im.seo_filename);
        });
        if (n) applied.push('متن جایگزین تصاویر (' + n + ')');
    }

    let msg = applied.length
        ? '✅ اعمال شد: ' + applied.join('، ')
        : '❌ هیچ فیلدی از این JSON قابل اعمال نبود؛ کلیدها را با قالب پرامپت مقایسه کنید.';
    if (skipped.length) msg += '\n⚠️ نادیده گرفته شد: ' + skipped.join('، ');
    msg += '\n\nقیمت، موجودی و وضعیت انتشار فقط با دست خودتان در فرم تغییر می‌کند.';
    alert(msg);
}
</script>
