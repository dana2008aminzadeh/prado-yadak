<?php
namespace App\controllers;

class OrderController
{
    public function checkout()
    {
        require_once VIEWS_PATH . '/checkout.php';
    }
}