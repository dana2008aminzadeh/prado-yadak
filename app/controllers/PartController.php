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
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        require_once VIEWS_PATH . '/product-detail.php';
    }

    public function apiList()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'categories' => $_GET['category'] ?? [], // تغییر کرد تا آرایه بگیرد
            'models' => $_GET['model'] ?? [],       // تغییر کرد تا آرایه بگیرد
            'maxPrice' => $_GET['maxPrice'] ?? null,
            'inStock' => $_GET['inStock'] ?? '',
            'brands' => $_GET['brand'] ?? [], 
            'sort' => $_GET['sort'] ?? 'newest'
        ];
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        $data = \App\models\Product::search($filters, $page, 20);
        
        echo json_encode($data);
        exit;
    }
}