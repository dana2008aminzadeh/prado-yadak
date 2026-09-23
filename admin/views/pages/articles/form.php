<?php
use Admin\core\Auth;
use Admin\core\Uploader;
$aid = (int) $article['id'];
$coverUrl = $article['cover_image']
    ? (str_starts_with((string) $article['cover_image'], 'uploads/')
        ? '/' . $article['cover_image']
        : (str_starts_with((string) $article['cover_image'], 'http') || str_starts_with((string) $article['cover_image'], '/')
            ? $article['cover_image']
            : '/image?id=' . urlencode((string) $article['cover_image'])))
    : '/assets/logo/logo.webp';
?>

<form method="POST" action="<?= admin_url('articles/save') ?>" enctype="multipart/form-data">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $aid ?>">

    <div class="grid g2" style="grid-template-columns:2.2fr 1fr;align-items:start">
        <div class="card">
            <div class="card-head"><h3>محتوای مقاله</h3></div>
            <div class="card-body">
                <div class="field"><label class="fl">عنوان *</label>
                    <input type="text" name="title" value="<?= e($article['title']) ?>" required></div>
                <div class="field"><label class="fl">اسلاگ</label>
                    <input type="text" name="slug" class="mono" value="<?= e($article['slug']) ?>" placeholder="خودکار"></div>
                <div class="field"><label class="fl">خلاصه</label>
                    <textarea name="summary" rows="3"><?= e($article['summary']) ?></textarea></div>
                <div class="field"><label class="fl">متن کامل (HTML مجاز)</label>
                    <textarea name="content" rows="20" class="mono" style="font-size:12px"><?= e($article['content']) ?></textarea></div>
            </div>
        </div>

        <div>
            <div class="card mb">
                <div class="card-head">
                    <h3>انتشار</h3>
                    <?php $gatePassed = (bool) ($publishGate['passed'] ?? false); ?>
                    <span class="badge" style="background:<?= $gatePassed ? 'var(--green)' : 'var(--red)' ?>1a;
                          color:<?= $gatePassed ? 'var(--green)' : 'var(--red)' ?>;
                          border-color:<?= $gatePassed ? 'var(--green)' : 'var(--red)' ?>55">
                        <?= $gatePassed ? 'آماده انتشار' : 'شرایط انتشار کامل نیست' ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (!empty($publishGate['errors'])): ?>
                        <div style="border:1px solid var(--red)55;background:var(--red)0d;border-radius:11px;
                                    padding:10px 12px;margin-bottom:12px;font-size:11.8px;line-height:2">
                            <b style="color:var(--red)">تا زمانی که موارد زیر اصلاح نشوند، مقاله منتشر نمی‌شود
                                و به‌صورت پیش‌نویس ذخیره می‌ماند:</b>
                            <ul style="margin:6px 0 0;padding-right:18px">
                                <?php foreach ($publishGate['errors'] as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php $gs = $publishGate['stats'] ?? []; ?>
                    <div class="hint" style="margin-bottom:10px;line-height:2">
                        حجم محتوا: <b><?= fa((int) ($gs['words'] ?? 0)) ?></b> کلمه (حداقل <?= fa(\Core\SeoAnalyzer::PUBLISH_MIN_WORDS) ?>)
                        — تیتر H2: <b><?= fa((int) ($gs['h2'] ?? 0)) ?></b> (حداقل <?= fa(\Core\SeoAnalyzer::PUBLISH_MIN_H2) ?>)
                        — H3: <b><?= fa((int) ($gs['h3'] ?? 0)) ?></b>
                        — تصاویر بدون alt: <b><?= fa((int) ($gs['images_missing_alt'] ?? 0)) ?></b> از <?= fa((int) ($gs['images'] ?? 0)) ?>
                    </div>
                    <div class="field"><label class="fl">وضعیت</label>
                        <select name="status">
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>منتشرشده</option>
                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                        </select></div>
                    <div class="field"><label class="fl">نویسنده</label>
                        <input type="text" name="author" value="<?= e($article['author']) ?>"></div>
                    <div class="field"><label class="fl">زمان مطالعه (دقیقه)</label>
                        <input type="number" name="reading_time" min="1" value="<?= (int) $article['reading_time'] ?>">
                        <div class="hint">خالی بگذارید تا خودکار محاسبه شود.</div></div>
                    <button class="btn btn-primary btn-block" type="submit">
                        <i data-lucide="save" style="width:15px"></i> ذخیره مقاله
                    </button>
                    <?php if ($aid): ?>
                        <a class="btn btn-block mt" target="_blank" rel="noopener" href="<?= e(\Core\Seo::articleUrl($article['slug'] ?? '')) ?>">مشاهده در سایت</a>
                        <div class="hint mt">بازدید: <?= money($article['views']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $seoEntity = $article;
            $seoType = 'article';
            $seoUrlBase = '/blog/';
            require ADMIN_PATH . '/views/partials/seo-box.php';
            ?>

            <div class="card mb">
                <div class="card-head"><h3>دسته‌بندی</h3></div>
                <div class="card-body">
                    <div class="field"><label class="fl">کلید دسته</label>
                        <select name="category">
                            <?php foreach (['genuine' => 'قطعات اصل', 'technical' => 'فنی', 'maintenance' => 'نگهداری'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $article['category'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">برچسب نمایشی</label>
                        <input type="text" name="category_label" value="<?= e($article['category_label']) ?>"></div>
                    <div class="field"><label class="fl">آیکون (Lucide)</label>
                        <input type="text" name="icon" class="mono" value="<?= e($article['icon']) ?>" placeholder="wrench"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>تصویر کاور</h3></div>
                <div class="card-body">
                    <img src="<?= e($coverUrl) ?>" alt="" style="width:100%;height:150px;object-fit:cover;
                         border-radius:11px;border:1px solid var(--line);margin-bottom:10px">
                    <div class="field">
                        <label class="fl">آپلود تصویر جدید</label>
                        <input type="file" name="cover_file" accept="image/*">
                    </div>
                    <div class="field">
                        <label class="fl">یا آدرس / file_id دستی</label>
                        <input type="text" name="cover_image" class="mono" value="<?= e($article['cover_image']) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- محصولات مرتبط (ساختار سیلو: اتصال وبلاگ به فروشگاه) -->
        <div class="card mt">
            <div class="card-head">
                <h3>محصولات مرتبط با این مقاله</h3>
                <span class="hint">کارت خرید این قطعات با قیمت و موجودی داخل بدنه مقاله نمایش داده می‌شود.</span>
            </div>
            <div class="card-body">
                <div class="field">
                    <label class="fl">جستجوی قطعه (نام یا کد فنی)</label>
                    <input type="text" id="prod-search" autocomplete="off" placeholder="مثلا: لنت ترمز جلو یا 04465-33471">
                    <div id="prod-results" style="display:none;border:1px solid var(--line);border-radius:11px;margin-top:6px;max-height:220px;overflow:auto"></div>
                </div>
                <div id="related-products-list" class="flex gap wrap">
                    <?php foreach (($linkedProducts ?? []) as $lp): ?>
                        <div class="flex gap" data-pid="<?= (int) $lp['id'] ?>"
                             style="align-items:center;border:1px solid var(--line);border-radius:11px;padding:6px 10px">
                            <input type="hidden" name="related_product_ids[]" value="<?= (int) $lp['id'] ?>">
                            <span style="font-size:12px;font-weight:700"><?= e(excerpt($lp['name'], 45)) ?></span>
                            <?php if ($lp['oem_code']): ?>
                                <span class="hint mono"><?= e($lp['oem_code']) ?></span>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="this.parentElement.remove()">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($linkedProducts)): ?>
                    <div class="hint mt" id="no-related">هنوز محصولی متصل نشده است. مقاله بدون کارت خرید، نرخ تبدیل ندارد.</div>
                <?php endif; ?>
            </div>
        </div>
</form>

<?php if ($aid && can('articles.edit')): ?>
    <div class="card mt">
        <div class="card-body text-left">
            <form method="POST" action="<?= admin_url('articles/delete') ?>" onsubmit="return confirmDelete('حذف این مقاله؟')">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="article_id" value="<?= $aid ?>">
                <button class="btn btn-danger" type="submit"><i data-lucide="trash-2" style="width:14px"></i> حذف مقاله</button>
            </form>
        </div>
    </div>
<?php endif; ?>


<script>
// ---- انتخاب محصولات مرتبط (جستجوی زنده) ----
(function () {
    var input = document.getElementById('prod-search');
    var box = document.getElementById('prod-results');
    var list = document.getElementById('related-products-list');
    if (!input) return;

    var timer = null;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        var q = input.value.trim();
        if (q.length < 2) { box.style.display = 'none'; return; }

        timer = setTimeout(function () {
            fetch('<?= admin_url('articles/searchProducts') ?>?q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    box.innerHTML = '';
                    (d.items || []).forEach(function (p) {
                        var row = document.createElement('div');
                        row.style.cssText = 'padding:8px 11px;cursor:pointer;border-bottom:1px solid var(--line);font-size:12px';
                        row.textContent = p.name + (p.oem_code ? '  —  ' + p.oem_code : '');
                        row.onmouseenter = function () { row.style.background = '#f6f7fb'; };
                        row.onmouseleave = function () { row.style.background = ''; };
                        row.onclick = function () { addProduct(p); };
                        box.appendChild(row);
                    });
                    box.style.display = (d.items || []).length ? 'block' : 'none';
                });
        }, 250);
    });

    function addProduct(p) {
        if (list.querySelector('[data-pid="' + p.id + '"]')) return;
        var el = document.createElement('div');
        el.className = 'flex gap';
        el.dataset.pid = p.id;
        el.style.cssText = 'align-items:center;border:1px solid var(--line);border-radius:11px;padding:6px 10px';
        el.innerHTML = '<input type="hidden" name="related_product_ids[]" value="' + p.id + '">'
            + '<span style="font-size:12px;font-weight:700"></span>'
            + (p.oem_code ? '<span class="hint mono">' + p.oem_code + '</span>' : '')
            + '<button type="button" class="btn btn-sm btn-danger">×</button>';
        el.querySelector('span').textContent = p.name;
        el.querySelector('button').onclick = function () { el.remove(); };
        list.appendChild(el);

        var empty = document.getElementById('no-related');
        if (empty) empty.remove();
        box.style.display = 'none';
        input.value = '';
    }
})();
</script>
