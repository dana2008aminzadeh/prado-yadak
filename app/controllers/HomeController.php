<?php
namespace App\controllers;

class HomeController
{
    public function index()
    {
        require_once VIEWS_PATH . '/index.php';
    }

    public function terms()
    {
        require_once VIEWS_PATH . '/terms.php';
    }
}