<?php use Admin\core\Auth; ?>

<div class="grid g4 mb">
    <div class="stat">
        <div class="ic-box"><i data-lucide="boxes" style="width:17px"></i></div>
        <span class="lbl">مجموع واحدهای انبار</span><div class="val"><?= money($stats['total_units']) ?></div>
    </div>
    <div class="stat">
        <div class="ic-box"><i data-lucide="banknote" style="width:17px"></i></div>
        <span class="lbl">ارزش ریالی انبار</span><div class="val"><?= money($stats['value']) ?></div>
    </div>
    <a class="stat warn" href="<?= admin_url('products/stock', ['filter' => 'low']) ?>">
        <div class="ic-box"><i data-lucide="alert-triangle" style="width:17px"></i></div>
        <span class="lbl">رو به اتمام</span><div class="val"><?= money($stats['low']) ?></div>
    </a>
    <a class="stat danger" href="<?= admin_url('products/stock', ['filter' => 'out']) ?>">
        <div class="ic-box"><i data-lucide="package-x" style="width:17px"></i></div>
        <span class="lbl">ناموجود</span><div class="val"><?= money($stats['out']) ?></div>
    </a>
</div>

<div class="grid g2" style="grid-template-columns:1.7fr 1fr;align-items:start">
    <div class="card">
        <div class="card-head">
            <h3>موجودی محصولات</h3>
            <form method="GET" action="<?= admin_url('products/stock') ?>" class="flex gap">
                <select name="filter" style="width:auto" onchange="this.form.submit()">
                    <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>همه محصولات</option>
                    <option value="low" <?= $filter === 'low' ? 'selected' : '' ?>>رو به اتمام</option>
                    <option value="out" <?= $filter === 'out' ? 'selected' : '' ?>>ناموجود</option>
                </select>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="جستجو" style="width:150px">
                <button class="btn btn-sm" type="submit">جستجو</button>
            </form>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>محصول</th><th>موجودی</th><th>حد هشدار</th><th>تنظیم سریع</th></tr></thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="4" class="empty">محصولی در این دسته یافت نشد. 🎉</td></tr>
                    <?php endif; ?>
                    <?php foreach ($products as $p):
                        $qty = (int) $p['stock_qty']; ?>
                        <tr>
                            <td>
                                <a href="<?= admin_url('products/edit/' . $p['id']) ?>" style="font-weight:700"><?= e($p['name']) ?></a>
                                <div class="hint mono"><?= e($p['oem_code'] ?: '—') ?> — <?= money($p['price']) ?> تومان</div>
                            </td>
                            <td>
                                <span class="badge <?= $qty <= 0 ? 'b-red' : ($qty <= (int) $p['low_stock_threshold'] ? 'b-amber' : 'b-green') ?>">
                                    <?= $qty ?> عدد
                                </span>
                            </td>
                            <td class="hint"><?= (int) $p['low_stock_threshold'] ?></td>
                            <td>
                                <form method="POST" action="<?= admin_url('products/setStock') ?>" class="flex gap" style="align-items:center">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="mode" value="adjust">
                                    <input type="number" name="value" value="1" style="width:66px" title="مقدار (منفی = خروج)">
                                    <select name="reason" style="width:auto">
                                        <?php foreach ($reasons as $k => $v): ?>
                                            <?php if (in_array($k, ['order', 'return'], true)) continue; ?>
                                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
    </div>

    <div>
        <form class="card mb" method="POST" action="<?= admin_url('products/setStock') ?>">
            <?= Auth::csrfField() ?>
            <div class="card-head"><h3>انبارگردانی دستی</h3></div>
            <div class="card-body">
                <div class="field">
                    <label class="fl">محصول</label>
                    <input type="text" id="prodSearch" placeholder="نام یا کد فنی را بنویسید..." autocomplete="off">
                    <input type="hidden" name="product_id" id="prodId">
                    <div id="prodResults" style="max-height:170px;overflow-y:auto;margin-top:5px"></div>
                </div>
                <div class="grid g2">
                    <div class="field">
                        <label class="fl">نوع عملیات</label>
                        <select name="mode">
                            <option value="set">تنظیم روی عدد دقیق</option>
                            <option value="adjust">افزایش / کاهش</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">مقدار</label>
                        <input type="number" name="value" value="0" required>
                    </div>
                </div>
                <div class="field">
                    <label class="fl">علت</label>
                    <select name="reason">
                        <?php foreach ($reasons as $k => $v): ?>
                            <?php if (in_array($k, ['order', 'return'], true)) continue; ?>
                            <option value="<?= e($k) ?>"><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label class="fl">یادداشت</label>
                    <input type="text" name="note" placeholder="مثلاً: ورود کالا از بار تهران">
                </div>
                <button class="btn btn-primary btn-block" type="submit">ثبت تغییر موجودی</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head">
                <h3>آخرین حرکات انبار</h3>
                <a class="btn btn-sm" href="<?= admin_url('tools/export', ['type' => 'stock']) ?>">CSV</a>
            </div>
            <div class="card-body" style="max-height:420px;overflow-y:auto">
                <?php if (!$movements): ?><div class="empty">حرکتی ثبت نشده است.</div><?php endif; ?>
                <?php foreach ($movements as $m): ?>
                    <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                        <div class="flex between items-center">
                            <span style="font-size:12px;font-weight:700"><?= e(excerpt($m['product_name'] ?? '—', 34)) ?></span>
                            <span style="font-weight:800;color:<?= (int) $m['change_qty'] > 0 ? 'var(--green)' : 'var(--red)' ?>">
                                <?= (int) $m['change_qty'] > 0 ? '+' : '' ?><?= (int) $m['change_qty'] ?>
                            </span>
                        </div>
                        <div class="hint">
                            <?= e($reasons[$m['reason']] ?? $m['reason']) ?>
                            → مانده <?= (int) $m['qty_after'] ?>
                            <?= $m['admin_name'] ? ' — ' . e($m['admin_name']) : '' ?>
                        </div>
                        <div class="hint"><?= e(shamsiTime($m['created_at'])) ?><?= $m['note'] ? ' — ' . e($m['note']) : '' ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
let stockTimer = null;
document.getElementById('prodSearch')?.addEventListener('input', function () {
    clearTimeout(stockTimer);
    const q = this.value.trim();
    const box = document.getElementById('prodResults');
    if (q.length < 2) { box.innerHTML = ''; return; }
    stockTimer = setTimeout(async () => {
        const res = await fetch(`<?= admin_url('api/products') ?>?q=${encodeURIComponent(q)}`,
            { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        box.innerHTML = (data.items || []).map(p => `
            <div onclick="pickProduct(${p.id}, '${p.name.replace(/'/g, "\\'")}')"
                 style="padding:6px 9px;border:1px solid var(--line);border-radius:9px;margin-bottom:4px;cursor:pointer;font-size:12px">
                ${p.name}<div class="hint">موجودی: ${p.stock_qty} — ${p.oem_code || ''}</div>
            </div>`).join('') || '<div class="hint">نتیجه‌ای یافت نشد.</div>';
    }, 280);
});
function pickProduct(id, name) {
    document.getElementById('prodId').value = id;
    document.getElementById('prodSearch').value = name;
    document.getElementById('prodResults').innerHTML = '';
}
</script>
