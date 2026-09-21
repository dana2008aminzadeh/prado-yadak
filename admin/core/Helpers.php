<?php
// توابع کمکی پنل مدیریت

if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('toShamsi')) {
    function toShamsi($dateString)
    {
        $timestamp = is_numeric($dateString) ? $dateString : strtotime((string) $dateString);
        if (!$timestamp) return 'نامشخص';
        $gy = (int) date('Y', $timestamp);
        $gm = (int) date('m', $timestamp);
        $gd = (int) date('d', $timestamp);
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + (int) (($gy2 + 3) / 4) - (int) (($gy2 + 99) / 100)
            + (int) (($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + 33 * (int) ($days / 12053);
        $days %= 12053;
        $jy += 4 * (int) ($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $jm = ($days < 186) ? 1 + (int) ($days / 31) : 7 + (int) (($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }
}

if (!function_exists('shamsiTime')) {
    function shamsiTime($dateString)
    {
        $ts = is_numeric($dateString) ? $dateString : strtotime((string) $dateString);
        if (!$ts) return '—';
        return toShamsi($ts) . ' - ' . date('H:i', $ts);
    }
}

function admin_url(string $path = '', array $params = []): string
{
    $url = '/admin' . ($path ? '/' . ltrim($path, '/') : '');
    if ($params) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function flash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flash(): array
{
    $f = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    return $f;
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function money($n): string
{
    return number_format((float) $n);
}

function make_slug(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $text);
    $text = preg_replace('/\s+/u', '-', $text);
    return trim(mb_strtolower($text, 'UTF-8'), '-') ?: ('item-' . time());
}

function param(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function post(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function paginate(int $total, int $page, int $perPage): array
{
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'offset' => ($page - 1) * $perPage, 'total' => $total];
}
