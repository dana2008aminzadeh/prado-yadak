<?php
namespace Admin\core;

/**
 * تبدیل دوطرفه تاریخ جلالی و میلادی (بدون وابستگی به intl)
 */
class Jalali
{
    public const MONTHS = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];

    public const DAYS = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

    /** میلادی → جلالی */
    public static function fromGregorian(int $gy, int $gm, int $gd): array
    {
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
        return [$jy, $jm, $jd];
    }

    /** جلالی → میلادی */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + ((int) ($jy / 33) * 8) + (int) ((($jy % 33) + 3) / 4) + $jd
            + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * (int) ($days / 146097);
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * (int) (--$days / 36524);
            $days %= 36524;
            if ($days >= 365) $days++;
        }
        $gy += 4 * (int) ($days / 1461);
        $days %= 1461;
        if ($days > 365) {
            $gy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $sal_a = [0, 31, (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0)) ? 29 : 28,
            31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        while ($gm < 13 && $gd > $sal_a[$gm]) {
            $gd -= $sal_a[$gm];
            $gm++;
        }
        return [$gy, $gm, $gd];
    }

    /** "1404/07/01" → "2025-09-23" ; ورودی نامعتبر → null */
    public static function parseToGregorian(?string $jalaliDate): ?string
    {
        if (!$jalaliDate) return null;
        $s = self::toEnglishDigits(trim($jalaliDate));
        if (!preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $s, $m)) {
            // شاید کاربر تاریخ میلادی وارد کرده باشد
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) return $s;
            return null;
        }
        [$jy, $jm, $jd] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        if ($jm < 1 || $jm > 12 || $jd < 1 || $jd > 31) return null;
        [$gy, $gm, $gd] = self::toGregorian($jy, $jm, $jd);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /** "2025-09-23" → "1404/07/01" */
    public static function fromGregorianString(?string $date, string $format = 'Y/m/d'): string
    {
        if (!$date) return '';
        $ts = is_numeric($date) ? (int) $date : strtotime($date);
        if (!$ts) return '';
        [$jy, $jm, $jd] = self::fromGregorian((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));

        $dayOfWeek = (int) date('w', $ts); // 0=یکشنبه
        $jDayIndex = ($dayOfWeek + 1) % 7; // 0=شنبه

        return strtr($format, [
            'Y' => sprintf('%04d', $jy),
            'm' => sprintf('%02d', $jm),
            'd' => sprintf('%02d', $jd),
            'n' => (string) $jm,
            'j' => (string) $jd,
            'F' => self::MONTHS[$jm - 1] ?? '',
            'l' => self::DAYS[$jDayIndex] ?? '',
            'H' => date('H', $ts),
            'i' => date('i', $ts),
        ]);
    }

    public static function toEnglishDigits(string $s): string
    {
        $fa = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $ar = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return str_replace($ar, range(0, 9), str_replace($fa, range(0, 9), $s));
    }

    public static function toPersianDigits(string $s): string
    {
        return str_replace(range(0, 9), ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], $s);
    }

    /** امروز به شمسی */
    public static function today(string $format = 'Y/m/d'): string
    {
        return self::fromGregorianString(date('Y-m-d'), $format);
    }

    /** فاصله زمانی به فارسی: «۳ ساعت پیش» */
    public static function ago($datetime): string
    {
        $ts = is_numeric($datetime) ? (int) $datetime : strtotime((string) $datetime);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 0) return 'چند لحظه دیگر';
        if ($diff < 60) return 'همین الان';
        if ($diff < 3600) return (int) ($diff / 60) . ' دقیقه پیش';
        if ($diff < 86400) return (int) ($diff / 3600) . ' ساعت پیش';
        if ($diff < 2592000) return (int) ($diff / 86400) . ' روز پیش';
        return self::fromGregorianString(date('Y-m-d', $ts));
    }
}
