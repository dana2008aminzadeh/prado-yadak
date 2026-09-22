<?php
use Admin\core\Auth;
use Admin\core\Settings;
use Admin\core\Uploader;

$sc = ['open' => 'b-amber', 'answered' => 'b-green', 'pending' => 'b-blue', 'closed' => 'b-gray'];
$pc = ['low' => 'b-gray', 'normal' => 'b-blue', 'high' => 'b-red'];
$canReply = can('tickets.reply');
$smsEnabled = Settings::bool('sms_enabled');
?>

<div class="flex gap mb between">
    <a class="btn" href="<?= admin_url('tickets') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت</a>
    <?php if (can('tickets.delete')): ?>
        <?= action_button(admin_url('tickets/delete'), 'حذف تیکت', [
            'class' => 'btn btn-danger',
            'confirm' => 'حذف کامل این تیکت و تمام پیام‌ها و پیوست‌های آن؟',
            'fields' => ['ticket_id' => $ticket['id']],
        ]) ?>
    <?php endif; ?>
</div>

<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
    <div>
        <div class="card mb">
            <div class="card-head">
                <h3><?= e($ticket['subject']) ?></h3>
                <div class="flex gap">
                    <span class="badge <?= $pc[$ticket['priority']] ?? 'b-gray' ?>"><?= e($priorities[$ticket['priority']] ?? '') ?></span>
                    <span class="badge <?= $sc[$ticket['status']] ?? 'b-gray' ?>"><?= e($statuses[$ticket['status']] ?? '') ?></span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!$messages): ?><div class="empty">پیامی در این تیکت ثبت نشده.</div><?php endif; ?>
                <?php foreach ($messages as $m):
                    $isAdmin = $m['sender_type'] === 'admin';
                    $isSystem = $m['sender_type'] === 'system';
                    $bg = $isAdmin ? '#f4efe9' : ($isSystem ? '#f6f7fb' : '#f3f6fb');
                    $bd = $isAdmin ? '#e2d3c6' : ($isSystem ? '#e6e9f0' : '#dbe5f3');
                    $files = $byMessage[(int) $m['id']] ?? []; ?>
                    <div style="margin-bottom:11px;padding:11px 13px;border-radius:13px;background:<?= $bg ?>;border:1px solid <?= $bd ?>">
                        <div class="flex between items-center" style="margin-bottom:5px">
                            <b style="font-size:11.5px">
                                <?= e($m['sender_name'] ?: ($isAdmin ? 'پشتیبانی' : 'کاربر')) ?>
                                <span class="badge <?= $isAdmin ? 'b-amber' : ($isSystem ? 'b-gray' : 'b-blue') ?>">
                                    <?= $isAdmin ? 'پشتیبانی' : ($isSystem ? 'سیستم' : 'کاربر') ?>
                                </span>
                            </b>
                            <span class="hint"><?= e(shamsiTime($m['created_at'])) ?></span>
                        </div>
                        <div style="font-size:12.8px"><?= nl2br(e($m['message'])) ?></div>

                        <?php if ($files): ?>
                            <div class="flex gap wrap" style="margin-top:9px">
                                <?php foreach ($files as $f):
                                    $url = Uploader::url($f['telegram_file_id'] ?? null, $f['file_path'] ?? null, '');
                                    $isImg = str_starts_with((string) ($f['mime_type'] ?? ''), 'image/') || !empty($f['telegram_file_id']);
                                    if (!$url) continue; ?>
                                    <a href="<?= e($url) ?>" target="_blank" rel="noopener"
                                       style="display:block;border:1px solid var(--line);border-radius:10px;overflow:hidden;background:#fff">
                                        <?php if ($isImg): ?>
                                            <img src="<?= e($url) ?>" alt="" style="width:110px;height:84px;object-fit:cover;display:block">
                                        <?php else: ?>
                                            <div style="padding:14px 18px;text-align:center;font-size:11px">
                                                <i data-lucide="file-text" style="width:22px;height:22px"></i>
                                                <div><?= e(excerpt($f['original_name'] ?? 'فایل', 18)) ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($canReply): ?>
            <form class="card" method="POST" action="<?= admin_url('tickets/reply') ?>" enctype="multipart/form-data">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                <div class="card-head"><h3>ارسال پاسخ</h3></div>
                <div class="card-body">
                    <div class="field">
                        <textarea name="message" rows="5" placeholder="پاسخ خود را بنویسید..."></textarea>
                    </div>

                    <div class="field">
                        <label class="fl">پیوست فایل (تصویر قطعه، عکس شماره شاسی، PDF)</label>
                        <div class="dropzone" id="atDrop" onclick="document.getElementById('atInput').click()" style="padding:18px">
                            <i data-lucide="paperclip" style="width:22px;height:22px;color:var(--brand)"></i>
                            <div style="font-weight:700;font-size:12px;margin-top:5px">فایل‌ها را بکشید یا کلیک کنید</div>
                            <div class="hint">JPG، PNG، WebP، GIF یا PDF — حداکثر ۲۰ مگابایت</div>
                            <input type="file" name="attachments[]" id="atInput" multiple style="display:none"
                                   accept="image/*,application/pdf" onchange="showFiles(this)">
                        </div>
                        <div id="atList" class="hint mt"></div>
                    </div>

                    <div class="flex gap items-center wrap">
                        <select name="status" style="width:auto">
                            <?php foreach ($statuses as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $k === 'answered' ? 'selected' : '' ?>>پس از ارسال: <?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="chk" style="margin:0">
                            <input type="checkbox" name="send_sms" value="1" <?= $smsEnabled ? '' : 'disabled' ?>>
                            <span>اطلاع پیامکی به کاربر</span>
                        </label>
                        <button class="btn btn-primary" type="submit"><i data-lucide="send" style="width:14px"></i> ارسال پاسخ</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($canReply): ?>
            <form class="card mb" method="POST" action="<?= admin_url('tickets/update') ?>">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="ticket_id" value="<?= (int) $ticket['id'] ?>">
                <div class="card-head"><h3>مدیریت تیکت</h3></div>
                <div class="card-body">
                    <div class="field"><label class="fl">وضعیت</label>
                        <select name="status">
                            <?php foreach ($statuses as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $ticket['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">اولویت</label>
                        <select name="priority">
                            <?php foreach ($priorities as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $ticket['priority'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <button class="btn btn-primary btn-block" type="submit">ذخیره</button>
                </div>
            </form>
        <?php endif; ?>

        <div class="card mb">
            <div class="card-head"><h3>کاربر</h3></div>
            <div class="card-body">
                <div style="font-weight:800"><?= e($ticket['full_name'] ?? '—') ?></div>
                <div class="hint mono"><?= e($ticket['phone'] ?? '') ?></div>
                <?php if ($ticket['email']): ?><div class="hint"><?= e($ticket['email']) ?></div><?php endif; ?>
                <div class="hint mt">ایجاد: <?= e(shamsiTime($ticket['created_at'])) ?></div>
                <div class="hint">آخرین بروزرسانی: <?= e(timeAgo($ticket['updated_at'])) ?></div>
                <?php if (can('users.view')): ?>
                    <a class="btn btn-sm mt" href="<?= admin_url('users/show/' . (int) $ticket['user_id']) ?>">پروفایل کاربر</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($userOrders): ?>
            <div class="card">
                <div class="card-head"><h3>سفارش‌های اخیر کاربر</h3></div>
                <div class="card-body">
                    <?php foreach ($userOrders as $o): ?>
                        <a href="<?= admin_url('orders/show/' . $o['id']) ?>"
                           style="display:block;padding:7px 0;border-bottom:1px solid var(--line)">
                            <div class="mono" style="font-size:11.5px;font-weight:700"><?= e($o['tracking_code']) ?></div>
                            <div class="hint"><?= money($o['total_amount']) ?> تومان — <?= e(toShamsi($o['created_at'])) ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function showFiles(input) {
    const list = document.getElementById('atList');
    if (!input.files.length) { list.textContent = ''; return; }
    list.innerHTML = Array.from(input.files).map(f =>
        `📎 ${f.name} (${(f.size / 1024 / 1024).toFixed(2)} MB)`).join('<br>');
}
const ad = document.getElementById('atDrop');
if (ad) {
    ['dragenter', 'dragover'].forEach(ev => ad.addEventListener(ev, e => { e.preventDefault(); ad.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach(ev => ad.addEventListener(ev, e => { e.preventDefault(); ad.classList.remove('drag'); }));
    ad.addEventListener('drop', e => {
        const input = document.getElementById('atInput');
        input.files = e.dataTransfer.files;
        showFiles(input);
    });
}
</script>
