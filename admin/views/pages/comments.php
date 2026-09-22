<?php
use Admin\core\Auth;
$sc = ['pending' => ['b-amber', 'در انتظار'], 'approved' => ['b-green', 'تأییدشده'], 'rejected' => ['b-red', 'ردشده']];
$canMod = can('comments.moderate');
?>

<div class="grid g3 mb">
    <a class="stat warn" href="<?= admin_url('comments', ['status' => 'pending']) ?>">
        <span class="lbl">در انتظار تأیید</span><div class="val"><?= money($counts['pending']) ?></div></a>
    <a class="stat" href="<?= admin_url('comments', ['status' => 'approved']) ?>">
        <span class="lbl">تأییدشده</span><div class="val"><?= money($counts['approved']) ?></div></a>
    <a class="stat" href="<?= admin_url('comments', ['status' => 'rejected']) ?>">
        <span class="lbl">ردشده</span><div class="val"><?= money($counts['rejected']) ?></div></a>
</div>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('comments') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="نام کاربر، متن دیدگاه، محصول"></div>
            <div class="f"><label class="fl">وضعیت</label>
                <select name="status">
                    <option value="">همه</option>
                    <?php foreach ($sc as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v[1]) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('comments') ?>">حذف فیلتر</a>
        </form>
    </div>
</div>

<form method="POST" action="<?= admin_url('comments/bulk') ?>" onsubmit="return confirmBulk()">
    <?= Auth::csrfField() ?>
    <div class="card">
        <div class="card-head">
            <h3>دیدگاه‌ها (<?= money($pg['total']) ?>)</h3>
            <?php if ($canMod): ?>
                <div class="flex gap">
                    <select name="bulk_action" id="bulkAction" style="width:auto">
                        <option value="">عملیات گروهی…</option>
                        <option value="approved">تأیید</option>
                        <option value="rejected">رد</option>
                        <option value="pending">بازگشت به انتظار</option>
                        <option value="delete">حذف</option>
                    </select>
                    <button class="btn" type="submit">اجرا</button>
                </div>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr>
                    <th style="width:32px"><input type="checkbox" onclick="document.querySelectorAll('.rowchk').forEach(c=>c.checked=this.checked)"></th>
                    <th>کاربر</th><th>محصول</th><th>امتیاز</th><th>متن دیدگاه</th><th>وضعیت</th><th>تاریخ</th><th></th>
                </tr></thead>
                <tbody>
                    <?php if (!$comments): ?><tr><td colspan="8" class="empty">دیدگاهی یافت نشد.</td></tr><?php endif; ?>
                    <?php foreach ($comments as $c): ?>
                        <tr>
                            <td><input class="rowchk" type="checkbox" name="ids[]" value="<?= (int) $c['id'] ?>"></td>
                            <td><div style="font-weight:700"><?= e($c['name']) ?></div>
                                <div class="hint mono"><?= e($c['phone'] ?? 'مهمان') ?></div></td>
                            <td class="hint"><a href="<?= admin_url('products/edit/' . (int) $c['product_id']) ?>"><?= e(excerpt($c['product_name'] ?? '—', 30)) ?></a></td>
                            <td style="color:#d97706;white-space:nowrap"><?= str_repeat('★', (int) $c['rating']) . str_repeat('☆', 5 - (int) $c['rating']) ?></td>
                            <td style="max-width:320px"><?= nl2br(e($c['comment_text'])) ?></td>
                            <td><span class="badge <?= $sc[$c['status']][0] ?? 'b-gray' ?>"><?= e($sc[$c['status']][1] ?? $c['status']) ?></span></td>
                            <td class="hint"><?= e(toShamsi($c['created_at'])) ?></td>
                            <td class="text-left">
                                <?php if ($canMod): ?>
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <?php if ($c['status'] !== 'approved'): ?>
                                            <?= action_button(admin_url('comments/approve'), 'تأیید', [
                                                'class' => 'btn btn-sm btn-success', 'fields' => ['comment_id' => $c['id']]]) ?>
                                        <?php endif; ?>
                                        <?php if ($c['status'] !== 'rejected'): ?>
                                            <?= action_button(admin_url('comments/reject'), 'رد', [
                                                'class' => 'btn btn-sm', 'fields' => ['comment_id' => $c['id']]]) ?>
                                        <?php endif; ?>
                                        <?= action_button(admin_url('comments/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این دیدگاه؟',
                                            'fields' => ['comment_id' => $c['id']]]) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
    </div>
</form>

<script>
function confirmBulk() {
    const a = document.getElementById('bulkAction');
    if (!a || !a.value) { alert('یک عملیات انتخاب کنید.'); return false; }
    const n = document.querySelectorAll('.rowchk:checked').length;
    if (!n) { alert('هیچ دیدگاهی انتخاب نشده است.'); return false; }
    return confirm(`عملیات روی ${n} دیدگاه اجرا شود؟`);
}
</script>
