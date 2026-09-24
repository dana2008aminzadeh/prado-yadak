<?php
// Run with PHP 8.1+ (mbstring): php tests/seo_regression.php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
define('SITE_URL', 'https://pradoyadak.com');
require __DIR__ . '/../core/UrlCanonicalizer.php';
require __DIR__ . '/../core/Seo.php';

use Core\Seo;
use Core\UrlCanonicalizer;

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function same($expected, $actual, string $message): void
{
    check($actual === $expected, $message . ': expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
}

/** @param array<string,mixed> $vars */
function renderHead(string $uri, array $get = [], array $vars = [], int $status = 200): string
{
    global $settings;
    $settings = ['site_title' => 'پرادو یدک'];
    $_GET = $get;
    $_SERVER['REQUEST_URI'] = $uri;
    http_response_code($status);
    extract($vars, EXTR_SKIP);
    ob_start();
    include __DIR__ . '/../assets/php/head.php';
    return ob_get_clean();
}

function tagContent(string $html, string $pattern): string
{
    check(preg_match($pattern, $html, $matches) === 1, 'Tag missing: ' . $pattern);
    return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
}

$base = 'https://pradoyadak.com';
$_SERVER['REQUEST_URI'] = '/product/example';
$_GET = [];
same($base . '/product/example', Seo::normalizeCanonicalHost('https://evil.test/bad#frag'), 'External canonical falls back to current path');
same($base . '/product/example', Seo::normalizeCanonicalHost('https://pradoyadak.com.evil.test/bad'), 'Lookalike host rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost('//evil.test/bad'), 'Protocol-relative external host rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost('https://user@pradoyadak.com/bad'), 'Userinfo rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost('https://pradoyadak.com:444/bad'), 'Unexpected port rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost("https://pradoyadak.com/\\evil"), 'Backslash rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost('javascript:alert(1)'), 'Unsafe scheme rejected');
same($base . '/product/example', Seo::normalizeCanonicalHost('/parts/%2e%2e/product/example'), 'Dot-segment rejected');
same($base . '/product/example', Seo::canonical('https://evil.test/bad', ['product' => ['slug' => 'example']]), 'Explicit canonical also validated in Seo::canonical');
same($base . '/terms', Seo::normalizeCanonicalHost('http://www.pradoyadak.com/terms?sort=x#anchor'), 'HTTP www, query and fragment normalized');
same($base . '/parts?category=brakes&model=camry&page=2', Seo::normalizeCanonicalHost('http://pradoyadak.com/parts/?page=02&sort=popular&model=camry&category=brakes#gallery'), 'Catalog query allowlist and order');
same($base . '/parts', Seo::normalizeCanonicalHost($base . '/parts?page=1&q=abc&minPrice=10'), 'First page and filters removed');
same($base . '/parts/category/brakes?page=3', Seo::normalizeCanonicalHost($base . '/parts/category/brakes?sort=price-asc&page=3#frag'), 'Landing only keeps page');
same($base . '/blog', Seo::normalizeCanonicalHost($base . '/blog?q=search&page=3'), 'Blog search is noncanonical');
same($base . '/parts', Seo::normalizeCanonicalHost($base . '/parts?page=99999'), 'Excessive page never clamped to page 1000');

same(null, Seo::catalogPage(['page' => '99999']), 'Excessive page rejected');
same(null, Seo::catalogPage(['page' => ['2']]), 'Array page rejected');
same(2, Seo::catalogPage(['page' => '2']), 'Valid page accepted');
same(1, Seo::catalogPage([]), 'Default page');
same('page=99999', UrlCanonicalizer::buildQuery(['page' => '99999'], '/parts'), 'Huge page reaches controller for 404');
same('', UrlCanonicalizer::buildQuery(['minPrice' => '10', 'view' => 'grid', 'unknown' => 'x'], '/parts'), 'Unsupported filters redirected to parent');
same('q=brakes', UrlCanonicalizer::buildQuery(['page' => '8', 'q' => 'brakes', 'sort' => 'popular'], '/blog'), 'Blog only accepts search');
same('noindex, follow', Seo::catalogRobots(['newFilter' => 'value']), 'Unregistered filter fails closed');
same('noindex, follow', Seo::catalogRobots(['category' => 'brakes', 'model' => 'camry']), 'Filter combinations require a dedicated landing');
same('index, follow', Seo::catalogRobots(['brand' => 'Toyota', 'page' => '2']), 'Single taxonomy can be indexed');

$GLOBALS['car_models'] = ['camry' => 'کمری'];
$prod = [
    'name' => 'لنت ترمز جلو', 'slug' => 'front-pads', 'oem' => '04465-33471',
    'model' => 'camry', 'inStock' => true, 'desc' => 'برای ترمزگیری نرم و توقف کوتاه در شهر.',
    'vehicles' => [['name' => 'کمری']],
    'technicalSpecifications' => [
        ['attr_key' => 'کاربرد', 'attr_value' => 'محور جلو'],
        ['attr_key' => 'گارانتی', 'attr_value' => '۱۲ ماه'],
    ],
];
$desc = Seo::productDescription($prod, 'پرادو یدک');
check(mb_strlen($desc, 'UTF-8') <= Seo::DESC_MAX, 'Product description length including ellipsis');
foreach (['لنت ترمز', 'کمری', '04465-33471', 'موجود در انبار', '۱۲ ماه', 'کاربرد'] as $fact) {
    check(str_contains($desc, $fact), 'Product description missing actual fact ' . $fact . ': ' . $desc);
}
check(str_starts_with(Seo::productTitle($prod, 'پرادو یدک'), 'قیمت و خرید'), 'Product title captures purchase intent');
check(str_contains(Seo::productTitle($prod, 'پرادو یدک'), '04465-33471'), 'Product title retains OEM identifier');
check(str_contains(Seo::productTitle($prod, 'پرادو یدک'), 'کمری'), 'Product title differentiates compatible model');
same(Seo::productDescription($prod, 'پرادو یدک'), Seo::metaDescription('کوتاه', '', $prod), 'Very short manual product description replaced by specific facts');
same('متن بدون تگ', Seo::metaDescription('<b>متن بدون تگ</b>', 'Fallback'), 'Description HTML stripped');
check(mb_strlen(Seo::truncate(str_repeat('عبارت ', 90), Seo::DESC_MAX), 'UTF-8') <= Seo::DESC_MAX, 'Ellipsis counts toward max length');
same('محتوا', Seo::clean('&lt;script&gt;محتوا&lt;/script&gt;'), 'Encoded HTML is stripped before SEO output');
$jsonLd = Seo::graph([Seo::webPageNode($base . '/parts', '</script><img src=x>', 'توضیح')]);
check(substr_count($jsonLd, '</script>') === 1 && str_contains($jsonLd, '\\u003C/script\\u003E'), 'JSON-LD cannot be ended by title text');
$seo = Seo::resolve(['canonical_url' => 'http://evil.test/click', 'meta_description' => str_repeat('الف ', 150)], [
    'title' => 'عنوان', 'canonical' => $base . '/product/example', 'description' => 'پیش‌فرض',
]);
same($base . '/product/example', $seo['canonical'], 'Manual canonical in database cannot change host');
check(mb_strlen($seo['description'], 'UTF-8') <= Seo::DESC_MAX, 'Resolved description is limited before schema');

$html = renderHead('/product/front-pads?utm_source=x', [], [
    'product' => $prod, 'canonicalUrl' => 'http://evil.test/steal?page=1#fragment',
    'pageTitle' => 'لنت جلو کمری', 'metaDescription' => '<b>کوتاه</b>',
]);
same($base . '/product/front-pads', tagContent($html, '/<link rel="canonical" href="([^"]+)"/'), 'Head final canonical uses entity');
same($base . '/product/front-pads', tagContent($html, '/<meta property="og:url" content="([^"]+)"/'), 'OG URL matches canonical');
same($base . '/product/front-pads', tagContent($html, '/<meta name="twitter:url" content="([^"]+)"/'), 'Twitter URL matches canonical');
check(!str_contains($html, '<meta property="og:image"'), 'No fake product image when product has no photo');
check(!str_contains($html, 'googlebot'), 'No unrequested Googlebot snippet directives');
check(!str_contains($html, '@latest') && str_contains($html, '/assets/js/vendor/lucide-0.468.0.min.js'), 'Pinned local icons');
check(mb_strlen(tagContent($html, '/<meta name="description" content="([^"]+)"/'), 'UTF-8') <= Seo::DESC_MAX, 'Final meta description limited');

$html = renderHead('/parts?sort=popular', ['sort' => 'popular'], ['pageTitle' => 'کاتالوگ', 'robotsMeta' => 'index, follow']);
same('noindex, follow', tagContent($html, '/<meta name="robots" content="([^"]+)"/'), 'Controller cannot index low-value filters');
same($base . '/parts', tagContent($html, '/<link rel="canonical" href="([^"]+)"/'), 'Filter canonical points to parent');
$html = renderHead('/parts?page=2', ['page' => '2'], ['pageTitle' => 'کاتالوگ - صفحه 2', 'page' => 2, 'totalPages' => 3]);
same($base . '/parts?page=2', tagContent($html, '/<link rel="canonical" href="([^"]+)"/'), 'Catalog page 2 self canonical');
same($base . '/parts', tagContent($html, '/<link rel="prev" href="([^"]+)"/'), 'Prev page drops ?page=1');
same($base . '/parts?page=3', tagContent($html, '/<link rel="next" href="([^"]+)"/'), 'Next page URL');
$html = renderHead('/parts/unique-landing?page=2', ['page' => '2', 'landing' => 'unique-landing'], [
    'pageTitle' => 'لندینگ اختصاصی - صفحه 2', 'page' => 2, 'totalPages' => 3,
]);
same($base . '/parts/unique-landing?page=2', tagContent($html, '/<link rel="canonical" href="([^"]+)"/'), 'Manual landing canonical page');
same($base . '/parts/unique-landing', tagContent($html, '/<link rel="prev" href="([^"]+)"/'), 'Manual landing has prev link');
$html = renderHead('/terms');
same('index, follow', tagContent($html, '/<meta name="robots" content="([^"]+)"/'), 'Known public route has specific fallback title');
check(str_contains($html, 'شرایط') || str_contains($html, 'قوانین'), 'Known route title reflects intent');
check(str_contains($html, '<meta property="og:image:width" content="700">')
    && str_contains($html, '<meta property="og:image:height" content="700">'), 'Logo shares its measured dimensions');
check(str_contains($html, 'twitter:image:alt'), 'Twitter image alt rendered');
$html = renderHead('/new-route');
same('noindex, follow', tagContent($html, '/<meta name="robots" content="([^"]+)"/'), 'New route without title is not indexable');
$html = renderHead('/parts?page=99999', ['page' => '99999'], ['page' => 99999, 'totalPages' => 3], 404);
same('noindex, nofollow', tagContent($html, '/<meta name="robots" content="([^"]+)"/'), '404 robots header');
check(!str_contains($html, 'rel="prev"') && !str_contains($html, 'rel="next"'), '404 never has pagination links');

echo "SEO regression checks passed\n";
