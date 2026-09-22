<?php
use Admin\core\Auth;
use Admin\core\Permission;

$r = $editing ?: ['id' => 0, 'name' => '', 'slug' => '', 'permissions' => '', 'is_system' => 0];
$selected = array_filter(array_map('trim', explode(',', (string) $r['permissions'])));
$isSuper = ($r['slug'] ?? '') === 'super_admin';
?>

<div class="tabs">
    <div class="tab active" onclick="switchTab(this,'tab-roles')">نقش‌ها و دسترسی‌ها</div>
    <div class="tab" onclick="switchTab(this,'tab-admins')">حساب‌های مدیریتی</div>
</div>

<!-- نقش‌ها -->
<div class="tab-panel active" id="tab-roles">
    <div class="grid g2" style="grid-template-columns:1.5fr 1fr;align-items:start">
        <form class="card" method="POST" action="<?= admin_url('roles/save') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <div class="card-head">
                <h3><?= $r['id'] ? 'ویرایش نقش: ' . e($r['name']) : 'تعریف نقش جدید' ?></h3>
                <?php if ($r['id']): ?><a class="btn btn-sm" href="<?= admin_url('roles') ?>">نقش جدید</a><?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($isSuper): ?>
                    <div class="flash info">نقش «مدیر کل» همیشه دسترسی کامل دارد و قابل ویرایش نیست.</div>
                <?php endif; ?>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">نام نقش *</label>
                        <input type="text" name="name" value="<?= e($r['name']) ?>" placeholder="مثلاً: انباردار ارشد"
                               required <?= $isSuper ? 'disabled' : '' ?>>
                    </div>
                    <div class="field">
                        <label class="fl">کلید انگلیسی</label>
                        <input type="text" name="slug" class="mono" value="<?= e($r['slug']) ?>"
                               placeholder="warehouse_lead" <?= $r['id'] ? 'readonly' : '' ?>>
                    </div>
                </div>

                <div class="flex gap mb wrap">
                    <button type="button" class="btn btn-sm" onclick="togglePerms(true)">انتخاب همه</button>
                    <button type="button" class="btn btn-sm" onclick="togglePerms(false)">لغو همه</button>
                    <span class="hint" id="permCount"></span>
                </div>

                <?php foreach ($tree as $groupName => $perms): ?>
                    <div style="border:1px solid var(--line);border-radius:12px;padding:11px;margin-bottom:10px">
                        <div class="flex between items-center" style="margin-bottom:7px">
                            <b style="font-size:12.5px"><?= e($groupName) ?></b>
                            <button type="button" class="btn btn-sm" onclick="toggleGroup(this)">انتخاب گروه</button>
                        </div>
                        <div class="grid g2" style="gap:4px">
                            <?php foreach ($perms as $key => $label): ?>
                                <label class="chk" style="margin:0;font-weight:500;font-size:11.5px">
                                    <input type="checkbox" name="permissions[]" value="<?= e($key) ?>"
                                           class="perm-chk" onchange="updateCount()"
                                           <?= Permission::granted($selected, $key) ? 'checked' : '' ?>
                                           <?= $isSuper ? 'disabled' : '' ?>>
                                    <span><?= e($label) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <button class="btn btn-primary btn-block" type="submit" <?= $isSuper ? 'disabled' : '' ?>>
                    <i data-lucide="save" style="width:15px"></i> ذخیره نقش
                </button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>نقش‌های تعریف‌شده</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>نقش</th><th>دسترسی</th><th>مدیران</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($roles as $role):
                            $perms = $role['permissions'] === '*' ? ['*']
                                : array_filter(array_map('trim', explode(',', (string) $role['permissions']))); ?>
                            <tr>
                                <td>
                                    <b><?= e($role['name']) ?></b>
                                    <?php if ((int) $role['is_system'] === 1): ?>
                                        <span class="badge b-gray" style="font-size:9px">پیش‌فرض</span>
                                    <?php endif; ?>
                                    <div class="hint mono" style="font-size:10px"><?= e($role['slug']) ?></div>
                                </td>
                                <td>
                                    <?php if ($role['permissions'] === '*'): ?>
                                        <span class="badge b-red">دسترسی کامل</span>
                                    <?php else: ?>
                                        <span class="badge b-blue"><?= count($perms) ?> مورد</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $role['admins_count'] ?></td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('roles', ['edit' => $role['id']]) ?>">ویرایش</a>
                                        <?php if ((int) $role['is_system'] !== 1): ?>
                                            <?= action_button(admin_url('roles/delete'), 'حذف', [
                                                'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این نقش؟',
                                                'fields' => ['role_id' => $role['id']],
                                            ]) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body" style="border-top:1px solid var(--line)">
                <div class="hint">
                    <b>نقش‌های پیش‌فرض:</b><br>
                    • <b>مدیر کل</b> — دسترسی کامل به همه بخش‌ها<br>
                    • <b>انباردار</b> — محصولات، موجودی و مشاهده سفارش‌ها<br>
                    • <b>پشتیبان</b> — تیکت‌ها، دیدگاه‌ها و مشاهده سفارش/کاربر<br>
                    • <b>حسابدار</b> — سفارش‌ها، کیف پول، فاکتور و گزارش‌ها<br>
                    • <b>مدیر محتوا</b> — مقالات، اطلاعیه‌ها و دیدگاه‌ها
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مدیران -->
<div class="tab-panel" id="tab-admins">
    <div class="card">
        <div class="card-head"><h3>حساب‌های مدیریتی (<?= count($admins) ?>)</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>مدیر</th><th>نقش فعلی</th><th>وضعیت</th><th>تخصیص نقش</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($admins as $a):
                        $isMe = (int) $a['id'] === (int) $meId; ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap">
                                    <div class="avatar"><?= e(mb_substr($a['full_name'] ?: 'م', 0, 1, 'UTF-8')) ?></div>
                                    <div>
                                        <div style="font-weight:700"><?= e($a['full_name'] ?: 'بدون نام') ?>
                                            <?php if ($isMe): ?><span class="badge b-blue" style="font-size:9px">شما</span><?php endif; ?>
                                        </div>
                                        <div class="hint mono"><?= e($a['phone']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $a['role_slug'] === 'super_admin' ? 'b-red' : 'b-blue' ?>">
                                    <?= e($a['role_name'] ?? 'بدون نقش (دسترسی کامل)') ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= (int) $a['is_active'] === 1 ? 'b-green' : 'b-gray' ?>">
                                    <?= (int) $a['is_active'] === 1 ? 'فعال' : 'غیرفعال' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="<?= admin_url('roles/assign') ?>" class="flex gap">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= (int) $a['id'] ?>">
                                    <select name="role_id" style="width:auto">
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?= (int) $role['id'] ?>" <?= (int) $a['admin_role_id'] === (int) $role['id'] ? 'selected' : '' ?>>
                                                <?= e($role['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="submit">اعمال</button>
                                </form>
                            </td>
                            <td class="text-left">
                                <?php if (!$isMe): ?>
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <?= action_button(admin_url('roles/toggleUser'),
                                            (int) $a['is_active'] === 1 ? 'غیرفعال' : 'فعال', [
                                            'class' => 'btn btn-sm',
                                            'fields' => ['user_id' => $a['id']],
                                        ]) ?>
                                        <?= action_button(admin_url('roles/revoke'), 'سلب دسترسی', [
                                            'class' => 'btn btn-sm btn-danger',
                                            'confirm' => 'دسترسی مدیریتی این کاربر حذف شود؟',
                                            'fields' => ['user_id' => $a['id']],
                                        ]) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-body" style="border-top:1px solid var(--line)">
            <div class="hint">
                برای ارتقای یک کاربر عادی به مدیر، به
                <a href="<?= admin_url('users') ?>" style="text-decoration:underline">صفحه کاربران</a>
                بروید، پروفایل او را باز کنید و نقش را به «مدیر» تغییر دهید.
            </div>
        </div>
    </div>
</div>

<script>
function togglePerms(state) {
    document.querySelectorAll('.perm-chk:not(:disabled)').forEach(c => c.checked = state);
    updateCount();
}
function toggleGroup(btn) {
    const box = btn.closest('div').parentElement;
    const chks = box.querySelectorAll('.perm-chk:not(:disabled)');
    const allChecked = Array.from(chks).every(c => c.checked);
    chks.forEach(c => c.checked = !allChecked);
    updateCount();
}
function updateCount() {
    const n = document.querySelectorAll('.perm-chk:checked').length;
    document.getElementById('permCount').textContent = `${n} دسترسی انتخاب شده`;
}
updateCount();
</script>
