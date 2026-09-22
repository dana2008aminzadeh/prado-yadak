<?php use Admin\core\Auth; ?>

<?php if (!$configured): ?>
    <div class="flash error">
        سرویس پیامک پیکربندی نشده است. کلید API و شماره خط را در
        <a href="<?= admin_url('settings') ?>" style="text-decoration:underline">تنظیمات سایت</a> وارد کنید.
    </div>
<?php elseif (!$enabled): ?>
    <div class="flash info">
        سرویس پیامک در تنظیمات <b>غیرفعال</b> است. برای ارسال، گزینه «فعال بودن سرویس پیامک» را روشن کنید.
    </div>
<?php endif; ?>

<div class="grid g4 mb">
    <div class="stat"><span class="lbl">ارسال موفق (کل)</span><div class="val"><?= money($stats['sent']) ?></div></div>
    <div class="stat"><span class="lbl">ناموفق</span><div class="val"><?= money($stats['failed']) ?></div></div>
    <div class="stat"><span class="lbl">امروز</span><div class="val"><?= money($stats['today']) ?></div></div>
    <div class="stat"><span class="lbl">۳۰ روز اخیر</span><div class="val"><?= money($stats['month']) ?></div></div>
</div>

<div class="tabs">
    <div class="tab active" onclick="switchTab(this,'tab-send')">ارسال پیامک</div>
    <div class="tab" onclick="switchTab(this,'tab-templates')">قالب‌های خودکار</div>
    <div class="tab" onclick="switchTab(this,'tab-campaigns')">کمپین‌ها</div>
    <div class="tab" onclick="switchTab(this,'tab-logs')">گزارش ارسال</div>
</div>

<!-- ارسال -->
<div class="tab-panel active" id="tab-send">
    <div class="grid g2" style="align-items:start">
        <?php if (can('sms.send')): ?>
            <form class="card" method="POST" action="<?= admin_url('sms/send') ?>">
                <?= Auth::csrfField() ?>
                <div class="card-head"><h3>ارسال پیامک تکی</h3></div>
                <div class="card-body">
                    <div class="field">
                        <label class="fl">شماره موبایل *</label>
                        <input type="tel" name="phone" class="mono" placeholder="09123456789" required>
                    </div>
                    <div class="field">
                        <label class="fl">متن پیامک *</label>
                        <textarea name="message" rows="5" required oninput="countSms(this,'c1')"></textarea>
                        <div class="hint" id="c1">۰ کاراکتر — ۰ پیامک</div>
                    </div>
                    <button class="btn btn-primary btn-block" type="submit" <?= $enabled ? '' : 'disabled' ?>>
                        <i data-lucide="send" style="width:15px"></i> ارسال
                    </button>
                </div>
            </form>

            <div class="card">
                <div class="card-head"><h3>ارسال گروهی (کمپین)</h3></div>
                <div class="card-body">
                    <p class="hint">برای ارسال پیام تبلیغاتی، کد تخفیف یا اطلاع‌رسانی به گروهی از مشتریان.</p>
                    <div class="grid g2 mt">
                        <?php foreach ($audiences as $k => $v): ?>
                            <a class="btn" href="<?= admin_url('sms/campaign', ['audience' => $k]) ?>"
                               style="justify-content:flex-start;font-size:11.5px"><?= e($v) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card"><div class="card-body"><div class="empty">شما اجازه ارسال پیامک ندارید.</div></div></div>
        <?php endif; ?>
    </div>
</div>

<!-- قالب‌ها -->
<div class="tab-panel" id="tab-templates">
    <div class="grid g2" style="grid-template-columns:1fr 1.5fr;align-items:start">
        <?php if (can('sms.send')): ?>
            <form class="card" method="POST" action="<?= admin_url('sms/saveTemplate') ?>">
                <?= Auth::csrfField() ?>
                <div class="card-head"><h3>ایجاد / ویرایش قالب</h3></div>
                <div class="card-body">
                    <div class="field">
                        <label class="fl">کلید قالب *</label>
                        <input type="text" name="template_key" id="tplKey" class="mono" placeholder="order_shipped" required>
                        <div class="hint">اگر کلید موجود باشد، همان قالب به‌روزرسانی می‌شود.</div>
                    </div>
                    <div class="field"><label class="fl">عنوان</label><input type="text" name="title" id="tplTitle"></div>
                    <div class="field">
                        <label class="fl">متن *</label>
                        <textarea name="body" id="tplBody" rows="5" required oninput="countSms(this,'c2')"></textarea>
                        <div class="hint" id="c2">۰ کاراکتر</div>
                        <div class="hint">متغیرها: <span class="mono">{name} {order} {tracking} {carrier} {amount} {code}</span></div>
                    </div>
                    <label class="chk"><input type="checkbox" name="is_active" id="tplActive" value="1" checked><span>فعال</span></label>
                    <label class="chk"><input type="checkbox" name="auto_send" id="tplAuto" value="1"><span>ارسال خودکار هنگام رویداد</span></label>
                    <button class="btn btn-primary btn-block" type="submit">ذخیره قالب</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="card">
            <div class="card-head"><h3>قالب‌های موجود</h3></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>کلید</th><th>عنوان</th><th>متن</th><th>خودکار</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!$templates): ?><tr><td colspan="5" class="empty">قالبی تعریف نشده.</td></tr><?php endif; ?>
                        <?php foreach ($templates as $t): ?>
                            <tr>
                                <td class="mono" style="font-size:11px"><?= e($t['template_key']) ?></td>
                                <td style="font-weight:700"><?= e($t['title']) ?></td>
                                <td class="hint" style="max-width:250px"><?= nl2br(e(excerpt($t['body'], 90))) ?></td>
                                <td>
                                    <span class="badge <?= $t['auto_send'] ? 'b-green' : 'b-gray' ?>"><?= $t['auto_send'] ? 'بله' : 'خیر' ?></span>
                                    <?php if (!$t['is_active']): ?><span class="badge b-red">غیرفعال</span><?php endif; ?>
                                </td>
                                <td class="text-left">
                                    <?php if (can('sms.send')): ?>
                                        <div class="flex gap" style="justify-content:flex-end">
                                            <button class="btn btn-sm" type="button" onclick='loadTpl(<?= json_encode([
                                                "key" => $t["template_key"], "title" => $t["title"], "body" => $t["body"],
                                                "active" => (int) $t["is_active"], "auto" => (int) $t["auto_send"],
                                            ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS) ?>)'>ویرایش</button>
                                            <?= action_button(admin_url('sms/deleteTemplate'), 'حذف', [
                                                'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این قالب؟',
                                                'fields' => ['template_id' => $t['id']],
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
</div>

<!-- کمپین‌ها -->
<div class="tab-panel" id="tab-campaigns">
    <div class="card">
        <div class="card-head"><h3>کمپین‌های اخیر</h3>
            <?php if (can('sms.send')): ?>
                <a class="btn btn-primary btn-sm" href="<?= admin_url('sms/campaign') ?>">کمپین جدید</a>
            <?php endif; ?>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>عنوان</th><th>مخاطبان</th><th>ارسال موفق</th><th>ناموفق</th><th>وضعیت</th><th>مدیر</th><th>تاریخ</th></tr></thead>
                <tbody>
                    <?php if (!$campaigns): ?><tr><td colspan="7" class="empty">کمپینی اجرا نشده است.</td></tr><?php endif; ?>
                    <?php foreach ($campaigns as $c): ?>
                        <tr>
                            <td style="font-weight:700"><?= e($c['title']) ?>
                                <div class="hint"><?= e(excerpt($c['message'], 60)) ?></div></td>
                            <td><?= money($c['total_recipients']) ?></td>
                            <td style="color:var(--green);font-weight:700"><?= money($c['sent_count']) ?></td>
                            <td style="color:var(--red)"><?= money($c['failed_count']) ?></td>
                            <td><span class="badge <?= $c['status'] === 'completed' ? 'b-green' : 'b-amber' ?>"><?= e($c['status']) ?></span></td>
                            <td class="hint"><?= e($c['admin_name'] ?? '—') ?></td>
                            <td class="hint"><?= e(shamsiTime($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- گزارش -->
<div class="tab-panel" id="tab-logs">
    <div class="card mb">
        <div class="card-body">
            <form method="GET" action="<?= admin_url('sms') ?>" class="filters">
                <div class="f" style="flex:2"><label class="fl">جستجو</label>
                    <input type="search" name="q" value="<?= e($q) ?>" placeholder="شماره، متن یا نام کاربر"></div>
                <div class="f"><label class="fl">وضعیت</label>
                    <select name="status">
                        <option value="">همه</option>
                        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>ارسال شده</option>
                        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>ناموفق</option>
                        <option value="queued" <?= $status === 'queued' ? 'selected' : '' ?>>در صف</option>
                    </select></div>
                <button class="btn btn-primary" type="submit">فیلتر</button>
                <a class="btn" href="<?= admin_url('sms') ?>">حذف فیلتر</a>
                <a class="btn" href="<?= admin_url('tools/export', ['type' => 'sms']) ?>">CSV</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h3>گزارش ارسال (<?= money($pg['total']) ?>)</h3></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>گیرنده</th><th>متن</th><th>قالب</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
                <tbody>
                    <?php if (!$logs): ?><tr><td colspan="6" class="empty">پیامکی ثبت نشده است.</td></tr><?php endif; ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td><div class="mono" style="font-weight:700"><?= e($l['phone']) ?></div>
                                <div class="hint"><?= e($l['full_name'] ?? '') ?></div></td>
                            <td style="max-width:300px;font-size:11.5px"><?= nl2br(e(excerpt($l['message'], 110))) ?></td>
                            <td class="hint mono" style="font-size:10px"><?= e($l['template_key'] ?: '—') ?></td>
                            <td>
                                <span class="badge <?= $l['status'] === 'sent' ? 'b-green' : ($l['status'] === 'failed' ? 'b-red' : 'b-amber') ?>">
                                    <?= $l['status'] === 'sent' ? 'ارسال شد' : ($l['status'] === 'failed' ? 'ناموفق' : 'در صف') ?>
                                </span>
                                <?php if ($l['error_message']): ?><div class="hint"><?= e(excerpt($l['error_message'], 40)) ?></div><?php endif; ?>
                            </td>
                            <td class="hint"><?= e(shamsiTime($l['created_at'])) ?></td>
                            <td class="text-left">
                                <?php if ($l['status'] === 'failed' && can('sms.send')): ?>
                                    <?= action_button(admin_url('sms/resend'), 'ارسال مجدد', [
                                        'class' => 'btn btn-sm', 'fields' => ['log_id' => $l['id']],
                                    ]) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
    </div>
</div>

<script>
function countSms(el, target) {
    const text = el.value;
    const unicode = /[^\x20-\x7E]/.test(text);
    const len = text.length;
    const per = unicode ? 70 : 160, perMulti = unicode ? 67 : 153;
    const parts = len === 0 ? 0 : (len <= per ? 1 : Math.ceil(len / perMulti));
    document.getElementById(target).textContent =
        `${len} کاراکتر — ${parts} پیامک ${unicode ? '(فارسی)' : '(انگلیسی)'}`;
}
function loadTpl(t) {
    document.querySelector('.tabs .tab:nth-child(2)').click();
    document.getElementById('tplKey').value = t.key;
    document.getElementById('tplTitle').value = t.title;
    document.getElementById('tplBody').value = t.body;
    document.getElementById('tplActive').checked = !!t.active;
    document.getElementById('tplAuto').checked = !!t.auto;
    countSms(document.getElementById('tplBody'), 'c2');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
