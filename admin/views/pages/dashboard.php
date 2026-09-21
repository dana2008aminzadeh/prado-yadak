<?php
$maxSum = max(1, max(array_column($series, 'sum')));
$statusLabels = ['processing' => 'در حال پردازش', 'shipped' => 'ارسال شده', 'delivered' => 'تحویل شده', 'cancelled' => 'لغو شده'];
$statusColors = ['processing' => 'b-amber', 'shipped' => 'b-blue', 'delivered' => 'b-green', 'cancelled' => 'b-red'];

$cards = [
    ['فروش کل (تومان)', money($stats['revenue_total']), 'banknote', admin_url('reports')],
    ['فروش ۳۰ روز اخیر', money($stats['revenue_month']), 'trending-up', admin_url('reports')],
    ['سفارش‌های امروز', $stats['orders_today'] . ' سفارش', 'shopping-bag', admin_url('orders')],
    ['در انتظار بررسی', $stats['orders_processing'] . ' سفارش', 'clock', admin_url('orders', ['status' => 'processing'])],
];
?>

<div class="grid g4 mb">
    <?php foreach ($cards as [$lbl, $val, $icon, $url]): ?>
        <a href="<?= e($url) ?>" class="stat">
            <div class="ic-box"><i data-lucide="<?= e($icon) ?>" style="width:18px;height:18px"></i></div>
            <span class="lbl"><?= e($lbl) ?></span>
            <div class="val"><?= e($val) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="grid g4 mb">
    <?php
    $mini = [
        ['کاربران', $stats['users_total'], '+' . $stats['users_month'] . ' در ۳۰ روز', admin_url('users')],
        ['محصولات', $stats['products_total'], $stats['products_out'] . ' ناموجود', admin_url('products')],
        ['تیکت باز', $stats['tickets_open'], 'نیازمند پاسخ', admin_url('tickets')],
        ['دیدگاه در انتظار', $stats['comments_pending'], 'نیازمند تأیید', admin_url('comments')],
    ];
    foreach ($mini as [$lbl, $val, $sub, $url]): ?>
        <a href="<?= e($url) ?>" class="stat">
            <span class="lbl"><?= e($lbl) ?></span>
            <div class="val"><?= money($val) ?></div>
            <div class="hint"><?= e($sub) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<div class="card mb">
    <div class="card-head">
        <h3>روند فروش ۱۴ روز اخیر</h3>
        <span class="hint">مبالغ به تومان</span>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:flex-end;gap:8px;height:190px">
            <?php foreach ($series as $s):
                $h = max(3, (int) round(($s['sum'] / $maxSum) * 160)); ?>
                <div style="flex:1;text-align:center" title="<?= e($s['label']) ?> — <?= money($s['sum']) ?> تومان / <?= $s['count'] ?> سفارش">
                    <div style="font-size:9px;color:var(--muted);margin-bottom:4px"><?= $s['count'] ?: '' ?></div>
                    <div style="height:<?= $h ?>px;background:linear-gradient(180deg,#a5664a,#8b533a);border-radius:8px 8px 3px 3px"></div>
                    <div style="font-size:9px;color:var(--muted);margin-top:6px;white-space:nowrap"><?= e(mb_substr($s['label'], 5)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid g2 mb" style="grid-template-columns:1.6fr 1fr">
    <div class="card">
        <div class="card-head">
            <h3>آخرین سفارش‌ها</h3>
            <a href="<?= admin_url('orders') ?>" class="btn btn-sm">همه سفارش‌ها</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>کد رهگیری</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr>
                </thead>
                <tbody>
                    <?php if (!$recentOrders): ?>
                        <tr><td colspan="6" class="empty">هنوز سفارشی ثبت نشده است.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td class="mono"><?= e($o['tracking_code']) ?></td>
                            <td>
                                <div style="font-weight:700"><?= e($o['full_name'] ?? 'حذف‌شده') ?></div>
                                <div class="hint mono"><?= e($o['phone'] ?? '') ?></div>
                            </td>
                            <td><?= money($o['total_amount']) ?></td>
                            <td><span class="badge <?= $statusColors[$o['status']] ?? 'b-gray' ?>"><?= e($statusLabels[$o['status']] ?? $o['status']) ?></span></td>
                            <td class="hint"><?= e(toShamsi($o['created_at'])) ?></td>
                            <td class="text-left"><a class="btn btn-sm" href="<?= admin_url('orders/show/' . $o['id']) ?>">جزئیات</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>وضعیت سفارش‌ها</h3></div>
        <div class="card-body">
            <?php
            $totalOrders = max(1, array_sum(array_column($statusBreakdown, 'c')));
            foreach ($statusBreakdown as $sb):
                $pct = round(($sb['c'] / $totalOrders) * 100); ?>
                <div style="margin-bottom:14px">
                    <div class="flex items-center" style="justify-content:space-between;margin-bottom:5px">
                        <span style="font-size:12px;font-weight:700"><?= e($statusLabels[$sb['status']] ?? $sb['status']) ?></span>
                        <span class="hint"><?= (int) $sb['c'] ?> (<?= $pct ?>٪)</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$statusBreakdown): ?><div class="empty">داده‌ای موجود نیست.</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="grid g3">
    <div class="card">
        <div class="card-head"><h3>پرفروش‌ترین محصولات</h3></div>
        <div class="card-body">
            <?php if (!$topProducts): ?><div class="empty">داده‌ای موجود نیست.</div><?php endif; ?>
            <?php foreach ($topProducts as $p): ?>
                <a href="<?= admin_url('products/edit/' . $p['id']) ?>" class="flex items-center gap" style="gap:10px;padding:8px 0;border-bottom:1px solid var(--line)">
                    <img class="thumb" src="<?= $p['telegram_photo_id'] ? '/image?id=' . urlencode($p['telegram_photo_id']) : '/assets/logo/logo.webp' ?>" alt="" loading="lazy">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['name']) ?></div>
                        <div class="hint"><?= (int) $p['qty'] ?> فروش — <?= money($p['revenue']) ?> تومان</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>کاربران جدید</h3><a href="<?= admin_url('users') ?>" class="btn btn-sm">همه</a></div>
        <div class="card-body">
            <?php if (!$recentUsers): ?><div class="empty">کاربری ثبت نشده.</div><?php endif; ?>
            <?php foreach ($recentUsers as $u): ?>
                <a href="<?= admin_url('users/show/' . $u['id']) ?>" class="flex items-center gap" style="gap:10px;padding:8px 0;border-bottom:1px solid var(--line)">
                    <div class="avatar"><?= e(mb_substr($u['full_name'] ?: 'ک', 0, 1, 'UTF-8')) ?></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700"><?= e($u['full_name'] ?: 'بدون نام') ?></div>
                        <div class="hint mono"><?= e($u['phone']) ?></div>
                    </div>
                    <span class="hint"><?= e(toShamsi($u['created_at'])) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>تیکت‌های باز</h3><a href="<?= admin_url('tickets') ?>" class="btn btn-sm">همه</a></div>
        <div class="card-body">
            <?php if (!$openTickets): ?><div class="empty">تیکت بازی وجود ندارد. 🎉</div><?php endif; ?>
            <?php foreach ($openTickets as $t): ?>
                <a href="<?= admin_url('tickets/show/' . $t['id']) ?>" style="display:block;padding:8px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:12px;font-weight:700"><?= e($t['subject']) ?></div>
                    <div class="hint"><?= e($t['full_name'] ?? '—') ?> — <?= e(shamsiTime($t['updated_at'])) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
