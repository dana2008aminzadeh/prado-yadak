<?php
use Admin\core\Auth;
$siteTitle = $GLOBALS['settings']['site_title'] ?? 'پرادو یدک';
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ورود به پنل مدیریت | <?= e($siteTitle) ?></title>
    <link rel="icon" href="/assets/logo/logo.webp">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #241c19 0%, #3a2b24 50%, #241c19 100%);
            font-family: 'IRANSans', 'Vazirmatn', Tahoma, sans-serif; padding: 20px; color: #1d2433; }
        .box { width: 100%; max-width: 396px; background: #fff; border-radius: 22px;
            padding: 32px 28px; box-shadow: 0 25px 60px rgba(0,0,0,.35); }
        .logo { width: 56px; height: 56px; border-radius: 17px; background: #8b533a; color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 900;
            font-size: 23px; margin: 0 auto 15px; }
        h1 { font-size: 18px; text-align: center; margin: 0 0 5px; font-weight: 900; }
        p.sub { text-align: center; font-size: 11.5px; color: #6b7484; margin: 0 0 20px; }
        label { display: block; font-size: 11.5px; font-weight: 700; margin-bottom: 5px; }
        input { width: 100%; padding: 11px 13px; border: 1px solid #e6e9f0; border-radius: 12px;
            font-family: inherit; font-size: 13.5px; margin-bottom: 13px; outline: none; }
        input:focus { border-color: #8b533a; box-shadow: 0 0 0 3px rgba(139,83,58,.12); }
        button { width: 100%; padding: 12px; border: 0; border-radius: 12px; background: #8b533a; color: #fff;
            font-family: inherit; font-size: 13.5px; font-weight: 800; cursor: pointer; transition: .18s; }
        button:hover { background: #74442f; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        .err { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 10px 13px;
            border-radius: 12px; font-size: 12px; font-weight: 700; margin-bottom: 15px; text-align: center; }
        .foot { text-align: center; font-size: 10.5px; color: #9aa1ad; margin-top: 16px; }
        .foot a { color: #8b533a; text-decoration: none; }
        .ltr { direction: ltr; text-align: left; font-family: ui-monospace, monospace; }
        .note { background: #f6f7fb; border-radius: 10px; padding: 9px 12px; font-size: 10.5px;
            color: #6b7484; margin-top: 14px; line-height: 1.9; }
    </style>
</head>

<body>
    <form class="box" method="POST" action="<?= admin_url('login') ?>" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <div class="logo">پ</div>
        <h1>ورود به پنل مدیریت</h1>
        <p class="sub"><?= e($siteTitle) ?> — دسترسی ویژه مدیران</p>

        <?php if (!empty($error)): ?>
            <div class="err"><?= e($error) ?></div>
        <?php endif; ?>

        <label for="phone">شماره موبایل مدیر</label>
        <input type="tel" id="phone" name="phone" class="ltr" placeholder="09189998852" required autofocus
               inputmode="numeric" oninput="this.value=this.value.replace(/[۰-۹]/g,c=>'۰۱۲۳۴۵۶۷۸۹'.indexOf(c))">

        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" class="ltr" placeholder="••••••••" required>

        <button type="submit">ورود به پنل</button>

        <div class="note">
            🔒 این صفحه با محدودسازی تلاش ناموفق محافظت می‌شود. پس از ۱۰ تلاش ناموفق،
            دسترسی از این IP به مدت ۱۵ دقیقه مسدود می‌شود.
        </div>

        <div class="foot"><a href="/">بازگشت به فروشگاه</a></div>
    </form>
</body>

</html>
