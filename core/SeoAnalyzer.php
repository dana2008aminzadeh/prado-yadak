<?php

namespace Core;

/**
 * موتور سنجش کیفیت سئوی محتوا
 * ---------------------------------------------------------------------------
 * همان منطقی که در پنل ادمین به صورت زنده (JavaScript) اجرا می‌شود، اینجا هم
 * سمت سرور پیاده شده است تا:
 *   ۱) امتیاز ذخیره‌شده در ستون seo_score همیشه معتبر باشد،
 *   ۲) داشبورد «دیده‌بان سئو» بتواند کل ۱۵٬۰۰۰ قطعه را بدون مرورگر تحلیل کند.
 *
 * خروجی هر بررسی: ['key','label','status' => ok|warn|fail,'message','weight']
 */
class SeoAnalyzer
{
    public const OK   = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /** تحلیل یک محصول */
    public static function analyzeProduct(array $product, array $extra = []): array
    {
        $siteName = $extra['site_name'] ?? 'پرادو یدک';
        $images   = $extra['images'] ?? [];

        $title = trim((string) ($product['meta_title'] ?? '')) ?: Seo::productTitle($product, $siteName);
        $desc  = trim((string) ($product['meta_description'] ?? '')) ?: Seo::productDescription($product, $siteName);
        $body  = Seo::clean((string) ($product['description'] ?? $product['desc'] ?? ''));
        $keyword = trim((string) ($product['focus_keyword'] ?? $product['name'] ?? ''));
        $oem   = trim((string) ($product['oem_code'] ?? $product['oem'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));

        $checks = [];
        $checks[] = self::checkTitleLength($title);
        $checks[] = self::checkDescriptionLength($desc);
        $checks[] = self::checkKeywordInTitle($keyword, $title);

        // کد فنی — حیاتی‌ترین المان جستجوی قطعات تویوتا
        $checks[] = [
            'key'    => 'oem',
            'label'  => 'کد فنی (OEM)',
            'weight' => 15,
            'status' => $oem === '' ? self::FAIL : (self::contains($title . ' ' . $body, $oem) ? self::OK : self::WARN),
            'message' => $oem === ''
                ? 'کد فنی ثبت نشده است؛ مهم‌ترین عبارت جستجوی خریدار قطعه همین کد است.'
                : (self::contains($title . ' ' . $body, $oem)
                    ? 'کد فنی ' . $oem . ' در عنوان یا متن حضور دارد.'
                    : 'کد فنی ثبت شده اما در عنوان یا متن تکرار نشده است.'),
        ];

        $checks[] = [
            'key'    => 'brand',
            'label'  => 'نام برند',
            'weight' => 8,
            'status' => $brand === '' ? self::WARN : (self::contains($title . ' ' . $body, $brand) ? self::OK : self::WARN),
            'message' => $brand === ''
                ? 'برند قطعه مشخص نشده است (Toyota, Denso, Aisin ...).'
                : (self::contains($title . ' ' . $body, $brand)
                    ? 'برند در محتوا ذکر شده است.'
                    : 'برند را در عنوان یا پاراگراف اول بیاورید.'),
        ];

        $len = mb_strlen($body, 'UTF-8');
        $checks[] = [
            'key'    => 'body_length',
            'label'  => 'طول توضیحات',
            'weight' => 15,
            'status' => $len >= 300 ? self::OK : ($len >= 100 ? self::WARN : self::FAIL),
            'message' => $len . ' کاراکتر؛ ' . ($len >= 300
                ? 'طول مناسب است.'
                : ($len >= 100 ? 'کمی کوتاه است؛ حداقل ۳۰۰ کاراکتر توصیه می‌شود.' : 'محتوای بسیار کوتاه (Thin Content).')),
        ];

        $checks[] = self::checkKeywordInFirstParagraph($keyword, $body);

        $withAlt = 0;
        foreach ($images as $img) {
            if (trim((string) ($img['alt_text'] ?? '')) !== '') {
                $withAlt++;
            }
        }
        $checks[] = [
            'key'    => 'images',
            'label'  => 'تصاویر و متن جایگزین',
            'weight' => 15,
            'status' => !$images ? self::FAIL : ($withAlt === count($images) ? self::OK : self::WARN),
            'message' => !$images
                ? 'هیچ تصویری ثبت نشده؛ ترافیک جستجوی تصویر گوگل از دست می‌رود.'
                : $withAlt . ' از ' . count($images) . ' تصویر دارای متن جایگزین است.',
        ];

        $checks[] = [
            'key'    => 'slug',
            'label'  => 'آدرس صفحه (Slug)',
            'weight' => 7,
            'status' => self::slugStatus((string) ($product['slug'] ?? '')),
            'message' => 'آدرس باید کوتاه، خوانا و شامل نام قطعه باشد.',
        ];

        return self::wrap($checks, ['title' => $title, 'description' => $desc]);
    }

    /** تحلیل یک مقاله */
    public static function analyzeArticle(array $article, array $extra = []): array
    {
        $siteName = $extra['site_name'] ?? 'پرادو یدک';
        $relatedProducts = (int) ($extra['related_products'] ?? 0);

        $title = trim((string) ($article['meta_title'] ?? ''))
            ?: (trim((string) ($article['title'] ?? '')) . ' | ' . $siteName);
        $desc = trim((string) ($article['meta_description'] ?? '')) ?: Seo::articleDescription($article, $siteName);
        $html = (string) ($article['content'] ?? '');
        $body = Seo::clean($html);
        $keyword = trim((string) ($article['focus_keyword'] ?? $article['title'] ?? ''));

        $checks = [];
        $checks[] = self::checkTitleLength($title);
        $checks[] = self::checkDescriptionLength($desc);
        $checks[] = self::checkKeywordInTitle($keyword, $title);
        $checks[] = self::checkKeywordInFirstParagraph($keyword, $body);

        $words = $body === '' ? 0 : count(preg_split('/\s+/u', $body) ?: []);
        $checks[] = [
            'key'    => 'word_count',
            'label'  => 'حجم محتوا',
            'weight' => 15,
            'status' => $words >= 600 ? self::OK : ($words >= 300 ? self::WARN : self::FAIL),
            'message' => $words . ' کلمه؛ برای مقاله آموزشی حداقل ۶۰۰ کلمه توصیه می‌شود.',
        ];

        $h2 = preg_match_all('/<h2\b/i', $html);
        $h3 = preg_match_all('/<h3\b/i', $html);
        $checks[] = [
            'key'    => 'headings',
            'label'  => 'تیترهای فرعی (H2/H3)',
            'weight' => 12,
            'status' => $h2 >= 2 ? self::OK : ($h2 >= 1 ? self::WARN : self::FAIL),
            'message' => $h2 . ' تیتر H2 و ' . $h3 . ' تیتر H3 پیدا شد؛ حداقل ۲ تیتر H2 لازم است.',
        ];

        $hasKeywordHeading = false;
        if ($keyword !== '' && preg_match_all('/<h[23]\b[^>]*>(.*?)<\/h[23]>/is', $html, $m)) {
            foreach ($m[1] as $heading) {
                if (self::contains(Seo::clean($heading), $keyword)) {
                    $hasKeywordHeading = true;
                    break;
                }
            }
        }
        $checks[] = [
            'key'    => 'keyword_heading',
            'label'  => 'کلمه کلیدی در تیترها',
            'weight' => 8,
            'status' => $hasKeywordHeading ? self::OK : self::WARN,
            'message' => $hasKeywordHeading
                ? 'کلمه کلیدی در یکی از تیترهای فرعی آمده است.'
                : 'کلمه کلیدی هدف را حداقل در یک تیتر H2 یا H3 بیاورید.',
        ];

        $imgCount = preg_match_all('/<img\b/i', $html);
        $altCount = preg_match_all('/<img\b[^>]*\balt\s*=\s*["\'][^"\']+["\']/i', $html);
        $checks[] = [
            'key'    => 'images',
            'label'  => 'تصاویر مقاله',
            'weight' => 8,
            'status' => $imgCount === 0 ? self::WARN : ($altCount === $imgCount ? self::OK : self::WARN),
            'message' => $imgCount === 0
                ? 'مقاله تصویر ندارد؛ حداقل یک تصویر با نسبت ۱۶:۹ اضافه کنید.'
                : $altCount . ' از ' . $imgCount . ' تصویر دارای alt است.',
        ];

        $checks[] = [
            'key'    => 'silo',
            'label'  => 'لینک به محصولات (Silo)',
            'weight' => 12,
            'status' => $relatedProducts > 0 ? self::OK : self::FAIL,
            'message' => $relatedProducts > 0
                ? $relatedProducts . ' محصول مرتبط به مقاله متصل شده است.'
                : 'هیچ محصولی به این مقاله متصل نیست؛ مسیر تبدیل خواننده به خریدار وجود ندارد.',
        ];

        $checks[] = [
            'key'    => 'slug',
            'label'  => 'آدرس صفحه (Slug)',
            'weight' => 5,
            'status' => self::slugStatus((string) ($article['slug'] ?? '')),
            'message' => 'آدرس باید کوتاه و شامل کلمه کلیدی باشد.',
        ];

        return self::wrap($checks, ['title' => $title, 'description' => $desc]);
    }

    // ------------------------------------------------- دروازه اجباری انتشار

    /**
     * حداقل‌های اجباری برای «انتشار» یک مقاله.
     * ---------------------------------------------------------------------
     * این مقادیر صرفاً نمایشی نیستند؛ اگر رعایت نشوند مقاله منتشر نمی‌شود و
     * به‌صورت پیش‌نویس ذخیره می‌ماند.
     */
    public const PUBLISH_MIN_WORDS = 300;   // حداقل حجم محتوا
    public const PUBLISH_MIN_H2    = 2;     // حداقل تعداد تیتر H2
    public const PUBLISH_MIN_DESC  = 80;    // حداقل طول توضیحات متا

    /**
     * بررسی اجباری پیش از انتشار مقاله.
     * ---------------------------------------------------------------------
     * برخلاف analyzeArticle که فقط «امتیاز» می‌دهد، این متد قانون است:
     * تعداد کلمات، ساختار تیترها (H2/H3) و alt تصاویر باید در حد قابل قبول
     * باشند، وگرنه اجازه انتشار صادر نمی‌شود.
     *
     * @return array{passed:bool, errors:array<int,string>, stats:array}
     */
    public static function publishGate(array $article): array
    {
        $html = (string) ($article['content'] ?? '');
        $body = Seo::clean($html);
        $title = trim((string) ($article['title'] ?? ''));
        $desc = trim((string) ($article['meta_description'] ?? ''));
        if ($desc === '') {
            $desc = Seo::clean((string) ($article['summary'] ?? ''));
        }

        $words = $body === '' ? 0 : count(preg_split('/\s+/u', $body) ?: []);
        $h2 = (int) preg_match_all('/<h2\b/i', $html);
        $h3 = (int) preg_match_all('/<h3\b/i', $html);

        // تصاویر بدون alt معنادار (alt="" یا نبود کامل صفت)
        $imgTotal = (int) preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        $imgMissingAlt = 0;
        foreach (($imgMatches[0] ?? []) as $tag) {
            if (!preg_match('/\balt\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $m)) {
                $imgMissingAlt++;
                continue;
            }
            $alt = trim((string) ($m[2] ?? '') . ($m[3] ?? '') . ($m[4] ?? ''));
            if ($alt === '') {
                $imgMissingAlt++;
            }
        }

        $errors = [];

        if ($title === '') {
            $errors[] = 'عنوان مقاله الزامی است.';
        }

        if ($words < self::PUBLISH_MIN_WORDS) {
            $errors[] = sprintf(
                'حجم محتوا کافی نیست: %d کلمه ثبت شده و حداقل %d کلمه برای انتشار لازم است.',
                $words,
                self::PUBLISH_MIN_WORDS
            );
        }

        if ($h2 < self::PUBLISH_MIN_H2) {
            $errors[] = sprintf(
                'ساختار تیترها ناقص است: %d تیتر H2 پیدا شد و حداقل %d تیتر H2 برای انتشار لازم است.',
                $h2,
                self::PUBLISH_MIN_H2
            );
        }

        if ($imgMissingAlt > 0) {
            $errors[] = sprintf(
                '%d تصویر از %d تصویر مقاله متن جایگزین (alt) ندارد؛ برای انتشار همه تصاویر باید alt داشته باشند.',
                $imgMissingAlt,
                $imgTotal
            );
        }

        if (mb_strlen($desc, 'UTF-8') < self::PUBLISH_MIN_DESC) {
            $errors[] = sprintf(
                'توضیحات متا یا خلاصه مقاله کوتاه است (%d کاراکتر)؛ حداقل %d کاراکتر لازم است.',
                mb_strlen($desc, 'UTF-8'),
                self::PUBLISH_MIN_DESC
            );
        }

        return [
            'passed' => $errors === [],
            'errors' => $errors,
            'stats'  => [
                'words'           => $words,
                'h2'              => $h2,
                'h3'              => $h3,
                'images'          => $imgTotal,
                'images_missing_alt' => $imgMissingAlt,
                'description_len' => mb_strlen($desc, 'UTF-8'),
            ],
        ];
    }

    // ----------------------------------------------------------- بررسی‌های پایه

    private static function checkTitleLength(string $title): array
    {
        $len = mb_strlen($title, 'UTF-8');
        return [
            'key'    => 'title_length',
            'label'  => 'طول عنوان سئو',
            'weight' => 15,
            'status' => ($len >= Seo::TITLE_MIN && $len <= Seo::TITLE_MAX) ? self::OK
                : (($len >= 35 && $len <= 70) ? self::WARN : self::FAIL),
            'message' => $len . ' کاراکتر (بازه ایده‌آل ' . Seo::TITLE_MIN . ' تا ' . Seo::TITLE_MAX . ').',
        ];
    }

    private static function checkDescriptionLength(string $desc): array
    {
        $len = mb_strlen($desc, 'UTF-8');
        return [
            'key'    => 'desc_length',
            'label'  => 'طول توضیحات متا',
            'weight' => 15,
            'status' => ($len >= Seo::DESC_MIN && $len <= Seo::DESC_MAX) ? self::OK
                : (($len >= 80 && $len <= 175) ? self::WARN : self::FAIL),
            'message' => $len . ' کاراکتر (بازه ایده‌آل ' . Seo::DESC_MIN . ' تا ' . Seo::DESC_MAX . ').',
        ];
    }

    private static function checkKeywordInTitle(string $keyword, string $title): array
    {
        return [
            'key'    => 'keyword_title',
            'label'  => 'کلمه کلیدی در عنوان',
            'weight' => 10,
            'status' => $keyword === '' ? self::WARN : (self::contains($title, $keyword) ? self::OK : self::FAIL),
            'message' => $keyword === ''
                ? 'کلمه کلیدی هدف تعیین نشده است.'
                : (self::contains($title, $keyword)
                    ? 'کلمه کلیدی در عنوان حضور دارد.'
                    : 'کلمه کلیدی «' . $keyword . '» در عنوان سئو دیده نمی‌شود.'),
        ];
    }

    private static function checkKeywordInFirstParagraph(string $keyword, string $body): array
    {
        $first = mb_substr($body, 0, 160, 'UTF-8');
        return [
            'key'    => 'keyword_intro',
            'label'  => 'کلمه کلیدی در پاراگراف اول',
            'weight' => 10,
            'status' => $keyword === '' ? self::WARN : (self::contains($first, $keyword) ? self::OK : self::FAIL),
            'message' => self::contains($first, $keyword)
                ? 'کلمه کلیدی در ابتدای متن آمده است.'
                : 'کلمه کلیدی را در ۱۶۰ کاراکتر ابتدایی متن بیاورید.',
        ];
    }

    private static function slugStatus(string $slug): string
    {
        $slug = trim($slug);
        if ($slug === '') {
            return self::FAIL;
        }
        $len = mb_strlen($slug, 'UTF-8');
        if ($len > 75 || preg_match('/(^|-)\d{3,}(-|$)/', $slug)) {
            return self::WARN;
        }
        return self::OK;
    }

    /** مقایسه‌ی مقاوم به نیم‌فاصله، «ی/ک» عربی و حروف بزرگ/کوچک */
    public static function contains(string $haystack, string $needle): bool
    {
        $needle = trim($needle);
        if ($needle === '') {
            return false;
        }
        return str_contains(self::normalize($haystack), self::normalize($needle));
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = str_replace(
            ['ي', 'ك', 'ۀ', 'ة', 'أ', 'إ', 'آ', "\u{200c}", "\u{200f}", "\u{200e}"],
            ['ی', 'ک', 'ه', 'ه', 'ا', 'ا', 'ا', ' ', '', ''],
            $text
        );
        $text = preg_replace('/[\p{Mn}]/u', '', $text) ?? $text;   // حذف اعراب
        $text = preg_replace('/[\s\-_.]+/u', ' ', $text) ?? $text;
        return trim($text);
    }

    /** جمع‌بندی و محاسبه امتیاز ۰ تا ۱۰۰ */
    private static function wrap(array $checks, array $preview): array
    {
        $total = 0;
        $earned = 0;
        foreach ($checks as $c) {
            $w = (int) $c['weight'];
            $total += $w;
            $earned += match ($c['status']) {
                self::OK   => $w,
                self::WARN => $w * 0.5,
                default    => 0,
            };
        }
        $score = $total > 0 ? (int) round($earned / $total * 100) : 0;

        return [
            'score'   => $score,
            'grade'   => $score >= 80 ? 'good' : ($score >= 50 ? 'average' : 'poor'),
            'checks'  => $checks,
            'preview' => $preview,
            'issues'  => array_values(array_filter($checks, fn($c) => $c['status'] !== self::OK)),
        ];
    }
}
