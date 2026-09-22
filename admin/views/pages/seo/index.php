<?php
use Admin\core\Auth;

$scoreColor = $stats['score'] >= 80 ? 'var(--green)' : ($stats['score'] >= 50 ? 'var(--amber)' : 'var(--red)');

$cards = [
    ['no_image',   'قطعات بدون عکس',              'image-off',      'danger'],
    ['no_alt',     'تصاویر بدون متن جایگزین',      'text-cursor',    'warn'],
    ['no_oem',     'بدون کد فنی OEM',              'hash',           'danger'],
    ['thin',       'توضیحات زیر ۱۰۰ کاراکتر',      'file-x',         'warn'],
    ['no_meta',    'بدون متاتایتل دستی',           'tag',            'warn'],
    ['orphan_out', 'ناموجود بدون جایگزین',         'package-x',      'warn'],
    ['low_score',  'امتیاز سئوی زیر ۵۰',           'gauge',          'warn'],
    ['noindex',    'صفحات Noindex',                'eye-off',        ''],
];
?>

<div class="grid g4 mb">
    <div class="stat">
        <div class="ic-box"><i data-lucide="activity" style="width:18px"></i></div>
        <span class="lbl">نمره سلامت سئوی دامنه</span>
        <span class="val" style="color:<?= $scoreColor ?>"><?= fa($stats['score']) ?><span style="font-size:13px">/۱۰۰</span></span>
        <div class="bar-track mt" style="margin-top:8px">
            <div class="bar-fill" style="width:<?= (int) $stats['score'] ?>%;background:<?= $scoreColor ?>"></div>
        </div>
    </div>
    <div class="stat">
        <div class="ic-box"><i data-lucide="package" style="width:18px"></i></div>
        <span class="lbl">کل قطعات فروشگاه</span>
        <span class="val"><?= money($stats['products']) ?></span>
        <div class="hint">میانگین امتیاز محتوا: <?= fa($stats['avg_score']) ?></div>
    </div>
    <a class="stat danger" href="<?= admin_url('seo/notfound') ?>">
        <div class="ic-box"><i data-lucide="link-2-off" style="width:18px"></i></div>
        <span class="lbl">خطاهای ۴۰۴ حل‌نشده</span>
        <span class="val"><?= money($stats['errors404']) ?></span>
        <div class="hint">برای ریدایرکت کلیک کنید</div>
    </a>
    <a class="stat" href="<?= admin_url('seo/redirects') ?>">
        <div class="ic-box"><i data-lucide="corner-down-left" style="width:18px"></i></div>
        <span class="lbl">ریدایرکت‌های فعال ۳۰۱</span>
        <span class="val"><?= money($stats['redirects']) ?></span>
        <div class="hint">حفاظت از اعتبار صفحات قدیمی</div>
    </a>
</div>

<div class="card mb">
    <div class="card-head">
        <h3>گزارش خطاهای کیفی محتوا</h3>
        <div class="flex gap wrap">
            <a class="btn btn-sm" href="<?= admin_url('seo/landing') ?>"><i data-lucide="layout-template" style="width:13px"></i> لندینگ‌پیج‌ها (<?= fa($stats['landing']) ?>)</a>
            <?php if (can('seo.manage')): ?>
                <?= action_button(admin_url('seo/rescan'), 'بازمحاسبه امتیازها', [
                    'class' => 'btn btn-sm', 'icon' => 'refresh-cw',
                    'confirm' => 'امتیاز سئوی ۵۰۰ محصول در هر بار محاسبه می‌شود. ادامه؟',
                    'fields' => ['offset' => (int) param('next', 0)],
                ]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="grid g4">
            <?php foreach ($cards as [$key, $label, $icon, $cls]):
                $count = (int) ($stats[$key] ?? 0);
                ?>
                <a class="stat <?= $count > 0 ? $cls : '' ?>" href="<?= admin_url('seo/issues', ['type' => $key]) ?>">
                    <div class="ic-box"><i data-lucide="<?= $icon ?>" style="width:18px"></i></div>
                    <span class="lbl"><?= e($label) ?></span>
                    <span class="val"><?= money($count) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid g2">
    <div class="card">
        <div class="card-head"><h3>ضعیف‌ترین صفحات از نظر سئو</h3></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>قطعه</th><th>امتیاز</th><th>عکس</th><th>کد فنی</th><th>طول متن</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($worst as $w): ?>
                        <tr>
                            <td style="max-width:240px"><?= e(excerpt($w['name'], 45)) ?></td>
                            <td>
                                <span class="badge <?= $w['seo_score'] >= 80 ? 'b-green' : ($w['seo_score'] >= 50 ? 'b-amber' : 'b-red') ?>">
                                    <?= fa((int) $w['seo_score']) ?>
                                </span>
                            </td>
                            <td><?= (int) $w['images'] > 0 ? fa($w['images']) : '<span class="badge b-red">ندارد</span>' ?></td>
                            <td class="mono"><?= $w['oem_code'] ? e($w['oem_code']) : '<span class="badge b-red">ندارد</span>' ?></td>
                            <td><?= fa((int) $w['desc_len']) ?></td>
                            <td><a class="btn btn-sm" href="<?= admin_url('products/edit/' . (int) $w['id']) ?>">اصلاح</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$worst): ?>
                        <tr><td colspan="6" class="empty">داده‌ای برای نمایش نیست.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h3>پرتکرارترین خطاهای ۴۰۴</h3>
            <a class="btn btn-sm" href="<?= admin_url('seo/notfound') ?>">همه موارد</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>آدرس</th><th>تعداد</th><th>آخرین بازدید</th></tr></thead>
                <tbody>
                    <?php foreach ($top404 as $l): ?>
                        <tr>
                            <td class="mono" style="max-width:260px;overflow:hidden;text-overflow:ellipsis"><?= e($l['path']) ?></td>
                            <td><span class="badge b-red"><?= fa((int) $l['hits']) ?></span></td>
                            <td class="hint"><?= e(timeAgo($l['last_seen_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$top404): ?>
                        <tr><td colspan="3" class="empty">خطای ۴۰۴ حل‌نشده‌ای ثبت نشده است. 👌</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body">
            <div class="hint">
                مقالات بدون محصول متصل: <b><?= money($stats['articles_nolink']) ?></b> از <?= money($stats['articles']) ?> مقاله —
                هر مقاله بدون کارت خرید یعنی از دست رفتن نرخ تبدیل.
            </div>
        </div>
    </div>
</div>
