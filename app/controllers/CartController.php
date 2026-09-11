<?php
namespace App\controllers;

use App\models\Cart;

class CartController
{
    // ذخیره سبد خرید از سمت جاوااسکریپت به دیتابیس
    public function sync()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $cartData = $input['cart'] ?? [];
        
        Cart::sync($_SESSION['user_id'], $cartData);
        echo json_encode(['status' => 'success']);
    }
    
    // دریافت سبد خرید ذخیره شده برای وقتی که کاربر با سیستم جدید وارد میشود
    public function get()
    {
        if (session_status() == PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['cart' => []]);
            exit;
        }
        
        $items = Cart::get($_SESSION['user_id']);
        $formatted = [];
        
        foreach($items as $item) {
            $images = !empty($item['telegram_photo_id']) ? json_decode($item['telegram_photo_id'], true) : [];
            $formatted[] = [
                'quantity' => (int)$item['quantity'],
                'product' => [
                    'id' => (int)$item['id'],
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'oem' => $item['oem_code'],
                    'price' => (float)$item['price'],
                    'images' => is_array($images) ? $images : []
                ]
            ];
        }
        
        echo json_encode(['cart' => $formatted]);
    }
}