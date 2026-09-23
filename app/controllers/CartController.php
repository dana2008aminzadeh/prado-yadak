<?php
namespace App\controllers;

use App\models\Cart;

class CartController extends Controller
{
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
            $rawPhoto = (string) ($item['telegram_photo_id'] ?? '');
            $images = $rawPhoto !== '' ? json_decode($rawPhoto, true) : [];
            $images = is_array($images) ? $images : ($rawPhoto !== '' ? [$rawPhoto] : []);
            $primary = $images[0] ?? '';
            $imageUrl = $primary !== ''
                ? \Core\Seo::imageUrl((string) $primary, \Core\Seo::imageSlug((string) $item['name'], $item['oem_code'] ?? null))
                : '';
            $formatted[] = [
                'quantity' => (int)$item['quantity'],
                'product' => [
                    'id' => (int)$item['id'],
                    'name' => $item['name'],
                    'slug' => $item['slug'],
                    'oem' => $item['oem_code'],
                    'price' => (float)$item['price'],
                    'images' => $images,
                    'image_url' => $imageUrl,
                    'image_alt' => \Core\Seo::suggestAlt((string) $item['name'], null, $item['oem_code'] ?? null)
                ]
            ];
        }
        
        echo json_encode(['cart' => $formatted]);
    }
}
