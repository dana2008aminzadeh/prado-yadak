<?php
namespace Core\Middleware;

class Auth
{
    public function handle()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}