<?php
use Admin\core\Auth;
$n = $editing ?: ['id' => 0, 'page' => 'checkout', 'type' => 'info', 'title' => '', 'message' => '',
    'icon' => 'info', 'is_active' => 1, 'priority' => 0];
$types = ['info' => ['اطلاع‌رسانی (آبی)', 'b-blue'], 'warning' => ['هشدار (زرد)', 'b-amber'], 'danger' => ['خطر (قرمز)', 'b-red']];
$pages = ['global' => 'همه صفحات', 'checkout' => 'تسویه حساب', 'parts' => 'صفحه قطعات',
          'home' => 'صفحه اصلی', 'cart' => 'سبد خرید'];
$canEdit = can('notices.edit');
?>

<div class="grid g2" style="grid-template-columns:1fr 1.6fr;align-items:start">
    <?php if ($canEdit): ?>
        <form class="card" method="POST" action="<?= admin_url('notices/save') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
            <div class="card-head">
                <h3><?= $n['id'] ? 'ویرایش اطلاعیه' : 'اطلاعیه جدید' ?></h3>
                <?php if ($n['id']): ?><a class="btn btn-sm" href="<?= admin_url('notices') ?>">جدید</a><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="field"><label class="fl">عنوان *</label>
                    <input type="text" name="title" value="<?= e($n['title']) ?>" required></div>
                <div class="field"><label class="fl">متن اطلاعیه</label>
                    <textarea name="message" rows="4"><?= e($n['message']) ?></textarea></div>
                <div class="grid g2">
                    <div class="field"><label class="fl">صفحه نمایش</label>
                        <select name="page">
                            <?php foreach ($pages as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $n['page'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">نوع</label>
                        <select name="type">
                            <?php foreach ($types as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $n['type'] === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">آیکون (Lucide)</label>
                        <input type="text" name="icon" class="mono" value="<?= e($n['icon']) ?>"></div>
                    <div class="field"><label class="fl">اولویت نمایش</label>
                        <input type="number" name="priority" value="<?= (int) $n['priority'] ?>"></div>
                </div>
                <label class="chk"><input type="checkbox" name="is_active" value="1"
                    <?= (int) $n['is_active'] === 1 ? 'checked' : '' ?>><span>فعال باشد</span></label>
                <button class="btn btn-primary btn-block" type="submit">ذخیره اطلاعیه</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h3>اطلاعیه‌ها (<?= count($notices) ?>)</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>عنوان</th><th>صفحه</th><th>نوع</th><th>اولویت</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$notices): ?><tr><td colspan="6" class="empty">اطلاعیه‌ای ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($notices as $no): ?>
                        <tr>
                            <td><b><?= e($no['title']) ?></b>
                                <div class="hint" style="max-width:300px"><?= e(excerpt($no['message'], 80)) ?></div></td>
                            <td class="hint"><?= e($pages[$no['page']] ?? $no['page']) ?></td>
                            <td><span class="badge <?= $types[$no['type']][1] ?? 'b-gray' ?>"><?= e($types[$no['type']][0] ?? $no['type']) ?></span></td>
                            <td><?= (int) $no['priority'] ?></td>
                            <td><span class="badge <?= $no['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $no['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                            <td class="text-left">
                                <?php if ($canEdit): ?>
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('notices', ['edit' => $no['id']]) ?>">ویرایش</a>
                                        <?= action_button(admin_url('notices/toggle'), $no['is_active'] ? 'غیرفعال' : 'فعال', [
                                            'class' => 'btn btn-sm', 'fields' => ['notice_id' => $no['id']]]) ?>
                                        <?= action_button(admin_url('notices/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این اطلاعیه؟',
                                            'fields' => ['notice_id' => $no['id']]]) ?>
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
