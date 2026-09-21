<?php
use Admin\core\Auth;
$s = $editing ?: ['id' => 0, 'title' => '', 'subtitle' => '', 'is_active' => 1, 'sort_order' => 0];
?>

<div class="grid g2" style="grid-template-columns:1fr 1.6fr;align-items:start">
    <form class="card" method="POST" action="<?= admin_url('shipping/save') ?>">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
        <div class="card-head"><h3><?= $s['id'] ? 'ویرایش روش ارسال' : 'روش ارسال جدید' ?></h3>
            <?php if ($s['id']): ?><a class="btn btn-sm" href="<?= admin_url('shipping') ?>">جدید</a><?php endif; ?></div>
        <div class="card-body">
            <div class="field"><label class="fl">عنوان *</label><input type="text" name="title" value="<?= e($s['title']) ?>" placeholder="پست پیشتاز" required></div>
            <div class="field"><label class="fl">توضیح کوتاه</label><input type="text" name="subtitle" value="<?= e($s['subtitle']) ?>" placeholder="تحویل ۲ تا ۴ روز کاری"></div>
            <div class="field"><label class="fl">ترتیب نمایش</label><input type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>"></div>
            <label class="flex items-center gap" style="margin-bottom:14px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" style="width:auto" <?= $s['is_active'] ? 'checked' : '' ?>>
                <span style="font-size:13px;font-weight:700">فعال باشد</span></label>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره</button>
        </div>
    </form>

    <div class="card">
        <div class="card-head"><h3>روش‌های ارسال</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ترتیب</th><th>عنوان</th><th>توضیح</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$methods): ?><tr><td colspan="5" class="empty">روشی تعریف نشده.</td></tr><?php endif; ?>
                    <?php foreach ($methods as $m): ?>
                        <tr>
                            <td class="hint"><?= (int) $m['sort_order'] ?></td>
                            <td><b><?= e($m['title']) ?></b></td>
                            <td class="hint"><?= e($m['subtitle'] ?: '—') ?></td>
                            <td><span class="badge <?= $m['is_active'] ? 'b-green' : 'b-gray' ?>"><?= $m['is_active'] ? 'فعال' : 'غیرفعال' ?></span></td>
                            <td class="text-left">
                                <div class="flex gap" style="justify-content:flex-end">
                                    <a class="btn btn-sm" href="<?= admin_url('shipping', ['edit' => $m['id']]) ?>">ویرایش</a>
                                    <a class="btn btn-sm" href="<?= admin_url('shipping/toggle/' . $m['id']) ?>"><?= $m['is_active'] ? 'غیرفعال' : 'فعال' ?></a>
                                    <a class="btn btn-sm btn-danger" href="<?= admin_url('shipping/delete/' . $m['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
