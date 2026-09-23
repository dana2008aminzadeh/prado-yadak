<?php
namespace Core\Middleware;

class Guest
{
    public function handle()
    {
        if (isset($_SESSION['user_id'])) {
            \Core\UrlCanonicalizer::redirect('/profile', 302, 'auth');
            exit;
        }
    }
}
