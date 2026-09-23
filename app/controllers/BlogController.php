<?php
namespace App\controllers;

use App\models\Article;
use Core\Seo;
use Core\UrlCanonicalizer;

/**
 * کنترلر وبلاگ و دانشنامه فنی
 * ---------------------------------------------------------------------------
 * قواعد ثابت این کنترلر (پس از یکپارچه‌سازی سئو):
 *   ۱) هر اکشن فقط یک بار تعریف می‌شود (متد تکراری show حذف شد).
 *   ۲) تمام آدرس‌ها از Core\Seo تولید می‌شوند؛ ساخت دستی $protocol . '://' . SITE_URL
 *      حذف شده تا آدرس‌های خرابی مثل https://https://... ممکن نباشد.
 *   ۳) مسیر URL همیشه با rawurlencode ساخته می‌شود، نه urlencode.
 *   ۴) فقط یک بلوک JSON-LD از نوع @graph تولید می‌شود (سیستم قدیمی حذف شد).
 *   ۵) نویسنده همیشه Person و ناشر همیشه Organization است (Seo::authorNode).
 */
class BlogController extends Controller
{
    public function index()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $articles = Article::getAll('published');

        $canonicalUrl = Seo::absolute('/blog');
        $pageTitle = 'دانشنامه و وبلاگ تخصصی تویوتا | ' . $siteName;
        $metaDescription = 'راهنمای جامع تشخیص اصالت لوازم یدکی تویوتا، سرویس‌های دوره‌ای و عیب‌یابی خودرو توسط کارشناسان ' . $siteName . '.';
        $robotsMeta = 'index, follow';

        // کاور اولین مقاله به‌عنوان تصویر اشتراک‌گذاری صفحه فهرست
        $pageImage = !empty($articles[0]) ? Seo::articleCover($articles[0]) : null;
        $pageImageAlt = $articles[0]['title'] ?? $pageTitle;

        // ---- فهرست مقالات به‌صورت ItemList (نه Blog تودرتو) تا تکرار محتوا نشود ----
        $itemList = [];
        foreach ($articles as $i => $art) {
            $itemList[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => Seo::articleUrl($art['slug'] ?? '', true),
                'name'     => $art['title'] ?? '',
            ];
        }

        $schemaMarkup = Seo::graph([
            Seo::organizationNode($settings ?? []),
            Seo::websiteNode($settings ?? []),
            Seo::webPageNode($canonicalUrl, $pageTitle, $metaDescription, $pageImage),
            Seo::breadcrumbNode([
                ['name' => 'صفحه اصلی', 'url' => '/'],
                ['name' => 'وبلاگ فنی', 'url' => '/blog'],
            ], $canonicalUrl),
            [
                '@type'       => 'Blog',
                '@id'         => $canonicalUrl . '#blog',
                'name'        => 'دانشنامه و راهنمای فنی تویوتا',
                'description' => $metaDescription,
                'url'         => $canonicalUrl,
                'inLanguage'  => 'fa-IR',
                'publisher'   => ['@id' => Seo::base() . '/#organization'],
            ],
            [
                '@type'           => 'ItemList',
                '@id'             => $canonicalUrl . '#itemlist',
                'numberOfItems'   => count($itemList),
                'itemListElement' => $itemList,
            ],
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
                // ریدایرکت پیش از هر خروجی: آدرس شناسه‌محور → آدرس اسلاگ‌محور
                $this->redirectPermanent(Seo::articleUrl($article['slug']));
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

        // اسلاگ واقعی دیتابیس، encoding فارسی و بزرگی/کوچکی حروف یک URL واحد دارند.
        UrlCanonicalizer::redirectIfDifferent(Seo::articleUrl((string) $article['slug']));

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
        $articleUrl = Seo::articleUrl($article['slug'], true);

        $seo = Seo::resolve($article, [
            'title'       => Seo::truncate($article['title'] . ' | ' . $siteName, 70),
            'description' => Seo::articleDescription($article, $siteName),
            'canonical'   => $articleUrl,
            'robots'      => 'index, follow',
        ]);

        $pageTitle = $seo['title'];
        $metaDescription = $seo['description'];
        $canonicalUrl = Seo::canonical($seo['canonical'], ['article' => $article]);
        $robotsMeta = $seo['robots'];

        $coverUrl = Seo::articleCover($article);
        $pageImage = $coverUrl;
        $pageImageAlt = trim((string) ($article['focus_keyword'] ?? '')) ?: $article['title'];

        // ------------------------------------------------------------------
        // بدنه مقاله: پاک‌سازی امنیتی + تضمین alt روی تمام تصاویر داخل متن
        // (نمای view فقط همین متغیر آماده را چاپ می‌کند)
        // ------------------------------------------------------------------
        $articleBody = Seo::ensureImageAlt(
            clean_html($article['content'] ?? ''),
            trim((string) ($article['focus_keyword'] ?? '')) ?: (string) $article['title']
        );

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
            // استاندارد ثابت: نویسنده Person، ناشر Organization
            'author' => Seo::authorNode($article['author'] ?? null),
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
                    'url'   => Seo::productUrl($rp['slug'], true),
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
                ['name' => $article['title'], 'url' => Seo::articleUrl($article['slug'])],
            ], $articleUrl),
            $postingNode,
        ]);

        require_once VIEWS_PATH . '/blog-detail.php';
    }

    /** ریدایرکت ۳۰۱ امن (پیش از ارسال هرگونه خروجی) */
    private function redirectPermanent(string $target): void
    {
        UrlCanonicalizer::redirect($target, 301, 'legacy-article');
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
