<?php
namespace App\controllers;

use App\models\Product;

class PartController
{
    public function index()
    {
        // در لود اولیه، فقط HTML صفحه لود می‌شود (بدون درگیری دیتابیس)
        require_once VIEWS_PATH . '/parts.php';
    }

    public function show()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        require_once VIEWS_PATH . '/product-detail.php';
    }

    // === این متد API وظیفه فیلتر و جستجوی زنده را دارد ===
    public function apiList()
    {
        header('Content-Type: application/json; charset=utf-8');
        
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'category' => trim($_GET['category'] ?? ''),
            'model' => trim($_GET['model'] ?? ''),
            'maxPrice' => $_GET['maxPrice'] ?? null,
            'inStock' => $_GET['inStock'] ?? '',
            'brands' => $_GET['brand'] ?? [], // آرایه‌ای از برندهای تیک‌خورده
            'sort' => $_GET['sort'] ?? 'newest'
        ];
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // واکشی امن قطعات از دیتابیس
        $data = Product::search($filters, $page, 20); // 20 قطعه در هر صفحه
        
        echo json_encode($data);
        exit;
    }
}