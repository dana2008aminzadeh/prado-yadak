<?php
namespace App\controllers;

class ImageController
{
    public function show()
    {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            http_response_code(400);
            die('شناسه تصویر نامعتبر است.');
        }

        $file_id = $_GET['id'];

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $file_id)) {
            http_response_code(400);
            die('فرمت شناسه غیرمجاز است.');
        }

        global $settings;
        $bot_token = $settings['telegram_bot_token'] ?? '';

        if (empty($bot_token)) {
            http_response_code(500);
            die('توکن ربات تلگرام تنظیم نشده است.');
        }

        $cache_dir = CORE_PATH . '/cache/paths';
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }

        $path_cache_file = $cache_dir . '/' . md5($file_id) . '.txt';
        $cache_life = 3000;
        $file_path = '';

        if (file_exists($path_cache_file) && (time() - filemtime($path_cache_file)) < $cache_life) {
            $file_path = file_get_contents($path_cache_file);
        } else {
            $api_url = "https://api.telegram.org/bot{$bot_token}/getFile?file_id={$file_id}";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            if (isset($data['ok']) && $data['ok']) {
                $file_path = $data['result']['file_path'];
                file_put_contents($path_cache_file, $file_path);
            } else {
                http_response_code(404);
                die('تصویر در سرورهای تلگرام یافت نشد.');
            }
        }

        $telegram_file_url = "https://api.telegram.org/file/bot{$bot_token}/{$file_path}";

        $etag = md5($file_id);
        header("Cache-Control: public, max-age=31536000");
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + 31536000) . " GMT");
        header("Etag: $etag");

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        // تشخیص فرمت عکس
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif'
        ];
        header("Content-Type: " . ($mime_types[$ext] ?? 'image/jpeg'));

        $ch = curl_init($telegram_file_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        curl_close($ch);

        exit;
    }
}