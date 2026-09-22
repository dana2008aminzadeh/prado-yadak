<?php use Admin\core\Auth; ?>

<?php if (can('seo.manage')): ?>
    <div class="card mb">
        <div class="card-head"><h3>ثبت ریدایرکت دستی</h3></div>
        <div class="card-body">
            <div class="hint mb">
                برای قطعاتی که برای همیشه ناموجود شده‌اند، آدرسشان را به قطعه مشابه ارجاع دهید تا
                اعتبار لینک‌های ورودی حفظ شود. اگر قطعه واقعاً حذف شده، کد ۴۱۰ را انتخاب کنید.
            </div>
            <form method="POST" action="<?= admin_url('seo/saveRedirect') ?>">
                <?= Auth::csrfField() ?>
                <div class="filters">
                    <div class="f">
                        <label class="fl">از آدرس (مبدأ)</label>
                        <input type="text" name="from_path" class="mono" required placeholder="/product/old-slug">
                    </div>
                    <div class="f">
                        <label class="fl">به آدرس (مقصد)</label>
                        <input type="text" name="to_path" class="mono" required placeholder="/product/new-slug">
                    </div>
                    <div class="f" style="max-width:150px">
                        <label class="fl">کد وضعیت</label>
                        <select name="status_code">
                            <option value="301">۳۰۱ — دائمی</option>
                            <option value="302">۳۰۲ — موقت</option>
                            <option value="410">۴۱۰ — حذف دائمی</option>
                        </select>
                    </div>
                    <div class="f">
                        <label class="fl">یادداشت</label>
                        <input type="text" name="note" placeholder="مثلا: قطعه منسوخ، ارجاع به نسخه جدید">
                    </div>
                    <button class="btn btn-primary" type="submit">
                        <i data-lucide="plus" style="width:14px"></i> ثبت
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <h3>قوانین ریدایرکت (<?= money($pg['total']) ?>)</h3>
        <form method="GET" class="flex gap">
            <input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجوی مسیر..." style="width:220px">
            <button class="btn btn-sm" type="submit">جستجو</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>مبدأ</th><th>مقصد</th><th>کد</th><th>منبع</th><th>بازدید</th><th>وضعیت</th><th>ثبت</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="mono" style="max-width:240px;overflow:hidden;text-overflow:ellipsis"><?= e($r['from_path']) ?></td>
                        <td class="mono" style="max-width:240px;overflow:hidden;text-overflow:ellipsis"><?= e($r['to_path']) ?></td>
                        <td><span class="badge <?= (int) $r['status_code'] === 301 ? 'b-green' : 'b-amber' ?>"><?= fa((int) $r['status_code']) ?></span></td>
                        <td><span class="badge b-gray"><?= $r['source'] === 'auto' ? 'خودکار' : 'دستی' ?></span></td>
                        <td><?= fa((int) $r['hits']) ?></td>
                        <td><?= (int) $r['is_active'] === 1 ? '<span class="badge b-green">فعال</span>' : '<span class="badge b-gray">غیرفعال</span>' ?></td>
                        <td class="hint"><?= e(shamsiTime($r['created_at'])) ?></td>
                        <td>
                            <?php if (can('seo.manage')): ?>
                                <?= action_button(admin_url('seo/deleteRedirect'), 'حذف', [
                                    'class' => 'btn btn-sm btn-danger', 'icon' => 'trash-2',
                                    'confirm' => 'حذف این ریدایرکت؟ آدرس قدیمی دوباره ۴۰۴ خواهد شد.',
                                    'fields' => ['redirect_id' => (int) $r['id']],
                                ]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="empty">هنوز ریدایرکتی ثبت نشده است.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
