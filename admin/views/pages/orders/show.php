<?php
use Admin\core\Auth;
$colors = ['processing' => 'b-amber', 'shipped' => 'b-blue', 'delivered' => 'b-green', 'cancelled' => 'b-red'];
$receipt = $order['receipt_telegram_id'] ? '/image?id=' . urlencode($order['receipt_telegram_id'])
    : ($order['receipt_path'] ? '/' . ltrim($order['receipt_path'], '/') : '');
?>

<div class="flex gap mb" style="justify-content:space-between;flex-wrap:wrap">
    <a class="btn" href="<?= admin_url('orders') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت به لیست</a>
    <div class="flex gap">
        <a class="btn" target="_blank" href="<?= admin_url('orders/invoice/' . $order['id']) ?>"><i data-lucide="printer" style="width:14px"></i> چاپ فاکتور</a>
        <a class="btn btn-danger" href="<?= admin_url('orders/delete/' . $order['id']) ?>" onclick="return confirmDelete('حذف کامل این سفارش؟')">حذف سفارش</a>
    </div>
</div>

<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
    <div>
        <div class="card mb">
            <div class="card-head">
                <h3>اقلام سفارش (<?= count($items) ?> قلم)</h3>
                <span class="badge <?= $colors[$order['status']] ?? 'b-gray' ?>"><?= e($statuses[$order['status']] ?? $order['status']) ?></span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>محصول</th><th>کد فنی</th><th>قیمت واحد</th><th>تعداد</th><th>جمع</th></tr></thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center gap">
                                        <img class="thumb" src="<?= $it['telegram_photo_id'] ? '/image?id=' . urlencode($it['telegram_photo_id']) : '/assets/logo/logo.webp' ?>" alt="">
                                        <a href="<?= admin_url('products/edit/' . (int) $it['product_id']) ?>" style="font-weight:700"><?= e($it['name'] ?? 'محصول حذف‌شده') ?></a>
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
                <div class="flex" style="justify-content:space-between;margin-bottom:6px"><span class="hint">جمع اقلام</span><span><?= money($order['subtotal']) ?> تومان</span></div>
                <?php if ((float) $order['discount_amount'] > 0): ?>
                    <div class="flex" style="justify-content:space-between;margin-bottom:6px;color:var(--green)">
                        <span class="hint">تخفیف <?= $order['applied_coupon'] ? '(' . e($order['applied_coupon']) . ')' : '' ?></span>
                        <span>− <?= money($order['discount_amount']) ?> تومان</span>
                    </div>
                <?php endif; ?>
                <div class="flex" style="justify-content:space-between;font-weight:900;font-size:15px;border-top:1px dashed var(--line);padding-top:10px">
                    <span>مبلغ قابل پرداخت</span><span><?= money($order['total_amount']) ?> تومان</span>
                </div>
            </div>
        </div>

        <form class="card mb" method="POST" action="<?= admin_url('orders/update/' . $order['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>اطلاعات ارسال و پرداخت</h3><button class="btn btn-primary btn-sm" type="submit">ذخیره تغییرات</button></div>
            <div class="card-body">
                <div class="grid g2">
                    <div class="field"><label class="fl">نام گیرنده</label><input type="text" name="recipient_name" value="<?= e($order['recipient_name']) ?>"></div>
                    <div class="field"><label class="fl">موبایل گیرنده</label><input type="text" name="recipient_phone" class="mono" value="<?= e($order['recipient_phone']) ?>"></div>
                </div>
                <div class="field"><label class="fl">آدرس ارسال</label><textarea name="shipping_address" rows="3"><?= e($order['shipping_address']) ?></textarea></div>
                <div class="grid g3">
                    <div class="field"><label class="fl">کد پستی</label><input type="text" name="postal_code" class="mono" value="<?= e($order['postal_code']) ?>"></div>
                    <div class="field"><label class="fl">نام پرداخت‌کننده</label><input type="text" name="payer_name" value="<?= e($order['payer_name']) ?>"></div>
                    <div class="field"><label class="fl">شماره مرجع بانکی</label><input type="text" name="bank_reference" class="mono" value="<?= e($order['bank_reference']) ?>"></div>
                </div>
                <div class="field"><label class="fl">یادداشت مشتری</label><textarea name="user_notes" rows="2"><?= e($order['user_notes']) ?></textarea></div>
            </div>
        </form>

        <?php if ($history): ?>
            <div class="card">
                <div class="card-head"><h3>سایر سفارش‌های این مشتری</h3></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>کد</th><th>مبلغ</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
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
        <form class="card mb" method="POST" action="<?= admin_url('orders/status/' . $order['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>تغییر وضعیت</h3></div>
            <div class="card-body">
                <div class="field">
                    <select name="status">
                        <?php foreach ($statuses as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= $order['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ثبت وضعیت</button>
            </div>
        </form>

        <div class="card mb">
            <div class="card-head"><h3>مشتری</h3></div>
            <div class="card-body">
                <div style="font-weight:800;margin-bottom:4px"><?= e($order['full_name'] ?? 'کاربر حذف‌شده') ?></div>
                <div class="hint mono"><?= e($order['phone'] ?? '') ?></div>
                <div class="hint"><?= e($order['email'] ?: '') ?></div>
                <div class="hint">موجودی کیف پول: <?= money($order['wallet_balance'] ?? 0) ?> تومان</div>
                <?php if ($order['user_id']): ?>
                    <a class="btn btn-sm mt" href="<?= admin_url('users/show/' . (int) $order['user_id']) ?>">پروفایل کاربر</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h3>رسید پرداخت</h3></div>
            <div class="card-body">
                <?php if ($receipt): ?>
                    <a href="<?= e($receipt) ?>" target="_blank">
                        <img src="<?= e($receipt) ?>" alt="رسید پرداخت" style="width:100%;border-radius:12px;border:1px solid var(--line)">
                    </a>
                    <div class="hint mt">برای مشاهده اندازه کامل کلیک کنید.</div>
                <?php else: ?>
                    <div class="empty">رسیدی آپلود نشده است.</div>
                <?php endif; ?>
                <div class="hint mt">تاریخ ثبت: <?= e(shamsiTime($order['created_at'])) ?></div>
            </div>
        </div>
    </div>
</div>
