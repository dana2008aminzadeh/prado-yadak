<?php
use Admin\core\Auth;
$s = $editing ?: ['id' => 0, 'title' => '', 'subtitle' => '', 'cost' => 0, 'free_above' => '',
    'carrier_slug' => '', 'is_active' => 1, 'sort_order' => 0];
$canEdit = can('shipping.edit');
?>

<div class="grid g2" style="grid-template-columns:1fr 1.6fr;align-items:start">
    <?php if ($canEdit): ?>
        <form class="card" method="POST" action="<?= admin_url('shipping/save') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
            <div class="card-head">
                <h3><?= $s['id'] ? 'ویرایش روش ارسال' : 'روش ارسال جدید' ?></h3>
                <?php if ($s['id']): ?><a class="btn btn-sm" href="<?= admin_url('shipping') ?>">جدید</a><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="field"><label class="fl">عنوان *</label>
                    <input type="text" name="title" value="<?= e($s['title']) ?>" placeholder="پست پیشتاز" required></div>
                <div class="field"><label class="fl">توضیح کوتاه</label>
                    <input type="text" name="subtitle" value="<?= e($s['subtitle']) ?>" placeholder="تحویل ۲ تا ۴ روز کاری"></div>
                <div class="field"><label class="fl">شرکت حمل</label>
                    <select name="carrier_slug">
                        <option value="">—</option>
                        <?php foreach ($carriers as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= ($s['carrier_slug'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="grid g2">
                    <div class="field"><label class="fl">هزینه (تومان)</label>
                        <input type="number" name="cost" step="1000" min="0" value="<?= (int) ($s['cost'] ?? 0) ?>"></div>
                    <div class="field"><label class="fl">رایگان بالای</label>
                        <input type="number" name="free_above" step="10000"
                               value="<?= $s['free_above'] !== null && $s['free_above'] !== '' ? (int) $s['free_above'] : '' ?>"
                               placeholder="بدون شرط"></div>
                </div>
                <div class="field"><label class="fl">ترتیب نمایش</label>
                    <input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>"></div>
                <label class="chk"><input type="checkbox" name="is_active" value="1"
                    <?= (int) $s['is_active'] === 1 ? 'checked' : '' ?>><span>فعال باشد</span></label>
                <button class="btn btn-primary btn-block" type="submit">ذخیره</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h3>روش‌های ارسال</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ترتیب</th><th>عنوان</th><th>شرکت حمل</th><th>هزینه</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$methods): ?><tr><td colspan="6" class="empty">روشی تعریف نشده.</td></tr><?php endif; ?>
                    <?php foreach ($methods as $m): ?>
                        <tr>
                            <td class="hint"><?= (int) $m['sort_order'] ?></td>
                            <td><b><?= e($m['title']) ?></b><div class="hint"><?= e($m['subtitle'] ?: '—') ?></div></td>
                            <td class="hint"><?= e($carriers[$m['carrier_slug'] ?? ''] ?? '—') ?></td>
                            <td>
                                <?= (float) ($m['cost'] ?? 0) > 0 ? money($m['cost']) . ' ت' : '<span class="badge b-green">رایگان</span>' ?>
                                <?php if (!empty($m['free_above'])): ?>
                                    <div class="hint">رایگان بالای <?= money($m['free_above']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $m['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $m['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                            <td class="text-left">
                                <?php if ($canEdit): ?>
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('shipping', ['edit' => $m['id']]) ?>">ویرایش</a>
                                        <?= action_button(admin_url('shipping/toggle'), $m['is_active'] ? 'غیرفعال' : 'فعال', [
                                            'class' => 'btn btn-sm', 'fields' => ['method_id' => $m['id']]]) ?>
                                        <?= action_button(admin_url('shipping/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این روش ارسال؟',
                                            'fields' => ['method_id' => $m['id']]]) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
