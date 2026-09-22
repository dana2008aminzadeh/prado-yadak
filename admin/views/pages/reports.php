<?php
use Admin\controllers\OrderController;
$sLabels = OrderController::STATUSES;
$carriers = OrderController::CARRIERS;
$maxDaily = max(1, max(array_merge([0], array_map('floatval', array_column($daily, 's')))));
?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('reports') ?>" class="filters">
            <div class="f"><label class="fl">از تاریخ</label>
                <input type="text" name="from" value="<?= e($fromJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <div class="f"><label class="fl">تا تاریخ</label>
                <input type="text" name="to" value="<?= e($toJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <button class="btn btn-primary" type="submit">اعمال بازه</button>
            <a class="btn" href="<?= admin_url('reports', ['from' => dateToJalali(date('Y-m-d', strtotime('-7 days'))), 'to' => dateToJalali(date('Y-m-d'))]) ?>">۷ روز</a>
            <a class="btn" href="<?= admin_url('reports', ['from' => dateToJalali(date('Y-m-d', strtotime('-30 days'))), 'to' => dateToJalali(date('Y-m-d'))]) ?>">۳۰ روز</a>
            <a class="btn" href="<?= admin_url('reports', ['from' => dateToJalali(date('Y-m-d', strtotime('-365 days'))), 'to' => dateToJalali(date('Y-m-d'))]) ?>">یک سال</a>
            <?php if (can('reports.export')): ?>
                <a class="btn" href="<?= admin_url('reports/export', ['from' => $fromJ, 'to' => $toJ]) ?>">
                    <i data-lucide="download" style="width:14px"></i> خروجی اکسل
                </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">درآمد بازه (تومان)</span><div class="val"><?= money($summary['revenue'] ?? 0) ?></div></div>
    <div class="stat"><span class="lbl">تعداد سفارش</span><div class="val"><?= money($summary['orders'] ?? 0) ?></div></div>
    <div class="stat"><span class="lbl">میانگین سبد خرید</span><div class="val"><?= money($summary['avg_order'] ?? 0) ?></div></div>
    <div class="stat"><span class="lbl">کاربران جدید</span><div class="val"><?= money($newUsers) ?></div></div>
</div>

<div class="card mb">
    <div class="card-head">
        <h3>فروش روزانه</h3>
        <span class="hint">
            تخفیف‌ها: <?= money($summary['discounts'] ?? 0) ?> ت —
            هزینه ارسال: <?= money($summary['shipping'] ?? 0) ?> ت
        </span>
    </div>
    <div class="card-body">
        <?php if (!$daily): ?><div class="empty">در این بازه فروشی ثبت نشده.</div><?php else: ?>
            <div style="display:flex;align-items:flex-end;gap:3px;height:195px;overflow-x:auto">
                <?php foreach ($daily as $d):
                    $h = max(3, (int) round(((float) $d['s'] / $maxDaily) * 165)); ?>
                    <div style="min-width:24px;flex:1;text-align:center" title="<?= e(toShamsi($d['d'])) ?> — <?= money($d['s']) ?> تومان / <?= (int) $d['c'] ?> سفارش">
                        <div style="height:<?= $h ?>px;background:linear-gradient(180deg,#a5664a,#8b533a);border-radius:5px 5px 2px 2px"></div>
                        <div style="font-size:8px;color:var(--muted);margin-top:4px"><?= e(mb_substr(toShamsi($d['d']), 5)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid g2 mb">
    <div class="card">
        <div class="card-head"><h3>پرفروش‌ترین محصولات</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>محصول</th><th>تعداد</th><th>موجودی</th><th>درآمد</th></tr></thead>
                <tbody>
                    <?php if (!$topProducts): ?><tr><td colspan="4" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topProducts as $p): ?>
                        <tr>
                            <td><a href="<?= admin_url('products/edit/' . $p['id']) ?>"><?= e(excerpt($p['name'], 40)) ?></a></td>
                            <td><?= (int) $p['qty'] ?></td>
                            <td><span class="badge <?= (int) $p['stock_qty'] <= 0 ? 'b-red' : 'b-green' ?>"><?= (int) $p['stock_qty'] ?></span></td>
                            <td style="font-weight:700"><?= money($p['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>فروش به تفکیک خودرو</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>مدل خودرو</th><th>تعداد قطعه</th><th>درآمد</th></tr></thead>
                <tbody>
                    <?php if (!$topVehicles): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topVehicles as $v): ?>
                        <tr><td><?= e($v['name']) ?></td><td><?= (int) $v['qty'] ?></td>
                            <td style="font-weight:700"><?= money($v['revenue']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-head" style="border-top:1px solid var(--line)"><h3>فروش به تفکیک دسته</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>دسته</th><th>تعداد</th><th>درآمد</th></tr></thead>
                <tbody>
                    <?php if (!$topCategories): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topCategories as $c): ?>
                        <tr><td><?= e($c['name']) ?></td><td><?= (int) $c['qty'] ?></td>
                            <td style="font-weight:700"><?= money($c['revenue']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid g3">
    <div class="card">
        <div class="card-head"><h3>مشتریان برتر</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>مشتری</th><th>سفارش</th><th>مبلغ</th></tr></thead>
                <tbody>
                    <?php if (!$topCustomers): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topCustomers as $c): ?>
                        <tr>
                            <td><a href="<?= admin_url('users/show/' . $c['id']) ?>"><?= e($c['full_name'] ?: $c['phone']) ?></a></td>
                            <td><?= (int) $c['orders'] ?></td>
                            <td style="font-weight:700"><?= money($c['spent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>وضعیت سفارش‌ها</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>وضعیت</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                    <?php if (!$byStatus): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($byStatus as $s): ?>
                        <tr><td><?= e($sLabels[$s['status']] ?? $s['status']) ?></td>
                            <td><?= (int) $s['c'] ?></td><td><?= money($s['s']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-head" style="border-top:1px solid var(--line)"><h3>ارسال به تفکیک شرکت حمل</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>شرکت</th><th>مرسوله</th><th>هزینه</th></tr></thead>
                <tbody>
                    <?php if (!$byCarrier): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($byCarrier as $c): ?>
                        <tr><td><?= e($carriers[$c['carrier']] ?? $c['carrier']) ?></td>
                            <td><?= (int) $c['c'] ?></td><td><?= money($c['cost']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card mb">
            <div class="card-head"><h3>کدهای تخفیف پراستفاده</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>کد</th><th>استفاده</th><th>تخفیف</th></tr></thead>
                    <tbody>
                        <?php if (!$coupons): ?><tr><td colspan="3" class="empty">استفاده‌ای ثبت نشده.</td></tr><?php endif; ?>
                        <?php foreach ($coupons as $c): ?>
                            <tr><td class="mono"><?= e($c['code']) ?></td><td><?= (int) $c['uses'] ?></td>
                                <td><?= money($c['total']) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb">
            <div class="card-head"><h3>آمار پیامک بازه</h3></div>
            <div class="card-body">
                <div class="flex between" style="padding:4px 0"><span class="hint">کل ارسال</span><b><?= money($smsStats['total'] ?? 0) ?></b></div>
                <div class="flex between" style="padding:4px 0"><span class="hint">موفق</span>
                    <b style="color:var(--green)"><?= money($smsStats['sent'] ?? 0) ?></b></div>
                <div class="flex between" style="padding:4px 0"><span class="hint">ناموفق</span>
                    <b style="color:var(--red)"><?= money($smsStats['failed'] ?? 0) ?></b></div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>نیازمند تأمین موجودی</h3></div>
            <div class="card-body">
                <?php if (!$lowStock): ?><div class="empty">موجودی همه محصولات کافی است. 🎉</div><?php endif; ?>
                <?php foreach ($lowStock as $p): ?>
                    <a href="<?= admin_url('products/edit/' . $p['id']) ?>"
                       class="flex between items-center" style="padding:6px 0;border-bottom:1px solid var(--line);gap:8px">
                        <span style="font-size:11.5px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['name']) ?></span>
                        <span class="badge <?= (int) $p['stock_qty'] <= 0 ? 'b-red' : 'b-amber' ?>"><?= (int) $p['stock_qty'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
