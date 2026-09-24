<?php
/**
 * جعبه سئو + دستیار تحلیل زنده
 * ---------------------------------------------------------------------------
 * در فرم محصول و مقاله include می‌شود.
 *
 * متغیرهای ورودی:
 *   $seoEntity  (array)  رکورد محصول یا مقاله
 *   $seoType    (string) 'product' | 'article'
 *   $seo        (array)  خروجی Core\SeoAnalyzer (اختیاری)
 *   $seoUrlBase (string) پیشوند آدرس صفحه در سایت، مثل '/product/'
 */
use Admin\core\Auth;

$ent = $seoEntity;
$type = $seoType ?? 'product';
$urlBase = $seoUrlBase ?? '/product/';
$siteName = $GLOBALS['settings']['site_title'] ?? 'پرادو یدک';
$analysis = $seo ?? null;
$score = (int) ($analysis['score'] ?? (int) ($ent['seo_score'] ?? 0));
$scoreColor = $score >= 80 ? 'var(--green)' : ($score >= 50 ? 'var(--amber)' : 'var(--red)');
?>

<div class="card mb" id="seo-box">
    <div class="card-head">
        <h3><i data-lucide="search-check" style="width:15px;vertical-align:-3px"></i> سئو و نمایش در گوگل</h3>
        <span class="badge" id="seo-score-badge"
              style="background:<?= $scoreColor ?>1a;color:<?= $scoreColor ?>;border-color:<?= $scoreColor ?>55">
            امتیاز سئو: <span id="seo-score-value"><?= fa($score) ?></span>/۱۰۰
        </span>
    </div>
    <div class="card-body">

        <!-- ========== شبیه‌ساز نتیجه گوگل (SERP Preview) ========== -->
        <div class="flex gap wrap mb">
            <button type="button" class="btn btn-sm btn-primary" id="serp-tab-desktop" onclick="seoSetDevice('desktop')">
                <i data-lucide="monitor" style="width:13px"></i> دسکتاپ
            </button>
            <button type="button" class="btn btn-sm" id="serp-tab-mobile" onclick="seoSetDevice('mobile')">
                <i data-lucide="smartphone" style="width:13px"></i> موبایل
            </button>
        </div>

        <div id="serp-preview" class="serp serp-desktop">
            <div class="serp-url" id="serp-url">pradoyadak.com<?= e($urlBase . ($ent['slug'] ?? '')) ?></div>
            <div class="serp-title" id="serp-title">—</div>
            <div class="serp-desc" id="serp-desc">—</div>
        </div>

        <!-- ========== فیلدهای اختصاصی سئو ========== -->
        <div class="field mt">
            <label class="fl">عنوان سئو (Meta Title)
                <span class="hint" style="display:inline">— خالی بگذارید تا خودکار ساخته شود</span>
            </label>
            <input type="text" name="meta_title" id="f-meta-title" maxlength="255"
                   value="<?= e($ent['meta_title'] ?? '') ?>"
                   placeholder="<?= e($analysis['preview']['title'] ?? '') ?>">
            <div class="hint">
                <span id="c-title">۰</span> کاراکتر —
                بازه پیشنهادی ۵۰ تا ۶۰ <span id="s-title"></span>؛ عنوان را در پیش‌نمایش موبایل/دسکتاپ هم بررسی کنید (عرض پیکسلی مهم است).
            </div>
        </div>

        <div class="field">
            <label class="fl">توضیحات متا (Meta Description)</label>
            <textarea name="meta_description" id="f-meta-desc" rows="3" maxlength="320"
                      placeholder="<?= e($analysis['preview']['description'] ?? '') ?>"><?= e($ent['meta_description'] ?? '') ?></textarea>
            <div class="hint">
                <span id="c-desc">۰</span> کاراکتر —
                بازه پیشنهادی ۱۲۰ تا ۱۵۵ <span id="s-desc"></span>
                <?php if ($type === 'product'): ?>
                    <br>کاربرد قطعه، مدل/سال سازگار، کد OEM، وضعیت موجودی، گارانتی ثبت‌شده و روش بررسی با VIN را مطابق اطلاعات واقعی محصول بنویسید؛ فقط نام محصول را در یک متن تکراری عوض نکنید.
                <?php endif; ?>
            </div>
        </div>

        <div class="grid g2">
            <div class="field">
                <label class="fl">کلمه کلیدی هدف</label>
                <input type="text" name="focus_keyword" id="f-keyword" maxlength="120"
                       value="<?= e($ent['focus_keyword'] ?? '') ?>"
                       placeholder="مثلا: لنت ترمز جلو کمری">
            </div>
            <div class="field">
                <label class="fl">وضعیت ایندکس (Robots)</label>
                <select name="robots_directive">
                    <?php
                    $rd = $ent['robots_directive'] ?? 'default';
                    $opts = [
                        'default' => 'پیش‌فرض سیستم (Index, Follow)',
                        'index' => 'Index اجباری',
                        'noindex' => 'Noindex, Follow — از نتایج حذف شود',
                        'noindex_nofollow' => 'Noindex, Nofollow — کاملاً مخفی',
                    ];
                    foreach ($opts as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $rd === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field">
            <label class="fl">آدرس کانونیکال سفارشی (اختیاری)</label>
            <input type="text" name="canonical_url" class="mono" maxlength="255"
                   value="<?= e($ent['canonical_url'] ?? '') ?>"
                   placeholder="خالی = آدرس خود صفحه">
            <div class="hint">فقط URL همان دامنه با HTTPS معتبر است؛ fragment و queryهای زائد پیش از نمایش حذف می‌شوند.</div>
        </div>

        <?php if ($type === 'product' && (int) ($ent['id'] ?? 0) > 0): ?>
            <button type="button" class="btn btn-sm" onclick="seoAutoSuggest(<?= (int) $ent['id'] ?>)">
                <i data-lucide="wand-2" style="width:13px"></i> پیشنهاد خودکار متا و متن جایگزین
            </button>
        <?php endif; ?>

        <!-- ========== چک‌لیست زنده ========== -->
        <div class="mt">
            <div class="fl" style="margin-bottom:8px">چک‌لیست سئو</div>
            <ul id="seo-checklist" style="list-style:none;padding:0;margin:0;font-size:11.8px">
                <?php foreach (($analysis['checks'] ?? []) as $c): ?>
                    <li data-key="<?= e($c['key']) ?>" style="display:flex;gap:8px;align-items:flex-start;padding:6px 0;border-bottom:1px solid var(--line)">
                        <span class="dot" style="width:9px;height:9px;border-radius:50%;margin-top:6px;flex-shrink:0;
                              background:<?= $c['status'] === 'ok' ? 'var(--green)' : ($c['status'] === 'warn' ? 'var(--amber)' : 'var(--red)') ?>"></span>
                        <span><b><?= e($c['label']) ?>:</b> <span class="msg text-muted"><?= e($c['message']) ?></span></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<style>
    .serp { border: 1px solid var(--line); border-radius: 12px; padding: 14px; background: #fff; direction: rtl; }
    .serp-url { font-size: 11.5px; color: #0b8043; direction: ltr; text-align: left; margin-bottom: 4px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .serp-title { color: #1a0dab; font-size: 17px; line-height: 1.5; margin-bottom: 3px; }
    .serp-desc { color: #4d5156; font-size: 12.5px; line-height: 1.75; }
    .serp-mobile { max-width: 380px; }
    .serp-mobile .serp-title { font-size: 15px; }
    .serp-mobile .serp-desc { font-size: 12px; }
    .seo-ok { color: var(--green); font-weight: 800; }
    .seo-warn { color: var(--amber); font-weight: 800; }
    .seo-bad { color: var(--red); font-weight: 800; }
</style>

<?php
$serverOnlyChecks = array_values(array_filter(
    $analysis['checks'] ?? [],
    static fn($c) => in_array($c['key'] ?? '', ['duplicate_content'], true)
));
?>
<script>
window.SEO_CONF = {
    type: <?= json_encode($type) ?>,
    siteName: <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>,
    urlBase: <?= json_encode($urlBase, JSON_UNESCAPED_UNICODE) ?>,
    fallbackTitle: <?= json_encode($analysis['preview']['title'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    fallbackDesc: <?= json_encode($analysis['preview']['description'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    serverOnlyChecks: <?= json_encode($serverOnlyChecks, JSON_UNESCAPED_UNICODE) ?>,
    csrf: <?= json_encode(Auth::csrf()) ?>,
    suggestUrl: <?= json_encode(admin_url('products/suggestSeo')) ?>
};
</script>
<script src="/admin/assets/seo-assistant.js" defer></script>
