<?php
use Admin\core\Auth;

$e = $edit ?? [
    'id' => 0, 'slug' => '', 'h1' => '', 'meta_title' => '', 'meta_description' => '', 'focus_keyword' => '',
    'intro_html' => '', 'outro_html' => '', 'filter_category' => '', 'filter_model' => '', 'filter_brand' => '',
    'robots_directive' => 'default', 'is_active' => 1, 'views' => 0,
];
$lid = (int) $e['id'];
?>

<div class="grid g2" style="grid-template-columns:1.6fr 1fr;align-items:start">
    <div class="card">
        <div class="card-head">
            <h3><?= $lid ? 'ویرایش لندینگ‌پیج' : 'ساخت لندینگ‌پیج جدید' ?></h3>
            <?php if ($lid): ?>
                <a class="btn btn-sm" href="<?= admin_url('seo/landing') ?>">+ مورد جدید</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="hint mb">
                به‌جای ارجاع گوگل به آدرس پارامتردار (<code class="mono">/parts?category=brake&model=camry</code>)
                برای ترکیب‌های پرجستجو یک آدرس تمیز و یکتا بسازید:
                <code class="mono">/parts/لوازم-یدکی-کمری-لنت-ترمز</code>
            </div>

            <form method="POST" action="<?= admin_url('seo/saveLanding') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="id" value="<?= $lid ?>">

                <div class="field">
                    <label class="fl">عنوان اصلی صفحه (H1) *</label>
                    <input type="text" name="h1" value="<?= e($e['h1']) ?>" required
                           placeholder="لوازم یدکی کمری — دسته لنت ترمز">
                </div>
                <div class="field">
                    <label class="fl">آدرس صفحه (Slug)</label>
                    <input type="text" name="slug" class="mono" value="<?= e($e['slug']) ?>" placeholder="خودکار از روی H1">
                    <div class="hint">آدرس نهایی: <code class="mono">/parts/<?= e($e['slug'] ?: '...') ?></code></div>
                </div>

                <div class="grid g3">
                    <div class="field">
                        <label class="fl">فیلتر دسته‌بندی</label>
                        <select name="filter_category">
                            <option value="">— بدون فیلتر —</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= e($c['slug']) ?>" <?= $e['filter_category'] === $c['slug'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">فیلتر مدل خودرو</label>
                        <select name="filter_model">
                            <option value="">— بدون فیلتر —</option>
                            <?php foreach ($carModels as $m): ?>
                                <option value="<?= e($m['slug']) ?>" <?= $e['filter_model'] === $m['slug'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">فیلتر برند</label>
                        <select name="filter_brand">
                            <option value="">— بدون فیلتر —</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= e($b['brand']) ?>" <?= $e['filter_brand'] === $b['brand'] ? 'selected' : '' ?>><?= e($b['brand']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label class="fl">متن معرفی (بالای لیست محصولات)</label>
                    <textarea name="intro_html" rows="5" class="mono" style="font-size:12px"><?= e($e['intro_html']) ?></textarea>
                    <div class="hint">متن یکتا بنویسید تا این صفحه محتوای تکراری کاتالوگ محسوب نشود.</div>
                </div>
                <div class="field">
                    <label class="fl">متن تکمیلی (پایین لیست)</label>
                    <textarea name="outro_html" rows="5" class="mono" style="font-size:12px"><?= e($e['outro_html']) ?></textarea>
                </div>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">عنوان سئو (Meta Title)</label>
                        <input type="text" name="meta_title" value="<?= e($e['meta_title']) ?>" maxlength="255">
                    </div>
                    <div class="field">
                        <label class="fl">کلمه کلیدی هدف</label>
                        <input type="text" name="focus_keyword" value="<?= e($e['focus_keyword']) ?>">
                    </div>
                </div>
                <div class="field">
                    <label class="fl">توضیحات متا (Meta Description)</label>
                    <textarea name="meta_description" rows="2" maxlength="320"><?= e($e['meta_description']) ?></textarea>
                </div>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">وضعیت ایندکس</label>
                        <select name="robots_directive">
                            <option value="default" <?= $e['robots_directive'] === 'default' ? 'selected' : '' ?>>پیش‌فرض (Index)</option>
                            <option value="index" <?= $e['robots_directive'] === 'index' ? 'selected' : '' ?>>Index اجباری</option>
                            <option value="noindex" <?= $e['robots_directive'] === 'noindex' ? 'selected' : '' ?>>Noindex</option>
                        </select>
                    </div>
                    <label class="chk" style="margin-top:26px">
                        <input type="checkbox" name="is_active" value="1" <?= (int) $e['is_active'] === 1 ? 'checked' : '' ?>>
                        <span>صفحه فعال باشد</span>
                    </label>
                </div>

                <button class="btn btn-primary" type="submit"><i data-lucide="save" style="width:14px"></i> ذخیره لندینگ‌پیج</button>
                <?php if ($lid): ?>
                    <a class="btn" target="_blank" rel="noopener" href="/parts/<?= e($e['slug']) ?>">مشاهده در سایت</a>
                <?php endif; ?>
            </form>

            <?php if ($lid && can('seo.manage')): ?>
                <div class="mt text-left">
                    <?= action_button(admin_url('seo/deleteLanding'), 'حذف این لندینگ‌پیج', [
                        'class' => 'btn btn-sm btn-danger', 'icon' => 'trash-2',
                        'confirm' => 'حذف شود؟ آدرس آن ۴۰۴ خواهد شد.',
                        'fields' => ['landing_id' => $lid],
                    ]) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>لندینگ‌پیج‌های موجود (<?= fa(count($pages)) ?>)</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>عنوان</th><th>آدرس</th><th>بازدید</th><th>وضعیت</th></tr></thead>
                <tbody>
                    <?php foreach ($pages as $p): ?>
                        <tr>
                            <td><a href="<?= admin_url('seo/landing/' . (int) $p['id']) ?>"><?= e(excerpt($p['h1'], 40)) ?></a></td>
                            <td class="mono" style="max-width:160px;overflow:hidden;text-overflow:ellipsis">/parts/<?= e($p['slug']) ?></td>
                            <td><?= fa((int) $p['views']) ?></td>
                            <td><?= (int) $p['is_active'] === 1 ? '<span class="badge b-green">فعال</span>' : '<span class="badge b-gray">غیرفعال</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$pages): ?>
                        <tr><td colspan="4" class="empty">هنوز لندینگ‌پیجی ساخته نشده است.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
