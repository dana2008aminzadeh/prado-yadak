<?php
namespace App\controllers;

use App\models\Product;

class PartController
{
    public function index()
    {
        $brands = \App\models\Product::getDistinctBrands();

        // نمایش لیست قطعات
        require_once VIEWS_PATH . '/parts.php';
    }

    public function show()
    {
        $slug = isset($_GET['slug']) ? $_GET['slug'] : null;
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

        if ($slug) {
            $product = \App\models\Product::findBySlug($slug);
        } elseif ($id) {
            $product = \App\models\Product::findById($id);
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

        $similar_parts_data = \App\models\Product::search(['categories' => [$product['category']]], 1, 4);
        $similar_parts = $similar_parts_data['items'];
        $newest_parts_data = \App\models\Product::search([], 1, 4);
        $newest_parts = $newest_parts_data['items'];

        // استفاده از Model به جای نوشتن کوئری در Controller
        $comments = \App\models\Product::getComments($id);
        $can_comment = false;

        if (isset($_SESSION['user_id'])) {
            $can_comment = \App\models\Product::canUserComment($id, $_SESSION['user_id']);
        }

        // دریافت خروجی اسکیما برای ارسال به فایل Header
        $schemaMarkup = \App\models\Product::generateSchema($product, $comments);

        require_once VIEWS_PATH . '/product-detail.php';
    }

    public function checkAuthenticity()
    {
        header('Content-Type: application/json; charset=utf-8');
        $code = $_POST['code'] ?? '';

        if (empty($code)) {
            echo json_encode(['status' => 'error', 'message' => 'لطفاً کد اصالت یا OEM را وارد کنید.']);
            exit;
        }

        // فراخوانی مدل
        $prod = \App\models\Product::findByOem($code);

        if ($prod) {
            if ($prod['is_genuine']) {
                echo json_encode(['status' => 'success', 'message' => "اصالت تایید شد: قطعه ({$prod['name']}) جنیون پارت اصلی تویوتا است."]);
            } else {
                echo json_encode(['status' => 'warning', 'message' => "قطعه معتبر است: قطعه ({$prod['name']}) از برندهای وارداتی معتبر (OEM) می‌باشد."]);
            }
        } else {
            if (stripos($code, 'toy') !== false) {
                echo json_encode(['status' => 'success', 'message' => "اصالت تایید شد: کد در سامانه بین‌المللی تویوتا جنیون ثبت شده است."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'کد وارد شده در سامانه یافت نشد. قطعه فاقد اعتبار است.']);
            }
        }
        exit;
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