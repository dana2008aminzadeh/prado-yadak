<?php
namespace App\controllers;

class PartController
{
    public function index()
    {
        // نمایش لیست قطعات
        require_once VIEWS_PATH . '/parts.php';
    }

    public function show()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        require_once VIEWS_PATH . '/product.php';
    }
}