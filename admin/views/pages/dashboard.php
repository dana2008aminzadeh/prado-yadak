<?php
use Admin\core\Audit;

$maxSum = max(1, max(array_column($series, 'sum')));
$growth = $stats['revenue_growth'];
?>

<div class="grid g4 mb">
    <div class="stat">
        <div class="ic-box"><i data-lucide="banknote" style="width:17px"></i></div>
        <span class="lbl">فروش امروز (تومان)</span>
        <div class="val"><?= money($stats['revenue_today']) ?></div>
        <div class="hint"><?= (int) $stats['orders_today'] ?> سفارش امروز</div>
    </div>
    <div class="stat">
        <div class="ic-box"><i data-lucide="trending-up" style="width:17px"></i></div>
        <span class="lbl">فروش ۳۰ روز اخیر</span>
        <div class="val"><?= money($stats['revenue_month']) ?></div>
        <?php if ($growth !== null): ?>
            <div class="hint" style="color:<?= $growth >= 0 ? 'var(--green)' : 'var(--red)' ?>">
                <?= $growth >= 0 ? '▲' : '▼' ?> <?= abs($growth) ?>٪ نسبت به ماه قبل
            </div>
        <?php endif; ?>
    </div>
    <a class="stat <?= $stats['orders_processing'] > 0 ? 'warn' : '' ?>" href="<?= admin_url('orders', ['status' => 'processing']) ?>">
        <div class="ic-box"><i data-lucide="clock" style="width:17px"></i></div>
        <span class="lbl">در انتظار بررسی</span>
        <div class="val"><?= money($stats['orders_processing']) ?></div>
        <div class="hint"><?= (int) $stats['orders_packing'] ?> در حال بسته‌بندی</div>
    </a>
    <div class="stat">
        <div class="ic-box"><i data-lucide="warehouse" style="width:17px"></i></div>
        <span class="lbl">ارزش موجودی انبار</span>
        <div class="val"><?= money($stats['stock_value']) ?></div>
        <div class="hint"><?= (int) $stats['products_total'] ?> محصول</div>
    </div>
</div>

<div class="grid g4 mb">
    <?php
    $mini = [
        ['کاربران', $stats['users_total'], '+' . $stats['users_month'] . ' در ۳۰ روز', admin_url('users'), 'users.view'],
        ['رو به اتمام', $stats['products_low'], $stats['products_out'] . ' ناموجود', admin_url('products', ['stock' => 'low']), 'products.view'],
        ['تیکت باز', $stats['tickets_open'], 'نیازمند پاسخ', admin_url('tickets'), 'tickets.view'],
        ['دیدگاه در انتظار', $stats['comments_pending'], 'نیازمند تأیید', admin_url('comments', ['status' => 'pending']), 'comments.view'],
    ];
    foreach ($mini as [$lbl, $val, $sub, $url, $perm]):
        if (!can($perm)) continue; ?>
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
        <div style="display:flex;align-items:flex-end;gap:7px;height:185px">
            <?php foreach ($series as $s):
                $h = max(3, (int) round(($s['sum'] / $maxSum) * 155)); ?>
                <div style="flex:1;text-align:center" title="<?= e($s['label']) ?> — <?= money($s['sum']) ?> تومان / <?= $s['count'] ?> سفارش">
                    <div style="font-size:9px;color:var(--muted);margin-bottom:3px"><?= $s['count'] ?: '' ?></div>
                    <div style="height:<?= $h ?>px;background:linear-gradient(180deg,#a5664a,#8b533a);border-radius:7px 7px 3px 3px"></div>
                    <div style="font-size:8.5px;color:var(--muted);margin-top:5px;white-space:nowrap"><?= e(mb_substr($s['label'], 5)) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid g2 mb" style="grid-template-columns:1.6fr 1fr">
    <?php if (can('orders.view')): ?>
        <div class="card">
            <div class="card-head">
                <h3>آخرین سفارش‌ها</h3>
                <a href="<?= admin_url('orders') ?>" class="btn btn-sm">همه سفارش‌ها</a>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>کد رهگیری</th><th>مشتری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
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
                                <td style="white-space:nowrap"><?= money($o['total_amount']) ?></td>
                                <td><span class="badge <?= $colors[$o['status']] ?? 'b-gray' ?>"><?= e($statuses[$o['status']] ?? $o['status']) ?></span></td>
                                <td class="hint"><?= e(timeAgo($o['created_at'])) ?></td>
                                <td class="text-left"><a class="btn btn-sm" href="<?= admin_url('orders/show/' . $o['id']) ?>">جزئیات</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h3>وضعیت سفارش‌ها</h3></div>
        <div class="card-body">
            <?php
            $totalOrders = max(1, array_sum(array_column($statusBreakdown, 'c')));
            foreach ($statusBreakdown as $sb):
                $pct = round(($sb['c'] / $totalOrders) * 100); ?>
                <div style="margin-bottom:12px">
                    <div class="flex between items-center" style="margin-bottom:4px">
                        <span style="font-size:11.5px;font-weight:700"><?= e($statuses[$sb['status']] ?? $sb['status']) ?></span>
                        <span class="hint"><?= (int) $sb['c'] ?> (<?= $pct ?>٪)</span>
                    </div>
                    <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$statusBreakdown): ?><div class="empty">داده‌ای موجود نیست.</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="grid g3 mb">
    <?php if (can('products.view')): ?>
        <div class="card">
            <div class="card-head"><h3>پرفروش‌ترین محصولات</h3></div>
            <div class="card-body">
                <?php if (!$topProducts): ?><div class="empty">داده‌ای موجود نیست.</div><?php endif; ?>
                <?php foreach ($topProducts as $p): ?>
                    <a href="<?= admin_url('products/edit/' . $p['id']) ?>" class="flex items-center gap"
                       style="gap:9px;padding:7px 0;border-bottom:1px solid var(--line)">
                        <img class="thumb" src="<?= e($p['telegram_photo_id'] ? \Core\Seo::imageUrl((string) $p['telegram_photo_id'], \Core\Seo::imageSlug((string) $p['name'])) : '/assets/logo/logo.webp') ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                        <div style="flex:1;min-width:0">
                            <div style="font-size:11.5px;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($p['name']) ?></div>
                            <div class="hint"><?= (int) $p['qty'] ?> فروش — موجودی: <?= (int) $p['stock_qty'] ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (can('products.view')): ?>
        <div class="card">
            <div class="card-head">
                <h3>هشدار موجودی انبار</h3>
                <?php if (can('products.stock')): ?>
                    <a class="btn btn-sm" href="<?= admin_url('products/stock') ?>">انبارداری</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!$lowStock): ?><div class="empty">موجودی همه محصولات کافی است. 🎉</div><?php endif; ?>
                <?php foreach ($lowStock as $p): ?>
                    <a href="<?= admin_url('products/edit/' . $p['id']) ?>"
                       class="flex between items-center" style="padding:7px 0;border-bottom:1px solid var(--line);gap:8px">
                        <span style="font-size:11.5px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= e($p['name']) ?>
                        </span>
                        <span class="badge <?= (int) $p['stock_qty'] <= 0 ? 'b-red' : 'b-amber' ?>">
                            <?= (int) $p['stock_qty'] ?> عدد
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (can('tickets.view')): ?>
        <div class="card">
            <div class="card-head"><h3>تیکت‌های باز</h3><a href="<?= admin_url('tickets') ?>" class="btn btn-sm">همه</a></div>
            <div class="card-body">
                <?php if (!$openTickets): ?><div class="empty">تیکت بازی وجود ندارد. 🎉</div><?php endif; ?>
                <?php foreach ($openTickets as $t): ?>
                    <a href="<?= admin_url('tickets/show/' . $t['id']) ?>" style="display:block;padding:7px 0;border-bottom:1px solid var(--line)">
                        <div style="font-size:11.5px;font-weight:700">
                            <?= e(excerpt($t['subject'], 44)) ?>
                            <?php if ($t['priority'] === 'high'): ?><span class="badge b-red" style="font-size:9px">فوری</span><?php endif; ?>
                        </div>
                        <div class="hint"><?= e($t['full_name'] ?? '—') ?> — <?= e(timeAgo($t['updated_at'])) ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="grid g2">
    <?php if (can('users.view')): ?>
        <div class="card">
            <div class="card-head"><h3>کاربران جدید</h3><a href="<?= admin_url('users') ?>" class="btn btn-sm">همه</a></div>
            <div class="card-body">
                <?php if (!$recentUsers): ?><div class="empty">کاربری ثبت نشده.</div><?php endif; ?>
                <?php foreach ($recentUsers as $u): ?>
                    <a href="<?= admin_url('users/show/' . $u['id']) ?>" class="flex items-center gap"
                       style="gap:9px;padding:7px 0;border-bottom:1px solid var(--line)">
                        <div class="avatar"><?= e(mb_substr($u['full_name'] ?: 'ک', 0, 1, 'UTF-8')) ?></div>
                        <div style="flex:1;min-width:0">
                            <div style="font-size:11.5px;font-weight:700"><?= e($u['full_name'] ?: 'بدون نام') ?></div>
                            <div class="hint mono"><?= e($u['phone']) ?></div>
                        </div>
                        <span class="hint"><?= e(timeAgo($u['created_at'])) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (can('audit.view') && $recentAudit): ?>
        <div class="card">
            <div class="card-head"><h3>آخرین فعالیت مدیران</h3><a href="<?= admin_url('audit') ?>" class="btn btn-sm">لاگ کامل</a></div>
            <div class="card-body">
                <?php foreach ($recentAudit as $l):
                    $isCritical = in_array($l['action'], Audit::CRITICAL, true); ?>
                    <div style="padding:6px 0;border-bottom:1px solid var(--line)">
                        <div class="flex between items-center" style="gap:8px">
                            <span style="font-size:11px;font-weight:700"><?= e($l['admin_name'] ?: 'سیستم') ?></span>
                            <span class="badge <?= $isCritical ? 'b-amber' : 'b-gray' ?>" style="font-size:9px">
                                <?= e(Audit::label($l['action'])) ?>
                            </span>
                        </div>
                        <div class="hint"><?= e(excerpt($l['description'] ?? '', 72)) ?> — <?= e(timeAgo($l['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
