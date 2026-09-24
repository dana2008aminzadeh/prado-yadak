<?php
// Database-free Sitemap smoke tests: php tests/sitemap_regression.php <map>

declare(strict_types=1);

namespace Core {
    class Database
    {
        public static function getInstance(): self { return new self(); }
        public function prepare(string $sql): FakeStatement { return new FakeStatement($sql); }
        public function query(string $sql): FakeStatement { return new FakeStatement($sql); }
    }

    class FakeStatement
    {
        private array $params = [];
        public function __construct(private string $sql) {}
        public function execute(array $params = []): void { $this->params = $params; }
        public function fetchColumn(): int
        {
            if (str_contains($this->sql, 'information_schema.COLUMNS')) {
                return 1; // SEO columns are migrated in these fixtures.
            }
            if (str_contains($this->sql, 'information_schema.TABLES')) {
                return 1;
            }
            return 0; // Empty public catalog/blog for the static-sitemap case.
        }
        public function fetchAll(int $mode): array
        {
            if (str_contains($this->sql, 'FROM products p') && str_contains($this->sql, 'stock_movements')) {
                if (!str_contains($this->sql, "NOT IN ('noindex', 'noindex_nofollow')")) {
                    throw new \RuntimeException('Product sitemap must exclude noindex');
                }
                return [
                    ['slug' => 'real', 'canonical_url' => null, 'in_stock' => 1, 'stock_qty' => 1],
                    ['slug' => 'duplicate', 'canonical_url' => '/product/real', 'in_stock' => 1, 'stock_qty' => 1],
                ];
            }
            if (str_contains($this->sql, 'FROM articles')) {
                if (!str_contains($this->sql, "NOT IN ('noindex', 'noindex_nofollow')")) {
                    throw new \RuntimeException('Article sitemap must exclude noindex_nofollow');
                }
                return [
                    ['slug' => 'guide', 'canonical_url' => null],
                    ['slug' => 'copy', 'canonical_url' => '/blog/guide'],
                ];
            }
            if (str_contains($this->sql, 'FROM seo_landing_pages')) {
                if (!str_contains($this->sql, "NOT IN ('noindex', 'noindex_nofollow')")) {
                    throw new \RuntimeException('Landing sitemap must exclude noindex_nofollow');
                }
                return [
                    ['slug' => 'brakes', 'filter_category' => 'brakes'],
                    ['slug' => 'empty', 'filter_category' => 'empty'],
                    ['slug' => 'other-canonical', 'canonical_url' => '/parts', 'filter_category' => 'brakes'],
                ];
            }
            if (str_contains($this->sql, 'GROUP BY brand')) {
                if (!str_contains($this->sql, 'discontinued')) {
                    throw new \RuntimeException('Brand sitemap must exclude discontinued-only brands');
                }
                return [['brand' => 'Toyota'], ['brand' => 'Invalid Brand']];
            }
            return [];
        }
    }
}

namespace App\models {
    class LandingPage
    {
        public static function toFilters(array $landing): array
        {
            return ['categories' => [$landing['filter_category']]];
        }
    }
    class Product
    {
        public static function search(array $filters, int $page, int $perPage): array
        {
            return ['total' => $filters['categories'][0] === 'empty' ? 0 : 1, 'items' => []];
        }
    }
}

namespace {
    define('SITE_URL', 'https://pradoyadak.com');
    require __DIR__ . '/../core/UrlCanonicalizer.php';
    require __DIR__ . '/../core/Seo.php';
    require __DIR__ . '/../app/controllers/SitemapController.php';

    $map = $argv[1] ?? '';
    $expected = match ($map) {
        'products' => ['include' => ['/product/real'], 'exclude' => ['/product/duplicate']],
        'articles' => ['include' => ['/blog/guide'], 'exclude' => ['/blog/copy']],
        'landing' => ['include' => ['/parts/brakes'], 'exclude' => ['/parts/empty', '/parts/other-canonical']],
        'brands' => ['include' => ['/parts?brand=Toyota'], 'exclude' => ['Invalid%20Brand']],
        'statics' => ['include' => ['/terms'], 'exclude' => ['/parts</loc>', '/blog</loc>']],
        default => throw new \RuntimeException('Unknown sitemap: ' . $map),
    };
    register_shutdown_function(static function () use ($map, $expected): void {
        $xml = ob_get_contents();
        ob_end_clean();
        foreach ($expected['include'] as $needle) {
            if (!str_contains($xml, $needle)) {
                throw new \RuntimeException("Missing {$needle} in {$map}");
            }
        }
        foreach ($expected['exclude'] as $needle) {
            if (str_contains($xml, $needle)) {
                throw new \RuntimeException("Unexpected {$needle} in {$map}");
            }
        }
        echo "PASS: {$map} contains only eligible URLs\n";
    });
    ob_start();
    (new \App\controllers\SitemapController())->$map();
}
