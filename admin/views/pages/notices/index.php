<?php
use Admin\core\Auth;
$n = $editing ?: ['id' => 0, 'page' => 'checkout', 'type' => 'info', 'title' => '', 'message' => '', 'icon' => 'info', 'is_active' => 1, 'priority' => 0];
$types = ['info' => ['اطلاع‌رسانی (آبی)', 'b-blue'], 'warning' => ['هشدار (زرد)', 'b-amber'], 'danger' => ['خطر (قرمز)', 'b-red']];
$pages = ['global' => 'همه صفحات', 'checkout' => 'تسویه حساب', 'parts' => 'صفحه قطعات', 'home' => 'صفحه اصلی', 'cart' => 'سبد خرید'];
?>

<div class="grid g2" style="grid-template-columns:1fr 1.6fr;align-items:start">
    <form class="card" method="POST" action="<?= admin_url('notices/save') ?>">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
        <div class="card-head"><h3><?= $n['id'] ? 'ویرایش اطلاعیه' : 'اطلاعیه جدید' ?></h3>
            <?php if ($n['id']): ?><a class="btn btn-sm" href="<?= admin_url('notices') ?>">جدید</a><?php endif; ?></div>
        <div class="card-body">
            <div class="field"><label class="fl">عنوان *</label><input type="text" name="title" value="<?= e($n['title']) ?>" required></div>
            <div class="field"><label class="fl">متن اطلاعیه</label><textarea name="message" rows="4"><?= e($n['message']) ?></textarea></div>
            <div class="grid g2">
                <div class="field"><label class="fl">صفحه نمایش</label>
                    <select name="page">
                        <?php foreach ($pages as $k => $v): ?><option value="<?= e($k) ?>" <?= $n['page'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label class="fl">نوع</label>
                    <select name="type">
                        <?php foreach ($types as $k => $v): ?><option value="<?= e($k) ?>" <?= $n['type'] === $k ? 'selected' : '' ?>><?= e($v[0]) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label class="fl">آیکون (Lucide)</label><input type="text" name="icon" class="mono" value="<?= e($n['icon']) ?>"></div>
                <div class="field"><label class="fl">اولویت نمایش</label><input type="number" name="priority" value="<?= (int) $n['priority'] ?>"></div>
            </div>
            <label class="flex items-center gap" style="margin-bottom:14px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" style="width:auto" <?= $n['is_active'] ? 'checked' : '' ?>>
                <span style="font-size:13px;font-weight:700">فعال باشد</span></label>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره اطلاعیه</button>
        </div>
    </form>

    <div class="card">
        <div class="card-head"><h3>اطلاعیه‌ها</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>عنوان</th><th>صفحه</th><th>نوع</th><th>اولویت</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$notices): ?><tr><td colspan="6" class="empty">اطلاعیه‌ای ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($notices as $no): ?>
                        <tr>
                            <td><b><?= e($no['title']) ?></b><div class="hint" style="max-width:320px"><?= e(mb_substr($no['message'], 0, 90)) ?></div></td>
                            <td class="hint"><?= e($pages[$no['page']] ?? $no['page']) ?></td>
                            <td><span class="badge <?= $types[$no['type']][1] ?? 'b-gray' ?>"><?= e($types[$no['type']][0] ?? $no['type']) ?></span></td>
                            <td><?= (int) $no['priority'] ?></td>
                            <td><span class="badge <?= $no['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $no['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                            <td class="text-left">
                                <div class="flex gap" style="justify-content:flex-end">
                                    <a class="btn btn-sm" href="<?= admin_url('notices', ['edit' => $no['id']]) ?>">ویرایش</a>
                                    <a class="btn btn-sm" href="<?= admin_url('notices/toggle/' . $no['id']) ?>"><?= $no['is_active'] ? 'غیرفعال' : 'فعال' ?></a>
                                    <a class="btn btn-sm btn-danger" href="<?= admin_url('notices/delete/' . $no['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
