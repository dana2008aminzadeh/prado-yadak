<?php
$colors = ['processing' => 'b-amber', 'shipped' => 'b-blue', 'delivered' => 'b-green', 'cancelled' => 'b-red'];
?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('orders') ?>" class="filters">
            <div class="f" style="flex:2">
                <label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="کد رهگیری، نام مشتری، موبایل، مرجع بانکی">
            </div>
            <div class="f">
                <label class="fl">وضعیت</label>
                <select name="status">
                    <option value="">همه</option>
                    <?php foreach ($statuses as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f"><label class="fl">از تاریخ (میلادی)</label><input type="date" name="from" value="<?= e($from) ?>"></div>
            <div class="f"><label class="fl">تا تاریخ</label><input type="date" name="to" value="<?= e($to) ?>"></div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('orders') ?>">حذف فیلتر</a>
            <a class="btn" href="<?= admin_url('orders/export') ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>سفارش‌ها (<?= money($pg['total']) ?>)</h3>
        <span class="hint">مجموع فروش فیلترشده: <b><?= money($sumFiltered) ?></b> تومان</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>کد رهگیری</th><th>مشتری</th><th>اقلام</th><th>مبلغ کل</th><th>کوپن</th><th>وضعیت</th><th>تاریخ ثبت</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?><tr><td colspan="8" class="empty">سفارشی یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="mono" style="font-weight:700"><?= e($o['tracking_code']) ?></td>
                        <td>
                            <a href="<?= admin_url('users/show/' . (int) $o['user_id']) ?>" style="font-weight:700"><?= e($o['full_name'] ?? 'کاربر حذف‌شده') ?></a>
                            <div class="hint mono"><?= e($o['phone'] ?? '') ?></div>
                        </td>
                        <td><?= (int) $o['items_count'] ?> قلم</td>
                        <td style="font-weight:700"><?= money($o['total_amount']) ?></td>
                        <td class="hint mono"><?= e($o['applied_coupon'] ?: '—') ?></td>
                        <td><span class="badge <?= $colors[$o['status']] ?? 'b-gray' ?>"><?= e($statuses[$o['status']] ?? $o['status']) ?></span></td>
                        <td class="hint"><?= e(shamsiTime($o['created_at'])) ?></td>
                        <td class="text-left">
                            <div class="flex gap" style="justify-content:flex-end">
                                <a class="btn btn-sm" target="_blank" href="<?= admin_url('orders/invoice/' . $o['id']) ?>"><i data-lucide="printer" style="width:13px"></i></a>
                                <a class="btn btn-sm btn-primary" href="<?= admin_url('orders/show/' . $o['id']) ?>">مدیریت</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
