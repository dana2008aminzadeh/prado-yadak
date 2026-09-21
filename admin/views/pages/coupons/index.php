<?php
use Admin\core\Auth;
$c = $editing ?: ['id' => 0, 'code' => '', 'type' => 'percent', 'value' => 10, 'min_order' => 0,
    'max_discount' => '', 'usage_limit' => 100, 'expires_at' => '', 'is_active' => 1];
?>

<div class="grid g2" style="grid-template-columns:1fr 2fr;align-items:start">
    <form class="card" method="POST" action="<?= admin_url('coupons/save') ?>">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
        <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
        <div class="card-head"><h3><?= $c['id'] ? 'ویرایش کد ' . e($c['code']) : 'ایجاد کد تخفیف' ?></h3>
            <?php if ($c['id']): ?><a class="btn btn-sm" href="<?= admin_url('coupons') ?>">جدید</a><?php endif; ?>
        </div>
        <div class="card-body">
            <div class="field"><label class="fl">کد تخفیف *</label>
                <input type="text" name="code" class="mono" value="<?= e($c['code']) ?>" placeholder="NOWRUZ1404" required style="text-transform:uppercase"></div>
            <div class="grid g2">
                <div class="field"><label class="fl">نوع</label>
                    <select name="type">
                        <option value="percent" <?= $c['type'] === 'percent' ? 'selected' : '' ?>>درصدی</option>
                        <option value="fixed" <?= $c['type'] === 'fixed' ? 'selected' : '' ?>>مبلغ ثابت</option>
                    </select></div>
                <div class="field"><label class="fl">مقدار</label><input type="number" name="value" step="0.01" value="<?= e($c['value']) ?>" required></div>
            </div>
            <div class="field"><label class="fl">حداقل مبلغ سفارش (تومان)</label><input type="number" name="min_order" step="1000" value="<?= (int) $c['min_order'] ?>"></div>
            <div class="field"><label class="fl">سقف تخفیف (تومان)</label><input type="number" name="max_discount" step="1000" value="<?= $c['max_discount'] !== null ? (int) $c['max_discount'] : '' ?>" placeholder="بدون سقف">
                <div class="hint">فقط برای تخفیف درصدی کاربرد دارد.</div></div>
            <div class="field"><label class="fl">محدودیت تعداد استفاده</label><input type="number" name="usage_limit" value="<?= $c['usage_limit'] !== null ? (int) $c['usage_limit'] : '' ?>" placeholder="نامحدود"></div>
            <div class="field"><label class="fl">تاریخ انقضا</label>
                <input type="datetime-local" name="expires_at" value="<?= $c['expires_at'] ? e(date('Y-m-d\TH:i', strtotime($c['expires_at']))) : '' ?>"></div>
            <label class="flex items-center gap" style="margin-bottom:14px;cursor:pointer">
                <input type="checkbox" name="is_active" value="1" style="width:auto" <?= $c['is_active'] ? 'checked' : '' ?>>
                <span style="font-size:13px;font-weight:700">فعال باشد</span>
            </label>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره کد تخفیف</button>
        </div>
    </form>

    <div class="card">
        <div class="card-head"><h3>کدهای تخفیف</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>کد</th><th>تخفیف</th><th>حداقل سفارش</th><th>استفاده</th><th>انقضا</th><th>وضعیت</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$coupons): ?><tr><td colspan="7" class="empty">کدی ثبت نشده.</td></tr><?php endif; ?>
                    <?php foreach ($coupons as $co):
                        $expired = $co['expires_at'] && strtotime($co['expires_at']) < time();
                        $u = $usage[$co['code']] ?? null; ?>
                        <tr>
                            <td class="mono" style="font-weight:800"><?= e($co['code']) ?></td>
                            <td><?= $co['type'] === 'percent' ? rtrim(rtrim(number_format($co['value'], 2), '0'), '.') . '٪' : money($co['value']) . ' تومان' ?>
                                <?php if ($co['max_discount']): ?><div class="hint">سقف <?= money($co['max_discount']) ?></div><?php endif; ?>
                            </td>
                            <td class="hint"><?= money($co['min_order']) ?></td>
                            <td><?= (int) $co['used_count'] ?><?= $co['usage_limit'] ? ' / ' . (int) $co['usage_limit'] : '' ?>
                                <?php if ($u): ?><div class="hint"><?= money($u['s']) ?> تومان تخفیف</div><?php endif; ?>
                            </td>
                            <td class="hint"><?= $co['expires_at'] ? e(toShamsi($co['expires_at'])) : 'بدون انقضا' ?></td>
                            <td>
                                <?php if ($expired): ?><span class="badge b-gray">منقضی</span>
                                <?php elseif ($co['is_active']): ?><span class="badge b-green">فعال</span>
                                <?php else: ?><span class="badge b-red">غیرفعال</span><?php endif; ?>
                            </td>
                            <td class="text-left">
                                <div class="flex gap" style="justify-content:flex-end">
                                    <a class="btn btn-sm" href="<?= admin_url('coupons', ['edit' => $co['id']]) ?>">ویرایش</a>
                                    <a class="btn btn-sm" href="<?= admin_url('coupons/toggle/' . $co['id']) ?>"><?= $co['is_active'] ? 'غیرفعال' : 'فعال' ?></a>
                                    <a class="btn btn-sm" href="<?= admin_url('coupons/reset/' . $co['id']) ?>" title="صفر کردن شمارنده">↺</a>
                                    <a class="btn btn-sm btn-danger" href="<?= admin_url('coupons/delete/' . $co['id']) ?>" onclick="return confirmDelete()">حذف</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
