<?php
use Admin\core\Auth;
use Admin\core\Settings;

$admin = Auth::user() ?? [];
$flashes = take_flash();
$siteTitle = $GLOBALS['settings']['site_title'] ?? 'پرادو یدک';
$section = $section ?? 'dashboard';
$badges = $GLOBALS['admin_badges'] ?? [];
$csrf = Auth::csrf();
$pollInterval = max(10, Settings::int('admin_poll_interval', 30));
$soundOn = Settings::bool('admin_notify_sound', true);

$menu = [
    ['group' => 'عمومی', 'items' => [
        ['key' => 'dashboard', 'label' => 'پیشخوان', 'icon' => 'layout-dashboard', 'url' => admin_url(), 'perm' => 'dashboard.view'],
        ['key' => 'reports', 'label' => 'گزارش‌ها و آمار', 'icon' => 'bar-chart-3', 'url' => admin_url('reports'), 'perm' => 'reports.view'],
    ]],
    ['group' => 'فروشگاه', 'items' => [
        ['key' => 'products', 'label' => 'محصولات', 'icon' => 'package', 'url' => admin_url('products'), 'perm' => 'products.view', 'badge' => $badges['products'] ?? 0, 'badgeClass' => 'warn'],
        ['key' => 'orders', 'label' => 'سفارش‌ها', 'icon' => 'shopping-bag', 'url' => admin_url('orders'), 'perm' => 'orders.view', 'badge' => $badges['orders'] ?? 0, 'id' => 'badge-orders'],
        ['key' => 'coupons', 'label' => 'کدهای تخفیف', 'icon' => 'ticket-percent', 'url' => admin_url('coupons'), 'perm' => 'coupons.view'],
        ['key' => 'shipping', 'label' => 'شیوه‌های ارسال', 'icon' => 'truck', 'url' => admin_url('shipping'), 'perm' => 'shipping.view'],
        ['key' => 'catalog', 'label' => 'دسته‌بندی و خودرو', 'icon' => 'layers', 'url' => admin_url('catalog'), 'perm' => 'catalog.view'],
    ]],
    ['group' => 'کاربران', 'items' => [
        ['key' => 'users', 'label' => 'کاربران', 'icon' => 'users', 'url' => admin_url('users'), 'perm' => 'users.view'],
        ['key' => 'wallet', 'label' => 'کیف پول', 'icon' => 'wallet', 'url' => admin_url('wallet'), 'perm' => 'wallet.view', 'badge' => $badges['wallet'] ?? 0, 'id' => 'badge-wallet'],
        ['key' => 'tickets', 'label' => 'تیکت‌های پشتیبانی', 'icon' => 'headphones', 'url' => admin_url('tickets'), 'perm' => 'tickets.view', 'badge' => $badges['tickets'] ?? 0, 'id' => 'badge-tickets'],
        ['key' => 'comments', 'label' => 'دیدگاه محصولات', 'icon' => 'message-square', 'url' => admin_url('comments'), 'perm' => 'comments.view', 'badge' => $badges['comments'] ?? 0, 'id' => 'badge-comments'],
        ['key' => 'sms', 'label' => 'سامانه پیامک', 'icon' => 'send', 'url' => admin_url('sms'), 'perm' => 'sms.view'],
    ]],
    ['group' => 'محتوا و پیکربندی', 'items' => [
        ['key' => 'articles', 'label' => 'مقالات وبلاگ', 'icon' => 'newspaper', 'url' => admin_url('articles'), 'perm' => 'articles.view'],
        ['key' => 'notices', 'label' => 'اطلاعیه‌های سایت', 'icon' => 'megaphone', 'url' => admin_url('notices'), 'perm' => 'notices.view'],
        ['key' => 'locations', 'label' => 'استان و شهرها', 'icon' => 'map-pin', 'url' => admin_url('locations'), 'perm' => 'locations.view'],
        ['key' => 'settings', 'label' => 'تنظیمات سایت', 'icon' => 'settings', 'url' => admin_url('settings'), 'perm' => 'settings.view'],
    ]],
    ['group' => 'مدیریت سیستم', 'items' => [
        ['key' => 'roles', 'label' => 'نقش‌ها و دسترسی', 'icon' => 'shield-check', 'url' => admin_url('roles'), 'perm' => 'roles.manage'],
        ['key' => 'audit', 'label' => 'لاگ رویدادها', 'icon' => 'scroll-text', 'url' => admin_url('audit'), 'perm' => 'audit.view'],
        ['key' => 'tools', 'label' => 'ابزار و پشتیبان‌گیری', 'icon' => 'database-backup', 'url' => admin_url('tools'), 'perm' => 'tools.backup'],
        ['key' => 'seo', 'label' => 'دیده‌بان سئو', 'icon' => 'search-check', 'url' => admin_url('seo'), 'perm' => 'seo.view', 'badge' => $badges['seo'] ?? 0, 'badgeClass' => 'warn'],
    ]],
];
?>
<!doctype html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e($csrf) ?>">
    <title><?= e($pageTitle ?? 'پنل مدیریت') ?> | <?= e($siteTitle) ?></title>
    <link rel="icon" href="/assets/logo/logo.webp">
    <style>
        :root {
            --bg: #f6f7fb; --surface: #fff; --ink: #1d2433; --muted: #6b7484; --line: #e6e9f0;
            --brand: #8b533a; --brand-dark: #74442f; --brand-soft: #8b533a1a;
            --red: #eb0a1e; --green: #059669; --amber: #d97706; --blue: #2563eb;
            --radius: 16px;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink);
            font-family: 'IRANSans', 'Vazirmatn', Tahoma, system-ui, sans-serif; font-size: 14px; line-height: 1.8; }
        a { color: inherit; text-decoration: none; }
        .layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 258px; flex-shrink: 0; background: #241c19; color: #d9d4d0; padding: 16px 12px;
            position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-thumb { background: #4a3c35; border-radius: 4px; }
        .brand { display: flex; align-items: center; gap: 10px; padding: 6px 8px 14px;
            border-bottom: 1px solid #3a2e29; margin-bottom: 10px; }
        .brand b { color: #fff; font-size: 14px; display: block; }
        .brand span { font-size: 9.5px; color: #9c8d85; letter-spacing: 1px; }
        .brand-logo { width: 36px; height: 36px; border-radius: 11px; background: var(--brand);
            display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 900; }
        .nav-group { font-size: 9.5px; color: #8a7a72; margin: 13px 10px 5px; letter-spacing: .5px; }
        .nav-item { display: flex; align-items: center; justify-content: space-between; gap: 8px;
            padding: 9px 11px; border-radius: 11px; font-size: 12.5px; font-weight: 600;
            color: #cfc7c2; transition: .18s; margin-bottom: 2px; }
        .nav-item:hover { background: rgba(255,255,255,.06); color: #fff; }
        .nav-item.active { background: var(--brand); color: #fff; }
        .nav-item .ic { width: 17px; height: 17px; flex-shrink: 0; }
        .nav-left { display: flex; align-items: center; gap: 9px; }
        .nav-badge { background: var(--red); color: #fff; font-size: 10.5px; font-weight: 800; min-width: 19px;
            height: 19px; border-radius: 999px; padding: 0 5px; display: inline-flex;
            align-items: center; justify-content: center; }
        .nav-badge.warn { background: var(--amber); }
        .nav-badge:empty, .nav-badge[data-count="0"] { display: none; }
        .role-chip { background: rgba(255,255,255,.08); border-radius: 8px; padding: 6px 10px;
            font-size: 10.5px; color: #b8aca5; margin: 4px 0 8px; text-align: center; }

        /* Main */
        .main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar { background: var(--surface); border-bottom: 1px solid var(--line); padding: 10px 20px;
            display: flex; align-items: center; justify-content: space-between; gap: 14px;
            position: sticky; top: 0; z-index: 40; }
        .topbar h1 { font-size: 16.5px; font-weight: 900; margin: 0; }
        .topbar .sub { font-size: 11px; color: var(--muted); font-weight: 400; }
        .content { padding: 20px; flex: 1; }

        /* Cards */
        .card { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
            box-shadow: 0 2px 10px rgba(29,36,51,.03); }
        .card-head { padding: 13px 17px; border-bottom: 1px solid var(--line); display: flex;
            align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .card-head h3 { margin: 0; font-size: 13.5px; font-weight: 800; }
        .card-body { padding: 17px; }
        .p-10 { padding: 40px; } .p-6 { padding: 24px; }

        .grid { display: grid; gap: 15px; }
        .g2 { grid-template-columns: repeat(2, minmax(0,1fr)); }
        .g3 { grid-template-columns: repeat(3, minmax(0,1fr)); }
        .g4 { grid-template-columns: repeat(4, minmax(0,1fr)); }
        @media (max-width:1100px){ .g4{grid-template-columns:repeat(2,minmax(0,1fr))} .g3{grid-template-columns:repeat(2,minmax(0,1fr))} }
        @media (max-width:720px){ .g2,.g3,.g4{grid-template-columns:1fr} }

        .stat { background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); padding: 15px; display: block; }
        .stat .lbl { font-size: 10.5px; color: var(--muted); display: block; margin-bottom: 5px; }
        .stat .val { font-size: 21px; font-weight: 900; }
        .stat .ic-box { width: 36px; height: 36px; border-radius: 11px; background: var(--brand-soft);
            color: var(--brand); display: flex; align-items: center; justify-content: center; margin-bottom: 9px; }
        .stat.danger .ic-box { background: #fff1f2; color: var(--red); }
        .stat.warn .ic-box { background: #fffbeb; color: var(--amber); }

        table { width: 100%; border-collapse: collapse; font-size: 12.8px; }
        thead th { text-align: right; font-size: 10.5px; color: var(--muted); font-weight: 700;
            padding: 11px 13px; border-bottom: 1px solid var(--line); background: #fafbfd; white-space: nowrap; }
        tbody td { padding: 11px 13px; border-bottom: 1px solid var(--line); vertical-align: middle; }
        tbody tr:hover { background: #fbfcfe; }
        .table-wrap { overflow-x: auto; }

        .btn { display: inline-flex; align-items: center; gap: 5px; padding: 8px 13px; border-radius: 11px;
            font-size: 12px; font-weight: 800; border: 1px solid var(--line); background: #fff; color: var(--ink);
            cursor: pointer; transition: .16s; white-space: nowrap; font-family: inherit; }
        .btn:hover { border-color: var(--brand); color: var(--brand); }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-primary { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); color: #fff; }
        .btn-danger { background: #fff1f2; border-color: #fecdd3; color: #be123c; }
        .btn-danger:hover { background: var(--red); border-color: var(--red); color: #fff; }
        .btn-success { background: #ecfdf5; border-color: #a7f3d0; color: #047857; }
        .btn-success:hover { background: var(--green); color: #fff; border-color: var(--green); }
        .btn-sm { padding: 5px 9px; font-size: 11px; border-radius: 9px; }
        .btn-block { width: 100%; justify-content: center; }

        input[type=text], input[type=number], input[type=password], input[type=tel], input[type=email],
        input[type=date], input[type=datetime-local], input[type=search], input[type=file], select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 11px;
            font-family: inherit; font-size: 12.8px; background: #fff; color: var(--ink); outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-soft); }
        textarea { resize: vertical; min-height: 85px; }
        label.fl { display: block; font-size: 11.5px; font-weight: 700; margin-bottom: 5px; }
        .field { margin-bottom: 13px; }
        .hint { font-size: 10.8px; color: var(--muted); margin-top: 3px; }
        .chk { display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 12.5px; font-weight: 700; margin-bottom: 10px; }
        .chk input { width: auto; }

        .badge { display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; border-radius: 8px;
            font-size: 10.5px; font-weight: 800; border: 1px solid transparent; white-space: nowrap; }
        .b-green { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
        .b-amber { background:#fffbeb; color:#b45309; border-color:#fde68a; }
        .b-red { background:#fff1f2; color:#be123c; border-color:#fecdd3; }
        .b-blue { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
        .b-gray { background:#f3f4f6; color:#4b5563; border-color:#e5e7eb; }

        .flash { padding: 11px 15px; border-radius: 12px; font-size: 12.5px; font-weight: 700; margin-bottom: 12px; }
        .flash.success { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; }
        .flash.error { background:#fff1f2; color:#be123c; border:1px solid #fecdd3; }
        .flash.info { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }

        .filters { display: flex; gap: 9px; flex-wrap: wrap; align-items: flex-end; }
        .filters .f { min-width: 140px; flex: 1; }

        .pagination { display: flex; gap: 5px; justify-content: center; padding: 15px; flex-wrap: wrap; }
        .pagination a, .pagination span { min-width: 33px; height: 33px; border-radius: 10px;
            border: 1px solid var(--line); display: inline-flex; align-items: center; justify-content: center;
            font-size: 11.5px; font-weight: 700; background: #fff; }
        .pagination .cur { background: var(--brand); color: #fff; border-color: var(--brand); }

        .muted, .text-muted { color: var(--muted); }
        .mono { font-family: ui-monospace, 'Courier New', monospace; direction: ltr; unicode-bidi: embed; }
        .text-center { text-align: center; } .text-left { text-align: left; }
        .flex { display: flex; } .items-center { align-items: center; } .gap { gap: 8px; }
        .between { justify-content: space-between; } .wrap { flex-wrap: wrap; }
        .mb { margin-bottom: 15px; } .mt { margin-top: 15px; }
        .thumb { width: 42px; height: 42px; border-radius: 10px; object-fit: contain;
            background: #f4f5f8; border: 1px solid var(--line); }
        .empty { text-align: center; padding: 45px 20px; color: var(--muted); font-size: 12.5px; }
        .avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--brand-soft); color: var(--brand);
            display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 12.5px; flex-shrink: 0; }
        .bar-track { height: 7px; border-radius: 99px; background: #eef0f5; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--brand); border-radius: 99px; }

        /* اعلان‌ها */
        .bell { position: relative; background: none; border: 0; cursor: pointer; padding: 7px;
            border-radius: 10px; color: var(--ink); }
        .bell:hover { background: var(--bg); }
        .bell-dot { position: absolute; top: 3px; left: 3px; min-width: 16px; height: 16px; border-radius: 99px;
            background: var(--red); color: #fff; font-size: 9.5px; font-weight: 800;
            display: none; align-items: center; justify-content: center; padding: 0 4px; }
        .notif-panel { position: absolute; top: 52px; left: 14px; width: 330px; max-height: 420px; overflow-y: auto;
            background: #fff; border: 1px solid var(--line); border-radius: 14px;
            box-shadow: 0 16px 40px rgba(0,0,0,.14); display: none; z-index: 100; }
        .notif-panel.open { display: block; }
        .notif-item { display: flex; gap: 10px; padding: 11px 13px; border-bottom: 1px solid var(--line); }
        .notif-item:hover { background: #fafbfd; }
        .notif-ic { width: 32px; height: 32px; border-radius: 10px; background: var(--brand-soft);
            color: var(--brand); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

        /* جستجوی سراسری */
        .gsearch { position: relative; width: 230px; }
        .gsearch input { padding: 7px 11px; font-size: 12px; border-radius: 10px; }
        .gsearch-results { position: absolute; top: 40px; right: 0; width: 340px; background: #fff;
            border: 1px solid var(--line); border-radius: 13px; box-shadow: 0 16px 40px rgba(0,0,0,.14);
            max-height: 380px; overflow-y: auto; display: none; z-index: 100; }
        .gsearch-results.open { display: block; }
        .gs-item { padding: 9px 13px; border-bottom: 1px solid var(--line); display: block; }
        .gs-item:hover { background: #fafbfd; }
        .gs-group { font-size: 9.5px; color: var(--brand); font-weight: 800; }

        /* تقویم شمسی */
        .jdp { position: absolute; background: #fff; border: 1px solid var(--line); border-radius: 14px;
            box-shadow: 0 16px 40px rgba(0,0,0,.16); padding: 12px; z-index: 200; width: 262px; display: none; }
        .jdp.open { display: block; }
        .jdp-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 9px; }
        .jdp-head button { background: none; border: 0; cursor: pointer; font-size: 15px;
            color: var(--brand); padding: 3px 8px; border-radius: 7px; font-family: inherit; }
        .jdp-head button:hover { background: var(--brand-soft); }
        .jdp-title { font-size: 12.5px; font-weight: 800; }
        .jdp-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
        .jdp-dow { font-size: 9.5px; color: var(--muted); text-align: center; padding: 3px 0; font-weight: 700; }
        .jdp-day { text-align: center; padding: 6px 0; font-size: 11.5px; border-radius: 8px;
            cursor: pointer; border: 1px solid transparent; }
        .jdp-day:hover { background: var(--brand-soft); }
        .jdp-day.today { border-color: var(--brand); font-weight: 800; }
        .jdp-day.sel { background: var(--brand); color: #fff; font-weight: 800; }
        .jdp-day.empty { visibility: hidden; cursor: default; }
        .jdp-foot { display: flex; gap: 6px; margin-top: 9px; }
        .jdp-foot button { flex: 1; font-size: 11px; padding: 6px; border-radius: 9px;
            border: 1px solid var(--line); background: #fff; cursor: pointer; font-family: inherit; }

        /* آپلود کشیدنی */
        .dropzone { border: 2px dashed var(--line); border-radius: 14px; padding: 26px 18px; text-align: center;
            cursor: pointer; transition: .18s; background: #fafbfd; }
        .dropzone:hover, .dropzone.drag { border-color: var(--brand); background: var(--brand-soft); }
        .img-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(96px, 1fr)); gap: 9px; }
        .img-cell { position: relative; border: 1px solid var(--line); border-radius: 11px;
            overflow: hidden; background: #f4f5f8; aspect-ratio: 1; }
        .img-cell img { width: 100%; height: 100%; object-fit: cover; }
        .img-cell .ops { position: absolute; inset: auto 0 0 0; background: rgba(0,0,0,.62);
            display: flex; gap: 3px; padding: 4px; justify-content: center; }
        .img-cell .ops button { background: none; border: 0; color: #fff; cursor: pointer;
            font-size: 10px; padding: 2px 5px; border-radius: 5px; font-family: inherit; }
        .img-cell .ops button:hover { background: rgba(255,255,255,.22); }
        .img-cell .star { position: absolute; top: 5px; right: 5px; background: var(--amber);
            color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 6px; font-weight: 800; }

        .row-repeat { display: grid; gap: 7px; margin-bottom: 8px; align-items: start; }
        .tabs { display: flex; gap: 5px; border-bottom: 1px solid var(--line); margin-bottom: 15px; flex-wrap: wrap; }
        .tab { padding: 9px 15px; font-size: 12.5px; font-weight: 700; cursor: pointer;
            border-bottom: 2px solid transparent; color: var(--muted); }
        .tab.active { color: var(--brand); border-bottom-color: var(--brand); }
        .tab-panel { display: none; } .tab-panel.active { display: block; }

        .sidebar-toggle { display: none; background: none; border: 0; cursor: pointer; }
        @media (max-width: 900px) {
            .sidebar { position: fixed; right: 0; top: 0; z-index: 60; transform: translateX(100%); transition: .25s; }
            .sidebar.open { transform: translateX(0); box-shadow: -10px 0 40px rgba(0,0,0,.3); }
            .sidebar-toggle { display: inline-flex; }
            .content { padding: 13px; } .topbar { padding: 10px 13px; }
            .gsearch { display: none; }
        }
        @media print { .sidebar, .topbar, .no-print { display: none !important; } .content { padding: 0; } }
    </style>
    <script src="/assets/js/vendor/lucide-0.468.0.min.js" defer></script>
</head>

<body data-csrf="<?= e($csrf) ?>">
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-logo">پ</div>
                <div>
                    <b><?= e($siteTitle) ?></b>
                    <span>ADMIN PANEL</span>
                </div>
            </div>

            <div class="role-chip">
                <?= e($admin['role_name'] ?? 'مدیر کل') ?>
            </div>

            <?php foreach ($menu as $group):
                $visible = array_filter($group['items'], fn($it) => can($it['perm']));
                if (!$visible) continue; ?>
                <div class="nav-group"><?= e($group['group']) ?></div>
                <?php foreach ($visible as $item): ?>
                    <a href="<?= e($item['url']) ?>" class="nav-item <?= $section === $item['key'] ? 'active' : '' ?>">
                        <span class="nav-left">
                            <i data-lucide="<?= e($item['icon']) ?>" class="ic"></i>
                            <?= e($item['label']) ?>
                        </span>
                        <span class="nav-badge <?= e($item['badgeClass'] ?? '') ?>"
                              <?= !empty($item['id']) ? 'id="' . e($item['id']) . '"' : '' ?>
                              data-count="<?= (int) ($item['badge'] ?? 0) ?>"
                              <?= empty($item['badge']) ? 'style="display:none"' : '' ?>><?= (int) ($item['badge'] ?? 0) ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="nav-group">حساب</div>
            <a href="/" target="_blank" rel="noopener" class="nav-item">
                <span class="nav-left"><i data-lucide="external-link" class="ic"></i> مشاهده سایت</span>
            </a>
            <form method="POST" action="<?= admin_url('logout') ?>" style="margin:0">
                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                <button type="submit" class="nav-item" style="width:100%;background:none;border:0;
                        cursor:pointer;color:#f87171;font-family:inherit;text-align:right">
                    <span class="nav-left"><i data-lucide="log-out" class="ic"></i> خروج از پنل</span>
                </button>
            </form>
        </aside>

        <div class="main">
            <header class="topbar">
                <div class="flex items-center" style="gap:12px">
                    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="منو">
                        <i data-lucide="menu"></i>
                    </button>
                    <div>
                        <h1><?= e($pageTitle ?? 'پیشخوان') ?></h1>
                        <?php if (!empty($pageSub)): ?><div class="sub"><?= e($pageSub) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center" style="gap:10px">
                    <div class="gsearch no-print">
                        <input type="search" id="gsearch" placeholder="جستجوی سریع… (Ctrl+K)" autocomplete="off">
                        <div class="gsearch-results" id="gsearch-results"></div>
                    </div>

                    <button class="bell no-print" id="bell" aria-label="اعلان‌ها">
                        <i data-lucide="bell"></i>
                        <span class="bell-dot" id="bell-dot">0</span>
                    </button>

                    <div style="text-align:left">
                        <div style="font-size:12px;font-weight:800"><?= e($admin['full_name'] ?? 'مدیر') ?></div>
                        <div class="sub mono"><?= e($admin['phone'] ?? '') ?></div>
                    </div>
                    <div class="avatar"><?= e(mb_substr($admin['full_name'] ?? 'م', 0, 1, 'UTF-8')) ?></div>
                </div>

                <div class="notif-panel" id="notif-panel">
                    <div style="padding:11px 13px;border-bottom:1px solid var(--line);font-weight:800;font-size:12.5px">
                        اعلان‌های جدید
                    </div>
                    <div id="notif-list">
                        <div class="empty" style="padding:26px">اعلان جدیدی وجود ندارد.</div>
                    </div>
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
    const CSRF = document.body.dataset.csrf;
    const POLL_INTERVAL = <?= (int) $pollInterval ?> * 1000;
    const SOUND_ON = <?= $soundOn ? 'true' : 'false' ?>;

    window.addEventListener('load', () => { if (window.lucide) lucide.createIcons(); });
    function refreshIcons() { if (window.lucide) lucide.createIcons(); }

    // ===================== تقویم شمسی =====================
    const JMONTHS = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    const JDOW = ['ش','ی','د','س','چ','پ','ج'];

    function gregorianToJalali(gy, gm, gd) {
        const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        const gy2 = (gm > 2) ? (gy + 1) : gy;
        let days = 355666 + (365 * gy) + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100)
            + Math.floor((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
        let jy = -1595 + 33 * Math.floor(days / 12053);
        days %= 12053;
        jy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        const jm = (days < 186) ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
        const jd = 1 + ((days < 186) ? (days % 31) : ((days - 186) % 30));
        return [jy, jm, jd];
    }
    function jalaliToGregorian(jy, jm, jd) {
        jy += 1595;
        let days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4)
            + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        let gy = 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        let gd = days + 1;
        const leap = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0));
        const sal_a = [0,31,leap?29:28,31,30,31,30,31,31,30,31,30,31];
        let gm = 0;
        while (gm < 13 && gd > sal_a[gm]) { gd -= sal_a[gm]; gm++; }
        return [gy, gm, gd];
    }
    function jDaysInMonth(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        const [gy] = jalaliToGregorian(jy, 12, 1);
        const leap = ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0));
        return leap ? 30 : 29;
    }
    function pad(n) { return String(n).padStart(2, '0'); }

    let jdpEl = null, jdpInput = null, jdpY = 0, jdpM = 0;

    function buildDatepicker() {
        if (jdpEl) return jdpEl;
        jdpEl = document.createElement('div');
        jdpEl.className = 'jdp';
        document.body.appendChild(jdpEl);
        return jdpEl;
    }
    function renderDatepicker() {
        const today = new Date();
        const [ty, tm, td] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
        const sel = (jdpInput.value || '').split('/').map(Number);
        const [gy, gm, gd] = jalaliToGregorian(jdpY, jdpM, 1);
        const firstDow = (new Date(gy, gm - 1, gd).getDay() + 1) % 7;
        const total = jDaysInMonth(jdpY, jdpM);

        let html = `<div class="jdp-head">
            <button type="button" data-nav="-1">‹</button>
            <span class="jdp-title">${JMONTHS[jdpM - 1]} ${jdpY}</span>
            <button type="button" data-nav="1">›</button></div><div class="jdp-grid">`;
        JDOW.forEach(d => html += `<div class="jdp-dow">${d}</div>`);
        for (let i = 0; i < firstDow; i++) html += '<div class="jdp-day empty"></div>';
        for (let d = 1; d <= total; d++) {
            const isToday = (jdpY === ty && jdpM === tm && d === td);
            const isSel = (sel[0] === jdpY && sel[1] === jdpM && sel[2] === d);
            html += `<div class="jdp-day ${isToday ? 'today' : ''} ${isSel ? 'sel' : ''}" data-day="${d}">${d}</div>`;
        }
        html += `</div><div class="jdp-foot">
            <button type="button" data-act="today">امروز</button>
            <button type="button" data-act="clear">پاک کردن</button></div>`;
        jdpEl.innerHTML = html;
    }
    function openDatepicker(input) {
        jdpInput = input;
        buildDatepicker();
        const parts = (input.value || '').split('/').map(Number);
        const today = new Date();
        const [ty, tm] = gregorianToJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
        jdpY = parts[0] || ty;
        jdpM = parts[1] || tm;
        renderDatepicker();
        const r = input.getBoundingClientRect();
        jdpEl.style.top = (r.bottom + window.scrollY + 5) + 'px';
        jdpEl.style.right = (document.documentElement.clientWidth - r.right - window.scrollX) + 'px';
        jdpEl.classList.add('open');
    }
    function closeDatepicker() { if (jdpEl) jdpEl.classList.remove('open'); }

    document.addEventListener('focusin', e => {
        if (e.target.matches('input[data-jdp]')) openDatepicker(e.target);
    });
    document.addEventListener('click', e => {
        if (e.target.matches('input[data-jdp]')) { openDatepicker(e.target); return; }
        if (jdpEl && jdpEl.contains(e.target)) {
            const nav = e.target.dataset.nav;
            if (nav) {
                jdpM += parseInt(nav, 10);
                if (jdpM < 1) { jdpM = 12; jdpY--; }
                if (jdpM > 12) { jdpM = 1; jdpY++; }
                renderDatepicker();
                return;
            }
            const act = e.target.dataset.act;
            if (act === 'today') {
                const t = new Date();
                const [y, m, d] = gregorianToJalali(t.getFullYear(), t.getMonth() + 1, t.getDate());
                jdpInput.value = `${y}/${pad(m)}/${pad(d)}`;
                jdpInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeDatepicker();
                return;
            }
            if (act === 'clear') {
                jdpInput.value = '';
                jdpInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeDatepicker();
                return;
            }
            const day = e.target.dataset.day;
            if (day) {
                jdpInput.value = `${jdpY}/${pad(jdpM)}/${pad(day)}`;
                jdpInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeDatepicker();
            }
            return;
        }
        closeDatepicker();
    });

    // تبدیل ارقام فارسی به انگلیسی هنگام تایپ
    document.addEventListener('input', e => {
        if (e.target.matches('input[data-jdp], .mono, input[type=tel]')) {
            const fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
            e.target.value = e.target.value.replace(/[۰-۹٠-٩]/g, c => {
                const i = fa.indexOf(c);
                return i > -1 ? i : ar.indexOf(c);
            });
        }
    });

    // ===================== اعلان لحظه‌ای =====================
    const lastSeen = { order: 0, ticket: 0, wallet: 0, comment: 0 };
    let notifications = [];

    function beep() {
        if (!SOUND_ON) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.18].forEach((delay, i) => {
                const osc = ctx.createOscillator(), gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = i === 0 ? 880 : 1180;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.0001, ctx.currentTime + delay);
                gain.gain.exponentialRampToValueAtTime(0.14, ctx.currentTime + delay + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + delay + 0.16);
                osc.start(ctx.currentTime + delay);
                osc.stop(ctx.currentTime + delay + 0.18);
            });
        } catch (err) {}
    }

    function setBadge(id, count) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = count;
        el.dataset.count = count;
        el.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    function renderNotifications() {
        const list = document.getElementById('notif-list');
        const dot = document.getElementById('bell-dot');
        if (!notifications.length) {
            list.innerHTML = '<div class="empty" style="padding:26px">اعلان جدیدی وجود ندارد.</div>';
            dot.style.display = 'none';
            return;
        }
        dot.textContent = notifications.length;
        dot.style.display = 'flex';
        list.innerHTML = notifications.slice(0, 12).map(n => `
            <a class="notif-item" href="${n.url}">
                <div class="notif-ic"><i data-lucide="${n.icon}" style="width:16px;height:16px"></i></div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:12px;font-weight:800">${n.title}</div>
                    <div style="font-size:11px;color:var(--muted)">${n.body}</div>
                </div>
            </a>`).join('');
        refreshIcons();
    }

    async function poll() {
        try {
            const url = `<?= admin_url('api/poll') ?>?last_order=${lastSeen.order}&last_ticket=${lastSeen.ticket}`
                + `&last_wallet=${lastSeen.wallet}&last_comment=${lastSeen.comment}`;
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.success) return;

            if (data.counts) {
                if ('orders' in data.counts) setBadge('badge-orders', data.counts.orders);
                if ('tickets' in data.counts) setBadge('badge-tickets', data.counts.tickets);
                if ('wallet' in data.counts) setBadge('badge-wallet', data.counts.wallet);
                if ('comments' in data.counts) setBadge('badge-comments', data.counts.comments);
            }

            const firstRun = lastSeen.order === 0 && lastSeen.ticket === 0;
            if (data.latest) {
                if (data.latest.order !== undefined) lastSeen.order = data.latest.order;
                if (data.latest.ticket !== undefined) lastSeen.ticket = data.latest.ticket;
                if (data.latest.wallet !== undefined) lastSeen.wallet = data.latest.wallet;
                if (data.latest.comment !== undefined) lastSeen.comment = data.latest.comment;
            }

            if (!firstRun && data.new && data.new.length) {
                notifications = data.new.concat(notifications).slice(0, 20);
                renderNotifications();
                beep();
                if (Notification && Notification.permission === 'granted') {
                    data.new.slice(0, 2).forEach(n => new Notification(n.title, { body: n.body, icon: '/assets/logo/logo.webp' }));
                }
                document.title = `(${notifications.length}) <?= e($pageTitle ?? 'پنل مدیریت') ?>`;
            }
        } catch (err) {}
    }

    document.getElementById('bell')?.addEventListener('click', () => {
        document.getElementById('notif-panel').classList.toggle('open');
        if (Notification && Notification.permission === 'default') Notification.requestPermission();
    });
    document.addEventListener('click', e => {
        const p = document.getElementById('notif-panel');
        if (p && !p.contains(e.target) && !e.target.closest('#bell')) p.classList.remove('open');
    });

    poll();
    setInterval(poll, POLL_INTERVAL);

    // ===================== جستجوی سراسری =====================
    let searchTimer = null;
    const gInput = document.getElementById('gsearch');
    const gResults = document.getElementById('gsearch-results');

    gInput?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = gInput.value.trim();
        if (q.length < 2) { gResults.classList.remove('open'); return; }
        searchTimer = setTimeout(async () => {
            try {
                const res = await fetch(`<?= admin_url('api/search') ?>?q=${encodeURIComponent(q)}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (!data.results || !data.results.length) {
                    gResults.innerHTML = '<div class="empty" style="padding:20px">نتیجه‌ای یافت نشد.</div>';
                } else {
                    gResults.innerHTML = data.results.map(r => `
                        <a class="gs-item" href="${r.url}">
                            <div class="gs-group">${r.group}</div>
                            <div style="font-size:12.5px;font-weight:700">${r.title}</div>
                            <div style="font-size:11px;color:var(--muted)">${r.sub}</div>
                        </a>`).join('');
                }
                gResults.classList.add('open');
            } catch (err) {}
        }, 280);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('.gsearch')) gResults?.classList.remove('open');
    });
    document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); gInput?.focus(); }
        if (e.key === 'Escape') { gResults?.classList.remove('open'); closeDatepicker(); }
    });

    // ===================== ابزارهای عمومی =====================
    function confirmDelete(msg) { return confirm(msg || 'آیا از حذف این مورد مطمئن هستید؟ این عمل بازگشت‌پذیر نیست.'); }

    function switchTab(el, id) {
        const root = el.closest('.card') || document;
        root.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        root.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        document.getElementById(id)?.classList.add('active');
    }

    // جلوگیری از ارسال دوباره فرم
    document.addEventListener('submit', e => {
        const btn = e.target.querySelector('button[type=submit]:not([data-nolock])');
        if (btn && !e.defaultPrevented) {
            setTimeout(() => { btn.disabled = true; btn.style.opacity = '.6'; }, 30);
            setTimeout(() => { btn.disabled = false; btn.style.opacity = ''; }, 8000);
        }
    });
    </script>
    <?= $scripts ?? '' ?>
</body>

</html>
