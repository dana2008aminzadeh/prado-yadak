<?php
/**
 * فاکتور فروش رسمی — مطابق الزامات سازمان امور مالیاتی
 */
use Admin\core\Jalali;

$g = fn(string $k, string $d = '') => trim((string) ($settings[$k] ?? '')) ?: $d;

$legalName = $g('company_legal_name', $g('site_title', 'پرادو یدک'));
$vat = (float) ($vatPercent ?? 0);

$subtotal = (float) $order['subtotal'];
$discount = (float) $order['discount_amount'];
$shipping = (float) ($order['shipping_cost'] ?? 0);
$netAmount = max(0, $subtotal - $discount);
$vatAmount = $vat > 0 ? round($netAmount * $vat / 100) : 0;
$grandTotal = (float) $order['total_amount'];

$serial = 'INV-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);

/** تولید بارکد Code128 به‌صورت SVG بدون کتابخانه خارجی */
function code128(string $data): string
{
    $patterns = [
        '11011001100','11001101100','11001100110','10010011000','10010001100','10001001100','10011001000',
        '10011000100','10001100100','11001001000','11001000100','11000100100','10110011100','10011011100',
        '10011001110','10111001100','10011101100','10011100110','11001110010','11001011100','11001001110',
        '11011100100','11001110100','11101101110','11101001100','11100101100','11100100110','11101100100',
        '11100110100','11100110010','11011011000','11011000110','11000110110','10100011000','10001011000',
        '10001000110','10110001000','10001101000','10001100010','11010001000','11000101000','11000100010',
        '10110111000','10110001110','10001101110','10111011000','10111000110','10001110110','11101110110',
        '11010001110','11000101110','11011101000','11011100010','11011101110','11101011000','11101000110',
        '11100010110','11101101000','11101100010','11100011010','11101111010','11001000010','11110001010',
        '10100110000','10100001100','10010110000','10010000110','10000101100','10000100110','10110010000',
        '10110000100','10011010000','10011000010','10000110100','10000110010','11000010010','11001010000',
        '11110111010','11000010100','10001111010','10100111100','10010111100','10010011110','10111100100',
        '10011110100','10011110010','11110100100','11110010100','11110010010','11011011110','11011110110',
        '11110110110','10101111000','10100011110','10001011110','10111101000','10111100010','11110101000',
        '11110100010','10111011110','10111101110','11101011110','11110101110','11010000100','11010010000',
        '11010011100','1100011101011',
    ];
    $data = preg_replace('/[^\x20-\x7E]/', '', $data) ?: '0';
    $data = substr($data, 0, 30);

    $codes = [104]; // START B
    $sum = 104;
    for ($i = 0, $n = strlen($data); $i < $n; $i++) {
        $v = ord($data[$i]) - 32;
        $codes[] = $v;
        $sum += $v * ($i + 1);
    }
    $codes[] = $sum % 103;
    $codes[] = 106; // STOP

    $bits = '';
    foreach ($codes as $c) {
        $bits .= $patterns[$c] ?? '';
    }

    $w = 1.6;
    $h = 44;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . (strlen($bits) * $w) . '" height="' . $h . '" shape-rendering="crispEdges">';
    $x = 0;
    for ($i = 0, $n = strlen($bits); $i < $n; $i++) {
        if ($bits[$i] === '1') {
            $svg .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="' . $h . '" fill="#000"/>';
        }
        $x += $w;
    }
    return $svg . '</svg>';
}
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>فاکتور فروش <?= e($order['tracking_code']) ?></title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: 'IRANSans', 'Vazirmatn', Tahoma, sans-serif; color: #111; font-size: 11.5px;
            padding: 14px; margin: 0; background: #fff; line-height: 1.85; }
        .sheet { max-width: 800px; margin: 0 auto; border: 2px solid #1a1a1a; border-radius: 6px; overflow: hidden; }

        .head { display: flex; justify-content: space-between; align-items: flex-start;
            padding: 12px 16px; border-bottom: 2px solid #1a1a1a; gap: 14px; }
        .head-title { text-align: center; flex: 1; }
        .head-title h1 { font-size: 17px; margin: 0 0 3px; font-weight: 900; }
        .head-title .sub { font-size: 10px; color: #555; }
        .serial-box { border: 1px solid #999; border-radius: 5px; padding: 6px 10px; font-size: 10px;
            min-width: 155px; text-align: right; }
        .serial-box div { display: flex; justify-content: space-between; gap: 8px; }
        .logo-box { width: 74px; text-align: center; }
        .logo-box .mark { width: 50px; height: 50px; border-radius: 12px; background: #8b533a; color: #fff;
            display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 900; margin: 0 auto 3px; }

        .parties { display: grid; grid-template-columns: 1fr 1fr; }
        .party { padding: 9px 14px; border-bottom: 1px solid #1a1a1a; }
        .party:first-child { border-left: 1px solid #1a1a1a; }
        .party h3 { font-size: 11px; margin: 0 0 5px; padding-bottom: 3px; border-bottom: 1px dashed #bbb; font-weight: 900; }
        .kv { display: flex; gap: 5px; font-size: 10.5px; margin-bottom: 1px; }
        .kv b { min-width: 76px; color: #444; font-weight: 700; }
        .kv span { flex: 1; }

        table { width: 100%; border-collapse: collapse; }
        thead th { background: #f0f0f0; border: 1px solid #1a1a1a; padding: 6px 5px;
            font-size: 10px; font-weight: 900; text-align: center; }
        tbody td { border: 1px solid #999; padding: 5px; font-size: 10.5px; text-align: center; }
        tbody td.right { text-align: right; }
        tbody tr:nth-child(even) { background: #fafafa; }

        .totals-wrap { display: grid; grid-template-columns: 1fr 300px; border-top: 1px solid #1a1a1a; }
        .notes-cell { padding: 9px 14px; border-left: 1px solid #1a1a1a; font-size: 10px; }
        .totals { padding: 8px 12px; }
        .totals .row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 11px; }
        .totals .grand { font-weight: 900; font-size: 13px; border-top: 2px solid #1a1a1a;
            margin-top: 5px; padding-top: 6px; }
        .words { padding: 7px 14px; border-top: 1px solid #1a1a1a; border-bottom: 1px solid #1a1a1a;
            font-size: 10.5px; background: #fafafa; }

        .shipping-box { padding: 8px 14px; border-bottom: 1px solid #1a1a1a; font-size: 10.5px;
            display: flex; gap: 20px; flex-wrap: wrap; }

        .signs { display: grid; grid-template-columns: 1fr 1fr 1fr; }
        .sign { padding: 10px 12px 34px; text-align: center; font-size: 10px; border-left: 1px solid #999; }
        .sign:last-child { border-left: 0; }

        .foot { padding: 8px 14px; font-size: 9.5px; color: #555; text-align: center;
            border-top: 2px solid #1a1a1a; background: #fafafa; }
        .barcode { text-align: center; padding: 8px; }
        .barcode svg { max-width: 100%; height: 40px; }
        .barcode div { font-size: 9px; font-family: monospace; letter-spacing: 1px; margin-top: 1px; }

        .toolbar { max-width: 800px; margin: 0 auto 12px; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar button, .toolbar a { padding: 8px 16px; border-radius: 9px; border: 1px solid #ddd;
            background: #fff; cursor: pointer; font-family: inherit; font-size: 12px; font-weight: 700; color: #111; }
        .toolbar button.primary { background: #8b533a; color: #fff; border-color: #8b533a; }
        @media print { .toolbar { display: none !important; } body { padding: 0; } .sheet { border-width: 1px; } }
    </style>
</head>

<body>
    <div class="toolbar">
        <button class="primary" onclick="window.print()">🖨 چاپ فاکتور</button>
        <a href="<?= admin_url('orders/show/' . (int) $order['id']) ?>">بازگشت به سفارش</a>
    </div>

    <div class="sheet">
        <!-- سربرگ -->
        <div class="head">
            <div class="logo-box">
                <div class="mark">پ</div>
                <div style="font-size:8.5px;color:#666"><?= e($g('site_subtitle', 'PRADO YADAK')) ?></div>
            </div>
            <div class="head-title">
                <h1><?= $vat > 0 ? 'صورتحساب فروش کالا و خدمات' : 'فاکتور فروش کالا' ?></h1>
                <div class="sub"><?= e($legalName) ?></div>
            </div>
            <div class="serial-box">
                <div><b>شماره فاکتور:</b><span><?= e($serial) ?></span></div>
                <div><b>کد سفارش:</b><span><?= e($order['tracking_code']) ?></span></div>
                <div><b>تاریخ صدور:</b><span><?= e(Jalali::fromGregorianString($order['created_at'])) ?></span></div>
                <div><b>تاریخ چاپ:</b><span><?= e(Jalali::today()) ?></span></div>
            </div>
        </div>

        <!-- طرفین معامله -->
        <div class="parties">
            <div class="party">
                <h3>مشخصات فروشنده</h3>
                <div class="kv"><b>نام:</b><span><?= e($legalName) ?></span></div>
                <div class="kv"><b>شناسه ملی:</b><span><?= e($g('company_national_id', '—')) ?></span></div>
                <div class="kv"><b>کد اقتصادی:</b><span><?= e($g('company_economic_code', '—')) ?></span></div>
                <div class="kv"><b>شماره ثبت:</b><span><?= e($g('company_registration_no', '—')) ?></span></div>
                <div class="kv"><b>نشانی:</b><span><?= e($g('company_full_address', $g('address', '—'))) ?></span></div>
                <div class="kv"><b>کد پستی:</b><span><?= e($g('company_postal_code', '—')) ?></span></div>
                <div class="kv"><b>تلفن:</b><span><?= e($g('company_phone', $g('phone_number', '—'))) ?></span></div>
            </div>
            <div class="party">
                <h3>مشخصات خریدار</h3>
                <div class="kv"><b>نام:</b><span><?= e($order['full_name'] ?: ($order['recipient_name'] ?: '—')) ?></span></div>
                <div class="kv"><b>کد ملی:</b><span><?= e($order['national_code'] ?: '—') ?></span></div>
                <div class="kv"><b>شناسه ملی:</b><span><?= e($order['national_company_id'] ?: '—') ?></span></div>
                <div class="kv"><b>کد اقتصادی:</b><span><?= e($order['economic_code'] ?: '—') ?></span></div>
                <div class="kv"><b>نشانی:</b><span><?= e($order['shipping_address'] ?: '—') ?></span></div>
                <div class="kv"><b>کد پستی:</b><span><?= e($order['postal_code'] ?: '—') ?></span></div>
                <div class="kv"><b>تلفن:</b><span><?= e($order['recipient_phone'] ?: ($order['phone'] ?: '—')) ?></span></div>
            </div>
        </div>

        <!-- اقلام -->
        <table>
            <thead>
                <tr>
                    <th style="width:28px">ردیف</th>
                    <th>شرح کالا / خدمات</th>
                    <th style="width:78px">کد کالا</th>
                    <th style="width:42px">تعداد</th>
                    <th style="width:78px">مبلغ واحد</th>
                    <th style="width:78px">مبلغ کل</th>
                    <?php if ($vat > 0): ?><th style="width:70px">مالیات و عوارض</th><?php endif; ?>
                    <th style="width:86px">جمع نهایی</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $it):
                    $line = (float) $it['price'] * (int) $it['quantity'];
                    $lineVat = $vat > 0 ? round($line * $vat / 100) : 0; ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td class="right">
                            <?= e($it['name'] ?? 'کالا') ?>
                            <?= !empty($it['brand']) ? '<span style="color:#777"> — ' . e($it['brand']) . '</span>' : '' ?>
                        </td>
                        <td style="font-family:monospace;direction:ltr"><?= e($it['oem_code'] ?: '—') ?></td>
                        <td><?= (int) $it['quantity'] ?></td>
                        <td><?= money($it['price']) ?></td>
                        <td><?= money($line) ?></td>
                        <?php if ($vat > 0): ?><td><?= money($lineVat) ?></td><?php endif; ?>
                        <td style="font-weight:700"><?= money($line + $lineVat) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php for ($f = count($items); $f < 4; $f++): ?>
                    <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><?php if ($vat > 0): ?><td></td><?php endif; ?><td></td></tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- جمع‌ها -->
        <div class="totals-wrap">
            <div class="notes-cell">
                <b>توضیحات:</b>
                <div style="min-height:52px">
                    <?= e($order['user_notes'] ?: '—') ?>
                    <?php if (!empty($order['applied_coupon'])): ?>
                        <br>کد تخفیف اعمال‌شده: <b><?= e($order['applied_coupon']) ?></b>
                    <?php endif; ?>
                </div>
                <div style="font-size:9.5px;color:#666">
                    نحوه پرداخت: کارت به کارت / واریز بانکی
                    <?php if (!empty($order['bank_reference'])): ?>
                        — شماره مرجع: <span style="font-family:monospace"><?= e($order['bank_reference']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="totals">
                <div class="row"><span>جمع کل کالا</span><span><?= money($subtotal) ?></span></div>
                <?php if ($discount > 0): ?>
                    <div class="row"><span>تخفیف</span><span>(<?= money($discount) ?>)</span></div>
                <?php endif; ?>
                <div class="row"><span>مبلغ پس از تخفیف</span><span><?= money($netAmount) ?></span></div>
                <?php if ($vat > 0): ?>
                    <div class="row"><span>مالیات بر ارزش افزوده (<?= $vat ?>٪)</span><span><?= money($vatAmount) ?></span></div>
                <?php endif; ?>
                <?php if ($shipping > 0): ?>
                    <div class="row"><span>هزینه حمل</span><span><?= money($shipping) ?></span></div>
                <?php endif; ?>
                <div class="row grand"><span>مبلغ قابل پرداخت</span><span><?= money($grandTotal) ?> تومان</span></div>
            </div>
        </div>

        <div class="words">
            <b>مبلغ به حروف:</b> <?= e(numberToPersianWords((int) $grandTotal)) ?> تومان
        </div>

        <?php if (!empty($order['shipping_carrier']) || !empty($order['shipping_tracking_code'])): ?>
            <div class="shipping-box">
                <span><b>شرکت حمل:</b> <?= e($carriers[$order['shipping_carrier']] ?? ($order['shipping_carrier'] ?: '—')) ?></span>
                <span><b>کد رهگیری مرسوله:</b>
                    <span style="font-family:monospace;direction:ltr"><?= e($order['shipping_tracking_code'] ?: '—') ?></span></span>
                <?php if (!empty($order['shipped_at'])): ?>
                    <span><b>تاریخ ارسال:</b> <?= e(Jalali::fromGregorianString($order['shipped_at'])) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- امضاها -->
        <div class="signs">
            <div class="sign"><b>مهر و امضای فروشنده</b></div>
            <div class="sign"><b>مهر و امضای خریدار</b></div>
            <div class="sign">
                <div class="barcode">
                    <?= code128((string) $order['tracking_code']) ?>
                    <div><?= e($order['tracking_code']) ?></div>
                </div>
            </div>
        </div>

        <div class="foot">
            <?= e($g('invoice_footer_note', 'این فاکتور مطابق مقررات سازمان امور مالیاتی صادر شده است.')) ?>
            <?php if ($vat <= 0): ?>
                <br><span style="font-size:9px">این مؤسسه مشمول مالیات بر ارزش افزوده نمی‌باشد.</span>
            <?php endif; ?>
            <br>
            <span style="font-size:9px">
                وضعیت سفارش در زمان چاپ: <?= e($statuses[$order['status']] ?? $order['status']) ?>
                | صادرشده در <?= e(Jalali::fromGregorianString(date('Y-m-d H:i:s'), 'Y/m/d - H:i')) ?>
            </span>
        </div>
    </div>

    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 400));</script>
</body>

</html>
<?php
/** تبدیل عدد به حروف فارسی */
function numberToPersianWords(int $number): string
{
    if ($number === 0) return 'صفر';
    if ($number < 0) return 'منفی ' . numberToPersianWords(-$number);

    $ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
    $teens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
    $tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
    $hundreds = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
    $scales = ['', ' هزار', ' میلیون', ' میلیارد', ' هزار میلیارد'];

    $chunkToWords = function (int $n) use ($ones, $teens, $tens, $hundreds): string {
        $parts = [];
        $h = intdiv($n, 100);
        $r = $n % 100;
        if ($h > 0) $parts[] = $hundreds[$h];
        if ($r >= 10 && $r < 20) {
            $parts[] = $teens[$r - 10];
        } else {
            $t = intdiv($r, 10);
            $o = $r % 10;
            if ($t > 0) $parts[] = $tens[$t];
            if ($o > 0) $parts[] = $ones[$o];
        }
        return implode(' و ', array_filter($parts));
    };

    $chunks = [];
    while ($number > 0) {
        $chunks[] = $number % 1000;
        $number = intdiv($number, 1000);
    }

    $words = [];
    for ($i = count($chunks) - 1; $i >= 0; $i--) {
        if ($chunks[$i] === 0) continue;
        $words[] = $chunkToWords($chunks[$i]) . ($scales[$i] ?? '');
    }
    return implode(' و ', $words);
}
