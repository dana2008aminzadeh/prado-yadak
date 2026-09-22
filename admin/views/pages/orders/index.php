<?php use Admin\core\Auth; ?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('orders') ?>" class="filters">
            <div class="f" style="flex:2">
                <label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="کد رهگیری، نام، موبایل، بارنامه، مرجع بانکی">
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
            <div class="f">
                <label class="fl">شرکت حمل</label>
                <select name="carrier">
                    <option value="">همه</option>
                    <?php foreach ($carriers as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $carrier === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f">
                <label class="fl">از تاریخ</label>
                <input type="text" name="from" value="<?= e($fromJ) ?>" class="mono" data-jdp placeholder="۱۴۰۴/۰۱/۰۱" autocomplete="off">
            </div>
            <div class="f">
                <label class="fl">تا تاریخ</label>
                <input type="text" name="to" value="<?= e($toJ) ?>" class="mono" data-jdp placeholder="۱۴۰۴/۱۲/۲۹" autocomplete="off">
            </div>
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
                <tr><th>کد رهگیری</th><th>مشتری</th><th>اقلام</th><th>مبلغ کل</th>
                    <th>ارسال / بارنامه</th><th>وضعیت</th><th>تاریخ ثبت</th><th></th></tr>
            </thead>
            <tbody>
                <?php if (!$orders): ?><tr><td colspan="8" class="empty">سفارشی یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="mono" style="font-weight:700"><?= e($o['tracking_code']) ?></td>
                        <td>
                            <?php if ($o['user_id'] && can('users.view')): ?>
                                <a href="<?= admin_url('users/show/' . (int) $o['user_id']) ?>" style="font-weight:700"><?= e($o['full_name'] ?? 'حذف‌شده') ?></a>
                            <?php else: ?>
                                <span style="font-weight:700"><?= e($o['full_name'] ?? 'حذف‌شده') ?></span>
                            <?php endif; ?>
                            <div class="hint mono"><?= e($o['phone'] ?? '') ?></div>
                        </td>
                        <td><?= (int) $o['items_count'] ?> قلم</td>
                        <td style="font-weight:700;white-space:nowrap"><?= money($o['total_amount']) ?></td>
                        <td>
                            <?php if (!empty($o['shipping_carrier'])): ?>
                                <div style="font-size:11.5px;font-weight:700"><?= e($carriers[$o['shipping_carrier']] ?? $o['shipping_carrier']) ?></div>
                                <div class="hint mono" style="font-size:10px"><?= e($o['shipping_tracking_code'] ?: 'بدون کد') ?></div>
                            <?php else: ?>
                                <span class="hint">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= $colors[$o['status']] ?? 'b-gray' ?>"><?= e($statuses[$o['status']] ?? $o['status']) ?></span></td>
                        <td class="hint" style="white-space:nowrap"><?= e(shamsiTime($o['created_at'])) ?></td>
                        <td class="text-left">
                            <div class="flex gap" style="justify-content:flex-end">
                                <?php if (can('orders.invoice')): ?>
                                    <a class="btn btn-sm" target="_blank" rel="noopener" href="<?= admin_url('orders/invoice/' . $o['id']) ?>" title="فاکتور رسمی">
                                        <i data-lucide="printer" style="width:13px"></i>
                                    </a>
                                <?php endif; ?>
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
