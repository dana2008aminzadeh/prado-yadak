<?php
use Admin\core\Auth;
$sc = ['pending' => 'b-amber', 'completed' => 'b-green', 'failed' => 'b-red', 'cancelled' => 'b-gray'];
$canApprove = can('wallet.approve');
?>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">مجموع شارژ موفق</span><div class="val"><?= money($sums['charged']) ?></div></div>
    <div class="stat"><span class="lbl">خرید از کیف پول</span><div class="val"><?= money($sums['spent']) ?></div></div>
    <a class="stat warn" href="<?= admin_url('wallet', ['status' => 'pending']) ?>">
        <div class="ic-box"><i data-lucide="clock" style="width:17px"></i></div>
        <span class="lbl">در انتظار تأیید</span><div class="val"><?= money($sums['pending']) ?></div></a>
    <div class="stat"><span class="lbl">مجموع موجودی کاربران</span><div class="val"><?= money($sums['balances']) ?></div></div>
</div>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('wallet') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="کاربر، موبایل، کد پیگیری، توضیح"></div>
            <div class="f"><label class="fl">نوع</label>
                <select name="type"><option value="">همه</option>
                    <?php foreach ($types as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $type === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="f"><label class="fl">وضعیت</label>
                <select name="status"><option value="">همه</option>
                    <?php foreach ($statuses as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select></div>
            <div class="f"><label class="fl">از تاریخ</label>
                <input type="text" name="from" value="<?= e($fromJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <div class="f"><label class="fl">تا تاریخ</label>
                <input type="text" name="to" value="<?= e($toJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('wallet') ?>">حذف فیلتر</a>
            <a class="btn" href="<?= admin_url('wallet/export') ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>تراکنش‌ها (<?= money($pg['total']) ?>)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>کاربر</th><th>نوع</th><th>مبلغ</th><th>مانده پس از</th><th>کد پیگیری</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
                <?php if (!$txs): ?><tr><td colspan="8" class="empty">تراکنشی یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($txs as $t):
                    $plus = in_array($t['type'], ['charge', 'refund'], true); ?>
                    <tr style="<?= $t['status'] === 'pending' ? 'background:#fffdf5' : '' ?>">
                        <td>
                            <?php if (can('users.view')): ?>
                                <a href="<?= admin_url('users/show/' . (int) $t['user_id']) ?>" style="font-weight:700"><?= e($t['full_name'] ?? '—') ?></a>
                            <?php else: ?>
                                <span style="font-weight:700"><?= e($t['full_name'] ?? '—') ?></span>
                            <?php endif; ?>
                            <div class="hint mono"><?= e($t['phone'] ?? '') ?></div>
                        </td>
                        <td><?= e($types[$t['type']] ?? $t['type']) ?>
                            <div class="hint"><?= e(excerpt($t['description'], 40)) ?></div></td>
                        <td style="font-weight:800;white-space:nowrap;color:<?= $plus ? 'var(--green)' : 'var(--red)' ?>">
                            <?= $plus ? '+' : '−' ?> <?= money($t['amount']) ?></td>
                        <td class="hint"><?= $t['balance_after'] !== null ? money($t['balance_after']) : '—' ?></td>
                        <td class="mono hint" style="font-size:10px"><?= e($t['reference_code'] ?: '—') ?></td>
                        <td><span class="badge <?= $sc[$t['status']] ?? 'b-gray' ?>"><?= e($statuses[$t['status']] ?? $t['status']) ?></span></td>
                        <td class="hint"><?= e(shamsiTime($t['created_at'])) ?></td>
                        <td class="text-left">
                            <div class="flex gap" style="justify-content:flex-end">
                                <?php if ($t['status'] === 'pending' && $canApprove): ?>
                                    <?= action_button(admin_url('wallet/approve'), 'تأیید', [
                                        'class' => 'btn btn-sm btn-success',
                                        'confirm' => 'تأیید تراکنش ' . money($t['amount']) . ' تومان و اعمال روی موجودی کاربر؟',
                                        'fields' => ['tx_id' => $t['id']]]) ?>
                                    <?= action_button(admin_url('wallet/reject'), 'رد', [
                                        'class' => 'btn btn-sm', 'confirm' => 'این تراکنش رد شود؟',
                                        'fields' => ['tx_id' => $t['id']]]) ?>
                                <?php endif; ?>
                                <?php if ($t['status'] !== 'completed' && can('wallet.adjust')): ?>
                                    <?= action_button(admin_url('wallet/delete'), 'حذف', [
                                        'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این تراکنش؟',
                                        'fields' => ['tx_id' => $t['id']]]) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
