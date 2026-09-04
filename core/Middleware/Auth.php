<?php
namespace Core\Middleware;

class Auth
{
    public function handle()
    {
        if (!isset($_SESSION['user_id'])) {
            
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            
            header('Location: /login');
            exit;
        }
    }
}