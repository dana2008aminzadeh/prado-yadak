<?php
namespace App\controllers;

use App\models\Article;

class BlogController extends Controller
{
    public function index()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $articles = Article::getAll('published');

        $pageTitle = 'دانشنامه و وبلاگ تخصصی تویوتا | ' . $siteName;
        $metaDescription = 'راهنمای جامع تشخیص اصالت لوازم یدکی تویوتا، سرویس‌های دوره‌ای و عیب‌یابی خودرو توسط کارشناسان ' . $siteName . '.';

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
        $hostUrl = $protocol . "://" . $host;

        $schemaArticles = [];
        foreach ($articles as $art) {
            $schemaArticles[] = [
                '@type' => 'BlogPosting',
                'headline' => $art['title'],
                'description' => $art['summary'],
                'url' => $hostUrl . '/blog/' . urlencode($art['slug']),
                'datePublished' => date('Y-m-d', strtotime($art['created_at'])),
                'author' => [
                    '@type' => 'Organization',
                    'name' => $art['author'] ?: $siteName
                ]
            ];
        }

        $schemaBlog = [
            '@context' => 'https://schema.org',
            '@type' => 'Blog',
            'name' => 'دانشنامه و راهنمای فنی تویوتا',
            'description' => $metaDescription,
            'url' => $hostUrl . '/blog',
            'blogPost' => $schemaArticles
        ];

        $schemaBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'صفحه اصلی',
                    'item' => $hostUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'وبلاگ فنی',
                    'item' => $hostUrl . '/blog'
                ]
            ]
        ];

        $schemaMarkup = "<script type=\"application/ld+json\">\n" . json_encode($schemaBlog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>\n";
        $schemaMarkup .= "<script type=\"application/ld+json\">\n" . json_encode($schemaBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>";

        require_once VIEWS_PATH . '/blog.php';
    }

    public function show()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        $article = null;
        if ($slug) {
            $article = Article::findBySlug($slug);
        } elseif ($id) {
            $article = Article::findById($id);
            if ($article) {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: /blog/" . urlencode($article['slug']));
                exit;
            }
        }

        if (!$article) {
            header("Location: /404");
            exit;
        }

        Article::incrementViews($article['id']);
        $relatedArticles = Article::getRelated($article['category'], $article['id'], 2);

        $pageTitle = $article['title'] . ' | ' . $siteName;
        $metaDescription = mb_substr(strip_tags($article['summary']), 0, 160, 'UTF-8');
        $pageImage = !empty($article['cover_image']) ? $article['cover_image'] : null;

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
        $hostUrl = $protocol . "://" . $host;
        $articleUrl = $hostUrl . '/blog/' . urlencode($article['slug']);

        $schemaPosting = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $articleUrl
            ],
            'headline' => $article['title'],
            'description' => $metaDescription,
            'image' => $article['cover_image'] ?: ($hostUrl . '/assets/logo/logo.webp'),
            'datePublished' => date('Y-m-d\TH:i:sP', strtotime($article['created_at'])),
            'dateModified' => date('Y-m-d\TH:i:sP', strtotime($article['updated_at'] ?? $article['created_at'])),
            'author' => [
                '@type' => 'Person',
                'name' => $article['author'] ?: 'تیم فنی پرادو یدک'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $hostUrl . '/assets/logo/logo.webp'
                ]
            ]
        ];

        $schemaBreadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'صفحه اصلی',
                    'item' => $hostUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'وبلاگ فنی',
                    'item' => $hostUrl . '/blog'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $article['title'],
                    'item' => $articleUrl
                ]
            ]
        ];

        $schemaMarkup = "<script type=\"application/ld+json\">\n" . json_encode($schemaPosting, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>\n";
        $schemaMarkup .= "<script type=\"application/ld+json\">\n" . json_encode($schemaBreadcrumb, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>";

        require_once VIEWS_PATH . '/blog-detail.php';
    }
}