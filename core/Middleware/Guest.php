<?php
namespace Core\Middleware;

class Guest
{
    public function handle()
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /profile');
            exit;
        }
    }
}