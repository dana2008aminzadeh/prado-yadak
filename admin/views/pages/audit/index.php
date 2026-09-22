<?php use Admin\core\Auth; ?>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">رویدادهای امروز</span><div class="val"><?= money($stats['today']) ?></div></div>
    <div class="stat"><span class="lbl">۷ روز اخیر</span><div class="val"><?= money($stats['week']) ?></div></div>
    <a class="stat warn" href="<?= admin_url('audit', ['critical' => 1]) ?>">
        <div class="ic-box"><i data-lucide="alert-triangle" style="width:17px"></i></div>
        <span class="lbl">عملیات حساس (۳۰ روز)</span><div class="val"><?= money($stats['critical']) ?></div>
    </a>
    <div class="stat <?= $stats['blocked'] > 0 ? 'danger' : '' ?>">
        <div class="ic-box"><i data-lucide="shield-alert" style="width:17px"></i></div>
        <span class="lbl">درخواست‌های مسدودشده</span><div class="val"><?= money($stats['blocked']) ?></div>
    </div>
</div>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('audit') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو در توضیحات</label>
                <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="توضیح، نام مدیر، شناسه"></div>
            <div class="f"><label class="fl">مدیر</label>
                <select name="admin_id">
                    <option value="">همه</option>
                    <?php foreach ($admins as $a): ?>
                        <option value="<?= (int) $a['admin_id'] ?>" <?= (int) $filters['admin_id'] === (int) $a['admin_id'] ? 'selected' : '' ?>>
                            <?= e($a['admin_name'] ?: ('#' . $a['admin_id'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select></div>
            <div class="f"><label class="fl">نوع رویداد</label>
                <select name="action">
                    <option value="">همه</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= e($a['action']) ?>" <?= $filters['action'] === $a['action'] ? 'selected' : '' ?>>
                            <?= e($labels[$a['action']] ?? $a['action']) ?> (<?= (int) $a['c'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select></div>
            <div class="f"><label class="fl">از تاریخ</label>
                <input type="text" name="from" value="<?= e($fromJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <div class="f"><label class="fl">تا تاریخ</label>
                <input type="text" name="to" value="<?= e($toJ) ?>" class="mono" data-jdp autocomplete="off"></div>
            <label class="chk" style="margin-bottom:0">
                <input type="checkbox" name="critical" value="1" <?= !empty($filters['critical']) ? 'checked' : '' ?>>
                <span>فقط حساس</span>
            </label>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('audit') ?>">حذف فیلتر</a>
            <a class="btn" href="<?= admin_url('audit/export', $_GET) ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h3>رویدادها (<?= money($pg['total']) ?>)</h3>
        <?php if (can('roles.manage')): ?>
            <form method="POST" action="<?= admin_url('audit/purge') ?>" class="flex gap"
                  onsubmit="return confirm('لاگ‌های قدیمی‌تر از بازه انتخابی حذف شوند؟')">
                <?= Auth::csrfField() ?>
                <select name="days" style="width:auto">
                    <option value="90">قدیمی‌تر از ۹۰ روز</option>
                    <option value="180" selected>قدیمی‌تر از ۶ ماه</option>
                    <option value="365">قدیمی‌تر از یک سال</option>
                </select>
                <button class="btn btn-sm btn-danger" type="submit">پاک‌سازی</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>زمان</th><th>مدیر</th><th>رویداد</th><th>توضیح</th><th>تغییرات</th><th>IP</th></tr></thead>
            <tbody>
                <?php if (!$logs): ?><tr><td colspan="6" class="empty">رویدادی با این فیلترها یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($logs as $l):
                    $isCritical = in_array($l['action'], $critical, true);
                    $isSecurity = str_starts_with((string) $l['action'], 'security.'); ?>
                    <tr style="<?= $isSecurity ? 'background:#fff8f8' : '' ?>">
                        <td class="hint" style="white-space:nowrap">
                            <?= e(shamsiTime($l['created_at'])) ?>
                            <div style="font-size:9.5px"><?= e(timeAgo($l['created_at'])) ?></div>
                        </td>
                        <td>
                            <div style="font-weight:700;font-size:11.5px"><?= e($l['admin_name'] ?: 'سیستم') ?></div>
                            <?php if ($l['admin_id']): ?><div class="hint" style="font-size:9.5px">#<?= (int) $l['admin_id'] ?></div><?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $isSecurity ? 'b-red' : ($isCritical ? 'b-amber' : 'b-gray') ?>">
                                <?= e($labels[$l['action']] ?? $l['action']) ?>
                            </span>
                            <?php if ($l['entity_type']): ?>
                                <div class="hint" style="font-size:9.5px"><?= e($l['entity_type']) ?>
                                    <?= $l['entity_id'] ? '#' . e(excerpt($l['entity_id'], 14)) : '' ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:320px;font-size:11.5px"><?= e($l['description'] ?? '—') ?></td>
                        <td style="max-width:220px">
                            <?php if ($l['old_values'] || $l['new_values']):
                                $old = json_decode((string) $l['old_values'], true) ?: [];
                                $new = json_decode((string) $l['new_values'], true) ?: []; ?>
                                <details>
                                    <summary style="cursor:pointer;font-size:10.5px;color:var(--brand)">مشاهده تغییرات</summary>
                                    <div style="font-size:10px;margin-top:5px">
                                        <?php foreach ($new as $k => $v): ?>
                                            <div style="border-bottom:1px solid var(--line);padding:2px 0">
                                                <b><?= e($k) ?>:</b>
                                                <span style="color:var(--red);text-decoration:line-through"><?= e(excerpt((string) ($old[$k] ?? '—'), 26)) ?></span>
                                                →
                                                <span style="color:var(--green)"><?= e(excerpt((string) $v, 26)) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </details>
                            <?php else: ?>
                                <span class="hint">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="mono hint" style="font-size:10px"><?= e($l['ip_address'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
