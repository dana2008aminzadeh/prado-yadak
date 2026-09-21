<?php use Admin\core\Auth; ?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('users') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="نام، موبایل، ایمیل، کد ملی"></div>
            <div class="f"><label class="fl">نقش</label>
                <select name="role">
                    <option value="">همه</option>
                    <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>مدیر</option>
                    <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>کاربر عادی</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('users') ?>">حذف فیلتر</a>
            <a class="btn" href="<?= admin_url('users/export') ?>"><i data-lucide="download" style="width:14px"></i> CSV</a>
            <button class="btn btn-primary" type="button" onclick="document.getElementById('newUser').style.display='block'">+ کاربر جدید</button>
        </form>
    </div>
</div>

<div class="card mb" id="newUser" style="display:none">
    <div class="card-head"><h3>ایجاد کاربر جدید</h3>
        <button class="btn btn-sm" onclick="document.getElementById('newUser').style.display='none'">بستن</button></div>
    <form method="POST" action="<?= admin_url('users/create') ?>" class="card-body">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <div class="grid g4">
            <div class="field"><label class="fl">نام و نام خانوادگی</label><input type="text" name="full_name"></div>
            <div class="field"><label class="fl">موبایل *</label><input type="tel" name="phone" class="mono" required></div>
            <div class="field"><label class="fl">رمز عبور *</label><input type="text" name="password" class="mono" required></div>
            <div class="field"><label class="fl">نقش</label>
                <select name="role"><option value="user">کاربر عادی</option><option value="admin">مدیر</option></select></div>
        </div>
        <button class="btn btn-primary" type="submit">ایجاد کاربر</button>
    </form>
</div>

<div class="card">
    <div class="card-head"><h3>کاربران (<?= money($pg['total']) ?>)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>کاربر</th><th>ایمیل</th><th>سفارش</th><th>مجموع خرید</th><th>کیف پول</th><th>نقش</th><th>عضویت</th><th></th></tr></thead>
            <tbody>
                <?php if (!$users): ?><tr><td colspan="8" class="empty">کاربری یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="flex items-center gap">
                                <div class="avatar"><?= e(mb_substr($u['full_name'] ?: 'ک', 0, 1, 'UTF-8')) ?></div>
                                <div>
                                    <div style="font-weight:700"><?= e($u['full_name'] ?: 'بدون نام') ?></div>
                                    <div class="hint mono"><?= e($u['phone']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="hint"><?= e($u['email'] ?: '—') ?></td>
                        <td><?= (int) $u['orders_count'] ?></td>
                        <td style="font-weight:700"><?= money($u['spent']) ?></td>
                        <td><?= money($u['wallet_balance']) ?></td>
                        <td><span class="badge <?= $u['role'] === 'admin' ? 'b-red' : 'b-gray' ?>"><?= $u['role'] === 'admin' ? 'مدیر' : 'کاربر' ?></span></td>
                        <td class="hint"><?= e(toShamsi($u['created_at'])) ?></td>
                        <td class="text-left"><a class="btn btn-sm btn-primary" href="<?= admin_url('users/show/' . $u['id']) ?>">پروفایل</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
