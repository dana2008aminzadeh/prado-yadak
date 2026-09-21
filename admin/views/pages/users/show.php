<?php
use Admin\core\Auth;
$colors = ['processing' => 'b-amber', 'shipped' => 'b-blue', 'delivered' => 'b-green', 'cancelled' => 'b-red'];
$sLabels = ['processing' => 'در حال پردازش', 'shipped' => 'ارسال شده', 'delivered' => 'تحویل شده', 'cancelled' => 'لغو شده'];
$txTypes = ['charge' => 'شارژ', 'purchase' => 'خرید', 'refund' => 'بازگشت وجه', 'adjust' => 'اصلاح'];
$txStatus = ['pending' => ['b-amber', 'در انتظار'], 'completed' => ['b-green', 'موفق'], 'failed' => ['b-red', 'ناموفق'], 'cancelled' => ['b-gray', 'لغو شده']];
?>

<div class="flex gap mb" style="justify-content:space-between">
    <a class="btn" href="<?= admin_url('users') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت</a>
    <a class="btn btn-danger" href="<?= admin_url('users/delete/' . $user['id']) ?>" onclick="return confirmDelete('حذف این کاربر؟')">حذف کاربر</a>
</div>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">تعداد سفارش</span><div class="val"><?= money($stats['orders']) ?></div></div>
    <div class="stat"><span class="lbl">مجموع خرید (تومان)</span><div class="val"><?= money($stats['spent']) ?></div></div>
    <div class="stat"><span class="lbl">موجودی کیف پول</span><div class="val"><?= money($user['wallet_balance']) ?></div></div>
    <div class="stat"><span class="lbl">تاریخ عضویت</span><div class="val" style="font-size:16px"><?= e(toShamsi($user['created_at'])) ?></div></div>
</div>

<div class="grid g2" style="grid-template-columns:1fr 1fr;align-items:start">
    <form class="card mb" method="POST" action="<?= admin_url('users/update/' . $user['id']) ?>">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <div class="card-head"><h3>ویرایش اطلاعات</h3><button class="btn btn-primary btn-sm" type="submit">ذخیره</button></div>
        <div class="card-body">
            <div class="grid g2">
                <div class="field"><label class="fl">نام و نام خانوادگی</label><input type="text" name="full_name" value="<?= e($user['full_name']) ?>"></div>
                <div class="field"><label class="fl">موبایل</label><input type="tel" name="phone" class="mono" value="<?= e($user['phone']) ?>"></div>
                <div class="field"><label class="fl">ایمیل</label><input type="email" name="email" class="mono" value="<?= e($user['email']) ?>"></div>
                <div class="field"><label class="fl">کد ملی</label><input type="text" name="national_code" class="mono" value="<?= e($user['national_code']) ?>"></div>
                <div class="field"><label class="fl">شهر</label><input type="text" name="city" value="<?= e($user['city']) ?>"></div>
                <div class="field"><label class="fl">نقش کاربری</label>
                    <select name="role">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>کاربر عادی</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>مدیر (دسترسی کامل)</option>
                    </select>
                </div>
            </div>
            <div class="field"><label class="fl">رمز عبور جدید</label>
                <input type="text" name="password" class="mono" placeholder="خالی بگذارید تا تغییر نکند">
            </div>
        </div>
    </form>

    <form class="card mb" method="POST" action="<?= admin_url('users/wallet/' . $user['id']) ?>">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <div class="card-head"><h3>مدیریت کیف پول</h3></div>
        <div class="card-body">
            <div class="grid g2">
                <div class="field"><label class="fl">مبلغ (تومان)</label><input type="number" name="amount" step="1000" min="0" required></div>
                <div class="field"><label class="fl">نوع تراکنش</label>
                    <select name="type">
                        <option value="charge">شارژ (افزایش)</option>
                        <option value="refund">بازگشت وجه (افزایش)</option>
                        <option value="purchase">خرید (کاهش)</option>
                        <option value="adjust">اصلاح (کاهش)</option>
                    </select>
                </div>
            </div>
            <div class="field"><label class="fl">توضیح</label><input type="text" name="description" placeholder="مثلاً: شارژ هدیه"></div>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ثبت تراکنش</button>
        </div>
    </form>
</div>

<div class="card mb">
    <div class="card-head"><h3>سفارش‌های کاربر</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>کد رهگیری</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
                <?php if (!$orders): ?><tr><td colspan="5" class="empty">سفارشی ثبت نشده.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="mono"><?= e($o['tracking_code']) ?></td>
                        <td><?= money($o['total_amount']) ?></td>
                        <td><span class="badge <?= $colors[$o['status']] ?? 'b-gray' ?>"><?= e($sLabels[$o['status']] ?? $o['status']) ?></span></td>
                        <td class="hint"><?= e(shamsiTime($o['created_at'])) ?></td>
                        <td class="text-left"><a class="btn btn-sm" href="<?= admin_url('orders/show/' . $o['id']) ?>">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid g2 mb">
    <div class="card">
        <div class="card-head"><h3>تراکنش‌های کیف پول</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>نوع</th><th>مبلغ</th><th>مانده</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                <tbody>
                    <?php if (!$wallet): ?><tr><td colspan="5" class="empty">تراکنشی ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($wallet as $t): ?>
                        <tr>
                            <td><?= e($txTypes[$t['type']] ?? $t['type']) ?><div class="hint"><?= e($t['description']) ?></div></td>
                            <td><?= money($t['amount']) ?></td>
                            <td class="hint"><?= money($t['balance_after']) ?></td>
                            <td><span class="badge <?= $txStatus[$t['status']][0] ?? 'b-gray' ?>"><?= e($txStatus[$t['status']][1] ?? $t['status']) ?></span></td>
                            <td class="hint"><?= e(toShamsi($t['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>تیکت‌های پشتیبانی</h3></div>
        <div class="card-body">
            <?php if (!$tickets): ?><div class="empty">تیکتی ثبت نشده.</div><?php endif; ?>
            <?php foreach ($tickets as $t): ?>
                <a href="<?= admin_url('tickets/show/' . $t['id']) ?>" style="display:block;padding:8px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:12px;font-weight:700"><?= e($t['subject']) ?></div>
                    <div class="hint"><?= e($t['status']) ?> — <?= e(shamsiTime($t['updated_at'])) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid g3">
    <div class="card">
        <div class="card-head"><h3>آدرس‌ها</h3></div>
        <div class="card-body">
            <?php if (!$addresses): ?><div class="empty">آدرسی ثبت نشده.</div><?php endif; ?>
            <?php foreach ($addresses as $a): ?>
                <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:12px;font-weight:700"><?= e($a['province_city']) ?>
                        <?= $a['is_default'] ? '<span class="badge b-green">پیش‌فرض</span>' : '' ?></div>
                    <div class="hint"><?= e($a['address_detail']) ?></div>
                    <div class="hint mono"><?= e($a['recipient_name']) ?> — <?= e($a['recipient_phone']) ?> — <?= e($a['postal_code']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>خودروهای کاربر</h3></div>
        <div class="card-body">
            <?php if (!$vehicles): ?><div class="empty">خودرویی ثبت نشده.</div><?php endif; ?>
            <?php foreach ($vehicles as $v): ?>
                <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:12px;font-weight:700"><?= e($v['model_name']) ?> <?= e($v['model_year']) ?>
                        <?= $v['is_primary'] ? '<span class="badge b-green">اصلی</span>' : '' ?></div>
                    <div class="hint mono">VIN: <?= e($v['vin'] ?: '—') ?> | موتور: <?= e($v['engine_code'] ?: '—') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>علاقه‌مندی‌ها</h3></div>
        <div class="card-body">
            <?php if (!$wishlist): ?><div class="empty">موردی وجود ندارد.</div><?php endif; ?>
            <?php foreach ($wishlist as $w): ?>
                <div style="padding:6px 0;border-bottom:1px solid var(--line);font-size:12px">
                    <?= e($w['name'] ?? 'محصول حذف‌شده') ?>
                    <span class="hint">— <?= money($w['price'] ?? 0) ?> تومان</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
