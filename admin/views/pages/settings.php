<?php use Admin\core\Auth; $canEdit = can('settings.edit'); ?>

<form method="POST" action="<?= admin_url('settings/save') ?>">
    <?= Auth::csrfField() ?>

    <div class="grid g2" style="align-items:start">
        <?php foreach ($groups as $groupName => $fields): ?>
            <div class="card">
                <div class="card-head"><h3><?= e($groupName) ?></h3></div>
                <div class="card-body">
                    <?php foreach ($fields as $key => [$label, $type]):
                        $val = $values[$key] ?? ''; ?>
                        <?php if ($type === 'bool'): ?>
                            <label class="chk">
                                <input type="checkbox" name="settings[<?= e($key) ?>]" value="1"
                                       <?= in_array(strtolower((string) $val), ['1','true','yes','on'], true) ? 'checked' : '' ?>
                                       <?= $canEdit ? '' : 'disabled' ?>>
                                <span><?= e($label) ?></span>
                            </label>
                        <?php else: ?>
                            <div class="field">
                                <label class="fl"><?= e($label) ?>
                                    <span class="hint mono" style="font-size:9.5px">(<?= e($key) ?>)</span></label>
                                <?php if ($type === 'textarea'): ?>
                                    <textarea name="settings[<?= e($key) ?>]" rows="2" <?= $canEdit ? '' : 'disabled' ?>><?= e($val) ?></textarea>
                                <?php elseif ($type === 'secret'): ?>
                                    <input type="password" name="settings[<?= e($key) ?>]" class="mono" value="<?= e($val) ?>"
                                           autocomplete="new-password" onfocus="this.type='text'" onblur="this.type='password'"
                                           <?= $canEdit ? '' : 'disabled' ?>>
                                    <div class="hint">برای دیدن مقدار، روی فیلد کلیک کنید.</div>
                                <?php else: ?>
                                    <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($val) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                                <?php endif; ?>
                                <?php if (!empty($descs[$key])): ?><div class="hint"><?= e($descs[$key]) ?></div><?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($extra): ?>
            <div class="card">
                <div class="card-head"><h3>سایر تنظیمات</h3></div>
                <div class="card-body">
                    <?php foreach ($extra as $key): ?>
                        <div class="field">
                            <label class="fl mono" style="font-size:11px"><?= e($key) ?></label>
                            <div class="flex gap">
                                <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($values[$key]) ?>" <?= $canEdit ? '' : 'disabled' ?>>
                            </div>
                            <?php if (!empty($descs[$key])): ?><div class="hint"><?= e($descs[$key]) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($canEdit): ?>
        <div class="card mt">
            <div class="card-body text-left">
                <button class="btn btn-primary" type="submit"><i data-lucide="save" style="width:15px"></i> ذخیره همه تنظیمات</button>
            </div>
        </div>
    <?php endif; ?>
</form>

<?php if ($canEdit): ?>
    <div class="grid g2 mt" style="align-items:start">
        <form class="card" method="POST" action="<?= admin_url('settings/add') ?>">
            <?= Auth::csrfField() ?>
            <div class="card-head"><h3>افزودن تنظیم سفارشی</h3></div>
            <div class="card-body">
                <div class="grid g3">
                    <div class="field"><label class="fl">کلید *</label>
                        <input type="text" name="setting_key" class="mono" placeholder="my_custom_key" required></div>
                    <div class="field"><label class="fl">مقدار</label><input type="text" name="setting_value"></div>
                    <div class="field"><label class="fl">توضیح</label><input type="text" name="description"></div>
                </div>
                <button class="btn btn-primary" type="submit">افزودن</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>حذف تنظیم</h3></div>
            <div class="card-body">
                <div class="hint mb">تنظیمات سفارشی را می‌توانید حذف کنید. تنظیمات اصلی سیستم را حذف نکنید.</div>
                <?php foreach ($extra as $key): ?>
                    <div class="flex between items-center" style="padding:5px 0;border-bottom:1px solid var(--line)">
                        <span class="mono" style="font-size:11px"><?= e($key) ?></span>
                        <?= action_button(admin_url('settings/delete'), 'حذف', [
                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف تنظیم «' . $key . '»؟',
                            'fields' => ['setting_id' => $ids[$key] ?? 0]]) ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$extra): ?><div class="empty">تنظیم سفارشی وجود ندارد.</div><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
