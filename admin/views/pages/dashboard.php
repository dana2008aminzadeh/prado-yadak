<?php
use Admin\core\Audit;

$maxSum = max(1, max(array_column($series, 'sum')));
$growth = $stats['revenue_growth'];
?>

<style>
    /* هشدارها و یادآورهای پیشخوان */
    .dash-alert { display: flex; align-items: center; gap: 11px; padding: 10px 12px;
        border-radius: 12px; border: 1px solid transparent; margin-bottom: 8px; }
    .dash-alert .al-ic { width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; }
    .dash-alert .al-main { flex: 1; min-width: 0; }
    .dash-alert .al-main b { display: block; font-size: 12.5px; }
    .dash-alert .al-main span { display: block; font-size: 11px; color: var(--muted); }
    .dash-alert .al-ops { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .dash-alert .al-x { background: none; border: 0; cursor: pointer; font-size: 16px;
        color: var(--muted); padding: 4px 8px; border-radius: 8px; line-height: 1;
        font-family: inherit; }
    .dash-alert .al-x:hover { background: rgba(0,0,0,.06); color: var(--ink); }
    .al-danger { background: #fff1f2; border-color: #fecdd3; }
    .al-danger .al-ic { background: #fff; color: #be123c; }
    .al-warn { background: #fffbeb; border-color: #fde68a; }
    .al-warn .al-ic { background: #fff; color: #b45309; }
    .al-info { background: #eff6ff; border-color: #bfdbfe; }
    .al-info .al-ic { background: #fff; color: #1d4ed8; }
    .dash-ok { text-align: center; padding: 16px; color: #047857; font-size: 12.5px;
        font-weight: 700; background: #ecfdf5; border: 1px dashed #a7f3d0; border-radius: 12px; }
    @media (max-width: 640px) {
        .dash-alert { flex-wrap: wrap; }
        .dash-alert .al-ops { width: 100%; justify-content: flex-end; padding-top: 2px; }
    }
</style>

<div class="card mb" id="alerts-card">
    <div class="card-head">
        <h3 class="flex items-center" style="gap:7px">
            <i data-lucide="bell-ring" style="width:15px"></i> هشدارها و یادآورها
        </h3>
        <span class="hint" id="alerts-count"><?= money(count($alerts)) ?> مورد</span>
    </div>
    <div class="card-body" id="alerts-list" style="padding-top:10px">
        <?php foreach ($alerts as $a): ?>
            <div class="dash-alert al-<?= e($a['level']) ?>" data-alert="<?= e($a['key']) ?>">
                <div class="al-ic"><i data-lucide="<?= e($a['icon']) ?>" style="width:17px"></i></div>
                <div class="al-main">
                    <b><?= e($a['title']) ?></b>
                    <span><?= e($a['desc']) ?></span>
                </div>
                <div class="al-ops">
                    <a class="btn btn-sm" href="<?= e($a['url']) ?>"><?= e($a['action']) ?></a>
                    <button type="button" class="al-x" title="مخفی کردن برای ۱۲ ساعت">&times;</button>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="dash-ok" id="alerts-ok" <?= $alerts ? 'style="display:none"' : '' ?>>✅ همه‌چیز مرتب است؛ هشدار یا یادآور جدیدی وجود ندارد.</div>
    </div>
</div>

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

<script>
// ===================== هشدارها و یادآورهای پیشخوان =====================
(function () {
    const list = document.getElementById('alerts-list');
    if (!list) return;

    const HIDE_MS = 12 * 3600 * 1000; // مخفی‌سازی موقت: ۱۲ ساعت
    const sKey = k => 'py_alert_' + k;

    function visibleCount() { return list.querySelectorAll('.dash-alert').length; }

    function refresh() {
        const n = visibleCount();
        const counter = document.getElementById('alerts-count');
        const ok = document.getElementById('alerts-ok');
        if (counter) counter.textContent = n ? n.toLocaleString('fa-IR') + ' مورد' : 'بدون هشدار فعال';
        if (ok) ok.style.display = n ? 'none' : 'block';
    }

    function hideRow(row, persist) {
        if (!row) return;
        if (persist && row.dataset.alert) {
            try { localStorage.setItem(sKey(row.dataset.alert), String(Date.now() + HIDE_MS)); } catch (err) {}
        }
        row.remove();
        refresh();
    }

    // هشدارهایی که مدیر قبلاً برای همین روز رد کرده است، دوباره نشان داده نشوند
    list.querySelectorAll('.dash-alert[data-alert]').forEach(row => {
        let until = 0;
        try { until = parseInt(localStorage.getItem(sKey(row.dataset.alert)) || '0', 10); } catch (err) {}
        if (until > Date.now()) row.remove();
    });

    list.addEventListener('click', e => {
        const x = e.target.closest('.al-x');
        if (x) hideRow(x.closest('.dash-alert'), true);
    });

    // هشدار لحظه‌ای: سفارش جدیدی که در حین بودن در پیشخوان ثبت می‌شود
    document.addEventListener('admin:live-alert', ev => {
        (ev.detail || []).forEach(n => {
            if (n.type !== 'order') return;
            const row = document.createElement('div');
            row.className = 'dash-alert al-info';
            row.dataset.alert = 'live-order-' + Date.now();

            const ic = document.createElement('div');
            ic.className = 'al-ic';
            ic.innerHTML = '<i data-lucide="shopping-bag" style="width:17px"></i>';

            const main = document.createElement('div');
            main.className = 'al-main';
            const b = document.createElement('b');
            b.textContent = n.title || 'سفارش جدید ثبت شد';
            const sp = document.createElement('span');
            sp.textContent = n.body || '';
            main.append(b, sp);

            const ops = document.createElement('div');
            ops.className = 'al-ops';
            const a = document.createElement('a');
            a.className = 'btn btn-sm btn-primary';
            a.href = n.url || <?= json_encode(admin_url('orders')) ?>;
            a.textContent = 'مشاهده سفارش';
            const x = document.createElement('button');
            x.type = 'button';
            x.className = 'al-x';
            x.title = 'مخفی کردن';
            x.innerHTML = '&times;';
            ops.append(a, x);

            row.append(ic, main, ops);
            list.prepend(row);
            refreshIcons();
            refresh();
        });
    });

    refresh();
})();
</script>
