<?php
namespace App\controllers;

class AuthController
{
    public function loginForm()
    {
        require_once VIEWS_PATH . '/login.php';
    }
}