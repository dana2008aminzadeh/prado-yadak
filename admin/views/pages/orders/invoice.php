<?php
$siteTitle = $settings['site_title'] ?? 'پرادو یدک';
$phone = $settings['site_phone'] ?? '';
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>فاکتور <?= e($order['tracking_code']) ?></title>
    <style>
        body { font-family: 'IRANSans', Tahoma, sans-serif; color: #111; padding: 28px; font-size: 13px; }
        .head { display: flex; justify-content: space-between; border-bottom: 2px solid #8b533a; padding-bottom: 14px; margin-bottom: 18px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #666; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #ddd; padding: 8px 10px; text-align: right; font-size: 12px; }
        th { background: #f5f5f5; }
        .totals { margin-top: 14px; width: 300px; margin-right: auto; }
        .totals div { display: flex; justify-content: space-between; padding: 5px 0; }
        .grand { font-weight: 900; border-top: 2px solid #8b533a; margin-top: 6px; padding-top: 8px; }
        .box { border: 1px solid #ddd; border-radius: 8px; padding: 12px; margin-top: 14px; }
        @media print { .noprint { display: none; } body { padding: 0; } }
    </style>
</head>

<body onload="window.print()">
    <div class="head">
        <div>
            <h1><?= e($siteTitle) ?></h1>
            <div class="muted">فروشگاه قطعات یدکی <?= $phone ? ' — تماس: ' . e($phone) : '' ?></div>
        </div>
        <div style="text-align:left">
            <div><b>فاکتور فروش</b></div>
            <div class="muted">کد رهگیری: <?= e($order['tracking_code']) ?></div>
            <div class="muted">تاریخ: <?= e(shamsiTime($order['created_at'])) ?></div>
        </div>
    </div>

    <div class="box">
        <b>مشخصات خریدار:</b>
        <?= e($order['full_name'] ?? '—') ?> — <?= e($order['phone'] ?? '') ?><br>
        <b>گیرنده:</b> <?= e($order['recipient_name'] ?: '—') ?> — <?= e($order['recipient_phone'] ?: '—') ?><br>
        <b>آدرس:</b> <?= e($order['shipping_address'] ?: '—') ?>
        <?= $order['postal_code'] ? ' (کد پستی: ' . e($order['postal_code']) . ')' : '' ?>
    </div>

    <table>
        <thead><tr><th>ردیف</th><th>شرح کالا</th><th>کد فنی</th><th>تعداد</th><th>قیمت واحد</th><th>مبلغ کل</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i => $it): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($it['name'] ?? '—') ?></td>
                    <td><?= e($it['oem_code'] ?: '—') ?></td>
                    <td><?= (int) $it['quantity'] ?></td>
                    <td><?= money($it['price']) ?></td>
                    <td><?= money($it['price'] * $it['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div><span>جمع اقلام</span><span><?= money($order['subtotal']) ?> تومان</span></div>
        <div><span>تخفیف</span><span><?= money($order['discount_amount']) ?> تومان</span></div>
        <div class="grand"><span>مبلغ قابل پرداخت</span><span><?= money($order['total_amount']) ?> تومان</span></div>
    </div>

    <p class="muted" style="margin-top:26px;text-align:center">این فاکتور به‌صورت سیستمی صادر شده و نیاز به مهر و امضا ندارد.</p>
    <p class="noprint" style="text-align:center"><button onclick="window.print()">چاپ</button></p>
</body>

</html>
