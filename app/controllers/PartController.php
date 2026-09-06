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
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        require_once VIEWS_PATH . '/product-detail.php';
    }

    public function apiList()
    {
        header('Content-Type: application/json; charset=utf-8');

        // تابعی کوچک برای تبدیل ایمن ورودی به آرایه
        $toArray = function ($input) {
            if (empty($input))
                return [];
            return is_array($input) ? $input : explode(',', $input); // پشتیبانی از فرمت category=engine,brakes
        };

        $filters = [
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
}