<?php
// Database-free controller smoke tests: php tests/catalog_pagination.php <case>

declare(strict_types=1);

namespace App\models {
    class Product
    {
        public static function search(array $filters, int $page, int $perPage): array
        {
            $total = $GLOBALS['fixtureTotal'];
            $pages = (int) ceil($total / $perPage);
            return ['total' => $total, 'items' => ($total > 0 && $page <= $pages)
                ? [['slug' => 'part-one', 'name' => 'قطعه نمونه']] : []];
        }
        public static function getDistinctBrands(): array { return ['Toyota']; }
    }

    class LandingPage
    {
        public static function findBySlug(string $slug): ?array
        {
            return $slug === 'sample' ? [
                'id' => 1, 'slug' => 'sample', 'h1' => 'قطعات منتخب پرادو',
                'intro_html' => 'توضیح اختصاصی این دسته', 'robots_directive' => 'index',
            ] : null;
        }
        public static function toFilters(array $page): array { return []; }
        public static function incrementViews(int $id): void {}
    }

    class Redirect
    {
        public static function handle(string $path, string $query): void {}
        public static function log404(string $path): void {}
    }
}

namespace App\controllers {
    class Controller {}
}

namespace {
    define('SITE_URL', 'https://pradoyadak.com');
    define('VIEWS_PATH', __DIR__ . '/fixtures');
    require __DIR__ . '/../core/UrlCanonicalizer.php';
    require __DIR__ . '/../core/Seo.php';
    require __DIR__ . '/../app/controllers/PartController.php';

    $case = $argv[1] ?? '';
    $GLOBALS['case'] = $case;
    $GLOBALS['settings'] = ['site_title' => 'پرادو یدک'];
    $GLOBALS['part_categories'] = ['brakes' => ['name' => 'ترمز']];
    $GLOBALS['car_models'] = ['camry' => 'کمری'];
    $GLOBALS['fixtureTotal'] = str_contains($case, 'empty') ? 0 : 21;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    http_response_code(200);

    $path = match ($case) {
        'parts-overflow', 'parts-empty-page', 'parts-partial', 'parts-brand' => '/parts',
        'taxonomy-overflow', 'taxonomy-empty-page', 'taxonomy-empty-base' => '/parts/category/brakes',
        'landing-overflow', 'landing-empty-page', 'landing-empty-base', 'landing-partial' => '/parts/sample',
        default => throw new \RuntimeException('Unknown test case: ' . $case),
    };
    $page = str_contains($case, 'overflow') ? '99999'
        : (str_ends_with($case, '-base') || $case === 'parts-brand' ? '1' : '2');
    $_GET = $page === '1' ? [] : ['page' => $page];
    if ($case === 'parts-brand') {
        $_GET['brand'] = 'Toyota';
    }
    if (str_starts_with($case, 'taxonomy')) {
        $_GET['category'] = 'brakes';
    }
    if (str_starts_with($case, 'landing')) {
        $_GET['landing'] = 'sample';
    }
    $_SERVER['QUERY_STRING'] = $case === 'parts-brand' ? 'brand=Toyota' : ($page === '1' ? '' : 'page=' . $page);
    $_SERVER['REQUEST_URI'] = $path . ($_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');

    $controller = new \App\controllers\PartController();
    if ($path === '/parts') {
        $controller->index();
    } elseif (str_starts_with($case, 'taxonomy')) {
        $controller->categoryLanding();
    } else {
        $controller->landing();
    }
}
