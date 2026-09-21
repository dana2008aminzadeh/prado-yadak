<?php use Admin\core\Auth; ?>

<form method="POST" action="<?= admin_url('settings/save') ?>">
    <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">

    <div class="grid g2" style="align-items:start">
        <?php foreach ($groups as $groupName => $fields): ?>
            <div class="card">
                <div class="card-head"><h3><?= e($groupName) ?></h3></div>
                <div class="card-body">
                    <?php foreach ($fields as $key => [$label, $type]):
                        $val = $values[$key] ?? ''; ?>
                        <div class="field">
                            <label class="fl"><?= e($label) ?> <span class="hint mono">(<?= e($key) ?>)</span></label>
                            <?php if ($type === 'textarea'): ?>
                                <textarea name="settings[<?= e($key) ?>]" rows="2"><?= e($val) ?></textarea>
                            <?php elseif ($type === 'password'): ?>
                                <input type="text" name="settings[<?= e($key) ?>]" class="mono" value="<?= e($val) ?>" autocomplete="off">
                            <?php else: ?>
                                <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($val) ?>">
                            <?php endif; ?>
                            <?php if (!empty($descs[$key])): ?><div class="hint"><?= e($descs[$key]) ?></div><?php endif; ?>
                        </div>
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
                            <label class="fl mono"><?= e($key) ?></label>
                            <input type="text" name="settings[<?= e($key) ?>]" value="<?= e($values[$key]) ?>">
                            <?php if (!empty($descs[$key])): ?><div class="hint"><?= e($descs[$key]) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card mt">
        <div class="card-body flex gap" style="justify-content:flex-end">
            <button class="btn btn-primary" type="submit"><i data-lucide="save" style="width:15px"></i> ذخیره همه تنظیمات</button>
        </div>
    </div>
</form>

<form class="card mt" method="POST" action="<?= admin_url('settings/add') ?>">
    <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
    <div class="card-head"><h3>افزودن تنظیم سفارشی</h3></div>
    <div class="card-body">
        <div class="grid g3">
            <div class="field"><label class="fl">کلید *</label><input type="text" name="setting_key" class="mono" placeholder="my_custom_key" required></div>
            <div class="field"><label class="fl">مقدار</label><input type="text" name="setting_value"></div>
            <div class="field"><label class="fl">توضیح</label><input type="text" name="description"></div>
        </div>
        <button class="btn btn-primary" type="submit">افزودن</button>
    </div>
</form>
