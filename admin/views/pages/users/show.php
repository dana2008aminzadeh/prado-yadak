<?php
use Admin\core\Auth;
use Admin\core\Settings;
use Admin\controllers\OrderController;

$colors = OrderController::STATUS_COLORS;
$sLabels = OrderController::STATUSES;
$txTypes = ['charge' => 'شارژ', 'purchase' => 'خرید', 'refund' => 'بازگشت وجه', 'adjust' => 'اصلاح'];
$txStatus = ['pending' => ['b-amber', 'در انتظار'], 'completed' => ['b-green', 'موفق'],
             'failed' => ['b-red', 'ناموفق'], 'cancelled' => ['b-gray', 'لغو شده']];
$smsEnabled = Settings::bool('sms_enabled');
?>

<div class="flex gap mb between">
    <a class="btn" href="<?= admin_url('users') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت</a>
    <?php if (can('users.delete') && (int) $user['id'] !== (int) (Auth::user()['id'] ?? 0)): ?>
        <?= action_button(admin_url('users/delete'), 'حذف کاربر', [
            'class' => 'btn btn-danger', 'confirm' => 'حذف کامل این کاربر؟',
            'fields' => ['user_id' => $user['id']]]) ?>
    <?php endif; ?>
</div>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">تعداد سفارش</span><div class="val"><?= money($stats['orders']) ?></div></div>
    <div class="stat"><span class="lbl">مجموع خرید (تومان)</span><div class="val"><?= money($stats['spent']) ?></div></div>
    <div class="stat"><span class="lbl">میانگین سبد</span><div class="val"><?= money($stats['avg']) ?></div></div>
    <div class="stat"><span class="lbl">موجودی کیف پول</span><div class="val"><?= money($user['wallet_balance']) ?></div>
        <div class="hint">آخرین خرید: <?= $stats['last'] ? e(timeAgo($stats['last'])) : 'بدون خرید' ?></div></div>
</div>

<div class="grid g2" style="align-items:start">
    <?php if (can('users.edit')): ?>
        <form class="card mb" method="POST" action="<?= admin_url('users/update') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
            <div class="card-head"><h3>ویرایش اطلاعات</h3><button class="btn btn-primary btn-sm" type="submit">ذخیره</button></div>
            <div class="card-body">
                <div class="grid g2">
                    <div class="field"><label class="fl">نام و نام خانوادگی</label>
                        <input type="text" name="full_name" value="<?= e($user['full_name']) ?>"></div>
                    <div class="field"><label class="fl">موبایل</label>
                        <input type="tel" name="phone" class="mono" value="<?= e($user['phone']) ?>"></div>
                    <div class="field"><label class="fl">ایمیل</label>
                        <input type="email" name="email" class="mono" value="<?= e($user['email']) ?>"></div>
                    <div class="field"><label class="fl">کد ملی</label>
                        <input type="text" name="national_code" class="mono" value="<?= e($user['national_code']) ?>" maxlength="10"></div>
                    <div class="field"><label class="fl">شناسه ملی (حقوقی)</label>
                        <input type="text" name="national_company_id" class="mono" value="<?= e($user['national_company_id'] ?? '') ?>"></div>
                    <div class="field"><label class="fl">کد اقتصادی</label>
                        <input type="text" name="economic_code" class="mono" value="<?= e($user['economic_code'] ?? '') ?>"></div>
                    <div class="field"><label class="fl">شهر</label>
                        <input type="text" name="city" value="<?= e($user['city']) ?>"></div>
                    <?php if (can('users.roles')): ?>
                        <div class="field"><label class="fl">نقش کاربری</label>
                            <select name="role" id="roleSel" onchange="document.getElementById('adminRoleBox').style.display=this.value==='admin'?'block':'none'">
                                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>کاربر عادی</option>
                                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>مدیر</option>
                            </select></div>
                    <?php endif; ?>
                </div>
                <?php if (can('users.roles')): ?>
                    <div class="field" id="adminRoleBox" style="<?= $user['role'] === 'admin' ? '' : 'display:none' ?>">
                        <label class="fl">سطح دسترسی مدیریتی</label>
                        <select name="admin_role_id">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int) $r['id'] ?>" <?= (int) ($user['admin_role_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>>
                                    <?= e($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="hint">مدیریت کامل نقش‌ها در <a href="<?= admin_url('roles') ?>" style="text-decoration:underline">بخش نقش‌ها</a>.</div>
                    </div>
                <?php endif; ?>
                <div class="field"><label class="fl">رمز عبور جدید</label>
                    <input type="text" name="password" class="mono" placeholder="خالی بگذارید تا تغییر نکند" minlength="8"></div>
            </div>
        </form>
    <?php endif; ?>

    <div>
        <?php if (can('wallet.adjust')): ?>
            <form class="card mb" method="POST" action="<?= admin_url('users/wallet') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                <div class="card-head"><h3>مدیریت کیف پول</h3></div>
                <div class="card-body">
                    <div class="grid g2">
                        <div class="field"><label class="fl">مبلغ (تومان)</label>
                            <input type="number" name="amount" step="1000" min="1000" required></div>
                        <div class="field"><label class="fl">نوع تراکنش</label>
                            <select name="type">
                                <option value="charge">شارژ (افزایش)</option>
                                <option value="refund">بازگشت وجه (افزایش)</option>
                                <option value="purchase">خرید (کاهش)</option>
                                <option value="adjust">اصلاح (کاهش)</option>
                            </select></div>
                    </div>
                    <div class="field"><label class="fl">توضیح</label>
                        <input type="text" name="description" placeholder="مثلاً: شارژ هدیه جشنواره"></div>
                    <label class="chk"><input type="checkbox" name="send_sms" value="1" <?= $smsEnabled ? '' : 'disabled' ?>>
                        <span>اطلاع پیامکی به کاربر</span></label>
                    <button class="btn btn-primary btn-block" type="submit">ثبت تراکنش</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (can('sms.send')): ?>
            <form class="card mb" method="POST" action="<?= admin_url('users/sms') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                <div class="card-head"><h3>ارسال پیامک به کاربر</h3></div>
                <div class="card-body">
                    <div class="field">
                        <textarea name="message" rows="3" placeholder="متن پیام… می‌توانید از {name} استفاده کنید." required></textarea>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit" <?= $smsEnabled ? '' : 'disabled' ?>>
                        <i data-lucide="send" style="width:14px"></i> ارسال پیامک
                    </button>
                    <?php if (!$smsEnabled): ?><div class="hint mt">سرویس پیامک غیرفعال است.</div><?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card mb">
    <div class="card-head"><h3>سفارش‌های کاربر</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>کد رهگیری</th><th>مبلغ</th><th>بارنامه</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
                <?php if (!$orders): ?><tr><td colspan="6" class="empty">سفارشی ثبت نشده.</td></tr><?php endif; ?>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="mono"><?= e($o['tracking_code']) ?></td>
                        <td><?= money($o['total_amount']) ?></td>
                        <td class="hint mono" style="font-size:10px"><?= e($o['shipping_tracking_code'] ?? '—') ?></td>
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
                            <td><?= e($txTypes[$t['type']] ?? $t['type']) ?>
                                <div class="hint"><?= e(excerpt($t['description'], 34)) ?></div></td>
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
        <div class="card-head"><h3>تیکت‌ها و پیامک‌ها</h3></div>
        <div class="card-body">
            <?php if ($tickets): ?>
                <div class="hint mb"><b>تیکت‌ها</b></div>
                <?php foreach ($tickets as $t): ?>
                    <a href="<?= admin_url('tickets/show/' . $t['id']) ?>" style="display:block;padding:6px 0;border-bottom:1px solid var(--line)">
                        <div style="font-size:11.5px;font-weight:700"><?= e(excerpt($t['subject'], 46)) ?></div>
                        <div class="hint"><?= e($t['status']) ?> — <?= e(timeAgo($t['updated_at'])) ?></div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($smsLogs): ?>
                <div class="hint mt mb"><b>آخرین پیامک‌ها</b></div>
                <?php foreach ($smsLogs as $s): ?>
                    <div style="padding:5px 0;border-bottom:1px solid var(--line);font-size:11px">
                        <span class="badge <?= $s['status'] === 'sent' ? 'b-green' : 'b-red' ?>" style="font-size:9px">
                            <?= $s['status'] === 'sent' ? 'ارسال' : 'ناموفق' ?></span>
                        <?= e(excerpt($s['message'], 52)) ?>
                        <div class="hint"><?= e(timeAgo($s['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (!$tickets && !$smsLogs): ?><div class="empty">موردی ثبت نشده.</div><?php endif; ?>
        </div>
    </div>
</div>

<div class="grid g3">
    <div class="card">
        <div class="card-head"><h3>آدرس‌ها</h3></div>
        <div class="card-body">
            <?php if (!$addresses): ?><div class="empty">آدرسی ثبت نشده.</div><?php endif; ?>
            <?php foreach ($addresses as $a): ?>
                <div style="padding:7px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:11.5px;font-weight:700"><?= e($a['province_city']) ?>
                        <?= $a['is_default'] ? '<span class="badge b-green" style="font-size:9px">پیش‌فرض</span>' : '' ?></div>
                    <div class="hint"><?= e($a['address_detail']) ?></div>
                    <div class="hint mono" style="font-size:10px"><?= e($a['recipient_name']) ?> — <?= e($a['recipient_phone']) ?> — <?= e($a['postal_code']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>خودروهای کاربر</h3></div>
        <div class="card-body">
            <?php if (!$vehicles): ?><div class="empty">خودرویی ثبت نشده.</div><?php endif; ?>
            <?php foreach ($vehicles as $v): ?>
                <div style="padding:7px 0;border-bottom:1px solid var(--line)">
                    <div style="font-size:11.5px;font-weight:700"><?= e($v['model_name']) ?> <?= e($v['model_year']) ?>
                        <?= $v['is_primary'] ? '<span class="badge b-green" style="font-size:9px">اصلی</span>' : '' ?></div>
                    <div class="hint mono" style="font-size:10px">VIN: <?= e($v['vin'] ?: '—') ?> | موتور: <?= e($v['engine_code'] ?: '—') ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>علاقه‌مندی‌ها</h3></div>
        <div class="card-body">
            <?php if (!$wishlist): ?><div class="empty">موردی وجود ندارد.</div><?php endif; ?>
            <?php foreach ($wishlist as $w): ?>
                <div style="padding:5px 0;border-bottom:1px solid var(--line);font-size:11.5px">
                    <?= e($w['name'] ?? 'محصول حذف‌شده') ?>
                    <span class="hint">— <?= money($w['price'] ?? 0) ?> تومان</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
