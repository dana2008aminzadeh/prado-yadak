<?php

namespace App\controllers;

use App\models\LandingPage;
use App\models\Product;
use Core\Seo;
use Core\UrlCanonicalizer;

class PartController extends Controller
{
    /** جلوی درخواست‌های بی‌نهایت و soft 404 صفحه‌بندی را پیش از جستجو می‌گیرد. */
    private function catalogPageOr404(): int
    {
        $page = Seo::catalogPage($_GET);
        if ($page === null) {
            $this->catalogNotFound();
        }
        return $page;
    }

    private function catalogNotFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/404.php';
        exit;
    }

    public function index()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $page = $this->catalogPageOr404();

        // درخواست‌های قدیمیِ تک‌فیلتره را به لندینگ تجاری با URL تمیز منتقل کن.
        // جستجو، مرتب‌سازی و ترکیب چند فیلتر همچنان روی /parts باقی می‌مانند.
        $this->redirectSingleTaxonomyFilter();

        $toArray = function ($input) {
            if (empty($input))
                return [];
            return is_array($input) ? $input : explode(',', $input);
        };

        $perPage = 20;

        $filters = [
            'q' => is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '',
            'categories' => $toArray($_GET['category'] ?? []),
            'models' => $toArray($_GET['model'] ?? []),
            'brands' => $toArray($_GET['brand'] ?? []),
            'maxPrice' => isset($_GET['maxPrice']) ? (float) $_GET['maxPrice'] : null,
            'inStock' => $_GET['inStock'] ?? '',
            'sort' => $_GET['sort'] ?? 'newest'
        ];

        $data = Product::search($filters, $page, $perPage);
        $products = $data['items'];
        $totalCount = (int) $data['total'];
        $totalPages = (int) ceil($totalCount / $perPage);
        if ($page > 1 && $page > $totalPages) {
            $this->catalogNotFound();
        }
        $brands = Product::getDistinctBrands();

        $selectedCat = is_string($_GET['category'] ?? null) ? $_GET['category'] : null;
        $selectedModel = is_string($_GET['model'] ?? null) ? $_GET['model'] : null;

        if (!empty($filters['q'])) {
            $term = Seo::truncate(Seo::clean($filters['q']), 40);
            $pageTitle = "نتایج جستجوی «{$term}» در قطعات تویوتا";
            $metaDescription = "نتایج جستجوی «{$term}» در کاتالوگ {$siteName}؛ مشخصات، قیمت و سازگاری قطعات تویوتا را بررسی کنید.";
        } elseif ($selectedCat && isset($GLOBALS['part_categories'][$selectedCat])) {
            $catInfo = $GLOBALS['part_categories'][$selectedCat];
            $catName = is_array($catInfo) ? ($catInfo['name'] ?? $selectedCat) : $catInfo;
            $pageTitle = "قیمت و خرید قطعات {$catName} تویوتا";
            $metaDescription = "خرید انواع قطعات و لوازم یدکی {$catName} تویوتا اصل جنیون پارت و وارداتی OEM با ضمانت بازگشت وجه در صورت اثبات عدم اصالت و ارسال سریع از فروشگاه {$siteName}.";
        } elseif ($selectedModel && isset($GLOBALS['car_models'][$selectedModel])) {
            $modInfo = $GLOBALS['car_models'][$selectedModel];
            $modName = is_array($modInfo) ? ($modInfo['name'] ?? $selectedModel) : $modInfo;
            $pageTitle = "فهرست قطعات تویوتا {$modName} و قیمت روز";
            $metaDescription = "کاتالوگ جامع قطعات یدکی تویوتا {$modName}؛ استعلام قیمت، تطابق با شماره شاسی (VIN) و خرید آنلاین با ضمانت اصالت کالا در {$siteName}.";
        } elseif (is_string($_GET['brand'] ?? null) && $_GET['brand'] !== '') {
            $brand = Seo::clean($_GET['brand']);
            $brandLabel = ['genuine' => 'جنیون پارت اصلی', 'oem' => 'وارداتی OEM'][$brand] ?? $brand;
            $h1_title = "قطعات {$brandLabel} برای تویوتا";
            $pageTitle = "قیمت قطعات {$brandLabel} برای تویوتا";
            $metaDescription = "مشاهده و مقایسه قطعات {$brandLabel} برای تویوتا، استعلام موجودی و بررسی کد فنی و سازگاری در {$siteName}.";
        } else {
            $pageTitle = 'کاتالوگ و قیمت قطعات یدکی تویوتا و لکسوس';
            $metaDescription = "کاتالوگ و لیست قیمت روز انواع لوازم یدکی و قطعات مصرفی تویوتا و لکسوس؛ ضمانت بازگشت وجه در صورت اثبات عدم اصالت جنیون پارتس با امکان مرجوعی در فروشگاه {$siteName}.";
        }
        if ($page > 1) {
            $pageTitle .= ' - صفحه ' . $page;
            $metaDescription = "صفحه {$page} از {$totalPages}؛ " . $metaDescription;
        }
        $metaDescription = Seo::truncate($metaDescription, Seo::DESC_MAX);

        // ---- سئوی کاتالوگ: کانونیکال نرمال‌شده + قانون noindex فیلترهای کم‌ارزش ----
        $canonicalUrl = Seo::catalogCanonical($_GET, '/parts');
        $robotsMeta = $totalCount > 0 ? Seo::catalogRobots($_GET) : 'noindex, follow';
        Seo::emitNoindexHeader($robotsMeta);

        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
        ];
        if ($selectedCat) {
            $catInfo = $GLOBALS['part_categories'][$selectedCat] ?? null;
            $crumbs[] = [
                'name' => is_array($catInfo) ? ($catInfo['name'] ?? $selectedCat) : ($catInfo ?: $selectedCat),
                'url'  => Seo::categoryUrl((string) $selectedCat),
            ];
        }

        $schemaMarkup = $this->catalogSchema($products, $canonicalUrl, $pageTitle, $metaDescription, $crumbs);

        require_once VIEWS_PATH . '/parts.php';
    }

    /** لندینگ تمیز یک دسته‌بندی: /parts/category/{slug} */
    public function categoryLanding(): void
    {
        $this->taxonomyLanding('category');
    }

    /** لندینگ تمیز یک مدل خودرو: /parts/model/{slug} */
    public function modelLanding(): void
    {
        $this->taxonomyLanding('model');
    }

    /**
     * صفحات دسته/مدل باید حتی بدون رکورد دستی در seo_landing_pages یک URL تجاری
     * پایدار، H1 و متای اختصاصی داشته باشند. لندینگ‌های ترکیبی همچنان از پنل سئو
     * و متد landing() تامین می‌شوند.
     */
    private function taxonomyLanding(string $type): void
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $isCategory = $type === 'category';
        $param = $isCategory ? 'category' : 'model';
        $slug = trim((string) ($_GET[$param] ?? ''));
        $source = $isCategory ? ($GLOBALS['part_categories'] ?? []) : ($GLOBALS['car_models'] ?? []);

        if ($slug === '' || !isset($source[$slug])) {
            \App\models\Redirect::handle('/parts/' . $param . '/' . $slug, (string) ($_SERVER['QUERY_STRING'] ?? ''));
            \App\models\Redirect::log404('/parts/' . $param . '/' . $slug);
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        $entity = $source[$slug];
        $name = is_array($entity) ? (string) ($entity['name'] ?? $slug) : (string) $entity;
        $catalogBasePath = $isCategory ? Seo::categoryUrl($slug) : Seo::modelUrl($slug);

        // در لندینگ تمیز فقط page معنادار است؛ page=1 و هر فیلتر زائدی به
        // نسخه یکتای صفحه برگردانده می‌شود تا URL موازی ایندکس نشود.
        $page = $this->catalogPageOr404();
        $requestQuery = UrlCanonicalizer::parseQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));
        $expectedQuery = $page > 1 ? 'page=' . $page : '';
        if (UrlCanonicalizer::buildQuery($requestQuery, $catalogBasePath) !== $expectedQuery) {
            UrlCanonicalizer::redirect($catalogBasePath . ($expectedQuery !== '' ? '?' . $expectedQuery : ''), 301, 'clean-taxonomy-query');
        }

        $perPage = 20;
        $filters = [
            'q' => '',
            'categories' => $isCategory ? [$slug] : [],
            'models' => $isCategory ? [] : [$slug],
            'brands' => [],
            'maxPrice' => null,
            'inStock' => '',
            'sort' => 'newest',
        ];

        $data = Product::search($filters, $page, $perPage);
        $products = $data['items'];
        $totalCount = (int) $data['total'];
        $totalPages = (int) ceil($totalCount / $perPage);

        // صفحه‌ی خالی (حتی اگر کل دسته هنوز محصولی ندارد) نباید soft 404 شود.
        if ($page > 1 && $page > $totalPages) {
            $this->catalogNotFound();
        }

        $brands = Product::getDistinctBrands();
        $selectedCat = $isCategory ? $slug : null;
        $selectedModel = $isCategory ? null : $slug;

        $canonicalUrl = Seo::absolute($catalogBasePath) . ($page > 1 ? '?page=' . $page : '');
        if ($isCategory) {
            $pageTitle = "خرید قطعات {$name} تویوتا | {$siteName}";
            $metaDescription = "خرید انواع قطعات و لوازم یدکی {$name} تویوتا اصل جنیون پارت و OEM با ضمانت اصالت، تطابق شماره شاسی و ارسال سریع از {$siteName}.";
            $h1_title = "خرید لوازم {$name} تویوتا";
        } else {
            $pageTitle = "قطعات یدکی تویوتا {$name} | {$siteName}";
            $metaDescription = "کاتالوگ و قیمت قطعات یدکی تویوتا {$name}؛ خرید قطعه اصلی با تطابق شماره شاسی (VIN)، ضمانت اصالت و ارسال سریع از {$siteName}.";
            $h1_title = "قطعات یدکی تویوتا {$name}";
        }
        if ($page > 1) {
            $pageTitle .= ' - صفحه ' . $page;
            $metaDescription = "صفحه {$page} از {$totalPages}؛ " . $metaDescription;
        }
        $metaDescription = Seo::truncate($metaDescription, Seo::DESC_MAX);
        // لندینگ خالی کم‌ارزش است؛ به محض افزودن اولین محصول index می‌شود.
        $robotsMeta = $totalCount > 0 ? 'index, follow' : 'noindex, follow';
        Seo::emitNoindexHeader($robotsMeta);
        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
            ['name' => $h1_title, 'url' => $catalogBasePath],
        ];
        $schemaMarkup = $this->catalogSchema($products, $canonicalUrl, $pageTitle, $metaDescription, $crumbs);

        require_once VIEWS_PATH . '/parts.php';
    }

    /**
     * ریدایرکت دائمی URLهای قدیمی تک‌فیلتره به لندینگ‌های تمیز. */
    private function redirectSingleTaxonomyFilter(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'HEAD'], true)) {
            return;
        }

        $meaningful = array_filter($_GET, static function ($value, $key): bool {
            if ($key === 'page') {
                return false;
            }
            $value = is_array($value) ? array_filter($value, 'strlen') : trim((string) $value);
            return $value !== '' && $value !== [];
        }, ARRAY_FILTER_USE_BOTH);

        if (count($meaningful) !== 1) {
            return;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        if (isset($meaningful['category']) && !is_array($meaningful['category'])) {
            $slug = trim((string) $meaningful['category']);
            if (isset(($GLOBALS['part_categories'] ?? [])[$slug])) {
                UrlCanonicalizer::redirect(Seo::categoryUrl($slug) . ($page > 1 ? '?page=' . $page : ''), 301, 'clean-category-landing');
            }
        }
        if (isset($meaningful['model']) && !is_array($meaningful['model'])) {
            $slug = trim((string) $meaningful['model']);
            if (isset(($GLOBALS['car_models'] ?? [])[$slug])) {
                UrlCanonicalizer::redirect(Seo::modelUrl($slug) . ($page > 1 ? '?page=' . $page : ''), 301, 'clean-model-landing');
            }
        }
    }

    /**
     * لندینگ‌پیج اختصاصی سئو با آدرس تمیز — مثلا /parts/لوازم-یدکی-کمری-لنت-ترمز
     * به‌جای آدرس پارامتردار، یک صفحه یکتا با متن و متاتگ اختصاصی سرو می‌شود.
     */
    public function landing()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $slug = trim((string) ($_GET['landing'] ?? ''));
        $landing = LandingPage::findBySlug($slug);

        if (!$landing) {
            \App\models\Redirect::handle('/parts/' . $slug, (string) ($_SERVER['QUERY_STRING'] ?? ''));
            \App\models\Redirect::log404('/parts/' . $slug);
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        $page = $this->catalogPageOr404();
        // لندینگ فقط page می‌پذیرد؛ پارامترهای دیگر canonical جدیدی نمی‌سازند.
        $basePath = '/parts/' . rawurlencode((string) $landing['slug']);
        $query = UrlCanonicalizer::parseQuery((string) ($_SERVER['QUERY_STRING'] ?? ''));
        $expectedQuery = $page > 1 ? 'page=' . $page : '';
        if (UrlCanonicalizer::buildQuery($query, $basePath) !== $expectedQuery) {
            UrlCanonicalizer::redirect($basePath . ($expectedQuery !== '' ? '?' . $expectedQuery : ''), 301, 'clean-landing-query');
        }
        $perPage = 20;

        $filters = LandingPage::toFilters($landing);
        $data = Product::search($filters, $page, $perPage);
        $products = $data['items'];
        $totalCount = (int) $data['total'];
        $totalPages = (int) ceil($totalCount / $perPage);

        if ($page > 1 && $page > $totalPages) {
            $this->catalogNotFound();
        }
        LandingPage::incrementViews((int) $landing['id']);

        $brands = Product::getDistinctBrands();

        $selectedCat = $filters['categories'][0] ?? null;
        $selectedModel = $filters['models'][0] ?? null;

        $canonicalUrl = Seo::absolute('/parts/' . rawurlencode($landing['slug']))
            . ($page > 1 ? '?page=' . $page : '');

        // لندینگ ترکیبی بدون هیچ محصولی، صفحه‌ای کم‌ارزش و بالقوه تکراری است؛
        // مدیر می‌تواند صراحتاً noindex ثبت کند، اما در نبود محتوای واقعی
        // هرگز به‌صورت پیش‌فرض index نمی‌شود.
        $resolved = Seo::resolve($landing, [
            'title'       => $landing['h1'] . ' | ' . $siteName,
            'description' => Seo::truncate(Seo::clean($landing['intro_html'] ?? $landing['h1']), Seo::DESC_MAX),
            'canonical'   => $canonicalUrl,
            'robots'      => $totalCount > 0 ? 'index, follow' : 'noindex, follow',
        ]);

        $pageTitle = $resolved['title'] . ($page > 1 ? ' - صفحه ' . $page : '');
        $metaDescription = $page > 1
            ? Seo::truncate("صفحه {$page} از {$totalPages}؛ " . $resolved['description'], Seo::DESC_MAX)
            : $resolved['description'];
        $canonicalUrl = $resolved['canonical'];
        $robotsMeta = $totalCount > 0 ? $resolved['robots'] : 'noindex, follow';
        Seo::emitNoindexHeader($robotsMeta);

        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
            ['name' => $landing['h1'], 'url' => '/parts/' . rawurlencode($landing['slug'])],
        ];
        $schemaMarkup = $this->catalogSchema($products, $canonicalUrl, $pageTitle, $metaDescription, $crumbs);

        $landingPage = $landing;
        require_once VIEWS_PATH . '/parts.php';
    }

    /** گراف اسکیمای صفحات فهرست (ItemList + Breadcrumb + سازمان) */
    private function catalogSchema(array $products, string $url, string $title, string $description, array $crumbs): string
    {
        $settings = $GLOBALS['settings'] ?? [];
        $base = Seo::base();

        $items = [];
        foreach (array_slice($products, 0, 20) as $i => $p) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => $base . '/product/' . rawurlencode((string) $p['slug']),
                'name'     => $p['name'],
            ];
        }

        return Seo::graph([
            Seo::organizationNode($settings),
            Seo::websiteNode($settings),
            Seo::webPageNode($url, $title, $description),
            Seo::breadcrumbNode($crumbs, $url),
            [
                '@type' => 'ItemList',
                '@id'   => $url . '#itemlist',
                'itemListElement' => $items,
                'numberOfItems' => count($items),
            ],
        ]);
    }

    public function show()
    {
        $slug = isset($_GET['slug']) ? trim($_GET['slug']) : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if ($id && !$slug) {
            $product = Product::findByIdIncludingDiscontinued($id);
            if ($product) {
                UrlCanonicalizer::redirect(Seo::productUrl($product['slug']), 301, 'legacy-product');
            }
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        if ($slug) {
            $product = \App\models\Product::findBySlug($slug);
        } else {
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        if (!$product) {
            // شاید اسلاگ قبلاً تغییر کرده باشد → ریدایرکت ۳۰۱ به آدرس جدید
            \App\models\Redirect::handle('/product/' . $slug, (string) ($_SERVER['QUERY_STRING'] ?? ''));
            \App\models\Redirect::log404('/product/' . $slug);
            http_response_code(404);
            require_once VIEWS_PATH . '/404.php';
            exit;
        }

        // بزرگی/کوچکی حروف، encoding فارسی و اسلاگ واقعی دیتابیس فقط به یک URL ختم شوند.
        UrlCanonicalizer::redirectIfDifferent(Seo::productUrl((string) $product['slug']));

        // محصول متوقف‌شده با جایگزین معتبر باید مستقیماً و دائمی منتقل شود.
        if (($product['lifecycle_status'] ?? 'active') === 'discontinued'
            && !empty($product['replacement_product_id'])) {
            $replacement = Product::findById((int) $product['replacement_product_id']);
            if ($replacement && (int) $replacement['id'] !== (int) $product['id']) {
                \App\models\Redirect::add(
                    Seo::productUrl((string) $product['slug']),
                    Seo::productUrl((string) $replacement['slug']),
                    [
                        'status_code' => 301,
                        'entity_type' => 'product',
                        'entity_id' => (int) $product['id'],
                        'source' => 'replacement',
                        'note' => 'انتقال محصول متوقف‌شده به جایگزین',
                    ]
                );
                UrlCanonicalizer::redirect(Seo::productUrl((string) $replacement['slug']), 301, 'replacement');
            }
        }

        $id = $product['id'];
        global $settings;
        $site_name = $settings['site_title'] ?? 'پرادو یدک';

        // ------------------------------------------------------------------
        // سئو با اولویت سلسله‌مراتبی:
        //   ۱) متای دستی مدیر → ۲) فرمول هوشمند → ۳) پیش‌فرض سراسری
        // ------------------------------------------------------------------
        $canonicalUrl = Seo::absolute('/product/' . rawurlencode((string) $product['slug']));
        $seo = Seo::resolve($product, [
            'title'       => Seo::productTitle($product, $site_name),
            'description' => Seo::productDescription($product, $site_name),
            'canonical'   => $canonicalUrl,
            'robots'      => 'index, follow',
        ]);

        $pageTitle = $seo['title'];
        $metaDescription = Seo::metaDescription($seo['description'], Seo::productDescription($product, $site_name), $product, $site_name);
        $canonicalUrl = $seo['canonical'];
        $robotsMeta = ($product['lifecycle_status'] ?? 'active') === 'discontinued'
            ? 'noindex, follow'
            : $seo['robots'];
        Seo::emitNoindexHeader($robotsMeta);

        // تصویر محصول برای اشتراک‌گذاری (og:image) با آدرس سئوشده؛ در نبود عکس
        // متغیر عمداً unset می‌ماند تا لوگو به‌عنوان تصویر محصول اعلام نشود.
        $gallery = $product['gallery'] ?? [];
        if (!empty($gallery[0]['url'])) {
            $pageImage = Seo::absolute((string) $gallery[0]['url']);
            $pageImageAlt = $gallery[0]['alt'];
        } elseif (!empty($product['image_url'])) {
            $pageImage = Seo::absolute((string) $product['image_url']);
            $pageImageAlt = (string) ($product['image_alt'] ?? '');
        }

        $similar_parts = Product::getSimilar($product, 4);
        $excludeIds = [(int) $product['id'], ...array_map(static fn($p): int => (int) $p['id'], $similar_parts)];
        $newest_parts = Product::getNewestExcluding($excludeIds, 4);

        $comments = \App\models\Product::getComments($id);
        $can_comment = false;
        if (isset($_SESSION['user_id'])) {
            $can_comment = \App\models\Product::canUserComment($id, $_SESSION['user_id']);
        }

        // مقالات آموزشی همین قطعه — بخش «راهنمای فنی و سرویس» (ساختار سیلو)
        $guideArticles = \App\models\Product::getRelatedArticles($id, 3);

        $schemaMarkup = \App\models\Product::generateSchema($product, $comments, $pageTitle, $metaDescription);

        require_once VIEWS_PATH . '/product-detail.php';
    }

    public function submitComment()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'برای ثبت نظر ابتدا باید وارد حساب کاربری شوید.']);
            exit;
        }

        $product_id = $_POST['product_id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $text = $_POST['text'] ?? '';
        $rating = $_POST['rating'] ?? 5;

        if (empty($name) || empty($text) || !$product_id) {
            echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است.']);
            exit;
        }

        try {
            if (!\App\models\Product::canUserComment($product_id, $_SESSION['user_id'])) {
                echo json_encode(['status' => 'error', 'message' => 'تنها خریداران این محصول مجاز به ثبت نظر هستند.']);
                exit;
            }

            $saved = \App\models\Product::addComment($product_id, (int) $_SESSION['user_id'], $name, $rating, $text);
            if (!$saved) {
                echo json_encode(['status' => 'error', 'message' => 'برای این خرید قبلاً نظر ثبت کرده‌اید یا اطلاعات نظر معتبر نیست.']);
                exit;
            }
            echo json_encode(['status' => 'success', 'message' => 'نظر شما با موفقیت ثبت شد و پس از تایید مدیریت نمایش داده می‌شود.']);

        } catch (\Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت نظر.']);
        }
        exit;
    }

    public function apiList()
    {
        header('Content-Type: application/json; charset=utf-8');

        $toArray = function ($input) {
            if (empty($input))
                return [];
            return is_array($input) ? $input : explode(',', $input);
        };

        $filters = [
            'id' => isset($_GET['id']) ? (int) $_GET['id'] : null,
            'q' => is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '',
            'categories' => $toArray($_GET['category'] ?? []),
            'models' => $toArray($_GET['model'] ?? []),
            'brands' => $toArray($_GET['brand'] ?? []),
            'maxPrice' => isset($_GET['maxPrice']) ? (float) $_GET['maxPrice'] : null,
            'inStock' => $_GET['inStock'] ?? '',
            'sort' => $_GET['sort'] ?? 'newest'
        ];

        $page = Seo::catalogPage($_GET);
        if ($page === null) {
            $this->jsonResponse(['error' => 'شماره صفحه نامعتبر است.'], 400);
        }
        $data = \App\models\Product::search($filters, $page, 20);

        echo json_encode($data);
        exit;
    }

    public function apiShow()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'شناسه قطعه نامعتبر است']);
            exit;
        }

        $product = \App\models\Product::findById($id);

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'قطعه یافت نشد']);
            exit;
        }

        echo json_encode($product);
        exit;
    }
}
