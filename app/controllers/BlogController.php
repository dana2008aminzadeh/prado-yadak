<?php
namespace App\controllers;

use App\models\Article;
use Core\Seo;

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
        $host = SITE_URL;
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
            // احتمال تغییر اسلاگ → بررسی جدول ریدایرکت پیش از نمایش ۴۰۴
            if ($slug) {
                \App\models\Redirect::handle('/blog/' . $slug, (string) ($_SERVER['QUERY_STRING'] ?? ''));
                \App\models\Redirect::log404('/blog/' . $slug);
            }
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        // =========================================================================
        // اصلاح شمارنده بازدید: نادیده گرفتن ربات‌ها و ممانعت از ثبت بازدید تکراری در رفرش
        // =========================================================================
        if (!$this->isBot()) {
            if (!isset($_SESSION['viewed_articles']) || !is_array($_SESSION['viewed_articles'])) {
                $_SESSION['viewed_articles'] = [];
            }

            // اگر کاربر در این سشن قبلاً این مقاله را ندیده باشد، بازدید را ثبت کن
            if (!in_array($article['id'], $_SESSION['viewed_articles'], true)) {
                Article::incrementViews($article['id']);
                $_SESSION['viewed_articles'][] = (int) $article['id'];
            }
        }

        $relatedArticles = Article::getRelated($article['category'], $article['id'], 2);

        // محصولات مرتبط — پل ساختاری بین وبلاگ و فروشگاه (Silo Structure)
        $relatedProducts = Article::getRelatedProducts($article['id'], 4);

        // ------------------------------------------------------------------
        // متاتگ‌ها با اولویت سلسله‌مراتبی (دستی → خودکار → پیش‌فرض)
        // ------------------------------------------------------------------
        $articleUrl = Seo::absolute('/blog/' . rawurlencode((string) $article['slug']));

        $seo = Seo::resolve($article, [
            'title'       => Seo::truncate($article['title'] . ' | ' . $siteName, 70),
            'description' => Seo::articleDescription($article, $siteName),
            'canonical'   => $articleUrl,
            'robots'      => 'index, follow',
        ]);

        $pageTitle = $seo['title'];
        $metaDescription = $seo['description'];
        $canonicalUrl = $seo['canonical'];
        $robotsMeta = $seo['robots'];

        $coverUrl = !empty($article['cover_image'])
            ? (str_starts_with((string) $article['cover_image'], 'http')
                ? $article['cover_image']
                : Seo::base() . Seo::imageUrl((string) $article['cover_image'], Seo::imageSlug((string) $article['title'])))
            : Seo::base() . '/assets/logo/logo.webp';
        $pageImage = $coverUrl;
        $pageImageAlt = $article['title'];

        $plain = Seo::clean($article['content'] ?? '');
        $wordCount = $plain === '' ? 0 : count(preg_split('/\s+/u', $plain) ?: []);

        $postingNode = [
            '@type' => 'BlogPosting',
            '@id' => $articleUrl . '#article',
            'mainEntityOfPage' => ['@id' => $articleUrl . '#webpage'],
            'headline' => Seo::truncate($article['title'], 110),
            'description' => $metaDescription,
            'image' => [$coverUrl],
            'datePublished' => date('Y-m-d\TH:i:sP', strtotime($article['created_at'])),
            'dateModified' => date('Y-m-d\TH:i:sP', strtotime($article['updated_at'] ?? $article['created_at'])),
            'wordCount' => $wordCount,
            'inLanguage' => 'fa-IR',
            'articleSection' => $article['category_label'] ?? 'راهنمای فنی',
            'author' => [
                '@type' => 'Person',
                'name' => $article['author'] ?: 'تیم فنی پرادو یدک',
                'worksFor' => ['@id' => Seo::base() . '/#organization'],
            ],
            'publisher' => ['@id' => Seo::base() . '/#organization'],
        ];

        if (!empty($article['focus_keyword'])) {
            $postingNode['keywords'] = $article['focus_keyword'];
        }

        // قطعاتی که مقاله درباره آن‌هاست، به‌صورت ساختاری به مقاله گره می‌خورند
        if ($relatedProducts) {
            $mentions = [];
            foreach ($relatedProducts as $rp) {
                $mentions[] = [
                    '@type' => 'Product',
                    'name'  => $rp['name'],
                    'url'   => Seo::absolute('/product/' . rawurlencode((string) $rp['slug'])),
                    'sku'   => (string) ($rp['oem'] ?: 'PRD-' . $rp['id']),
                ];
            }
            $postingNode['mentions'] = $mentions;
        }

        $schemaMarkup = Seo::graph([
            Seo::organizationNode($settings),
            Seo::websiteNode($settings),
            Seo::webPageNode($articleUrl, $pageTitle, $metaDescription, $coverUrl),
            Seo::breadcrumbNode([
                ['name' => 'صفحه اصلی', 'url' => '/'],
                ['name' => 'وبلاگ فنی', 'url' => '/blog'],
                ['name' => $article['title'], 'url' => '/blog/' . rawurlencode((string) $article['slug'])],
            ], $articleUrl),
            $postingNode,
        ]);

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
            // احتمال تغییر اسلاگ → بررسی جدول ریدایرکت پیش از نمایش ۴۰۴
            if ($slug) {
                \App\models\Redirect::handle('/blog/' . $slug, (string) ($_SERVER['QUERY_STRING'] ?? ''));
                \App\models\Redirect::log404('/blog/' . $slug);
            }
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        // =========================================================================
        // اصلاح شمارنده بازدید: نادیده گرفتن ربات‌ها و ممانعت از ثبت بازدید تکراری در رفرش
        // =========================================================================
        if (!$this->isBot()) {
            if (!isset($_SESSION['viewed_articles']) || !is_array($_SESSION['viewed_articles'])) {
                $_SESSION['viewed_articles'] = [];
            }

            // اگر کاربر در این سشن قبلاً این مقاله را ندیده باشد، بازدید را ثبت کن
            if (!in_array($article['id'], $_SESSION['viewed_articles'], true)) {
                Article::incrementViews($article['id']);
                $_SESSION['viewed_articles'][] = (int) $article['id'];
            }
        }

        $relatedArticles = Article::getRelated($article['category'], $article['id'], 2);
        $pageTitle = $article['title'] . ' | ' . $siteName;
        $metaDescription = mb_substr(strip_tags($article['summary']), 0, 160, 'UTF-8');
        $pageImage = !empty($article['cover_image']) ? $article['cover_image'] : null;

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = SITE_URL;
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

    private function isBot(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent)) {
            return true;
        }

        $botPatterns = [
            'googlebot',
            'bingbot',
            'yandexbot',
            'duckduckbot',
            'baiduspider',
            'slurp',
            'twitterbot',
            'facebookexternalhit',
            'facebot',
            'linkedinbot',
            'embedly',
            'quora link preview',
            'showyoubot',
            'outbrain',
            'pinterest',
            'slackbot',
            'vkShare',
            'W3C_Validator',
            'whatsapp',
            'telegrambot',
            'curl',
            'wget',
            'python',
            'postman',
            'crawler',
            'spider'
        ];

        return (bool) preg_match('/(' . implode('|', $botPatterns) . ')/i', $userAgent);
    }
}