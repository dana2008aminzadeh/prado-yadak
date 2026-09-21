<?php
$sc = ['open' => 'b-amber', 'answered' => 'b-green', 'pending' => 'b-blue', 'closed' => 'b-gray'];
$pc = ['low' => 'b-gray', 'normal' => 'b-blue', 'high' => 'b-red'];
?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('tickets') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label><input type="search" name="q" value="<?= e($q) ?>" placeholder="موضوع، نام کاربر، موبایل"></div>
            <div class="f"><label class="fl">وضعیت</label>
                <select name="status"><option value="">همه</option>
                    <?php foreach ($statuses as $k => $v): ?><option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                </select></div>
            <div class="f"><label class="fl">اولویت</label>
                <select name="priority"><option value="">همه</option>
                    <?php foreach ($priorities as $k => $v): ?><option value="<?= e($k) ?>" <?= $priority === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                </select></div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('tickets') ?>">حذف فیلتر</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>تیکت‌ها (<?= money($pg['total']) ?>)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>موضوع</th><th>کاربر</th><th>پیام‌ها</th><th>اولویت</th><th>وضعیت</th><th>آخرین بروزرسانی</th><th></th></tr></thead>
            <tbody>
                <?php if (!$tickets): ?><tr><td colspan="8" class="empty">تیکتی یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td class="mono hint">#<?= (int) $t['id'] ?></td>
                        <td style="font-weight:700;max-width:280px"><?= e($t['subject']) ?></td>
                        <td><?= e($t['full_name'] ?? '—') ?><div class="hint mono"><?= e($t['phone'] ?? '') ?></div></td>
                        <td><?= (int) $t['msg_count'] ?></td>
                        <td><span class="badge <?= $pc[$t['priority']] ?? 'b-gray' ?>"><?= e($priorities[$t['priority']] ?? $t['priority']) ?></span></td>
                        <td><span class="badge <?= $sc[$t['status']] ?? 'b-gray' ?>"><?= e($statuses[$t['status']] ?? $t['status']) ?></span></td>
                        <td class="hint"><?= e(shamsiTime($t['updated_at'])) ?></td>
                        <td class="text-left"><a class="btn btn-sm btn-primary" href="<?= admin_url('tickets/show/' . $t['id']) ?>">پاسخ</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
