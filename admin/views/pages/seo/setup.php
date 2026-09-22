<?php use Admin\core\Auth; ?>
<div class="card">
    <div class="card-head"><h3>راه‌اندازی دیده‌بان سئو</h3></div>
    <div class="card-body">
        <div class="flash info">
            جداول و ستون‌های موردنیاز سئو هنوز در پایگاه داده ساخته نشده‌اند.
        </div>
        <p class="text-muted" style="font-size:12.5px">
            برای فعال شدن فیلدهای متاتگ، ریدایرکت ۳۰۱، لاگ ۴۰۴، پیوند مقاله به محصول و لندینگ‌پیج‌ها،
            یکی از دو روش زیر را اجرا کنید:
        </p>
        <ol style="font-size:12.5px;line-height:2.2">
            <li>از طریق SSH: <code class="mono">php admin/migrate-seo.php</code></li>
            <li>یا اجرای فایل <code class="mono">admin/migrations/2026_09_22_seo.sql</code> در phpMyAdmin</li>
        </ol>
        <div class="hint">
            اجرای چندباره بی‌خطر است؛ اسکریپت فقط مواردی را می‌سازد که وجود ندارند.
            پس از اطمینان، فایل <code class="mono">migrate-seo.php</code> را از سرور حذف کنید.
        </div>
    </div>
</div>
