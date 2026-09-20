<?php

namespace App\controllers;

use App\models\Product;

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

        require_once VIEWS_PATH . '/parts.php';
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
            header("Location: /404");
            exit;
        }

        if ($slug) {
            $product = \App\models\Product::findBySlug($slug);
        } else {
            header("Location: /404");
            exit;
        }

        if (!$product) {
            header("Location: /404");
            exit;
        }

        $id = $product['id'];
        global $settings;
        $site_name = $settings['site_title'] ?? 'پرادو یدک';
        $pageTitle = $product['name'] . ' | ' . $site_name;

        $cleanDesc = !empty($product['desc']) ? trim(preg_replace('/\s+/u', ' ', strip_tags($product['desc']))) : '';
        $oemTag = !empty($product['oem']) ? " با کد فنی {$product['oem']}" : '';

        if (!empty($cleanDesc)) {
            $metaDescription = mb_substr("خرید {$product['name']}{$oemTag}. " . $cleanDesc, 0, 155, 'UTF-8');
        } else {
            $metaDescription = "خرید و استعلام قیمت آنلاین {$product['name']}{$oemTag} تویوتا اصل جنیون و وارداتی با ضمانت اصالت کالا و ارسال سریع در {$site_name}.";
        }

        // تصویر محصول برای اشتراک‌گذاری در شبکه‌های اجتماعی (og:image)
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
        if (!empty($product['images']) && is_array($product['images']) && !empty($product['images'][0])) {
            $pageImage = $protocol . "://" . $host . "/image?id=" . urlencode($product['images'][0]);
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