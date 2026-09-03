<?php
namespace Core;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $pdo;

    // تنظیمات دیتابیس
    private $host = 'localhost';
    private $db_name = 'danasvip_pradoyadak';
    private $username = 'danasvip_pradoyadak';
    private $password = '1Ob{&XPQ*G^EX8eE';
    private $charset = 'utf8mb4';

    private function __construct()
    {
        $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // خروجی به صورت آرایه انجمنی
            PDO::ATTR_EMULATE_PREPARES => false, // امنیت بالا برای جلوگیری از SQL Injection
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            die("خطا در اتصال به پایگاه داده: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }
}