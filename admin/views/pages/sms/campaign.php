<?php use Admin\core\Auth; ?>

<div class="flex gap mb">
    <a class="btn" href="<?= admin_url('sms') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت</a>
</div>

<?php if (!$enabled): ?>
    <div class="flash error">سرویس پیامک غیرفعال است؛ ابتدا آن را در تنظیمات فعال کنید.</div>
<?php endif; ?>

<form method="POST" action="<?= admin_url('sms/runCampaign') ?>"
      onsubmit="return confirm('کمپین برای مخاطبان انتخابی ارسال شود؟ این عمل قابل بازگشت نیست.')">
    <?= Auth::csrfField() ?>

    <div class="grid g2" style="grid-template-columns:1.4fr 1fr;align-items:start">
        <div class="card">
            <div class="card-head"><h3>تنظیم کمپین پیامکی</h3></div>
            <div class="card-body">
                <div class="field">
                    <label class="fl">عنوان کمپین (داخلی)</label>
                    <input type="text" name="title" placeholder="مثال: تخفیف نوروزی لنت ترمز" required>
                </div>

                <div class="field">
                    <label class="fl">گروه مخاطبان</label>
                    <select name="audience" onchange="location.href='<?= admin_url('sms/campaign') ?>?audience='+this.value">
                        <?php foreach ($audiences as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= $audience === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="hint">تعداد مخاطبان این گروه: <b><?= money($total) ?></b> نفر</div>
                </div>

                <div class="field" id="customBox" style="<?= $audience === 'custom' ? '' : 'display:none' ?>">
                    <label class="fl">شماره‌های دستی</label>
                    <textarea name="numbers" rows="4" placeholder="هر شماره در یک خط یا جداشده با کاما"></textarea>
                </div>

                <div class="field">
                    <label class="fl">متن پیامک *</label>
                    <textarea name="message" rows="6" required oninput="countSms(this,'cc')"
                              placeholder="{name} عزیز، به مناسبت نوروز ۱۵٪ تخفیف روی همه قطعات پرادو. کد: NOWRUZ"></textarea>
                    <div class="hint" id="cc">۰ کاراکتر — ۰ پیامک</div>
                    <div class="hint">متغیر <span class="mono">{name}</span> با نام مشتری جایگزین می‌شود.</div>
                </div>

                <div class="field">
                    <label class="fl">حداکثر تعداد ارسال در این اجرا</label>
                    <input type="number" name="limit" value="<?= min(200, max(1, $total)) ?>" min="1" max="500">
                    <div class="hint">برای جلوگیری از قطع شدن اجرا، حداکثر ۵۰۰ پیامک در هر بار ارسال می‌شود.</div>
                </div>

                <button class="btn btn-primary btn-block" type="submit" <?= ($enabled && $total > 0) ? '' : 'disabled' ?>>
                    <i data-lucide="send" style="width:15px"></i> شروع ارسال کمپین
                </button>
                <div class="hint mt">ارسال ممکن است چند دقیقه طول بکشد؛ صفحه را نبندید.</div>
            </div>
        </div>

        <div>
            <div class="card mb">
                <div class="card-head"><h3>پیش‌نمایش مخاطبان</h3><span class="hint"><?= money($total) ?> نفر</span></div>
                <div class="card-body" style="max-height:330px;overflow-y:auto">
                    <?php if (!$preview): ?>
                        <div class="empty">مخاطبی در این گروه یافت نشد.</div>
                    <?php endif; ?>
                    <?php foreach ($preview as $p): ?>
                        <div style="padding:6px 0;border-bottom:1px solid var(--line);font-size:12px">
                            <b><?= e($p['full_name'] ?: 'بدون نام') ?></b>
                            <span class="hint mono"> — <?= e($p['phone']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($total > count($preview)): ?>
                        <div class="hint mt">و <?= money($total - count($preview)) ?> نفر دیگر…</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>قالب‌های آماده</h3></div>
                <div class="card-body">
                    <?php foreach ($templates as $t): ?>
                        <button type="button" class="btn btn-sm mb" style="width:100%;justify-content:flex-start"
                                onclick="useTpl(<?= htmlspecialchars(json_encode($t['body'], JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                            <?= e($t['title']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function countSms(el, target) {
    const text = el.value;
    const unicode = /[^\x20-\x7E]/.test(text);
    const len = text.length;
    const per = unicode ? 70 : 160, perMulti = unicode ? 67 : 153;
    const parts = len === 0 ? 0 : (len <= per ? 1 : Math.ceil(len / perMulti));
    const recipients = <?= (int) $total ?>;
    document.getElementById(target).textContent =
        `${len} کاراکتر — ${parts} پیامک برای هر نفر — مجموع تقریبی: ${parts * recipients} پیامک`;
}
function useTpl(body) {
    const ta = document.querySelector('textarea[name=message]');
    ta.value = body;
    countSms(ta, 'cc');
}
</script>
