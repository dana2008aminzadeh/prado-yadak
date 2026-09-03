<?php
namespace App\controllers;

class UserController
{
    public function profile()
    {
        // در پروژه‌های واقعی اینجا ابتدا چک می‌شود که کاربر لاگین هست یا خیر
        // سپس اطلاعات کاربر از Database گرفته و به ویو ارسال می‌شود

        require_once VIEWS_PATH . '/profile.php';
    }
}