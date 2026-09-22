<?php
use Admin\core\Auth;

$c = $editing ?: ['id' => 0, 'code' => '', 'type' => 'percent', 'value' => 10, 'min_order' => 0,
    'max_discount' => '', 'usage_limit' => 100, 'per_user_limit' => 1, 'starts_at' => '', 'expires_at' => '',
    'is_active' => 1, 'category_id' => null, 'car_model_id' => null, 'first_order_only' => 0];
$canEdit = can('coupons.edit');
?>

<div class="grid g2" style="grid-template-columns:1fr 1.7fr;align-items:start">
    <?php if ($canEdit): ?>
        <form class="card" method="POST" action="<?= admin_url('coupons/save') ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <div class="card-head">
                <h3><?= $c['id'] ? 'ویرایش کد ' . e($c['code']) : 'ایجاد کد تخفیف' ?></h3>
                <?php if ($c['id']): ?><a class="btn btn-sm" href="<?= admin_url('coupons') ?>">کد جدید</a><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="field">
                    <label class="fl">کد تخفیف *</label>
                    <input type="text" name="code" class="mono" value="<?= e($c['code']) ?>"
                           placeholder="NOWRUZ1404" required style="text-transform:uppercase">
                </div>
                <div class="grid g2">
                    <div class="field">
                        <label class="fl">نوع</label>
                        <select name="type">
                            <option value="percent" <?= $c['type'] === 'percent' ? 'selected' : '' ?>>درصدی</option>
                            <option value="fixed" <?= $c['type'] === 'fixed' ? 'selected' : '' ?>>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">مقدار *</label>
                        <input type="number" name="value" step="0.01" value="<?= e($c['value']) ?>" required>
                    </div>
                </div>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">حداقل مبلغ سفارش</label>
                        <input type="number" name="min_order" step="1000" value="<?= (int) $c['min_order'] ?>">
                    </div>
                    <div class="field">
                        <label class="fl">سقف تخفیف</label>
                        <input type="number" name="max_discount" step="1000"
                               value="<?= $c['max_discount'] !== null ? (int) $c['max_discount'] : '' ?>" placeholder="بدون سقف">
                    </div>
                </div>

                <div style="border-top:1px dashed var(--line);padding-top:11px;margin-top:4px">
                    <div class="hint mb"><b>محدودسازی هدفمند</b></div>
                    <div class="field">
                        <label class="fl">فقط برای دسته‌بندی</label>
                        <select name="category_id">
                            <option value="">همه دسته‌ها</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int) $cat['id'] ?>" <?= (int) $c['category_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label class="fl">فقط برای مدل خودرو</label>
                        <select name="car_model_id">
                            <option value="">همه خودروها</option>
                            <?php foreach ($carModels as $m): ?>
                                <option value="<?= (int) $m['id'] ?>" <?= (int) $c['car_model_id'] === (int) $m['id'] ? 'selected' : '' ?>>
                                    <?= e($m['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <label class="chk">
                        <input type="checkbox" name="first_order_only" value="1" <?= (int) $c['first_order_only'] === 1 ? 'checked' : '' ?>>
                        <span>فقط برای اولین خرید مشتری</span>
                    </label>
                </div>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">سقف کل استفاده</label>
                        <input type="number" name="usage_limit" value="<?= $c['usage_limit'] !== null ? (int) $c['usage_limit'] : '' ?>" placeholder="نامحدود">
                    </div>
                    <div class="field">
                        <label class="fl">سقف هر کاربر</label>
                        <input type="number" name="per_user_limit" value="<?= $c['per_user_limit'] !== null ? (int) $c['per_user_limit'] : '' ?>" placeholder="نامحدود">
                    </div>
                </div>

                <div class="grid g2">
                    <div class="field">
                        <label class="fl">تاریخ شروع</label>
                        <input type="text" name="starts_at" class="mono" data-jdp autocomplete="off"
                               value="<?= e($c['starts_at'] ? dateToJalali($c['starts_at']) : '') ?>" placeholder="۱۴۰۴/۰۱/۰۱">
                    </div>
                    <div class="field">
                        <label class="fl">تاریخ انقضا</label>
                        <input type="text" name="expires_at" class="mono" data-jdp autocomplete="off"
                               value="<?= e($c['expires_at'] ? dateToJalali($c['expires_at']) : '') ?>" placeholder="۱۴۰۴/۱۲/۲۹">
                    </div>
                </div>

                <label class="chk">
                    <input type="checkbox" name="is_active" value="1" <?= (int) $c['is_active'] === 1 ? 'checked' : '' ?>>
                    <span>فعال باشد</span>
                </label>
                <button class="btn btn-primary btn-block" type="submit">ذخیره کد تخفیف</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="card">
        <div class="card-head"><h3>کدهای تخفیف (<?= count($coupons) ?>)</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>کد</th><th>تخفیف</th><th>محدودیت‌ها</th><th>استفاده</th><th>اعتبار</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$coupons): ?><tr><td colspan="7" class="empty">کدی ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($coupons as $co):
                        $expired = $co['expires_at'] && strtotime($co['expires_at']) < time();
                        $notStarted = $co['starts_at'] && strtotime($co['starts_at']) > time();
                        $u = $usage[$co['code']] ?? null; ?>
                        <tr>
                            <td class="mono" style="font-weight:800"><?= e($co['code']) ?></td>
                            <td style="white-space:nowrap">
                                <?= $co['type'] === 'percent'
                                    ? rtrim(rtrim(number_format((float) $co['value'], 2), '0'), '.') . '٪'
                                    : money($co['value']) . ' ت' ?>
                                <?php if ($co['max_discount']): ?><div class="hint">سقف <?= money($co['max_discount']) ?></div><?php endif; ?>
                            </td>
                            <td style="font-size:10.5px">
                                <?php if ((float) $co['min_order'] > 0): ?>
                                    <div class="hint">حداقل <?= money($co['min_order']) ?> ت</div>
                                <?php endif; ?>
                                <?php if (!empty($co['category_name'])): ?>
                                    <span class="badge b-blue" style="font-size:9px"><?= e($co['category_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($co['car_model_name'])): ?>
                                    <span class="badge b-blue" style="font-size:9px"><?= e($co['car_model_name']) ?></span>
                                <?php endif; ?>
                                <?php if ((int) ($co['first_order_only'] ?? 0) === 1): ?>
                                    <span class="badge b-amber" style="font-size:9px">خرید اول</span>
                                <?php endif; ?>
                                <?php if (empty($co['category_name']) && empty($co['car_model_name'])
                                    && (float) $co['min_order'] <= 0 && !($co['first_order_only'] ?? 0)): ?>
                                    <span class="hint">بدون محدودیت</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= (int) $co['used_count'] ?><?= $co['usage_limit'] ? ' / ' . (int) $co['usage_limit'] : '' ?>
                                <?php if (!empty($co['per_user_limit'])): ?>
                                    <div class="hint">هر کاربر: <?= (int) $co['per_user_limit'] ?></div>
                                <?php endif; ?>
                                <?php if ($u): ?><div class="hint"><?= money($u['s']) ?> ت تخفیف</div><?php endif; ?>
                            </td>
                            <td class="hint" style="font-size:10.5px">
                                <?php if ($co['starts_at']): ?><div>از <?= e(toShamsi($co['starts_at'])) ?></div><?php endif; ?>
                                <div><?= $co['expires_at'] ? 'تا ' . e(toShamsi($co['expires_at'])) : 'بدون انقضا' ?></div>
                            </td>
                            <td>
                                <?php if ($expired): ?><span class="badge b-gray">منقضی</span>
                                <?php elseif ($notStarted): ?><span class="badge b-blue">شروع نشده</span>
                                <?php elseif ($co['is_active']): ?><span class="badge b-green">فعال</span>
                                <?php else: ?><span class="badge b-red">غیرفعال</span><?php endif; ?>
                            </td>
                            <td class="text-left">
                                <?php if ($canEdit): ?>
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm" href="<?= admin_url('coupons', ['edit' => $co['id']]) ?>">ویرایش</a>
                                        <?= action_button(admin_url('coupons/toggle'), $co['is_active'] ? 'غیرفعال' : 'فعال', [
                                            'class' => 'btn btn-sm', 'fields' => ['coupon_id' => $co['id']],
                                        ]) ?>
                                        <?= action_button(admin_url('coupons/reset'), '↺', [
                                            'class' => 'btn btn-sm', 'title' => 'صفر کردن شمارنده',
                                            'confirm' => 'شمارنده استفاده صفر شود؟',
                                            'fields' => ['coupon_id' => $co['id']],
                                        ]) ?>
                                        <?= action_button(admin_url('coupons/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این کد تخفیف؟',
                                            'fields' => ['coupon_id' => $co['id']],
                                        ]) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
