<?php
namespace App\controllers;

class CartController
{
    public function add()
    {
        $productId = isset($_POST['product_id']) ? $_POST['product_id'] : null;
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : 1;

        // ۲. انجام عملیات ثبت در سشن (Session) یا دیتابیس
        // ... کد منطق سبد خرید ...

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'message' => 'محصول با موفقیت به سبد خرید اضافه شد.'
        ]);
        exit;
    }
}