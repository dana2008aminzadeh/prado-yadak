<?php
namespace App\controllers;

class BlogController
{
    public function index()
    {
        require_once VIEWS_PATH . '/blog.php';
    }

    public function show()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        require_once VIEWS_PATH . '/blog-detail.php';
    }
}