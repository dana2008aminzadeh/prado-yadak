# آزمون‌های رگرسیون سئو

به PHP 8.1+ با افزونه‌های `mbstring` و `PDO` نیاز است؛ پایگاه داده لازم نیست (کنترلر کاتالوگ و Sitemap با fixture اجرا می‌شوند).

```sh
php tests/seo_regression.php
for scenario in parts-overflow parts-empty-page parts-partial parts-brand taxonomy-overflow taxonomy-empty-page taxonomy-empty-base landing-overflow landing-empty-page landing-empty-base landing-partial; do
  php tests/catalog_pagination.php "$scenario"
done
for map in products articles landing brands statics; do
  php tests/sitemap_regression.php "$map"
done
```

خروجی هر سناریو باید با `PASS:` آغاز شود. بعد از استقرار، ریدایرکت HTTP/www و پاسخ 404 صفحه‌های خارج از بازه را **روی سرور اصلی یا edge** با `curl -I` هم کنترل کنید؛ فایل `.htaccess` در سرور PHP داخلی اجرا نمی‌شود. طول نمایشی عنوان‌های فارسی و LCP/preload استایل را نیز با داده‌های واقعی Search Console/Lighthouse بررسی کنید، نه فقط با شمارش نویسه.
