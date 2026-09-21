<?php
$sLabels = ['processing' => 'در حال پردازش', 'shipped' => 'ارسال شده', 'delivered' => 'تحویل شده', 'cancelled' => 'لغو شده'];
$maxDaily = max(1, max(array_merge([0], array_column($daily, 's'))));
?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('reports') ?>" class="filters">
            <div class="f"><label class="fl">از تاریخ (میلادی)</label><input type="date" name="from" value="<?= e($from) ?>"></div>
            <div class="f"><label class="fl">تا تاریخ</label><input type="date" name="to" value="<?= e($to) ?>"></div>
            <button class="btn btn-primary" type="submit">اعمال بازه</button>
            <a class="btn" href="<?= admin_url('reports', ['from' => date('Y-m-d', strtotime('-7 days')), 'to' => date('Y-m-d')]) ?>">۷ روز</a>
            <a class="btn" href="<?= admin_url('reports', ['from' => date('Y-m-d', strtotime('-30 days')), 'to' => date('Y-m-d')]) ?>">۳۰ روز</a>
            <a class="btn" href="<?= admin_url('reports', ['from' => date('Y-m-d', strtotime('-365 days')), 'to' => date('Y-m-d')]) ?>">یک سال</a>
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
    <div class="card-head"><h3>فروش روزانه</h3><span class="hint">مجموع تخفیف‌ها: <?= money($summary['discounts'] ?? 0) ?> تومان</span></div>
    <div class="card-body">
        <?php if (!$daily): ?><div class="empty">در این بازه فروشی ثبت نشده.</div><?php else: ?>
            <div style="display:flex;align-items:flex-end;gap:4px;height:200px;overflow-x:auto">
                <?php foreach ($daily as $d):
                    $h = max(3, (int) round(($d['s'] / $maxDaily) * 170)); ?>
                    <div style="min-width:26px;flex:1;text-align:center" title="<?= e(toShamsi($d['d'])) ?> — <?= money($d['s']) ?> تومان">
                        <div style="height:<?= $h ?>px;background:linear-gradient(180deg,#a5664a,#8b533a);border-radius:6px 6px 2px 2px"></div>
                        <div style="font-size:8.5px;color:var(--muted);margin-top:5px"><?= e(mb_substr(toShamsi($d['d']), 5)) ?></div>
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
                <thead><tr><th>محصول</th><th>تعداد</th><th>درآمد</th></tr></thead>
                <tbody>
                    <?php if (!$topProducts): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topProducts as $p): ?>
                        <tr>
                            <td><a href="<?= admin_url('products/edit/' . $p['id']) ?>"><?= e($p['name']) ?></a></td>
                            <td><?= (int) $p['qty'] ?></td>
                            <td style="font-weight:700"><?= money($p['revenue']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>فروش به تفکیک دسته</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>دسته</th><th>تعداد</th><th>درآمد</th></tr></thead>
                <tbody>
                    <?php if (!$topCategories): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($topCategories as $c): ?>
                        <tr><td><?= e($c['name']) ?></td><td><?= (int) $c['qty'] ?></td><td style="font-weight:700"><?= money($c['revenue']) ?></td></tr>
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
        <div class="card-head"><h3>وضعیت سفارش‌ها در بازه</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>وضعیت</th><th>تعداد</th><th>مبلغ</th></tr></thead>
                <tbody>
                    <?php if (!$byStatus): ?><tr><td colspan="3" class="empty">داده‌ای نیست.</td></tr><?php endif; ?>
                    <?php foreach ($byStatus as $s): ?>
                        <tr><td><?= e($sLabels[$s['status']] ?? $s['status']) ?></td><td><?= (int) $s['c'] ?></td><td><?= money($s['s']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-head" style="border-top:1px solid var(--line)"><h3>کدهای تخفیف پراستفاده</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>کد</th><th>استفاده</th><th>تخفیف</th></tr></thead>
                <tbody>
                    <?php if (!$coupons): ?><tr><td colspan="3" class="empty">استفاده‌ای ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($coupons as $c): ?>
                        <tr><td class="mono"><?= e($c['code']) ?></td><td><?= (int) $c['uses'] ?></td><td><?= money($c['total']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>محصولات ناموجود</h3></div>
        <div class="card-body">
            <?php if (!$lowStock): ?><div class="empty">همه محصولات موجودند. 🎉</div><?php endif; ?>
            <?php foreach ($lowStock as $p): ?>
                <a href="<?= admin_url('products/edit/' . $p['id']) ?>" style="display:block;padding:7px 0;border-bottom:1px solid var(--line);font-size:12px">
                    <?= e($p['name']) ?><span class="hint"> — <?= money($p['price']) ?> تومان</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
