<?php
use Admin\core\Auth;

$receipt = $order['receipt_telegram_id']
    ? \Core\Seo::imageUrl((string) $order['receipt_telegram_id'], 'receipt-' . (string) ($order['id'] ?? ''))
    : ($order['receipt_path'] ? '/' . ltrim($order['receipt_path'], '/') : '');

$canStatus = can('orders.status');
$canEdit = can('orders.edit');
?>

<div class="flex gap mb between wrap">
    <a class="btn" href="<?= admin_url('orders') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت به لیست</a>
    <div class="flex gap">
        <?php if (can('orders.invoice')): ?>
            <a class="btn" target="_blank" rel="noopener" href="<?= admin_url('orders/invoice/' . $order['id']) ?>">
                <i data-lucide="printer" style="width:14px"></i> فاکتور رسمی
            </a>
        <?php endif; ?>
        <?php if (can('orders.delete')): ?>
            <?= action_button(admin_url('orders/delete'), 'حذف سفارش', [
                'class' => 'btn btn-danger',
                'confirm' => 'حذف کامل این سفارش؟ موجودی اقلام به انبار بازمی‌گردد.',
                'fields' => ['order_id' => $order['id']],
            ]) ?>
        <?php endif; ?>
    </div>
</div>

<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
    <div>
        <!-- اقلام -->
        <div class="card mb">
            <div class="card-head">
                <h3>اقلام سفارش (<?= count($items) ?> قلم)</h3>
                <div class="flex gap items-center">
                    <span class="badge <?= $colors[$order['status']] ?? 'b-gray' ?>"><?= e($statuses[$order['status']] ?? $order['status']) ?></span>
                    <?php if ((int) $order['stock_deducted'] === 1): ?>
                        <span class="badge b-blue">موجودی کسر شده</span>
                    <?php else: ?>
                        <span class="badge b-gray">موجودی کسر نشده</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>محصول</th><th>کد فنی</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap">
                                        <img class="thumb" src="<?= e($it['telegram_photo_id'] ? \Core\Seo::imageUrl((string) $it['telegram_photo_id'], \Core\Seo::imageSlug((string) ($it['name'] ?? 'محصول'))) : '/assets/logo/logo.webp') ?>" alt="<?= e($it['name'] ?? 'محصول') ?>" loading="lazy">
                                        <div>
                                            <a href="<?= admin_url('products/edit/' . (int) $it['product_id']) ?>" style="font-weight:700"><?= e($it['name'] ?? 'محصول حذف‌شده') ?></a>
                                            <?php if ($it['stock_qty'] !== null): ?>
                                                <div class="hint">موجودی فعلی انبار: <?= (int) $it['stock_qty'] ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="mono hint"><?= e($it['oem_code'] ?: '—') ?></td>
                                <td><?= money($it['price']) ?></td>
                                <td><?= (int) $it['quantity'] ?></td>
                                <td style="font-weight:700"><?= money($it['price'] * $it['quantity']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body" style="border-top:1px solid var(--line)">
                <div class="flex between" style="margin-bottom:5px"><span class="hint">جمع اقلام</span><span><?= money($order['subtotal']) ?> تومان</span></div>
                <?php if ((float) $order['discount_amount'] > 0): ?>
                    <div class="flex between" style="margin-bottom:5px;color:var(--green)">
                        <span class="hint">تخفیف <?= $order['applied_coupon'] ? '(' . e($order['applied_coupon']) . ')' : '' ?></span>
                        <span>− <?= money($order['discount_amount']) ?> تومان</span>
                    </div>
                <?php endif; ?>
                <?php if ((float) ($order['shipping_cost'] ?? 0) > 0): ?>
                    <div class="flex between" style="margin-bottom:5px">
                        <span class="hint">هزینه ارسال</span><span><?= money($order['shipping_cost']) ?> تومان</span>
                    </div>
                <?php endif; ?>
                <div class="flex between" style="font-weight:900;font-size:15px;border-top:1px dashed var(--line);padding-top:9px">
                    <span>مبلغ قابل پرداخت</span><span><?= money($order['total_amount']) ?> تومان</span>
                </div>
            </div>
        </div>

        <!-- ثبت بارنامه -->
        <?php if ($canStatus): ?>
            <form class="card mb" method="POST" action="<?= admin_url('orders/shipping') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <div class="card-head">
                    <h3><i data-lucide="truck" style="width:15px"></i> ارسال و بارنامه</h3>
                    <button class="btn btn-primary btn-sm" type="submit">ثبت اطلاعات ارسال</button>
                </div>
                <div class="card-body">
                    <div class="grid g3">
                        <div class="field">
                            <label class="fl">شرکت حمل</label>
                            <select name="shipping_carrier" id="carrierSel">
                                <option value="">انتخاب کنید</option>
                                <?php foreach ($carriers as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= $order['shipping_carrier'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field" style="grid-column:span 2">
                            <label class="fl">کد رهگیری / شماره بارنامه</label>
                            <input type="text" name="shipping_tracking_code" class="mono"
                                   value="<?= e($order['shipping_tracking_code']) ?>"
                                   placeholder="کد رهگیری پست ۲۴ رقمی است" maxlength="30">
                            <div class="hint" id="trackHint">برای پست ایران باید عددی و حدود ۲۴ رقم باشد.</div>
                        </div>
                    </div>
                    <div class="grid g2">
                        <div class="field">
                            <label class="fl">هزینه ارسال (تومان)</label>
                            <input type="number" name="shipping_cost" value="<?= (int) ($order['shipping_cost'] ?? 0) ?>" min="0" step="1000">
                        </div>
                        <div class="field">
                            <label class="fl">روش ارسال ثبت‌شده</label>
                            <select name="shipping_method_id">
                                <option value="">—</option>
                                <?php foreach ($shippingMethods as $sm): ?>
                                    <option value="<?= (int) $sm['id'] ?>" <?= (int) ($order['shipping_method_id'] ?? 0) === (int) $sm['id'] ? 'selected' : '' ?>>
                                        <?= e($sm['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label class="fl">یادداشت وضعیت (برای مشتری قابل مشاهده است)</label>
                        <input type="text" name="note" maxlength="500"
                               placeholder="مثال: بسته تحویل شعبه تیپاکس سقز شد و فردا ارسال می‌شود.">
                    </div>
                    <div class="flex gap wrap">
                        <label class="chk"><input type="checkbox" name="mark_shipped" value="1" <?= $order['status'] !== 'shipped' ? 'checked' : '' ?>>
                            <span>وضعیت سفارش «ارسال شده» شود</span></label>
                        <label class="chk"><input type="checkbox" name="send_sms" value="1" <?= $smsEnabled ? 'checked' : 'disabled' ?>>
                            <span>ارسال پیامک کد رهگیری به مشتری <?= $smsEnabled ? '' : '(سرویس پیامک غیرفعال است)' ?></span></label>
                        <label class="chk"><input type="checkbox" name="is_public" value="1" checked>
                            <span>یادداشت برای مشتری نمایش داده شود</span></label>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- اطلاعات ارسال و پرداخت -->
        <?php if ($canEdit): ?>
            <form class="card mb" method="POST" action="<?= admin_url('orders/update') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <div class="card-head"><h3>اطلاعات گیرنده و پرداخت</h3><button class="btn btn-primary btn-sm" type="submit">ذخیره</button></div>
                <div class="card-body">
                    <div class="grid g2">
                        <div class="field"><label class="fl">نام گیرنده</label><input type="text" name="recipient_name" value="<?= e($order['recipient_name']) ?>"></div>
                        <div class="field"><label class="fl">موبایل گیرنده</label><input type="tel" name="recipient_phone" class="mono" value="<?= e($order['recipient_phone']) ?>"></div>
                    </div>
                    <div class="field"><label class="fl">آدرس ارسال</label><textarea name="shipping_address" rows="3"><?= e($order['shipping_address']) ?></textarea></div>
                    <div class="grid g3">
                        <div class="field"><label class="fl">کد پستی</label><input type="text" name="postal_code" class="mono" value="<?= e($order['postal_code']) ?>" maxlength="10"></div>
                        <div class="field"><label class="fl">نام پرداخت‌کننده</label><input type="text" name="payer_name" value="<?= e($order['payer_name']) ?>"></div>
                        <div class="field"><label class="fl">شماره مرجع بانکی</label><input type="text" name="bank_reference" class="mono" value="<?= e($order['bank_reference']) ?>"></div>
                    </div>
                    <div class="grid g2">
                        <div class="field"><label class="fl">یادداشت مشتری</label><textarea name="user_notes" rows="2"><?= e($order['user_notes']) ?></textarea></div>
                        <div class="field"><label class="fl">یادداشت داخلی (فقط مدیران)</label><textarea name="admin_notes" rows="2"><?= e($order['admin_notes'] ?? '') ?></textarea></div>
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- تاریخچه وضعیت -->
        <div class="card mb">
            <div class="card-head"><h3>تاریخچه وضعیت و یادداشت‌ها</h3></div>
            <div class="card-body">
                <?php if ($canStatus): ?>
                    <form method="POST" action="<?= admin_url('orders/note') ?>" class="flex gap mb" style="align-items:flex-start">
                        <?= Auth::csrfField() ?>
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="text" name="note" placeholder="افزودن یادداشت به تاریخچه…" style="flex:1" required>
                        <label class="chk" style="margin:0;white-space:nowrap">
                            <input type="checkbox" name="is_public" value="1" checked><span>عمومی</span>
                        </label>
                        <button class="btn btn-primary" type="submit">ثبت</button>
                    </form>
                <?php endif; ?>

                <?php if (!$history): ?><div class="empty" style="padding:26px">تاریخچه‌ای ثبت نشده است.</div><?php endif; ?>
                <?php foreach ($history as $h): ?>
                    <div style="padding:9px 0;border-bottom:1px solid var(--line)">
                        <div class="flex between items-center wrap" style="gap:6px">
                            <div class="flex gap items-center wrap">
                                <?php if ($h['from_status'] !== $h['to_status']): ?>
                                    <span class="badge b-gray"><?= e($statuses[$h['from_status']] ?? $h['from_status']) ?></span>
                                    <span class="hint">←</span>
                                <?php endif; ?>
                                <span class="badge <?= $colors[$h['to_status']] ?? 'b-gray' ?>"><?= e($statuses[$h['to_status']] ?? $h['to_status']) ?></span>
                                <?php if (!(int) $h['is_public']): ?><span class="badge b-gray">داخلی</span><?php endif; ?>
                                <?php if ((int) $h['notified_sms']): ?><span class="badge b-blue">پیامک ارسال شد</span><?php endif; ?>
                            </div>
                            <span class="hint"><?= e(shamsiTime($h['created_at'])) ?></span>
                        </div>
                        <?php if ($h['note']): ?>
                            <div style="font-size:12.5px;margin-top:4px"><?= nl2br(e($h['note'])) ?></div>
                        <?php endif; ?>
                        <div class="hint">ثبت توسط: <?= e($h['admin_name'] ?: 'سیستم') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($otherOrders): ?>
            <div class="card">
                <div class="card-head"><h3>سایر سفارش‌های این مشتری</h3></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>کد</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                        <tbody>
                            <?php foreach ($otherOrders as $h): ?>
                                <tr>
                                    <td><a class="mono" href="<?= admin_url('orders/show/' . $h['id']) ?>"><?= e($h['tracking_code']) ?></a></td>
                                    <td><?= money($h['total_amount']) ?></td>
                                    <td><span class="badge <?= $colors[$h['status']] ?? 'b-gray' ?>"><?= e($statuses[$h['status']] ?? $h['status']) ?></span></td>
                                    <td class="hint"><?= e(toShamsi($h['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <!-- تغییر وضعیت -->
        <?php if ($canStatus): ?>
            <form class="card mb" method="POST" action="<?= admin_url('orders/status') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <div class="card-head"><h3>تغییر وضعیت</h3></div>
                <div class="card-body">
                    <div class="field">
                        <select name="status">
                            <?php foreach ($statuses as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">یادداشت (اختیاری)</label>
                        <textarea name="note" rows="2" placeholder="توضیح این تغییر برای مشتری یا همکاران"></textarea>
                    </div>
                    <label class="chk"><input type="checkbox" name="is_public" value="1" checked><span>نمایش به مشتری</span></label>
                    <label class="chk"><input type="checkbox" name="send_sms" value="1" <?= $smsEnabled ? '' : 'disabled' ?>>
                        <span>اطلاع‌رسانی پیامکی</span></label>
                    <button class="btn btn-primary btn-block" type="submit">ثبت وضعیت</button>
                    <div class="hint mt">
                        با انتخاب «در حال پردازش» به بعد، موجودی انبار به‌صورت خودکار کسر می‌شود و
                        با «لغو» یا «مرجوع» به انبار بازمی‌گردد.
                    </div>
                </div>
            </form>
        <?php endif; ?>

        <!-- مشتری -->
        <div class="card mb">
            <div class="card-head"><h3>مشتری</h3></div>
            <div class="card-body">
                <div style="font-weight:800;margin-bottom:3px"><?= e($order['full_name'] ?? 'کاربر حذف‌شده') ?></div>
                <div class="hint mono"><?= e($order['phone'] ?? '') ?></div>
                <?php if ($order['email']): ?><div class="hint"><?= e($order['email']) ?></div><?php endif; ?>
                <?php if ($order['national_code']): ?><div class="hint mono">کد ملی: <?= e($order['national_code']) ?></div><?php endif; ?>
                <div class="hint">کیف پول: <?= money($order['wallet_balance'] ?? 0) ?> تومان</div>
                <?php if ($order['user_id'] && can('users.view')): ?>
                    <a class="btn btn-sm mt" href="<?= admin_url('users/show/' . (int) $order['user_id']) ?>">پروفایل کامل کاربر</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ارسال پیامک دستی -->
        <?php if ($canStatus && $smsEnabled): ?>
            <div class="card mb">
                <div class="card-head"><h3>اطلاع‌رسانی پیامکی</h3></div>
                <div class="card-body">
                    <div class="flex gap wrap">
                        <?php foreach (\Admin\controllers\OrderController::STATUS_SMS as $st => $tpl): ?>
                            <?= action_button(admin_url('orders/notify'), $statuses[$st] ?? $st, [
                                'class' => 'btn btn-sm',
                                'confirm' => 'ارسال پیامک «' . ($statuses[$st] ?? $st) . '» به مشتری؟',
                                'fields' => ['order_id' => $order['id'], 'status' => $st],
                            ]) ?>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($smsLogs): ?>
                        <div class="hint mt">آخرین پیامک‌ها:</div>
                        <?php foreach (array_slice($smsLogs, 0, 3) as $s): ?>
                            <div class="hint" style="border-bottom:1px solid var(--line);padding:4px 0">
                                <span class="badge <?= $s['status'] === 'sent' ? 'b-green' : 'b-red' ?>" style="font-size:9px">
                                    <?= $s['status'] === 'sent' ? 'ارسال شد' : 'ناموفق' ?>
                                </span>
                                <?= e(excerpt($s['message'], 44)) ?> — <?= e(timeAgo($s['created_at'])) ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- مدیریت انبار سفارش -->
        <?php if ($canStatus): ?>
            <div class="card mb">
                <div class="card-head"><h3>انبار این سفارش</h3></div>
                <div class="card-body flex gap wrap">
                    <?php if ((int) $order['stock_deducted'] === 0): ?>
                        <?= action_button(admin_url('orders/restock'), 'کسر موجودی اقلام', [
                            'class' => 'btn btn-sm',
                            'confirm' => 'موجودی اقلام این سفارش از انبار کسر شود؟',
                            'fields' => ['order_id' => $order['id'], 'mode' => 'deduct'],
                        ]) ?>
                    <?php else: ?>
                        <?= action_button(admin_url('orders/restock'), 'بازگرداندن به انبار', [
                            'class' => 'btn btn-sm',
                            'confirm' => 'موجودی اقلام به انبار بازگردانده شود؟',
                            'fields' => ['order_id' => $order['id'], 'mode' => 'restore'],
                        ]) ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- رسید -->
        <div class="card">
            <div class="card-head"><h3>رسید پرداخت</h3></div>
            <div class="card-body">
                <?php if ($receipt): ?>
                    <a href="<?= e($receipt) ?>" target="_blank" rel="noopener">
                        <img src="<?= e($receipt) ?>" alt="رسید پرداخت" style="width:100%;border-radius:11px;border:1px solid var(--line)">
                    </a>
                    <div class="hint mt">برای مشاهده اندازه کامل کلیک کنید.</div>
                <?php else: ?>
                    <div class="empty" style="padding:28px">رسیدی آپلود نشده است.</div>
                <?php endif; ?>
                <div class="hint mt">ثبت سفارش: <?= e(shamsiTime($order['created_at'])) ?></div>
                <?php if ($order['shipped_at']): ?><div class="hint">ارسال: <?= e(shamsiTime($order['shipped_at'])) ?></div><?php endif; ?>
                <?php if ($order['delivered_at']): ?><div class="hint">تحویل: <?= e(shamsiTime($order['delivered_at'])) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('carrierSel')?.addEventListener('change', function () {
    const hint = document.getElementById('trackHint');
    hint.textContent = this.value === 'post'
        ? 'کد رهگیری پست ایران باید عددی و حدود ۲۴ رقم باشد.'
        : 'شماره بارنامه شرکت حمل انتخابی را وارد کنید.';
});
</script>
