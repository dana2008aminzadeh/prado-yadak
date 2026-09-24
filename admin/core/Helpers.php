<?php
// توابع کمکی پنل مدیریت

use Admin\core\Auth;
use Admin\core\Jalali;

if (!function_exists('e')) {
    function e($s)
    {
        return htmlspecialchars((string) ($s ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('toShamsi')) {
    function toShamsi($dateString)
    {
        return Jalali::fromGregorianString(is_numeric($dateString) ? (string) $dateString : (string) $dateString) ?: 'نامشخص';
    }
}

if (!function_exists('shamsiTime')) {
    function shamsiTime($dateString)
    {
        $s = Jalali::fromGregorianString((string) $dateString, 'Y/m/d - H:i');
        return $s ?: '—';
    }
}

if (!function_exists('shamsiLong')) {
    function shamsiLong($dateString)
    {
        $s = Jalali::fromGregorianString((string) $dateString, 'l j F Y');
        return $s ?: '—';
    }
}

if (!function_exists('timeAgo')) {
    function timeAgo($dateString)
    {
        return Jalali::ago($dateString);
    }
}

/** تبدیل ورودی تاریخ شمسی فرم به میلادی برای کوئری */
function jalaliToDate(?string $input): ?string
{
    return Jalali::parseToGregorian($input);
}

/** تبدیل تاریخ میلادی به رشته شمسی جهت مقداردهی input */
function dateToJalali(?string $date): string
{
    return $date ? Jalali::fromGregorianString($date) : '';
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
    \Core\UrlCanonicalizer::redirect($url, 302, 'admin');
}

/** بازگشت به صفحه قبلی به‌صورت امن (فقط مسیرهای داخلی /admin) */
function back(string $fallback = '/admin'): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $path = $ref ? (string) parse_url($ref, PHP_URL_PATH) : '';
    $host = $ref ? (string) parse_url($ref, PHP_URL_HOST) : '';
    $baseHost = strtolower((string) parse_url(\Core\Seo::base(), PHP_URL_HOST));
    $refHost = strtolower($host);
    $isSameHost = $refHost === '' || $refHost === $baseHost || str_ends_with($refHost, '.' . $baseHost) || in_array($refHost, ['localhost', '127.0.0.1', ''], true);
    // همچنین برای سازگاری با محیط فعلی، HTTP_HOST را هم به عنوان fallback بپذیر اما اولویت با baseHost است
    if ($refHost !== '' && !$isSameHost) {
        $httpHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $isSameHost = $refHost === $httpHost;
    }
    if ($path && str_starts_with($path, '/admin') && $isSameHost) {
        $query = (string) parse_url($ref, PHP_URL_QUERY);
        redirect($path . ($query ? '?' . $query : ''));
    }
    redirect($fallback);
}

function money($n): string
{
    return number_format((float) $n);
}

/** مبلغ با واحد */
function toman($n): string
{
    return number_format((float) $n) . ' تومان';
}

/**
 * ساخت اسلاگ سئو شده — کوتاه، پایدار، توصیفی
 *
 * اصول:
 * - اسلاگ باید کوتاه، پایدار و توصیفی باشد
 * - تغییر اسلاگ باید همیشه با 301 انجام شود (مدیریت در SeoController)
 * - نباید نام، برند، مدل و کد فنی بی‌دلیل همگی داخل slug تکرار شوند
 * - برای محصول، پیشنهاد ساختار: /product/lent-tormoz-jolo-camry-04465-33471
 *   یا فارسی، اما یک الگوی ثابت در کل سایت
 */
function make_slug(string $text): string
{
    $text = trim($text);
    // حذف کاراکترهای غیرمجاز اما حروف فارسی/لاتین و اعداد نگه داشته شوند
    $text = preg_replace('/[^\p{L}\p{N}\s\-_]+/u', '', $text) ?? '';
    // نرمال‌سازی فاصله‌ها
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    $text = trim($text);
    if ($text === '') {
        return 'item-' . time();
    }

    // جدا کردن توکن‌ها و حذف تکرار بی‌دلیل
    $parts = preg_split('/[\s\-_]+/u', $text) ?: [];
    $seen = [];
    $cleanParts = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $key = mb_strtolower($part, 'UTF-8');
        // از تکرار جلوگیری کن، اما اعداد (مثل کد فنی) را اگر مهم هستند نگه دار
        if (isset($seen[$key]) && !preg_match('/^\d+$/', $part)) {
            continue;
        }
        $seen[$key] = true;
        $cleanParts[] = $part;
        // محدودیت تعداد توکن‌ها برای جلوگیری از اسلاگ‌های بسیار طولانی
        if (count($cleanParts) >= 8) {
            break;
        }
    }

    $slug = implode('-', $cleanParts);
    $slug = preg_replace('/-+/u', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    $slug = mb_strtolower($slug, 'UTF-8');

    // محدودیت طول نهایی — حداکثر 80 کاراکتر برای سئوی بهتر
    if (mb_strlen($slug, 'UTF-8') > 80) {
        $slug = mb_substr($slug, 0, 80, 'UTF-8');
        $slug = rtrim($slug, '-');
    }

    return $slug !== '' ? $slug : ('item-' . time());
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

/**
 * دکمه/لینکِ یک عملیات تغییر‌دهنده وضعیت.
 * به‌جای <a href> یک فرم POST با توکن CSRF می‌سازد تا در برابر CSRF ایمن باشد.
 */
function action_button(string $url, string $label, array $opts = []): string
{
    $class   = $opts['class']   ?? 'btn btn-sm';
    $confirm = $opts['confirm'] ?? '';
    $icon    = $opts['icon']    ?? '';
    $title   = $opts['title']   ?? $label;
    $fields  = $opts['fields']  ?? [];
    $disabled = !empty($opts['disabled']);

    $html = '<form method="POST" action="' . e($url) . '" style="display:inline" '
        . ($confirm ? 'onsubmit="return confirm(' . "'" . e($confirm) . "'" . ')"' : '') . '>';
    $html .= Auth::csrfField();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
    }
    $html .= '<button type="submit" class="' . e($class) . '" title="' . e($title) . '"' . ($disabled ? ' disabled' : '') . '>';
    if ($icon) {
        $html .= '<i data-lucide="' . e($icon) . '" style="width:13px;height:13px"></i> ';
    }
    $html .= e($label) . '</button></form>';
    return $html;
}

/** فیلد ورودی تاریخ شمسی */
function jalali_input(string $name, ?string $value = null, string $placeholder = '۱۴۰۴/۰۱/۰۱'): string
{
    $jalali = $value ? e(dateToJalali($value)) : '';
    return '<input type="text" name="' . e($name) . '" value="' . $jalali . '"'
        . ' class="jdate mono" autocomplete="off" placeholder="' . e($placeholder) . '" data-jdp>';
}

/** نمایش عدد به فارسی */
function fa($n): string
{
    return Jalali::toPersianDigits((string) $n);
}

/** بررسی دسترسی در ویوها */
function can(string $permission): bool
{
    return Auth::can($permission);
}

/** نمایش شرطی یک بخش بر اساس دسترسی */
function if_can(string $permission, string $html): string
{
    return Auth::can($permission) ? $html : '';
}

/** کوتاه کردن متن */
function excerpt(?string $text, int $len = 90): string
{
    $text = trim(strip_tags((string) $text));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
}
