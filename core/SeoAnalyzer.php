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

    private const DUPLICATE_SIMILARITY_THRESHOLD = 82;

    /** تحلیل یک محصول */
    public static function analyzeProduct(array $product, array $extra = []): array
    {
        $siteName = $extra['site_name'] ?? 'پرادو یدک';
        $images   = $extra['images'] ?? [];

        $title = trim((string) ($product['meta_title'] ?? '')) ?: Seo::productTitle($product, $siteName);
        $desc  = trim((string) ($product['meta_description'] ?? '')) ?: Seo::productDescription($product, $siteName);
        $body  = Seo::clean((string) ($product['description'] ?? $product['desc'] ?? ''));
        $keyword = self::resolveFocusKeyword($product, 'name');
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

        // به‌جای حد ثابت ۳۰۰ کاراکتر، نوع جستجو، پیچیدگی قطعه و سیگنال‌های ارزش واقعی لحاظ می‌شود.
        $checks[] = self::checkProductContentQuality($product, $body, $extra);

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

        $duplicate = self::checkDuplicateContent('product', $product, $title, $desc, $body, $extra);
        if ($duplicate !== null) {
            $checks[] = $duplicate;
        }

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
        $keyword = self::resolveFocusKeyword($article, 'title');

        $checks = [];
        $checks[] = self::checkTitleLength($title);
        $checks[] = self::checkDescriptionLength($desc);
        $checks[] = self::checkKeywordInTitle($keyword, $title);
        $checks[] = self::checkKeywordInFirstParagraph($keyword, $body);

        // به‌جای حد ثابت ۶۰۰ کلمه، هدف جستجو و سیگنال‌های ارزش محتوا لحاظ می‌شود.
        $checks[] = self::checkArticleContentDepth($article, $body, $html, $extra);

        $h2 = (int) preg_match_all('/<h2\b/i', $html);
        $h3 = (int) preg_match_all('/<h3\b/i', $html);
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
                : ($keyword === ''
                    ? 'کلمه کلیدی هدف تعیین نشده است.'
                    : 'کلمه کلیدی هدف را حداقل در یک تیتر H2 یا H3 بیاورید.'),
        ];

        $imgCount = (int) preg_match_all('/<img\b/i', $html);
        $altCount = (int) preg_match_all('/<img\b[^>]*\balt\s*=\s*["\'][^"\']+["\']/i', $html);
        $checks[] = [
            'key'    => 'images',
            'label'  => 'تصاویر مقاله',
            'weight' => 8,
            'status' => $imgCount === 0 ? self::WARN : ($altCount === $imgCount ? self::OK : self::WARN),
            'message' => $imgCount === 0
                ? 'مقاله تصویر ندارد؛ حداقل یک تصویر با نسبت ۱۶:۹ اضافه کنید.'
                : $altCount . ' از ' . $imgCount . ' تصویر دارای alt است.',
        ];

        $checks[] = self::checkArticleInternalLinks($html, $relatedProducts, $extra);

        $checks[] = [
            'key'    => 'slug',
            'label'  => 'آدرس صفحه (Slug)',
            'weight' => 5,
            'status' => self::slugStatus((string) ($article['slug'] ?? '')),
            'message' => 'آدرس باید کوتاه و شامل کلمه کلیدی باشد.',
        ];

        $duplicate = self::checkDuplicateContent('article', $article, $title, $desc, $body, $extra);
        if ($duplicate !== null) {
            $checks[] = $duplicate;
        }

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

    // ----------------------------------------------------------- داده‌های کمکی اختیاری

    /**
     * ساخت کانتکست Duplicate Content از دیتابیس.
     * این متد عمداً جدا از analyze* است تا تحلیل‌گر بدون دیتابیس هم قابل تست باشد.
     */
    public static function duplicateContextFromDatabase(string $type, array $entity, array $options = []): array
    {
        try {
            $db = $options['pdo'] ?? Database::getInstance();
            $siteName = (string) ($options['site_name'] ?? 'پرادو یدک');
            $limit = max(20, min(1000, (int) ($options['candidate_limit'] ?? 300)));

            return $type === 'article'
                ? self::articleDuplicateContextFromDatabase($db, $entity, $siteName, $limit)
                : self::productDuplicateContextFromDatabase($db, $entity, $siteName, $limit);
        } catch (\Throwable $e) {
            return [
                'checked' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /** بررسی واقعی لینک‌های داخلی مقاله با کمک دیتابیس؛ خروجی را به analyzeArticle پاس بدهید. */
    public static function internalLinkAuditFromDatabase(string $html, array $options = []): array
    {
        try {
            $db = $options['pdo'] ?? Database::getInstance();
            $links = self::extractLinks($html, $options);
            $broken = [];
            $seen = [];

            foreach ($links as $link) {
                if (!($link['internal'] ?? false)) {
                    continue;
                }
                $href = (string) ($link['href'] ?? '');
                $path = (string) ($link['path'] ?? '');
                $query = (string) ($link['query'] ?? '');
                $key = $path . ($query !== '' ? '?' . $query : '');
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                if (($link['broken'] ?? false) || !self::internalPathExists($db, $path, $query)) {
                    $broken[] = $href !== '' ? $href : $key;
                }
            }

            return [
                'checked'      => true,
                'broken_count' => count($broken),
                'broken_links' => $broken,
            ];
        } catch (\Throwable $e) {
            return [
                'checked'      => false,
                'broken_count' => 0,
                'broken_links' => [],
                'error'        => $e->getMessage(),
            ];
        }
    }

    // ----------------------------------------------------------- بررسی‌های پایه

    private static function resolveFocusKeyword(array $entity, string $fallbackKey): string
    {
        if (array_key_exists('focus_keyword', $entity)) {
            return trim((string) ($entity['focus_keyword'] ?? ''));
        }
        return trim((string) ($entity[$fallbackKey] ?? ''));
    }

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
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [
                'key'     => 'keyword_intro',
                'label'   => 'کلمه کلیدی در پاراگراف اول',
                'weight'  => 10,
                'status'  => self::WARN,
                'message' => 'کلمه کلیدی هدف تعیین نشده است.',
            ];
        }

        $first = mb_substr($body, 0, 160, 'UTF-8');
        $present = self::contains($first, $keyword);

        return [
            'key'    => 'keyword_intro',
            'label'  => 'کلمه کلیدی در پاراگراف اول',
            'weight' => 10,
            'status' => $present ? self::OK : self::FAIL,
            'message' => $present
                ? 'کلمه کلیدی در ابتدای متن آمده است.'
                : 'کلمه کلیدی را در ۱۶۰ کاراکتر ابتدایی متن بیاورید.',
        ];
    }

    private static function checkProductContentQuality(array $product, string $body, array $extra): array
    {
        $len = mb_strlen($body, 'UTF-8');
        $intent = self::resolveProductIntent($product, $body, $extra);
        $targets = self::productContentTargets($intent);
        $signals = self::productValueSignals($product, $body, $extra);
        $valueScore = count($signals);

        if ($len === 0) {
            $status = self::FAIL;
        } elseif ($len >= $targets['ok'] && $valueScore >= 2) {
            $status = self::OK;
        } elseif ($len >= (int) floor($targets['ok'] * 0.75) && $valueScore >= 4) {
            // محتوای کوتاه‌تر، اما واقعاً مفید و اختصاصی است.
            $status = self::OK;
        } elseif ($len >= $targets['warn'] || ($len >= 80 && $valueScore >= 3)) {
            $status = self::WARN;
        } else {
            $status = self::FAIL;
        }

        $valueText = $signals ? implode('، ', $signals) : 'سیگنال ارزش اختصاصی کافی دیده نشد';

        return [
            'key'    => 'body_length',
            'label'  => 'طول و ارزش توضیحات',
            'weight' => 15,
            'status' => $status,
            'message' => $len . ' کاراکتر؛ معیار پویا برای «' . $targets['label'] . '»: هشدار از حدود '
                . $targets['warn'] . ' و وضعیت خوب از حدود ' . $targets['ok']
                . ' کاراکتر، مشروط به ارزش واقعی محتوا. سیگنال‌ها: ' . $valueText . '.',
        ];
    }

    private static function checkArticleContentDepth(array $article, string $body, string $html, array $extra): array
    {
        $words = $body === '' ? 0 : count(preg_split('/\s+/u', $body) ?: []);
        $intent = self::resolveArticleIntent($article, $body, $extra);
        $targets = self::articleContentTargets($intent);
        $signals = self::articleValueSignals($html, $body, $extra);
        $valueScore = count($signals);

        if ($words === 0) {
            $status = self::FAIL;
        } elseif ($words >= $targets['ok'] && $valueScore >= 2) {
            $status = self::OK;
        } elseif ($words >= (int) floor($targets['ok'] * 0.75) && $valueScore >= 4) {
            // مقاله کوتاه‌تر، اما ساختارمند، لینک‌دار و پاسخ‌گو است.
            $status = self::OK;
        } elseif ($words >= $targets['warn'] || ($words >= 180 && $valueScore >= 3)) {
            $status = self::WARN;
        } else {
            $status = self::FAIL;
        }

        $valueText = $signals ? implode('، ', $signals) : 'سیگنال ارزش کافی دیده نشد';

        return [
            'key'    => 'word_count',
            'label'  => 'حجم و ارزش محتوا',
            'weight' => 15,
            'status' => $status,
            'message' => $words . ' کلمه؛ هدف جستجو «' . $targets['label'] . '» تشخیص داده شد. معیار پویا: هشدار از حدود '
                . $targets['warn'] . ' و وضعیت خوب از حدود ' . $targets['ok']
                . ' کلمه، همراه با پوشش مفید موضوع. سیگنال‌ها: ' . $valueText . '.',
        ];
    }

    private static function checkArticleInternalLinks(string $html, int $relatedProducts, array $extra): array
    {
        $links = self::extractLinks($html, $extra);
        $counts = [
            'product'  => max(0, $relatedProducts),
            'article'  => (int) ($extra['related_articles'] ?? $extra['related_articles_count'] ?? 0),
            'category' => (int) ($extra['category_links'] ?? $extra['category_links_count'] ?? 0),
            'parent'   => (int) ($extra['parent_links'] ?? $extra['parent_links_count'] ?? 0),
        ];
        $internalLinkCount = 0;
        $goodAnchors = 0;
        $badAnchors = 0;
        $placeholderBroken = 0;

        foreach ($links as $link) {
            if ($link['broken'] ?? false) {
                $placeholderBroken++;
                continue;
            }
            if (!($link['internal'] ?? false)) {
                continue;
            }
            $internalLinkCount++;
            $type = (string) ($link['type'] ?? 'other');
            if (isset($counts[$type])) {
                $counts[$type]++;
            }
            if (self::isUsefulAnchor((string) ($link['anchor'] ?? ''), (string) ($link['href'] ?? ''))) {
                $goodAnchors++;
            } else {
                $badAnchors++;
            }
        }

        $brokenExtra = self::extraCount($extra, 'broken_links')
            + (int) ($extra['broken_links_count'] ?? $extra['broken_internal_links'] ?? 0);
        $broken = $placeholderBroken + $brokenExtra;

        $missing = [];
        if ($counts['product'] <= 0) {
            $missing[] = 'محصول مرتبط';
        }
        if ($counts['article'] <= 0) {
            $missing[] = 'مقاله مرتبط';
        }
        if ($counts['category'] <= 0) {
            $missing[] = 'دسته‌بندی/لندینگ';
        }
        if ($counts['parent'] <= 0) {
            $missing[] = 'صفحه مادر';
        }
        if ($badAnchors > 0 || ($internalLinkCount > 0 && $goodAnchors < max(1, (int) ceil($internalLinkCount * 0.6)))) {
            $missing[] = 'انکر تکست توصیفی';
        }

        if ($broken > 0) {
            $status = self::FAIL;
        } elseif ($missing === []) {
            $status = self::OK;
        } elseif ($internalLinkCount > 0 || $relatedProducts > 0) {
            $status = self::WARN;
        } else {
            $status = self::FAIL;
        }

        $message = 'محصول: ' . $counts['product']
            . '، مقاله: ' . $counts['article']
            . '، دسته‌بندی/لندینگ: ' . $counts['category']
            . '، صفحه مادر: ' . $counts['parent']
            . '، انکر مناسب: ' . $goodAnchors . '/' . $internalLinkCount . '.';

        if ($broken > 0) {
            $message .= ' ' . $broken . ' لینک شکسته یا نامعتبر پیدا شد.';
        } elseif ($missing) {
            $message .= ' موارد قابل بهبود: ' . implode('، ', array_values(array_unique($missing))) . '.';
        } else {
            $message .= ' لینک‌سازی داخلی کامل و قابل فهم است.';
        }

        return [
            'key'    => 'internal_links',
            'label'  => 'لینک‌سازی داخلی',
            'weight' => 12,
            'status' => $status,
            'message' => $message,
        ];
    }

    private static function checkDuplicateContent(string $type, array $entity, string $title, string $desc, string $body, array $extra): ?array
    {
        $ctx = $extra['duplicate_content'] ?? $extra['duplicates'] ?? null;
        if ($ctx === null && isset($extra['duplicate_candidates']) && is_array($extra['duplicate_candidates'])) {
            $ctx = self::duplicateContextFromCandidates($type, $entity, $title, $desc, $body, $extra['duplicate_candidates'], $extra);
        }

        if ($ctx === null) {
            if (!empty($extra['skip_duplicate_check'])) {
                return null;
            }
            return [
                'key'    => 'duplicate_content',
                'label'  => 'محتوای تکراری (Duplicate)',
                'weight' => 12,
                'status' => self::WARN,
                'message' => 'برای بررسی title، meta description، slug و شباهت محتوا باید داده‌های مرجع یا duplicate_content به تحلیل‌گر داده شود.',
            ];
        }

        if (!is_array($ctx) || (($ctx['checked'] ?? true) === false)) {
            return [
                'key'    => 'duplicate_content',
                'label'  => 'محتوای تکراری (Duplicate)',
                'weight' => 12,
                'status' => self::WARN,
                'message' => 'بررسی محتوای تکراری کامل انجام نشد؛ دسترسی به داده‌های مرجع یا دیتابیس لازم است.',
            ];
        }

        $titleDup = self::duplicateCount($ctx, 'title');
        $descDup = self::duplicateCount($ctx, 'description');
        $slugDup = self::duplicateCount($ctx, 'slug');
        $similarDup = self::duplicateCount($ctx, 'similar_content');
        $sameProductDesc = self::duplicateCount($ctx, 'same_description');
        $maxSimilarity = (int) ($ctx['max_similarity'] ?? 0);
        if ($maxSimilarity === 0 && isset($ctx['similar_content']) && is_array($ctx['similar_content'])) {
            $maxSimilarity = (int) ($ctx['similar_content']['max_similarity'] ?? 0);
        }

        $hardFail = $slugDup > 0 || $titleDup > 0 || $sameProductDesc > 0;
        $softWarn = $descDup > 0 || $similarDup > 0;
        $status = $hardFail ? self::FAIL : ($softWarn ? self::WARN : self::OK);

        $parts = [];
        if ($titleDup > 0) {
            $parts[] = 'title تکراری: ' . $titleDup;
        }
        if ($descDup > 0) {
            $parts[] = 'meta description تکراری: ' . $descDup;
        }
        if ($slugDup > 0) {
            $parts[] = 'slug تکراری: ' . $slugDup;
        }
        if ($similarDup > 0) {
            $parts[] = 'محتوای بسیار مشابه: ' . $similarDup . ($maxSimilarity > 0 ? ' (تا ' . $maxSimilarity . '٪)' : '');
        }
        if ($sameProductDesc > 0) {
            $parts[] = 'محصول با توضیح یکسان: ' . $sameProductDesc;
        }

        $message = $parts
            ? implode('، ', $parts) . '. ' . self::duplicateExamplesMessage($ctx)
            : 'title، meta description، slug و بدنه محتوا نسبت به داده‌های مرجع تکراری نیستند.';

        return [
            'key'    => 'duplicate_content',
            'label'  => 'محتوای تکراری (Duplicate)',
            'weight' => 12,
            'status' => $status,
            'message' => trim($message),
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

    private static function resolveProductIntent(array $product, string $body, array $extra): string
    {
        $explicit = self::normalizeIntent((string) ($extra['search_intent'] ?? $product['search_intent'] ?? $product['intent'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $oem = trim((string) ($product['oem_code'] ?? $product['oem'] ?? ''));
        $hasFitment = self::extraCount($extra, 'vehicles') > 0
            || self::containsAny($body, ['سازگار', 'مناسب', 'مدل', 'سال', 'پرادو', 'لندکروزر', 'هایلوکس']);
        $hasSpecs = self::extraCount($extra, 'attributes') > 0
            || self::containsAny($body, ['مشخصات', 'ابعاد', 'جنس', 'ساخت', 'کشور', 'گارانتی']);

        if ($oem !== '' && ($hasFitment || $hasSpecs || trim((string) ($product['brand'] ?? '')) !== '')) {
            return 'exact_part';
        }
        if ($oem === '' || $hasFitment) {
            return 'complex_part';
        }
        return 'transactional';
    }

    private static function productContentTargets(string $intent): array
    {
        return match ($intent) {
            'exact_part'   => ['warn' => 80,  'ok' => 180, 'label' => 'جستجوی قطعه با کد فنی/OEM'],
            'complex_part' => ['warn' => 180, 'ok' => 420, 'label' => 'قطعه نیازمند توضیح سازگاری و مشخصات'],
            default        => ['warn' => 120, 'ok' => 280, 'label' => 'صفحه محصول تراکنشی'],
        };
    }

    private static function productValueSignals(array $product, string $body, array $extra): array
    {
        $signals = [];
        $oem = trim((string) ($product['oem_code'] ?? $product['oem'] ?? ''));
        $brand = trim((string) ($product['brand'] ?? ''));
        $name = trim((string) ($product['name'] ?? ''));

        if ($oem !== '' && self::contains($body, $oem)) {
            $signals[] = 'کد فنی در متن';
        }
        if ($brand !== '' && self::contains($body, $brand)) {
            $signals[] = 'برند در متن';
        }
        if ($name !== '' && self::contains($body, $name)) {
            $signals[] = 'نام دقیق قطعه';
        }
        if (self::extraCount($extra, 'vehicles') > 0 || self::containsAny($body, ['سازگار', 'مناسب', 'مدل', 'سال', 'خودرو'])) {
            $signals[] = 'سازگاری خودرو';
        }
        if (self::extraCount($extra, 'attributes') > 0 || self::containsAny($body, ['مشخصات', 'ابعاد', 'جنس', 'کشور سازنده', 'شماره فنی'])) {
            $signals[] = 'مشخصات فنی';
        }
        if (self::containsAny($body, ['ضمانت', 'اصالت', 'ارسال', 'موجود', 'فاکتور', 'نصب'])) {
            $signals[] = 'اطلاعات خرید/اعتماد';
        }

        return array_values(array_unique($signals));
    }

    private static function resolveArticleIntent(array $article, string $body, array $extra): string
    {
        $explicit = self::normalizeIntent((string) ($extra['search_intent'] ?? $article['search_intent'] ?? $article['intent'] ?? ''));
        if ($explicit !== '') {
            return $explicit;
        }

        $haystack = (string) ($article['title'] ?? '') . ' ' . (string) ($article['category'] ?? '') . ' ' . $body;
        if (self::containsAny($haystack, ['خبر', 'اطلاعیه', 'اعلام شد', 'قیمت روز'])) {
            return 'news';
        }
        if (self::containsAny($haystack, ['چگونه', 'آموزش', 'تعویض', 'نصب', 'عیب‌یابی', 'علت', 'راهنما'])) {
            return 'how_to';
        }
        if (self::containsAny($haystack, ['لیست', 'چک لیست', 'جدول', 'مقایسه'])) {
            return 'list';
        }
        if (self::containsAny($haystack, ['خرید', 'قیمت', 'بهترین', 'انتخاب'])) {
            return 'commercial';
        }
        return 'educational';
    }

    private static function articleContentTargets(string $intent): array
    {
        return match ($intent) {
            'news'       => ['warn' => 160, 'ok' => 300, 'label' => 'خبری/اطلاع‌رسانی کوتاه'],
            'commercial' => ['warn' => 250, 'ok' => 450, 'label' => 'تجاری/راهنمای خرید'],
            'list'       => ['warn' => 280, 'ok' => 500, 'label' => 'لیستی/مقایسه‌ای'],
            'how_to'     => ['warn' => 320, 'ok' => 650, 'label' => 'آموزشی/How-to'],
            default      => ['warn' => 300, 'ok' => 600, 'label' => 'آموزشی/اطلاعاتی'],
        };
    }

    private static function articleValueSignals(string $html, string $body, array $extra): array
    {
        $signals = [];
        $h2 = (int) preg_match_all('/<h2\b/i', $html);
        $h3 = (int) preg_match_all('/<h3\b/i', $html);
        $imgCount = (int) preg_match_all('/<img\b/i', $html);
        $altCount = (int) preg_match_all('/<img\b[^>]*\balt\s*=\s*["\'][^"\']+["\']/i', $html);
        $internalLinks = 0;
        foreach (self::extractLinks($html, $extra) as $link) {
            if (($link['internal'] ?? false) && !($link['broken'] ?? false)) {
                $internalLinks++;
            }
        }

        if ($h2 >= 2 || ($h2 >= 1 && $h3 >= 1)) {
            $signals[] = 'ساختار تیتر مناسب';
        }
        if ($imgCount > 0 && $altCount === $imgCount) {
            $signals[] = 'تصویر با alt';
        }
        if ($internalLinks > 0 || (int) ($extra['related_products'] ?? 0) > 0) {
            $signals[] = 'لینک داخلی';
        }
        if (preg_match('/<(ul|ol|table)\b/i', $html)) {
            $signals[] = 'لیست/جدول کاربردی';
        }
        if (self::containsAny($body, ['مثال', 'مرحله', 'نکته', 'هشدار', 'علائم', 'روش'])) {
            $signals[] = 'پاسخ عملی به نیاز کاربر';
        }

        return array_values(array_unique($signals));
    }

    private static function normalizeIntent(string $intent): string
    {
        $intent = trim(mb_strtolower($intent, 'UTF-8'));
        return match ($intent) {
            'exact', 'exact_part', 'oem', 'part_number' => 'exact_part',
            'complex', 'complex_part', 'fitment' => 'complex_part',
            'transactional', 'commercial_product', 'product' => 'transactional',
            'news', 'fresh' => 'news',
            'howto', 'how_to', 'educational_howto' => 'how_to',
            'list', 'comparison', 'compare' => 'list',
            'commercial', 'buying_guide' => 'commercial',
            'educational', 'informational' => 'educational',
            default => '',
        };
    }

    private static function containsAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (self::contains($text, (string) $needle)) {
                return true;
            }
        }
        return false;
    }

    private static function extraCount(array $extra, string $key): int
    {
        if (!array_key_exists($key, $extra)) {
            return 0;
        }
        $value = $extra[$key];
        if (is_array($value)) {
            return count($value);
        }
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }
        return trim((string) $value) === '' ? 0 : 1;
    }

    /** @return array<int,array{href:string,anchor:string,path:string,query:string,internal:bool,type:string,broken:bool}> */
    private static function extractLinks(string $html, array $extra = []): array
    {
        $links = [];
        if ($html === '' || !preg_match_all('/<a\b([^>]*)>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return $links;
        }

        $siteHost = self::hostFromUrl((string) ($extra['site_url'] ?? (defined('SITE_URL') ? SITE_URL : Seo::base())));

        foreach ($matches as $m) {
            $href = trim(self::htmlAttr((string) $m[1], 'href'));
            $anchor = Seo::clean((string) $m[2]);
            $lower = mb_strtolower($href, 'UTF-8');
            $broken = $href === '' || $href === '#' || str_starts_with($lower, 'javascript:');
            $fragmentOnly = !$broken && str_starts_with($href, '#');
            $internal = false;
            $path = '';
            $query = '';

            if (!$broken && !$fragmentOnly && !preg_match('/^(mailto|tel|sms):/i', $href)) {
                $parts = parse_url($href);
                if ($parts !== false) {
                    $host = isset($parts['host']) ? mb_strtolower((string) $parts['host'], 'UTF-8') : '';
                    if ($host === '' || $host === $siteHost || ($siteHost !== '' && str_ends_with($host, '.' . $siteHost))) {
                        $internal = true;
                        $path = (string) ($parts['path'] ?? '');
                        $query = (string) ($parts['query'] ?? '');
                        if ($path === '') {
                            $path = '/';
                        }
                        $path = '/' . ltrim(rawurldecode($path), '/');
                        if ($path !== '/' && str_ends_with($path, '/')) {
                            $path = rtrim($path, '/');
                        }
                    }
                }
            }

            $links[] = [
                'href'     => $href,
                'anchor'   => $anchor,
                'path'     => $path,
                'query'    => $query,
                'internal' => $internal,
                'type'     => $internal ? self::internalLinkType($path, $query) : 'external',
                'broken'   => $broken,
            ];
        }

        return $links;
    }

    private static function htmlAttr(string $attrs, string $name): string
    {
        if (!preg_match('/\b' . preg_quote($name, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $attrs, $m)) {
            return '';
        }
        return html_entity_decode((string) ($m[2] ?? $m[3] ?? $m[4] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private static function internalLinkType(string $path, string $query = ''): string
    {
        if (str_starts_with($path, '/product/')) {
            return 'product';
        }
        if (str_starts_with($path, '/blog/') && $path !== '/blog') {
            return 'article';
        }
        if (str_starts_with($path, '/parts/category/') || str_starts_with($path, '/parts/model/')) {
            return 'category';
        }
        if ($path === '/parts' && preg_match('/(^|&)(category|model)=/i', $query)) {
            return 'category';
        }
        if ($path === '/blog' || $path === '/parts' || $path === '/') {
            return 'parent';
        }
        return 'other';
    }

    private static function isUsefulAnchor(string $anchor, string $href): bool
    {
        $anchor = trim(Seo::clean($anchor));
        if ($anchor === '') {
            return false;
        }
        $len = mb_strlen($anchor, 'UTF-8');
        if ($len < 3 || $len > 90) {
            return false;
        }
        if (preg_match('#^https?://#i', $anchor) || $anchor === $href) {
            return false;
        }
        $generic = ['اینجا', 'کلیک کنید', 'بیشتر', 'ادامه مطلب', 'لینک', 'مشاهده', 'این صفحه', 'ادامه', 'خرید'];
        foreach ($generic as $word) {
            if (self::normalize($anchor) === self::normalize($word)) {
                return false;
            }
        }
        return (bool) preg_match('/[\p{L}\p{N}]/u', $anchor);
    }

    private static function hostFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return mb_strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''), 'UTF-8');
    }

    private static function internalPathExists($db, string $path, string $query = ''): bool
    {
        $staticPaths = ['/', '/blog', '/parts', '/terms', '/login', '/checkout', '/profile', '/product', '/blog-detail'];
        if (in_array($path, $staticPaths, true) || str_starts_with($path, '/media/') || str_starts_with($path, '/uploads/')) {
            return true;
        }

        if (preg_match('#^/product/([^/?#]+)$#u', $path, $m)) {
            return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM products WHERE slug = ?', [rawurldecode($m[1])]) > 0;
        }
        if (preg_match('#^/blog/([^/?#]+)$#u', $path, $m)) {
            return (int) self::dbScalar($db, "SELECT COUNT(*) FROM articles WHERE slug = ? AND status = 'published'", [rawurldecode($m[1])]) > 0;
        }
        if (preg_match('#^/parts/category/([^/?#]+)$#u', $path, $m)) {
            return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM categories WHERE slug = ?', [rawurldecode($m[1])]) > 0;
        }
        if (preg_match('#^/parts/model/([^/?#]+)$#u', $path, $m)) {
            return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM car_models WHERE slug = ?', [rawurldecode($m[1])]) > 0;
        }
        if ($path === '/parts' && $query !== '') {
            parse_str($query, $q);
            if (!empty($q['category'])) {
                $category = is_array($q['category']) ? reset($q['category']) : $q['category'];
                return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM categories WHERE slug = ?', [(string) $category]) > 0;
            }
            if (!empty($q['model'])) {
                $model = is_array($q['model']) ? reset($q['model']) : $q['model'];
                return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM car_models WHERE slug = ?', [(string) $model]) > 0;
            }
        }
        if (preg_match('#^/parts/([^/?#]+)$#u', $path, $m)) {
            return (int) self::dbScalar($db, 'SELECT COUNT(*) FROM seo_landing_pages WHERE slug = ? AND is_active = 1', [rawurldecode($m[1])]) > 0;
        }

        return false;
    }

    private static function productDuplicateContextFromDatabase($db, array $product, string $siteName, int $limit): array
    {
        $title = trim((string) ($product['meta_title'] ?? '')) ?: Seo::productTitle($product, $siteName);
        $desc = trim((string) ($product['meta_description'] ?? '')) ?: Seo::productDescription($product, $siteName);
        $body = Seo::clean((string) ($product['description'] ?? $product['desc'] ?? ''));
        $id = max(0, (int) ($product['id'] ?? 0));
        $conditions = [];
        $params = [];
        $add = static function (string $sql, $value) use (&$conditions, &$params): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }
            $conditions[] = $sql;
            $params[] = $value;
        };

        $add('slug = ?', $product['slug'] ?? '');
        $add('meta_title = ?', $title);
        $add('meta_description = ?', $desc);
        $add('name = ?', $product['name'] ?? '');
        $add('oem_code = ?', $product['oem_code'] ?? $product['oem'] ?? '');
        $add('brand = ?', $product['brand'] ?? '');
        $add('description = ?', $product['description'] ?? $product['desc'] ?? '');
        if (isset($product['category_id']) && (string) $product['category_id'] !== '') {
            $conditions[] = 'category_id = ?';
            $params[] = (int) $product['category_id'];
        }

        if (!$conditions) {
            return self::emptyDuplicateContext(true);
        }

        $where = $id > 0 ? 'id <> ? AND ' : '';
        $baseParams = $id > 0 ? [$id] : [];
        $rows = self::dbFetchAll(
            $db,
            'SELECT id, name, oem_code, brand, category_id, slug, meta_title, meta_description, description
             FROM products WHERE ' . $where . '(' . implode(' OR ', $conditions) . ') ORDER BY id DESC LIMIT ' . $limit,
            array_merge($baseParams, $params)
        );

        return self::duplicateContextFromCandidates('product', $product, $title, $desc, $body, $rows, ['site_name' => $siteName]);
    }

    private static function articleDuplicateContextFromDatabase($db, array $article, string $siteName, int $limit): array
    {
        $title = trim((string) ($article['meta_title'] ?? ''))
            ?: (trim((string) ($article['title'] ?? '')) . ' | ' . $siteName);
        $desc = trim((string) ($article['meta_description'] ?? '')) ?: Seo::articleDescription($article, $siteName);
        $body = Seo::clean((string) ($article['content'] ?? ''));
        $id = max(0, (int) ($article['id'] ?? 0));
        $conditions = [];
        $params = [];
        $add = static function (string $sql, $value) use (&$conditions, &$params): void {
            if ($value === null || trim((string) $value) === '') {
                return;
            }
            $conditions[] = $sql;
            $params[] = $value;
        };

        $add('slug = ?', $article['slug'] ?? '');
        $add('meta_title = ?', $title);
        $add('meta_description = ?', $desc);
        $add('title = ?', $article['title'] ?? '');
        $add('category = ?', $article['category'] ?? '');

        if (!$conditions) {
            return self::emptyDuplicateContext(true);
        }

        $where = $id > 0 ? 'id <> ? AND ' : '';
        $baseParams = $id > 0 ? [$id] : [];
        $rows = self::dbFetchAll(
            $db,
            'SELECT id, title, slug, meta_title, meta_description, summary, content, category
             FROM articles WHERE ' . $where . '(' . implode(' OR ', $conditions) . ') ORDER BY id DESC LIMIT ' . $limit,
            array_merge($baseParams, $params)
        );

        return self::duplicateContextFromCandidates('article', $article, $title, $desc, $body, $rows, ['site_name' => $siteName]);
    }

    private static function duplicateContextFromCandidates(
        string $type,
        array $entity,
        string $title,
        string $desc,
        string $body,
        array $candidates,
        array $extra = []
    ): array {
        $ctx = self::emptyDuplicateContext(true);
        $siteName = (string) ($extra['site_name'] ?? 'پرادو یدک');
        $threshold = (int) ($extra['similarity_threshold'] ?? self::DUPLICATE_SIMILARITY_THRESHOLD);
        $currentId = (int) ($entity['id'] ?? 0);
        $titleKey = self::exactKey($title);
        $descKey = self::exactKey($desc);
        $slugKey = self::slugKey((string) ($entity['slug'] ?? ''));
        $bodyKey = self::exactKey($body);
        $bodyLen = mb_strlen($body, 'UTF-8');

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            if ($currentId > 0 && (int) ($candidate['id'] ?? 0) === $currentId) {
                continue;
            }

            $candidateTitle = self::candidateSeoTitle($type, $candidate, $siteName);
            $candidateDesc = self::candidateSeoDescription($type, $candidate, $siteName);
            $candidateSlug = self::slugKey((string) ($candidate['slug'] ?? ''));
            $candidateBody = self::candidateBody($type, $candidate);

            if ($titleKey !== '' && $titleKey === self::exactKey($candidateTitle)) {
                self::addDuplicate($ctx['title'], $candidate);
            }
            if ($descKey !== '' && $descKey === self::exactKey($candidateDesc)) {
                self::addDuplicate($ctx['description'], $candidate);
            }
            if ($slugKey !== '' && $slugKey === $candidateSlug) {
                self::addDuplicate($ctx['slug'], $candidate);
            }

            if ($type === 'product' && $bodyLen >= 80 && $bodyKey !== '' && $bodyKey === self::exactKey($candidateBody)) {
                self::addDuplicate($ctx['same_description'], $candidate);
            }

            $similarity = self::contentSimilarityPercent($body, $candidateBody);
            if ($similarity >= $threshold) {
                self::addDuplicate($ctx['similar_content'], $candidate);
                $ctx['similar_content']['max_similarity'] = max((int) ($ctx['similar_content']['max_similarity'] ?? 0), $similarity);
                $ctx['max_similarity'] = max((int) ($ctx['max_similarity'] ?? 0), $similarity);
            }
        }

        return $ctx;
    }

    private static function emptyDuplicateContext(bool $checked): array
    {
        return [
            'checked' => $checked,
            'title' => ['count' => 0, 'examples' => []],
            'description' => ['count' => 0, 'examples' => []],
            'slug' => ['count' => 0, 'examples' => []],
            'similar_content' => ['count' => 0, 'examples' => [], 'max_similarity' => 0],
            'same_description' => ['count' => 0, 'examples' => []],
            'max_similarity' => 0,
        ];
    }

    private static function addDuplicate(array &$bucket, array $candidate): void
    {
        $bucket['count'] = (int) ($bucket['count'] ?? 0) + 1;
        if (!isset($bucket['examples']) || !is_array($bucket['examples'])) {
            $bucket['examples'] = [];
        }
        if (count($bucket['examples']) < 3) {
            $bucket['examples'][] = self::candidateLabel($candidate);
        }
    }

    private static function duplicateCount(array $ctx, string $key): int
    {
        if (!array_key_exists($key, $ctx)) {
            return 0;
        }
        $value = $ctx[$key];
        if (is_array($value)) {
            if (array_key_exists('count', $value)) {
                return max(0, (int) $value['count']);
            }
            return count($value);
        }
        return max(0, (int) $value);
    }

    private static function duplicateExamplesMessage(array $ctx): string
    {
        $examples = [];
        foreach (['title', 'description', 'slug', 'similar_content', 'same_description'] as $key) {
            $bucket = $ctx[$key] ?? null;
            if (is_array($bucket) && !empty($bucket['examples']) && is_array($bucket['examples'])) {
                foreach ($bucket['examples'] as $example) {
                    $example = trim((string) $example);
                    if ($example !== '') {
                        $examples[] = $example;
                    }
                }
            }
        }
        $examples = array_values(array_unique($examples));
        return $examples ? 'نمونه: ' . implode('، ', array_slice($examples, 0, 3)) . '.' : '';
    }

    private static function candidateSeoTitle(string $type, array $candidate, string $siteName): string
    {
        $manual = trim((string) ($candidate['meta_title'] ?? ''));
        if ($manual !== '') {
            return $manual;
        }
        return $type === 'article'
            ? trim((string) ($candidate['title'] ?? '')) . ' | ' . $siteName
            : Seo::productTitle($candidate, $siteName);
    }

    private static function candidateSeoDescription(string $type, array $candidate, string $siteName): string
    {
        $manual = trim((string) ($candidate['meta_description'] ?? ''));
        if ($manual !== '') {
            return $manual;
        }
        return $type === 'article'
            ? Seo::articleDescription($candidate, $siteName)
            : Seo::productDescription($candidate, $siteName);
    }

    private static function candidateBody(string $type, array $candidate): string
    {
        return Seo::clean((string) ($type === 'article'
            ? ($candidate['content'] ?? '')
            : ($candidate['description'] ?? $candidate['desc'] ?? '')));
    }

    private static function candidateLabel(array $candidate): string
    {
        $label = trim((string) ($candidate['name'] ?? $candidate['title'] ?? $candidate['slug'] ?? ('#' . ($candidate['id'] ?? ''))));
        $id = (int) ($candidate['id'] ?? 0);
        return $id > 0 ? $label . ' (#' . $id . ')' : $label;
    }

    private static function exactKey(string $text): string
    {
        $text = Seo::clean($text);
        if ($text === '') {
            return '';
        }
        return self::normalize($text);
    }

    private static function slugKey(string $slug): string
    {
        return mb_strtolower(trim(rawurldecode($slug)), 'UTF-8');
    }

    private static function contentSimilarityPercent(string $a, string $b): int
    {
        $a = Seo::clean($a);
        $b = Seo::clean($b);
        if (mb_strlen($a, 'UTF-8') < 80 || mb_strlen($b, 'UTF-8') < 80) {
            return 0;
        }
        if (self::exactKey($a) === self::exactKey($b)) {
            return 100;
        }

        $ta = self::significantTokens($a);
        $tb = self::significantTokens($b);
        if (count($ta) < 12 || count($tb) < 12) {
            return 0;
        }

        $setA = array_fill_keys($ta, true);
        $setB = array_fill_keys($tb, true);
        $intersection = count(array_intersect_key($setA, $setB));
        $union = count($setA + $setB);

        return $union > 0 ? (int) round($intersection / $union * 100) : 0;
    }

    /** @return array<int,string> */
    private static function significantTokens(string $text): array
    {
        $text = self::normalize($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? $text;
        $tokens = preg_split('/\s+/u', $text) ?: [];
        $stop = array_fill_keys([
            'از', 'به', 'در', 'با', 'برای', 'که', 'این', 'آن', 'یک', 'را', 'های', 'ها', 'می', 'شود', 'است',
            'روی', 'تا', 'یا', 'و', 'هم', 'اگر', 'اما', 'هر', 'بر', 'کردن', 'دارد', 'دارای', 'the', 'and', 'for'
        ], true);
        $out = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if ($token === '' || isset($stop[$token])) {
                continue;
            }
            $len = mb_strlen($token, 'UTF-8');
            if ($len < 3 && !preg_match('/^\d{3,}$/', $token)) {
                continue;
            }
            $out[$token] = true;
            if (count($out) >= 500) {
                break;
            }
        }
        return array_keys($out);
    }

    private static function dbFetchAll($db, string $sql, array $params = []): array
    {
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private static function dbScalar($db, string $sql, array $params = [])
    {
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
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
