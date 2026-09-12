<?php
namespace App\controllers;

class Controller
{
    public function __construct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrfToken();
        }
    }

    protected function verifyCsrfToken()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        $client_csrf = $_POST['csrf_token'] ??
            $headers['X-CSRF-Token'] ??
            $headers['x-csrf-token'] ??
            $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (empty($client_csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $client_csrf)) {
            http_response_code(403);
            echo json_encode(['error' => 'درخواست نامعتبر است (خطای امنیتی CSRF)']);
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