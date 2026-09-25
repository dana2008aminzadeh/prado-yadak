<?php
use Admin\core\Auth;
$s = $editing ?: ['id' => 0, 'title' => '', 'subtitle' => '', 'cost' => 0, 'free_above' => '',
    'carrier_slug' => '', 'is_active' => 1, 'sort_order' => 0];
$canEdit = can('shipping.edit');
?>

<style>
    .shipping-actions { justify-content: flex-end; flex-wrap: wrap; }
    @media (max-width: 1100px) {
        /* کنار هم بودن فرم و فهرست، عرض جدول را در نمایشگرهای کوچک محدود می‌کند. */
        .shipping-layout { grid-template-columns: minmax(0, 1fr) !important; }
    }
    @media (max-width: 720px) {
        .shipping-table, .shipping-table tbody { display: block; width: 100%; }
        .shipping-table thead { display: none; }
        .shipping-table tr.shipping-method { display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px 12px;
            padding: 14px; border-bottom: 1px solid var(--line); }
        .shipping-table tr.shipping-method:last-child { border-bottom: 0; }
        .shipping-table .shipping-method td { display: block; min-width: 0; padding: 0;
            border: 0; overflow-wrap: anywhere; }
        .shipping-table .shipping-method td[data-label]::before { content: attr(data-label);
            display: block; color: var(--muted); font-size: 10.5px; font-weight: 700; margin-bottom: 3px; }
        .shipping-table .method-title { grid-column: 1 / -1; grid-row: 1; font-size: 13px; }
        .shipping-table .method-order { grid-column: 1; grid-row: 2; }
        .shipping-table .method-carrier { grid-column: 2; grid-row: 2; }
        .shipping-table .method-cost { grid-column: 1; grid-row: 3; }
        .shipping-table .method-status { grid-column: 2; grid-row: 3; }
        .shipping-table .method-actions { grid-column: 1 / -1; grid-row: 4; padding-top: 4px; }
        .shipping-table .shipping-actions { justify-content: flex-start; }
        .shipping-table .shipping-actions .btn { min-height: 38px; }
        .shipping-table tr.shipping-empty, .shipping-table tr.shipping-empty td { display: block; }
    }
</style>

<div class="grid g2 shipping-layout" style="grid-template-columns:<?= $canEdit ? '1fr 1.6fr' : 'minmax(0,1fr)' ?>;align-items:start">
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
            <table class="shipping-table">
                <thead><tr><th>ترتیب</th><th>عنوان</th><th>شرکت حمل</th><th>هزینه</th><th>وضعیت</th><?php if ($canEdit): ?><th>عملیات</th><?php endif; ?></tr></thead>
                <tbody>
                    <?php if (!$methods): ?><tr class="shipping-empty"><td colspan="<?= $canEdit ? 6 : 5 ?>" class="empty">روشی تعریف نشده.</td></tr><?php endif; ?>
                    <?php foreach ($methods as $m): ?>
                        <tr class="shipping-method">
                            <td class="hint method-order" data-label="ترتیب"><?= (int) $m['sort_order'] ?></td>
                            <td class="method-title"><b><?= e($m['title']) ?></b><div class="hint"><?= e($m['subtitle'] ?: '—') ?></div></td>
                            <td class="hint method-carrier" data-label="شرکت حمل"><?= e($carriers[$m['carrier_slug'] ?? ''] ?? '—') ?></td>
                            <td class="method-cost" data-label="هزینه">
                                <?= (float) ($m['cost'] ?? 0) > 0 ? money($m['cost']) . ' ت' : '<span class="badge b-green">رایگان</span>' ?>
                                <?php if (!empty($m['free_above'])): ?>
                                    <div class="hint">رایگان بالای <?= money($m['free_above']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="method-status" data-label="وضعیت"><span class="badge <?= $m['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $m['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                            <?php if ($canEdit): ?>
                                <td class="text-left method-actions">
                                    <div class="flex gap shipping-actions">
                                        <a class="btn btn-sm" href="<?= admin_url('shipping', ['edit' => $m['id']]) ?>">ویرایش</a>
                                        <?= action_button(admin_url('shipping/toggle'), $m['is_active'] ? 'غیرفعال' : 'فعال', [
                                            'class' => 'btn btn-sm', 'fields' => ['method_id' => $m['id']]]) ?>
                                        <?= action_button(admin_url('shipping/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این روش ارسال؟',
                                            'fields' => ['method_id' => $m['id']]]) ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
