<?php
namespace Core;

class Controller
{
    public function __construct()
    {
        // در متدهای POST، توکن CSRF اجباری بررسی می‌شود
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();
        }
    }

    protected function verifyCsrfToken()
    {
        $headers = apache_request_headers();
        $token = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? '';

        if (empty($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'درخواست نامعتبر است (CSRF Token Mismatch)']);
            exit;
        }
    }

    protected function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}