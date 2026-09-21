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
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #241c19 0%, #3a2b24 50%, #241c19 100%);
            font-family: 'IRANSans', Tahoma, system-ui, sans-serif; padding: 20px; color: #1d2433;
        }
        .box {
            width: 100%; max-width: 400px; background: #fff; border-radius: 22px;
            padding: 32px 28px; box-shadow: 0 25px 60px rgba(0, 0, 0, .35);
        }
        .logo {
            width: 58px; height: 58px; border-radius: 18px; background: #8b533a; color: #fff;
            display: flex; align-items: center; justify-content: center; font-weight: 900;
            font-size: 24px; margin: 0 auto 16px;
        }
        h1 { font-size: 19px; text-align: center; margin: 0 0 6px; font-weight: 900; }
        p.sub { text-align: center; font-size: 12px; color: #6b7484; margin: 0 0 22px; }
        label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        input {
            width: 100%; padding: 12px 14px; border: 1px solid #e6e9f0; border-radius: 12px;
            font-family: inherit; font-size: 14px; margin-bottom: 14px; outline: none;
        }
        input:focus { border-color: #8b533a; box-shadow: 0 0 0 3px rgba(139, 83, 58, .12); }
        button {
            width: 100%; padding: 13px; border: 0; border-radius: 12px; background: #8b533a; color: #fff;
            font-family: inherit; font-size: 14px; font-weight: 800; cursor: pointer; transition: .18s;
        }
        button:hover { background: #74442f; }
        .err {
            background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 11px 14px;
            border-radius: 12px; font-size: 12.5px; font-weight: 700; margin-bottom: 16px; text-align: center;
        }
        .foot { text-align: center; font-size: 11px; color: #9aa1ad; margin-top: 18px; }
        .foot a { color: #8b533a; }
        .ltr { direction: ltr; text-align: left; font-family: ui-monospace, monospace; }
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
        <input type="tel" id="phone" name="phone" class="ltr" placeholder="09189998852" required autofocus>

        <label for="password">رمز عبور</label>
        <input type="password" id="password" name="password" class="ltr" placeholder="••••••••" required>

        <button type="submit">ورود به پنل</button>

        <div class="foot"><a href="/">بازگشت به فروشگاه</a></div>
    </form>
</body>

</html>
