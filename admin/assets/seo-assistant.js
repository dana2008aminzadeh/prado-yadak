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

    function containsAny(text, needles) {
        for (var i = 0; i < needles.length; i++) {
            if (has(text, needles[i])) return true;
        }
        return false;
    }

    function faNum(n) {
        return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
    }

    function esc(text) {
        return String(text || '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[ch];
        });
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

    function valueSignalsProduct(f, body) {
        var signals = [];
        if (f.oem && has(body, f.oem)) signals.push('کد فنی در متن');
        if (f.brand && has(body, f.brand)) signals.push('برند در متن');
        if (f.name && has(body, f.name)) signals.push('نام دقیق قطعه');
        if (containsAny(body, ['سازگار', 'مناسب', 'مدل', 'سال', 'خودرو'])) signals.push('سازگاری خودرو');
        if (containsAny(body, ['مشخصات', 'ابعاد', 'جنس', 'کشور سازنده', 'شماره فنی'])) signals.push('مشخصات فنی');
        if (containsAny(body, ['ضمانت', 'اصالت', 'ارسال', 'موجود', 'فاکتور', 'نصب'])) signals.push('اطلاعات خرید/اعتماد');
        return signals.filter(function (v, i, a) { return a.indexOf(v) === i; });
    }

    function productTargets(f, body) {
        var hasFitment = containsAny(body, ['سازگار', 'مناسب', 'مدل', 'سال', 'پرادو', 'لندکروزر', 'هایلوکس']);
        var hasSpecs = containsAny(body, ['مشخصات', 'ابعاد', 'جنس', 'ساخت', 'کشور', 'گارانتی']);
        if (f.oem && (hasFitment || hasSpecs || f.brand)) {
            return { warn: 80, ok: 180, label: 'جستجوی قطعه با کد فنی/OEM' };
        }
        if (!f.oem || hasFitment) {
            return { warn: 180, ok: 420, label: 'قطعه نیازمند توضیح سازگاری و مشخصات' };
        }
        return { warn: 120, ok: 280, label: 'صفحه محصول تراکنشی' };
    }

    function productContentCheck(f, body) {
        var targets = productTargets(f, body);
        var signals = valueSignalsProduct(f, body);
        var len = body.length;
        var status;
        if (len === 0) status = 'fail';
        else if (len >= targets.ok && signals.length >= 2) status = 'ok';
        else if (len >= Math.floor(targets.ok * 0.75) && signals.length >= 4) status = 'ok';
        else if (len >= targets.warn || (len >= 80 && signals.length >= 3)) status = 'warn';
        else status = 'fail';

        return check('body_length', 'طول و ارزش توضیحات', status,
            faNum(len) + ' کاراکتر؛ معیار پویا برای «' + targets.label + '»: هشدار از حدود '
            + faNum(targets.warn) + ' و وضعیت خوب از حدود ' + faNum(targets.ok)
            + ' کاراکتر. سیگنال‌ها: ' + (signals.length ? signals.join('، ') : 'سیگنال ارزش اختصاصی کافی دیده نشد') + '.', 15);
    }

    function articleTargets(title, body) {
        var haystack = title + ' ' + body;
        if (containsAny(haystack, ['خبر', 'اطلاعیه', 'اعلام شد', 'قیمت روز'])) {
            return { warn: 160, ok: 300, label: 'خبری/اطلاع‌رسانی کوتاه' };
        }
        if (containsAny(haystack, ['چگونه', 'آموزش', 'تعویض', 'نصب', 'عیب‌یابی', 'علت', 'راهنما'])) {
            return { warn: 320, ok: 650, label: 'آموزشی/How-to' };
        }
        if (containsAny(haystack, ['لیست', 'چک لیست', 'جدول', 'مقایسه'])) {
            return { warn: 280, ok: 500, label: 'لیستی/مقایسه‌ای' };
        }
        if (containsAny(haystack, ['خرید', 'قیمت', 'بهترین', 'انتخاب'])) {
            return { warn: 250, ok: 450, label: 'تجاری/راهنمای خرید' };
        }
        return { warn: 300, ok: 600, label: 'آموزشی/اطلاعاتی' };
    }

    function usefulAnchor(anchor, href) {
        anchor = (anchor || '').replace(/\s+/g, ' ').trim();
        if (!anchor || anchor.length < 3 || anchor.length > 90) return false;
        if (/^https?:\/\//i.test(anchor) || anchor === href) return false;
        var generic = ['اینجا', 'کلیک کنید', 'بیشتر', 'ادامه مطلب', 'لینک', 'مشاهده', 'این صفحه', 'ادامه', 'خرید'];
        for (var i = 0; i < generic.length; i++) {
            if (norm(anchor) === norm(generic[i])) return false;
        }
        return /[\p{L}\p{N}]/u.test(anchor);
    }

    function linkStats(html) {
        var d = document.createElement('div');
        d.innerHTML = html || '';
        var stats = { internal: 0, product: 0, article: 0, category: 0, parent: 0, goodAnchor: 0, badAnchor: 0, broken: 0 };
        d.querySelectorAll('a').forEach(function (a) {
            var href = (a.getAttribute('href') || '').trim();
            var lower = href.toLowerCase();
            if (!href || href === '#' || lower.indexOf('javascript:') === 0) {
                stats.broken++;
                return;
            }
            if (href.charAt(0) === '#') return;
            if (/^(mailto|tel|sms):/i.test(href)) return;

            var url;
            try {
                url = new URL(href, window.location.origin);
            } catch (e) {
                stats.broken++;
                return;
            }

            var host = url.hostname.toLowerCase();
            var isInternal = href.charAt(0) === '/' || host === window.location.hostname.toLowerCase() || /(^|\.)pradoyadak\.com$/i.test(host);
            if (!isInternal) return;

            var path = url.pathname.replace(/\/$/, '') || '/';
            stats.internal++;
            if (path.indexOf('/product/') === 0) stats.product++;
            else if (path.indexOf('/blog/') === 0 && path !== '/blog') stats.article++;
            else if (path.indexOf('/parts/category/') === 0 || path.indexOf('/parts/model/') === 0 || (path === '/parts' && /(category|model)=/i.test(url.search))) stats.category++;
            else if (path === '/blog' || path === '/parts' || path === '/') stats.parent++;

            if (usefulAnchor(a.textContent || '', href)) stats.goodAnchor++;
            else stats.badAnchor++;
        });
        return stats;
    }

    function articleValueSignals(f, body, stats) {
        var signals = [];
        var h2 = (f.bodyHtml.match(/<h2\b/gi) || []).length;
        var h3 = (f.bodyHtml.match(/<h3\b/gi) || []).length;
        var imgs = (f.bodyHtml.match(/<img\b/gi) || []).length;
        var alts = (f.bodyHtml.match(/<img\b[^>]*\balt\s*=\s*["'][^"']+["']/gi) || []).length;
        if (h2 >= 2 || (h2 >= 1 && h3 >= 1)) signals.push('ساختار تیتر مناسب');
        if (imgs > 0 && alts === imgs) signals.push('تصویر با alt');
        if (stats.internal > 0 || f.relatedProducts > 0) signals.push('لینک داخلی');
        if (/<(ul|ol|table)\b/i.test(f.bodyHtml)) signals.push('لیست/جدول کاربردی');
        if (containsAny(body, ['مثال', 'مرحله', 'نکته', 'هشدار', 'علائم', 'روش'])) signals.push('پاسخ عملی به نیاز کاربر');
        return signals;
    }

    function articleContentCheck(f, body, words, stats) {
        var targets = articleTargets(f.title, body);
        var signals = articleValueSignals(f, body, stats);
        var status;
        if (words === 0) status = 'fail';
        else if (words >= targets.ok && signals.length >= 2) status = 'ok';
        else if (words >= Math.floor(targets.ok * 0.75) && signals.length >= 4) status = 'ok';
        else if (words >= targets.warn || (words >= 180 && signals.length >= 3)) status = 'warn';
        else status = 'fail';

        return check('word_count', 'حجم و ارزش محتوا', status,
            faNum(words) + ' کلمه؛ هدف جستجو «' + targets.label + '». معیار پویا: هشدار از حدود '
            + faNum(targets.warn) + ' و وضعیت خوب از حدود ' + faNum(targets.ok)
            + ' کلمه. سیگنال‌ها: ' + (signals.length ? signals.join('، ') : 'سیگنال ارزش کافی دیده نشد') + '.', 15);
    }

    function internalLinksCheck(f, stats) {
        var counts = {
            product: f.relatedProducts + stats.product,
            article: stats.article,
            category: stats.category,
            parent: stats.parent
        };
        var missing = [];
        if (counts.product <= 0) missing.push('محصول مرتبط');
        if (counts.article <= 0) missing.push('مقاله مرتبط');
        if (counts.category <= 0) missing.push('دسته‌بندی/لندینگ');
        if (counts.parent <= 0) missing.push('صفحه مادر');
        if (stats.badAnchor > 0 || (stats.internal > 0 && stats.goodAnchor < Math.max(1, Math.ceil(stats.internal * 0.6)))) missing.push('انکر تکست توصیفی');

        var status = stats.broken > 0 ? 'fail' : (missing.length === 0 ? 'ok' : ((stats.internal > 0 || f.relatedProducts > 0) ? 'warn' : 'fail'));
        var message = 'محصول: ' + faNum(counts.product)
            + '، مقاله: ' + faNum(counts.article)
            + '، دسته‌بندی/لندینگ: ' + faNum(counts.category)
            + '، صفحه مادر: ' + faNum(counts.parent)
            + '، انکر مناسب: ' + faNum(stats.goodAnchor) + '/' + faNum(stats.internal) + '.';
        if (stats.broken > 0) message += ' ' + faNum(stats.broken) + ' لینک شکسته یا نامعتبر پیدا شد.';
        else if (missing.length) message += ' موارد قابل بهبود: ' + missing.join('، ') + '.';
        else message += ' لینک‌سازی داخلی کامل و قابل فهم است.';

        return check('internal_links', 'لینک‌سازی داخلی', status, message, 12);
    }

    function appendServerOnly(checks) {
        var seen = {};
        checks.forEach(function (c) { seen[c.key] = true; });
        (CONF.serverOnlyChecks || []).forEach(function (c) {
            if (c && c.key && !seen[c.key]) checks.push(c);
        });
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
            !f.keyword ? 'کلمه کلیدی هدف تعیین نشده است.'
                : (has(f.title, f.keyword) ? 'در عنوان حضور دارد.' : 'در عنوان سئو دیده نمی‌شود.'), 10));

        checks.push(check('keyword_intro', 'کلمه کلیدی در پاراگراف اول',
            !f.keyword ? 'warn' : (has(body.slice(0, 160), f.keyword) ? 'ok' : 'fail'),
            !f.keyword ? 'کلمه کلیدی هدف تعیین نشده است.'
                : (has(body.slice(0, 160), f.keyword) ? 'در ابتدای متن آمده است.' : 'کلمه کلیدی را در ۱۶۰ کاراکتر ابتدایی متن بیاورید.'), 10));

        if (CONF.type === 'product') {
            checks.push(check('oem', 'کد فنی (OEM)',
                !f.oem ? 'fail' : (has(f.title + ' ' + body, f.oem) ? 'ok' : 'warn'),
                !f.oem ? 'کد فنی ثبت نشده؛ مهم‌ترین عبارت جستجوی خریدار قطعه.'
                    : (has(f.title + ' ' + body, f.oem) ? 'کد فنی در محتوا تکرار شده است.'
                        : 'کد فنی را در عنوان یا متن هم بیاورید.'), 15));

            checks.push(check('brand', 'نام برند',
                !f.brand ? 'warn' : (has(f.title + ' ' + body, f.brand) ? 'ok' : 'warn'),
                !f.brand ? 'برند قطعه مشخص نشده است.'
                    : (has(f.title + ' ' + body, f.brand) ? 'برند در محتوا ذکر شده است.' : 'برند را در عنوان یا پاراگراف اول بیاورید.'), 8));

            checks.push(productContentCheck(f, body));

            checks.push(check('images', 'تصاویر و متن جایگزین',
                f.images === 0 ? 'fail' : (f.imagesWithAlt >= f.images ? 'ok' : 'warn'),
                f.images === 0 ? 'هیچ تصویری آپلود نشده است.'
                    : faNum(f.imagesWithAlt) + ' از ' + faNum(f.images) + ' تصویر دارای متن جایگزین است.', 15));
        } else {
            var stats = linkStats(f.bodyHtml);
            checks.push(articleContentCheck(f, body, words, stats));

            var h2 = (f.bodyHtml.match(/<h2\b/gi) || []).length;
            var h3 = (f.bodyHtml.match(/<h3\b/gi) || []).length;
            checks.push(check('headings', 'تیترهای فرعی (H2/H3)',
                h2 >= 2 ? 'ok' : (h2 >= 1 ? 'warn' : 'fail'),
                faNum(h2) + ' تیتر H2 و ' + faNum(h3) + ' تیتر H3.', 12));

            var hasKeywordHeading = false;
            if (f.keyword) {
                var d = document.createElement('div');
                d.innerHTML = f.bodyHtml || '';
                d.querySelectorAll('h2,h3').forEach(function (h) {
                    if (has(h.textContent || '', f.keyword)) hasKeywordHeading = true;
                });
            }
            checks.push(check('keyword_heading', 'کلمه کلیدی در تیترها',
                hasKeywordHeading ? 'ok' : 'warn',
                hasKeywordHeading ? 'کلمه کلیدی در یکی از تیترهای فرعی آمده است.'
                    : (!f.keyword ? 'کلمه کلیدی هدف تعیین نشده است.' : 'کلمه کلیدی هدف را حداقل در یک تیتر H2 یا H3 بیاورید.'), 8));

            var imgs = (f.bodyHtml.match(/<img\b/gi) || []).length;
            var alts = (f.bodyHtml.match(/<img\b[^>]*\balt\s*=\s*["'][^"']+["']/gi) || []).length;
            checks.push(check('images', 'تصاویر مقاله',
                imgs === 0 ? 'warn' : (alts === imgs ? 'ok' : 'warn'),
                imgs === 0 ? 'مقاله تصویر ندارد (نسبت ۱۶:۹ توصیه می‌شود).'
                    : faNum(alts) + ' از ' + faNum(imgs) + ' تصویر دارای alt است.', 8));

            checks.push(internalLinksCheck(f, stats));
        }

        var slugStatus = !f.slug ? 'fail' : (f.slug.length > 75 ? 'warn' : 'ok');
        checks.push(check('slug', 'آدرس صفحه (Slug)', slugStatus,
            'آدرس باید کوتاه، خوانا و شامل کلمه کلیدی باشد.', CONF.type === 'article' ? 5 : 7));

        appendServerOnly(checks);

        var total = 0, earned = 0;
        checks.forEach(function (c) {
            total += Number(c.weight || 0);
            earned += c.status === 'ok' ? Number(c.weight || 0) : (c.status === 'warn' ? Number(c.weight || 0) / 2 : 0);
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
                + color(chk.status) + '"></span><span><b>' + esc(chk.label) + ':</b> <span class="text-muted">'
                + esc(chk.message) + '</span></span>';
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
