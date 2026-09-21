<?php
use Admin\core\Auth;

$admin = Auth::user() ?? [];
$flashes = take_flash();
$siteTitle = $GLOBALS['settings']['site_title'] ?? 'پرادو یدک';
$section = $section ?? 'dashboard';
$badges = $GLOBALS['admin_badges'] ?? [];

$menu = [
    ['group' => 'عمومی', 'items' => [
        ['key' => 'dashboard', 'label' => 'پیشخوان', 'icon' => 'layout-dashboard', 'url' => admin_url()],
        ['key' => 'reports', 'label' => 'گزارش‌ها و آمار', 'icon' => 'bar-chart-3', 'url' => admin_url('reports')],
    ]],
    ['group' => 'فروشگاه', 'items' => [
        ['key' => 'products', 'label' => 'محصولات', 'icon' => 'package', 'url' => admin_url('products')],
        ['key' => 'orders', 'label' => 'سفارش‌ها', 'icon' => 'shopping-bag', 'url' => admin_url('orders'), 'badge' => $badges['orders'] ?? 0],
        ['key' => 'coupons', 'label' => 'کدهای تخفیف', 'icon' => 'ticket-percent', 'url' => admin_url('coupons')],
        ['key' => 'shipping', 'label' => 'شیوه‌های ارسال', 'icon' => 'truck', 'url' => admin_url('shipping')],
        ['key' => 'catalog', 'label' => 'دسته‌بندی و مدل خودرو', 'icon' => 'layers', 'url' => admin_url('catalog')],
    ]],
    ['group' => 'کاربران', 'items' => [
        ['key' => 'users', 'label' => 'کاربران', 'icon' => 'users', 'url' => admin_url('users')],
        ['key' => 'wallet', 'label' => 'کیف پول و تراکنش‌ها', 'icon' => 'wallet', 'url' => admin_url('wallet'), 'badge' => $badges['wallet'] ?? 0],
        ['key' => 'tickets', 'label' => 'تیکت‌های پشتیبانی', 'icon' => 'headphones', 'url' => admin_url('tickets'), 'badge' => $badges['tickets'] ?? 0],
        ['key' => 'comments', 'label' => 'دیدگاه محصولات', 'icon' => 'message-square', 'url' => admin_url('comments'), 'badge' => $badges['comments'] ?? 0],
    ]],
    ['group' => 'محتوا و پیکربندی', 'items' => [
        ['key' => 'articles', 'label' => 'مقالات وبلاگ', 'icon' => 'newspaper', 'url' => admin_url('articles')],
        ['key' => 'notices', 'label' => 'اطلاعیه‌های سایت', 'icon' => 'megaphone', 'url' => admin_url('notices')],
        ['key' => 'locations', 'label' => 'استان و شهرها', 'icon' => 'map-pin', 'url' => admin_url('locations')],
        ['key' => 'settings', 'label' => 'تنظیمات سایت', 'icon' => 'settings', 'url' => admin_url('settings')],
    ]],
];
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(Auth::csrf()) ?>">
    <title><?= e($pageTitle ?? 'پنل مدیریت') ?> | <?= e($siteTitle) ?></title>
    <link rel="icon" href="/assets/logo/logo.webp">
    <style>
        :root {
            --bg: #f6f7fb;
            --surface: #ffffff;
            --ink: #1d2433;
            --muted: #6b7484;
            --line: #e6e9f0;
            --brand: #8b533a;
            --brand-soft: #8b533a1a;
            --red: #eb0a1e;
            --green: #059669;
            --amber: #d97706;
            --blue: #2563eb;
            --radius: 16px;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: 'IRANSans', Tahoma, system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.8;
        }

        a { color: inherit; text-decoration: none; }

        .layout { display: flex; min-height: 100vh; }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: #241c19;
            color: #d9d4d0;
            padding: 18px 14px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: #4a3c35; border-radius: 4px; }

        .brand {
            display: flex; align-items: center; gap: 10px;
            padding: 6px 8px 16px; border-bottom: 1px solid #3a2e29; margin-bottom: 14px;
        }
        .brand b { color: #fff; font-size: 15px; display: block; }
        .brand span { font-size: 10px; color: #9c8d85; letter-spacing: 1px; }
        .brand-logo {
            width: 38px; height: 38px; border-radius: 12px; background: var(--brand);
            display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900;
        }

        .nav-group { font-size: 10px; color: #8a7a72; margin: 14px 10px 6px; letter-spacing: .5px; }

        .nav-item {
            display: flex; align-items: center; justify-content: space-between; gap: 10px;
            padding: 10px 12px; border-radius: 12px; font-size: 13px; font-weight: 600;
            color: #cfc7c2; transition: .18s; margin-bottom: 2px;
        }
        .nav-item:hover { background: #33282333; background: rgba(255,255,255,.06); color: #fff; }
        .nav-item.active { background: var(--brand); color: #fff; }
        .nav-item .ic { width: 18px; height: 18px; flex-shrink: 0; }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .nav-badge {
            background: var(--red); color: #fff; font-size: 11px; font-weight: 800;
            min-width: 20px; height: 20px; border-radius: 999px; padding: 0 6px;
            display: inline-flex; align-items: center; justify-content: center;
        }

        /* ---------- Main ---------- */
        .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }

        .topbar {
            background: var(--surface); border-bottom: 1px solid var(--line);
            padding: 12px 22px; display: flex; align-items: center; justify-content: space-between;
            gap: 14px; position: sticky; top: 0; z-index: 30;
        }
        .topbar h1 { font-size: 17px; font-weight: 900; margin: 0; }
        .topbar .sub { font-size: 11px; color: var(--muted); font-weight: 400; }

        .content { padding: 22px; flex: 1; }

        /* ---------- Components ---------- */
        .card {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: var(--radius); box-shadow: 0 2px 10px rgba(29,36,51,.03);
        }
        .card-head {
            padding: 14px 18px; border-bottom: 1px solid var(--line);
            display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
        }
        .card-head h3 { margin: 0; font-size: 14px; font-weight: 800; }
        .card-body { padding: 18px; }
        .p-10 { padding: 40px; } .p-6 { padding: 24px; }

        .grid { display: grid; gap: 16px; }
        .g2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .g3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .g4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        @media (max-width: 1100px) { .g4 { grid-template-columns: repeat(2, minmax(0,1fr)); } .g3 { grid-template-columns: repeat(2, minmax(0,1fr)); } }
        @media (max-width: 720px) { .g2, .g3, .g4 { grid-template-columns: 1fr; } }

        .stat { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: 16px; }
        .stat .lbl { font-size: 11px; color: var(--muted); display: block; margin-bottom: 6px; }
        .stat .val { font-size: 22px; font-weight: 900; }
        .stat .ic-box {
            width: 38px; height: 38px; border-radius: 12px; background: var(--brand-soft);
            color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 10px;
        }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        thead th {
            text-align: right; font-size: 11px; color: var(--muted); font-weight: 700;
            padding: 12px 14px; border-bottom: 1px solid var(--line); background: #fafbfd; white-space: nowrap;
        }
        tbody td { padding: 12px 14px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        tbody tr:hover { background: #fbfcfe; }
        .table-wrap { overflow-x: auto; }

        .btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px;
            border-radius: 11px; font-size: 12px; font-weight: 800; border: 1px solid var(--line);
            background: #fff; color: var(--ink); cursor: pointer; transition: .16s; white-space: nowrap;
        }
        .btn:hover { border-color: var(--brand); color: var(--brand); }
        .btn-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-primary:hover { background: #74442f; color: #fff; }
        .btn-danger { background: #fff1f2; border-color: #fecdd3; color: #be123c; }
        .btn-danger:hover { background: var(--red); border-color: var(--red); color: #fff; }
        .btn-success { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .btn-success:hover { background: var(--green); color: #fff; border-color: var(--green); }
        .btn-sm { padding: 5px 10px; font-size: 11px; border-radius: 9px; }

        input[type=text], input[type=number], input[type=password], input[type=tel],
        input[type=email], input[type=date], input[type=datetime-local], input[type=search],
        select, textarea {
            width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 11px;
            font-family: inherit; font-size: 13px; background: #fff; color: var(--ink); outline: none;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
        textarea { resize: vertical; min-height: 90px; }
        label.fl { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        .field { margin-bottom: 14px; }
        .hint { font-size: 11px; color: var(--muted); margin-top: 4px; }

        .badge {
            display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 8px;
            font-size: 11px; font-weight: 800; border: 1px solid transparent;
        }
        .b-green { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .b-amber { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .b-red { background: #fff1f2; color: #be123c; border-color: #fecdd3; }
        .b-blue { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .b-gray { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }

        .flash { padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; margin-bottom: 14px; }
        .flash.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .flash.error { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; }
        .flash.info { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .filters .f { min-width: 150px; flex: 1; }

        .pagination { display: flex; gap: 6px; justify-content: center; padding: 16px; flex-wrap: wrap; }
        .pagination a, .pagination span {
            min-width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--line);
            display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: #fff;
        }
        .pagination .cur { background: var(--brand); color: #fff; border-color: var(--brand); }

        .muted, .text-muted { color: var(--muted); }
        .mono { font-family: ui-monospace, monospace; direction: ltr; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .flex { display: flex; } .items-center { align-items: center; } .gap { gap: 8px; }
        .mb { margin-bottom: 16px; } .mt { margin-top: 16px; }
        .thumb { width: 44px; height: 44px; border-radius: 10px; object-fit: contain; background: #f4f5f8; border: 1px solid var(--line); }
        .empty { text-align: center; padding: 50px 20px; color: var(--muted); font-size: 13px; }

        .sidebar-toggle { display: none; background: none; border: 0; cursor: pointer; }
        @media (max-width: 900px) {
            .sidebar { position: fixed; right: 0; top: 0; z-index: 60; transform: translateX(100%); transition: .25s; }
            .sidebar.open { transform: translateX(0); box-shadow: -10px 0 40px rgba(0,0,0,.3); }
            .sidebar-toggle { display: inline-flex; }
            .content { padding: 14px; }
            .topbar { padding: 12px 14px; }
        }

        .avatar {
            width: 36px; height: 36px; border-radius: 50%; background: var(--brand-soft); color: var(--brand);
            display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 13px;
        }
        .bar-track { height: 8px; border-radius: 99px; background: #eef0f5; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--brand); border-radius: 99px; }
    </style>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js" defer></script>
</head>

<body>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-logo">پ</div>
                <div>
                    <b><?= e($siteTitle) ?></b>
                    <span>ADMIN PANEL</span>
                </div>
            </div>

            <?php foreach ($menu as $group): ?>
                <div class="nav-group"><?= e($group['group']) ?></div>
                <?php foreach ($group['items'] as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="nav-item <?= $section === $item['key'] ? 'active' : '' ?>">
                        <span class="nav-left">
                            <i data-lucide="<?= e($item['icon']) ?>" class="ic"></i>
                            <?= e($item['label']) ?>
                        </span>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="nav-badge"><?= (int) $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="nav-group">حساب</div>
            <a href="/" target="_blank" class="nav-item">
                <span class="nav-left"><i data-lucide="external-link" class="ic"></i> مشاهده سایت</span>
            </a>
            <a href="<?= admin_url('logout') ?>" class="nav-item" style="color:#f87171">
                <span class="nav-left"><i data-lucide="log-out" class="ic"></i> خروج از پنل</span>
            </a>
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="flex items-center gap" style="gap:12px">
                    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                        <i data-lucide="menu"></i>
                    </button>
                    <div>
                        <h1><?= e($pageTitle ?? 'پیشخوان') ?></h1>
                        <?php if (!empty($pageSub)): ?><div class="sub"><?= e($pageSub) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center gap" style="gap:10px">
                    <div style="text-align:left">
                        <div style="font-size:12px;font-weight:800"><?= e($admin['full_name'] ?? 'مدیر') ?></div>
                        <div class="sub mono"><?= e($admin['phone'] ?? '') ?></div>
                    </div>
                    <div class="avatar"><?= e(mb_substr($admin['full_name'] ?? 'م', 0, 1, 'UTF-8')) ?></div>
                </div>
            </header>

            <main class="content">
                <?php foreach ($flashes as $f): ?>
                    <div class="flash <?= e($f['type']) ?>"><?= e($f['message']) ?></div>
                <?php endforeach; ?>

                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <script>
        window.addEventListener('load', () => { if (window.lucide) lucide.createIcons(); });
        function confirmDelete(msg) { return confirm(msg || 'آیا از حذف این مورد مطمئن هستید؟ این عمل بازگشت‌پذیر نیست.'); }
    </script>
    <?= $scripts ?? '' ?>
</body>

</html>
