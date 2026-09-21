<?php use Admin\core\Auth; ?>

<div class="flex gap mb" style="justify-content:space-between">
    <a class="btn" href="<?= admin_url('tickets') ?>"><i data-lucide="arrow-right" style="width:14px"></i> بازگشت</a>
    <a class="btn btn-danger" href="<?= admin_url('tickets/delete/' . $ticket['id']) ?>" onclick="return confirmDelete('حذف کامل این تیکت؟')">حذف تیکت</a>
</div>

<div class="grid g2" style="grid-template-columns:2fr 1fr;align-items:start">
    <div>
        <div class="card mb">
            <div class="card-head"><h3><?= e($ticket['subject']) ?></h3><span class="hint">#<?= (int) $ticket['id'] ?></span></div>
            <div class="card-body">
                <?php if (!$messages): ?><div class="empty">پیامی در این تیکت ثبت نشده.</div><?php endif; ?>
                <?php foreach ($messages as $m):
                    $isAdmin = $m['sender_type'] === 'admin';
                    $bg = $isAdmin ? '#f4efe9' : '#f3f6fb';
                    $bd = $isAdmin ? '#e2d3c6' : '#dbe5f3'; ?>
                    <div style="margin-bottom:12px;padding:12px 14px;border-radius:14px;background:<?= $bg ?>;border:1px solid <?= $bd ?>">
                        <div class="flex items-center" style="justify-content:space-between;margin-bottom:6px">
                            <b style="font-size:12px"><?= e($m['sender_name'] ?: ($isAdmin ? 'پشتیبانی' : 'کاربر')) ?>
                                <span class="badge <?= $isAdmin ? 'b-amber' : 'b-blue' ?>"><?= $isAdmin ? 'پشتیبانی' : ($m['sender_type'] === 'system' ? 'سیستم' : 'کاربر') ?></span>
                            </b>
                            <span class="hint"><?= e(shamsiTime($m['created_at'])) ?></span>
                        </div>
                        <div style="font-size:13px"><?= nl2br(e($m['message'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <form class="card" method="POST" action="<?= admin_url('tickets/reply/' . $ticket['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>ارسال پاسخ</h3></div>
            <div class="card-body">
                <div class="field"><textarea name="message" rows="5" placeholder="پاسخ خود را بنویسید..." required></textarea></div>
                <div class="flex gap items-center">
                    <select name="status" style="width:auto">
                        <?php foreach ($statuses as $k => $v): ?>
                            <option value="<?= e($k) ?>" <?= $k === 'answered' ? 'selected' : '' ?>>پس از ارسال: <?= e($v) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary" type="submit"><i data-lucide="send" style="width:14px"></i> ارسال پاسخ</button>
                </div>
            </div>
        </form>
    </div>

    <div>
        <form class="card mb" method="POST" action="<?= admin_url('tickets/update/' . $ticket['id']) ?>">
            <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">
            <div class="card-head"><h3>مدیریت تیکت</h3></div>
            <div class="card-body">
                <div class="field"><label class="fl">وضعیت</label>
                    <select name="status">
                        <?php foreach ($statuses as $k => $v): ?><option value="<?= e($k) ?>" <?= $ticket['status'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="field"><label class="fl">اولویت</label>
                    <select name="priority">
                        <?php foreach ($priorities as $k => $v): ?><option value="<?= e($k) ?>" <?= $ticket['priority'] === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
                    </select></div>
                <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">ذخیره</button>
            </div>
        </form>

        <div class="card">
            <div class="card-head"><h3>کاربر</h3></div>
            <div class="card-body">
                <div style="font-weight:800"><?= e($ticket['full_name'] ?? '—') ?></div>
                <div class="hint mono"><?= e($ticket['phone'] ?? '') ?></div>
                <div class="hint"><?= e($ticket['email'] ?: '') ?></div>
                <div class="hint mt">ایجاد: <?= e(shamsiTime($ticket['created_at'])) ?></div>
                <a class="btn btn-sm mt" href="<?= admin_url('users/show/' . (int) $ticket['user_id']) ?>">پروفایل کاربر</a>
            </div>
        </div>
    </div>
</div>
