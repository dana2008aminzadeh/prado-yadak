<?php

namespace App\controllers;

use App\models\LandingPage;
use App\models\Product;
use Core\Seo;

class PartController extends Controller
{
    public function index()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $toArray = function ($input) {
            if (empty($input))
                return [];
            return is_array($input) ? $input : explode(',', $input);
        };

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
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
        $brands = Product::getDistinctBrands();

        $selectedCat = !empty($_GET['category']) ? $_GET['category'] : null;
        $selectedModel = !empty($_GET['model']) ? $_GET['model'] : null;

        if ($selectedCat && isset($GLOBALS['part_categories'][$selectedCat])) {
            $catInfo = $GLOBALS['part_categories'][$selectedCat];
            $catName = is_array($catInfo) ? ($catInfo['name'] ?? $selectedCat) : $catInfo;
            $metaDescription = "خرید انواع قطعات و لوازم یدکی {$catName} تویوتا اصل جنیون پارت و وارداتی OEM با تضمین ۱۰۰٪ اصالت و ارسال سریع از فروشگاه {$siteName}.";
        } elseif ($selectedModel && isset($GLOBALS['car_models'][$selectedModel])) {
            $modInfo = $GLOBALS['car_models'][$selectedModel];
            $modName = is_array($modInfo) ? ($modInfo['name'] ?? $selectedModel) : $modInfo;
            $metaDescription = "کاتالوگ جامع قطعات یدکی تویوتا {$modName}؛ استعلام قیمت، تطابق با شماره شاسی (VIN) و خرید آنلاین با ضمانت اصالت کالا در {$siteName}.";
        } elseif (!empty($filters['q'])) {
            $metaDescription = "نتایج جستجو برای قطعه «" . htmlspecialchars($filters['q']) . "» در فروشگاه {$siteName}؛ خرید آنلاین قطعات اصلی تویوتا با ارسال فوری به سراسر کشور.";
        } else {
            $metaDescription = "کاتالوگ و لیست قیمت روز انواع لوازم یدکی و قطعات مصرفی تویوتا و لکسوس؛ ضمانت ۱۰۰٪ اصالت جنیون پارتس با امکان مرجوعی در فروشگاه {$siteName}.";
        }

        // ---- سئوی کاتالوگ: کانونیکال نرمال‌شده + قانون noindex فیلترهای کم‌ارزش ----
        $canonicalUrl = Seo::catalogCanonical($_GET, '/parts');
        $robotsMeta = Seo::catalogRobots($_GET);

        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
        ];
        if ($selectedCat) {
            $catInfo = $GLOBALS['part_categories'][$selectedCat] ?? null;
            $crumbs[] = [
                'name' => is_array($catInfo) ? ($catInfo['name'] ?? $selectedCat) : ($catInfo ?: $selectedCat),
                'url'  => '/parts?category=' . rawurlencode((string) $selectedCat),
            ];
        }

        $schemaMarkup = $this->catalogSchema($products, $canonicalUrl, $metaDescription, $crumbs);

        require_once VIEWS_PATH . '/parts.php';
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

        LandingPage::incrementViews((int) $landing['id']);

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $perPage = 20;

        $filters = LandingPage::toFilters($landing);
        $data = Product::search($filters, $page, $perPage);
        $products = $data['items'];
        $totalCount = (int) $data['total'];
        $totalPages = (int) ceil($totalCount / $perPage);
        $brands = Product::getDistinctBrands();

        $selectedCat = $filters['categories'][0] ?? null;
        $selectedModel = $filters['models'][0] ?? null;

        $canonicalUrl = Seo::absolute('/parts/' . rawurlencode($landing['slug']))
            . ($page > 1 ? '?page=' . $page : '');

        $resolved = Seo::resolve($landing, [
            'title'       => $landing['h1'] . ' | ' . $siteName,
            'description' => Seo::truncate(Seo::clean($landing['intro_html'] ?? $landing['h1']), Seo::DESC_MAX),
            'canonical'   => $canonicalUrl,
            'robots'      => 'index, follow',
        ]);

        $pageTitle = $resolved['title'];
        $metaDescription = $resolved['description'];
        $canonicalUrl = $resolved['canonical'];
        $robotsMeta = $resolved['robots'];

        $crumbs = [
            ['name' => 'صفحه اصلی', 'url' => '/'],
            ['name' => 'کاتالوگ قطعات', 'url' => '/parts'],
            ['name' => $landing['h1'], 'url' => '/parts/' . rawurlencode($landing['slug'])],
        ];
        $schemaMarkup = $this->catalogSchema($products, $canonicalUrl, $metaDescription, $crumbs);

        $landingPage = $landing;
        require_once VIEWS_PATH . '/parts.php';
    }

    /** گراف اسکیمای صفحات فهرست (ItemList + Breadcrumb + سازمان) */
    private function catalogSchema(array $products, string $url, string $description, array $crumbs): string
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
            Seo::webPageNode($url, $crumbs[count($crumbs) - 1]['name'] ?? 'کاتالوگ', $description),
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
            $product = \App\models\Product::findById($id);
            if ($product) {
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: /product/" . urlencode($product['slug']));
                exit;
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
        $metaDescription = $seo['description'];
        $canonicalUrl = $seo['canonical'];
        $robotsMeta = $seo['robots'];

        // تصویر محصول برای اشتراک‌گذاری (og:image) با آدرس سئوشده
        $gallery = $product['gallery'] ?? [];
        if (!empty($gallery)) {
            $pageImage = Seo::base() . $gallery[0]['url'];
            $pageImageAlt = $gallery[0]['alt'];
        } elseif (!empty($product['images'][0])) {
            $pageImage = Seo::base() . Seo::imageUrl(
                (string) $product['images'][0],
                Seo::imageSlug((string) $product['name'], $product['oem'] ?? null, $product['model'] ?? null)
            );
            $pageImageAlt = Seo::suggestAlt((string) $product['name'], null, $product['oem'] ?? null);
        }

        $similar_parts_data = \App\models\Product::search(['categories' => [$product['category']]], 1, 4);
        $similar_parts = $similar_parts_data['items'];

        $newest_parts_data = \App\models\Product::search([], 1, 4);
        $newest_parts = $newest_parts_data['items'];

        $comments = \App\models\Product::getComments($id);
        $can_comment = false;
        if (isset($_SESSION['user_id'])) {
            $can_comment = \App\models\Product::canUserComment($id, $_SESSION['user_id']);
        }

        // مقالات آموزشی همین قطعه — بخش «راهنمای فنی و سرویس» (ساختار سیلو)
        $guideArticles = \App\models\Product::getRelatedArticles($id, 3);

        $schemaMarkup = \App\models\Product::generateSchema($product, $comments);

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

            \App\models\Product::addComment($product_id, $name, $rating, $text);
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
            'q' => trim($_GET['q'] ?? ''),
            'categories' => $toArray($_GET['category'] ?? []),
            'models' => $toArray($_GET['model'] ?? []),
            'brands' => $toArray($_GET['brand'] ?? []),
            'maxPrice' => isset($_GET['maxPrice']) ? (float) $_GET['maxPrice'] : null,
            'inStock' => $_GET['inStock'] ?? '',
            'sort' => $_GET['sort'] ?? 'newest'
        ];

        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

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