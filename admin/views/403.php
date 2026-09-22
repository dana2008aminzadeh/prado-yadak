<?php
use Admin\core\Auth;
$admin = Auth::user() ?? [];
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>دسترسی غیرمجاز</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #f6f7fb; font-family: 'IRANSans', Tahoma, sans-serif; padding: 20px; color: #1d2433; }
        .box { max-width: 460px; background: #fff; border-radius: 20px; padding: 36px 30px;
            text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,.08); }
        .ic { width: 68px; height: 68px; border-radius: 50%; background: #fff1f2; color: #be123c;
            display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 18px; }
        h1 { font-size: 20px; margin: 0 0 10px; }
        p { color: #6b7484; font-size: 13.5px; line-height: 2; margin: 0 0 8px; }
        .perm { background: #f3f4f6; border-radius: 9px; padding: 8px 14px; font-size: 12px;
            display: inline-block; margin: 10px 0 18px; font-weight: 700; }
        .btn { display: inline-block; padding: 11px 22px; border-radius: 12px; background: #8b533a;
            color: #fff; font-weight: 800; font-size: 13px; margin: 4px; }
        .btn.ghost { background: #fff; color: #1d2433; border: 1px solid #e6e9f0; }
    </style>
</head>

<body>
    <div class="box">
        <div class="ic">🔒</div>
        <h1>دسترسی غیرمجاز</h1>
        <p>حساب کاربری شما اجازه انجام این عملیات را ندارد.</p>
        <?php if (!empty($label)): ?>
            <div class="perm">دسترسی موردنیاز: <?= e($label) ?></div>
        <?php endif; ?>
        <p style="font-size:12px">
            نقش فعلی شما: <b><?= e($admin['role_name'] ?? 'نامشخص') ?></b><br>
            در صورت نیاز با مدیر کل سیستم تماس بگیرید.
        </p>
        <div style="margin-top:14px">
            <a class="btn" href="/admin">بازگشت به پیشخوان</a>
            <a class="btn ghost" href="javascript:history.back()">صفحه قبلی</a>
        </div>
    </div>
</body>

</html>
