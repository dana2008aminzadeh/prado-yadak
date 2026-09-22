/**
 * دستیار تحلیل زنده سئو — پنل مدیریت پرادو یدک
 * ---------------------------------------------------------------------------
 * همان قواعد Core\SeoAnalyzer را در مرورگر اجرا می‌کند تا مدیر هنگام تایپ،
 * امتیاز عددی، چراغ راهنما و پیش‌نمایش نتیجه گوگل (دسکتاپ/موبایل) را ببیند.
 */
(function () {
    'use strict';

    var CONF = window.SEO_CONF || {};
    var TITLE_MIN = 50, TITLE_MAX = 60;
    var DESC_MIN = 120, DESC_MAX = 155;

    var $ = function (id) { return document.getElementById(id); };

    /** نرمال‌سازی فارسی: ی/ک عربی، نیم‌فاصله، اعراب و فاصله‌های اضافه */
    function norm(text) {
        return (text || '')
            .toLowerCase()
            .replace(/[يﻱ]/g, 'ی')
            .replace(/[كﻙ]/g, 'ک')
            .replace(/[ةۀ]/g, 'ه')
            .replace(/[أإآ]/g, 'ا')
            .replace(/\u200c/g, ' ')
            .replace(/[\u064B-\u065F]/g, '')
            .replace(/[\s\-_.]+/g, ' ')
            .trim();
    }

    function has(haystack, needle) {
        needle = (needle || '').trim();
        if (!needle) return false;
        return norm(haystack).indexOf(norm(needle)) !== -1;
    }

    function faNum(n) {
        return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
    }

    function stripTags(html) {
        var d = document.createElement('div');
        d.innerHTML = html || '';
        return (d.textContent || '').replace(/\s+/g, ' ').trim();
    }

    /** مقادیر فعلی فرم */
    function readForm() {
        var v = function (sel) { var el = document.querySelector(sel); return el ? el.value : ''; };

        return {
            title: ($('f-meta-title') && $('f-meta-title').value.trim()) || CONF.fallbackTitle || '',
            desc: ($('f-meta-desc') && $('f-meta-desc').value.trim()) || CONF.fallbackDesc || '',
            keyword: ($('f-keyword') && $('f-keyword').value.trim()) || '',
            name: v('input[name=name]') || v('input[name=title]'),
            slug: v('input[name=slug]'),
            oem: v('input[name=oem_code]'),
            brand: v('input[name=brand]'),
            bodyHtml: v('textarea[name=description]') || v('textarea[name=content]'),
            images: document.querySelectorAll('.img-cell').length,
            imagesWithAlt: (function () {
                var n = 0;
                document.querySelectorAll('input[name^="image_alt"]').forEach(function (i) {
                    if (i.value.trim()) n++;
                });
                return n;
            })(),
            relatedProducts: document.querySelectorAll('#related-products-list [data-pid]').length
        };
    }

    function check(key, label, status, message, weight) {
        return { key: key, label: label, status: status, message: message, weight: weight };
    }

    function analyze(f) {
        var checks = [];
        var tLen = f.title.length;
        var dLen = f.desc.length;
        var body = stripTags(f.bodyHtml);
        var words = body ? body.split(/\s+/).length : 0;

        checks.push(check('title_length', 'طول عنوان سئو',
            (tLen >= TITLE_MIN && tLen <= TITLE_MAX) ? 'ok' : ((tLen >= 35 && tLen <= 70) ? 'warn' : 'fail'),
            faNum(tLen) + ' کاراکتر (ایده‌آل ۵۰ تا ۶۰).', 15));

        checks.push(check('desc_length', 'طول توضیحات متا',
            (dLen >= DESC_MIN && dLen <= DESC_MAX) ? 'ok' : ((dLen >= 80 && dLen <= 175) ? 'warn' : 'fail'),
            faNum(dLen) + ' کاراکتر (ایده‌آل ۱۲۰ تا ۱۵۵).', 15));

        checks.push(check('keyword_title', 'کلمه کلیدی در عنوان',
            !f.keyword ? 'warn' : (has(f.title, f.keyword) ? 'ok' : 'fail'),
            !f.keyword ? 'کلمه کلیدی هدف را وارد کنید.'
                : (has(f.title, f.keyword) ? 'در عنوان حضور دارد.' : 'در عنوان سئو دیده نمی‌شود.'), 10));

        checks.push(check('keyword_intro', 'کلمه کلیدی در پاراگراف اول',
            !f.keyword ? 'warn' : (has(body.slice(0, 160), f.keyword) ? 'ok' : 'fail'),
            has(body.slice(0, 160), f.keyword) ? 'در ابتدای متن آمده است.'
                : 'کلمه کلیدی را در ۱۶۰ کاراکتر ابتدایی متن بیاورید.', 10));

        if (CONF.type === 'product') {
            checks.push(check('oem', 'کد فنی (OEM)',
                !f.oem ? 'fail' : (has(f.title + ' ' + body, f.oem) ? 'ok' : 'warn'),
                !f.oem ? 'کد فنی ثبت نشده؛ مهم‌ترین عبارت جستجوی خریدار قطعه.'
                    : (has(f.title + ' ' + body, f.oem) ? 'کد فنی در محتوا تکرار شده است.'
                        : 'کد فنی را در عنوان یا متن هم بیاورید.'), 15));

            checks.push(check('brand', 'نام برند',
                !f.brand ? 'warn' : (has(f.title + ' ' + body, f.brand) ? 'ok' : 'warn'),
                !f.brand ? 'برند قطعه مشخص نشده است.' : 'برند را در عنوان یا پاراگراف اول بیاورید.', 8));

            checks.push(check('body_length', 'طول توضیحات',
                body.length >= 300 ? 'ok' : (body.length >= 100 ? 'warn' : 'fail'),
                faNum(body.length) + ' کاراکتر؛ حداقل ۳۰۰ کاراکتر توصیه می‌شود.', 15));

            checks.push(check('images', 'تصاویر و متن جایگزین',
                f.images === 0 ? 'fail' : (f.imagesWithAlt >= f.images ? 'ok' : 'warn'),
                f.images === 0 ? 'هیچ تصویری آپلود نشده است.'
                    : faNum(f.imagesWithAlt) + ' از ' + faNum(f.images) + ' تصویر دارای متن جایگزین است.', 15));
        } else {
            checks.push(check('word_count', 'حجم محتوا',
                words >= 600 ? 'ok' : (words >= 300 ? 'warn' : 'fail'),
                faNum(words) + ' کلمه؛ برای مقاله حداقل ۶۰۰ کلمه توصیه می‌شود.', 15));

            var h2 = (f.bodyHtml.match(/<h2\b/gi) || []).length;
            var h3 = (f.bodyHtml.match(/<h3\b/gi) || []).length;
            checks.push(check('headings', 'تیترهای فرعی (H2/H3)',
                h2 >= 2 ? 'ok' : (h2 >= 1 ? 'warn' : 'fail'),
                faNum(h2) + ' تیتر H2 و ' + faNum(h3) + ' تیتر H3.', 12));

            var imgs = (f.bodyHtml.match(/<img\b/gi) || []).length;
            var alts = (f.bodyHtml.match(/<img\b[^>]*\balt\s*=\s*["'][^"']+["']/gi) || []).length;
            checks.push(check('images', 'تصاویر مقاله',
                imgs === 0 ? 'warn' : (alts === imgs ? 'ok' : 'warn'),
                imgs === 0 ? 'مقاله تصویر ندارد (نسبت ۱۶:۹ توصیه می‌شود).'
                    : faNum(alts) + ' از ' + faNum(imgs) + ' تصویر دارای alt است.', 8));

            checks.push(check('silo', 'لینک به محصولات (Silo)',
                f.relatedProducts > 0 ? 'ok' : 'fail',
                f.relatedProducts > 0 ? faNum(f.relatedProducts) + ' محصول متصل شده است.'
                    : 'هیچ محصولی متصل نیست؛ خواننده بدون مسیر خرید خارج می‌شود.', 12));
        }

        var slugStatus = !f.slug ? 'fail' : (f.slug.length > 75 ? 'warn' : 'ok');
        checks.push(check('slug', 'آدرس صفحه (Slug)', slugStatus,
            'آدرس باید کوتاه، خوانا و شامل کلمه کلیدی باشد.', 7));

        var total = 0, earned = 0;
        checks.forEach(function (c) {
            total += c.weight;
            earned += c.status === 'ok' ? c.weight : (c.status === 'warn' ? c.weight / 2 : 0);
        });

        return { score: total ? Math.round(earned / total * 100) : 0, checks: checks };
    }

    function color(status) {
        return status === 'ok' ? 'var(--green)' : (status === 'warn' ? 'var(--amber)' : 'var(--red)');
    }

    function render() {
        var f = readForm();
        var res = analyze(f);

        // ---- پیش‌نمایش نتیجه گوگل ----
        var maxT = document.getElementById('serp-preview').classList.contains('serp-mobile') ? 55 : 60;
        var maxD = 155;
        $('serp-title').textContent = f.title.length > maxT ? f.title.slice(0, maxT) + '…' : (f.title || '—');
        $('serp-desc').textContent = f.desc.length > maxD ? f.desc.slice(0, maxD) + '…' : (f.desc || '—');
        if (f.slug) {
            $('serp-url').textContent = 'pradoyadak.com' + CONF.urlBase + f.slug;
        }

        // ---- شمارنده‌ها ----
        $('c-title').textContent = faNum(f.title.length);
        $('c-desc').textContent = faNum(f.desc.length);

        var tc = res.checks.find(function (c) { return c.key === 'title_length'; });
        var dc = res.checks.find(function (c) { return c.key === 'desc_length'; });
        $('s-title').className = tc.status === 'ok' ? 'seo-ok' : (tc.status === 'warn' ? 'seo-warn' : 'seo-bad');
        $('s-title').textContent = tc.status === 'ok' ? '✓ مناسب' : (tc.status === 'warn' ? '! قابل بهبود' : '✕ نامناسب');
        $('s-desc').className = dc.status === 'ok' ? 'seo-ok' : (dc.status === 'warn' ? 'seo-warn' : 'seo-bad');
        $('s-desc').textContent = dc.status === 'ok' ? '✓ مناسب' : (dc.status === 'warn' ? '! قابل بهبود' : '✕ نامناسب');

        // ---- امتیاز ----
        var c = res.score >= 80 ? 'var(--green)' : (res.score >= 50 ? 'var(--amber)' : 'var(--red)');
        $('seo-score-value').textContent = faNum(res.score);
        var badge = $('seo-score-badge');
        badge.style.color = c;
        badge.style.borderColor = c;
        badge.style.background = 'transparent';

        // ---- چک‌لیست ----
        var ul = $('seo-checklist');
        ul.innerHTML = '';
        res.checks.forEach(function (chk) {
            var li = document.createElement('li');
            li.style.cssText = 'display:flex;gap:8px;align-items:flex-start;padding:6px 0;border-bottom:1px solid var(--line)';
            li.innerHTML = '<span style="width:9px;height:9px;border-radius:50%;margin-top:6px;flex-shrink:0;background:'
                + color(chk.status) + '"></span><span><b>' + chk.label + ':</b> <span class="text-muted">'
                + chk.message + '</span></span>';
            ul.appendChild(li);
        });
    }

    window.seoSetDevice = function (mode) {
        var box = $('serp-preview');
        box.className = 'serp ' + (mode === 'mobile' ? 'serp-mobile' : 'serp-desktop');
        $('serp-tab-desktop').classList.toggle('btn-primary', mode !== 'mobile');
        $('serp-tab-mobile').classList.toggle('btn-primary', mode === 'mobile');
        render();
    };

    /** پیشنهاد خودکار متا و alt از سرور */
    window.seoAutoSuggest = function (productId) {
        var body = new FormData();
        body.append('product_id', productId);
        body.append('_csrf', CONF.csrf);

        fetch(CONF.suggestUrl || '/admin/products/suggestSeo', {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-Token': CONF.csrf }
        })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success) { alert(d.message || 'خطا در دریافت پیشنهاد'); return; }
                if (!$('f-meta-title').value.trim()) $('f-meta-title').value = d.meta_title;
                if (!$('f-meta-desc').value.trim()) $('f-meta-desc').value = d.meta_description;
                if (!$('f-keyword').value.trim()) $('f-keyword').value = d.focus_keyword;

                Object.keys(d.alts || {}).forEach(function (imgId) {
                    var input = document.querySelector('input[name="image_alt[' + imgId + ']"]');
                    if (input && !input.value.trim()) input.value = d.alts[imgId];
                });
                render();
            })
            .catch(function () { alert('ارتباط با سرور برقرار نشد.'); });
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!$('seo-box')) return;

        ['f-meta-title', 'f-meta-desc', 'f-keyword'].forEach(function (id) {
            var el = $(id);
            if (el) el.addEventListener('input', render);
        });

        ['input[name=name]', 'input[name=title]', 'input[name=slug]', 'input[name=oem_code]',
         'input[name=brand]', 'textarea[name=description]', 'textarea[name=content]'].forEach(function (sel) {
            var el = document.querySelector(sel);
            if (el) el.addEventListener('input', render);
        });

        document.addEventListener('input', function (ev) {
            if (ev.target && ev.target.name && ev.target.name.indexOf('image_alt') === 0) render();
        });

        render();
    });
})();
