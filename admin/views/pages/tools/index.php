<?php use Admin\core\Auth; ?>

<div class="grid g2" style="grid-template-columns:1.3fr 1fr;align-items:start">
    <div>
        <!-- پشتیبان‌گیری -->
        <div class="card mb">
            <div class="card-head">
                <h3><i data-lucide="database-backup" style="width:15px"></i> پشتیبان‌گیری از پایگاه داده</h3>
                <form method="POST" action="<?= admin_url('tools/backup') ?>" class="flex gap"
                      onsubmit="this.querySelector('button').textContent='در حال ساخت…'">
                    <?= Auth::csrfField() ?>
                    <label class="chk" style="margin:0;font-size:11px">
                        <input type="checkbox" name="structure_only" value="1"><span>فقط ساختار</span>
                    </label>
                    <button class="btn btn-primary btn-sm" type="submit" data-nolock>ایجاد نسخه پشتیبان</button>
                </form>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>نام فایل</th><th>حجم</th><th>تاریخ ساخت</th><th></th></tr></thead>
                    <tbody>
                        <?php if (!$backups): ?>
                            <tr><td colspan="4" class="empty">هنوز نسخه پشتیبانی ساخته نشده است.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($backups as $b): ?>
                            <tr>
                                <td class="mono" style="font-size:11px"><?= e($b['name']) ?></td>
                                <td><?= e($b['size']) ?></td>
                                <td class="hint"><?= e(shamsiTime(date('Y-m-d H:i:s', $b['modified']))) ?>
                                    <div style="font-size:9.5px"><?= e(timeAgo(date('Y-m-d H:i:s', $b['modified']))) ?></div>
                                </td>
                                <td class="text-left">
                                    <div class="flex gap" style="justify-content:flex-end">
                                        <a class="btn btn-sm btn-primary" href="<?= admin_url('tools/download', ['file' => $b['name']]) ?>">
                                            <i data-lucide="download" style="width:13px"></i> دانلود
                                        </a>
                                        <?= action_button(admin_url('tools/delete'), 'حذف', [
                                            'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این نسخه پشتیبان؟',
                                            'fields' => ['file' => $b['name']],
                                        ]) ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-body" style="border-top:1px solid var(--line)">
                <div class="hint">
                    • نسخه‌های پشتیبان در پوشه <span class="mono">backups/</span> ذخیره می‌شوند و از دسترسی وب محافظت شده‌اند.<br>
                    • تنها ۱۰ نسخه آخر نگهداری می‌شود؛ نسخه‌های قدیمی‌تر خودکار حذف می‌شوند.<br>
                    • برای پشتیبان‌گیری خودکار روزانه، در کرون‌جاب هاست این دستور را اضافه کنید:<br>
                    <span class="mono" style="display:block;background:#f4f5f8;padding:7px 9px;border-radius:8px;margin-top:5px;font-size:10.5px">
                        0 3 * * * cd <?= e(SITE_ROOT) ?> &amp;&amp; php admin/cron-backup.php
                    </span>
                </div>
            </div>
        </div>

        <!-- خروجی اکسل -->
        <div class="card mb">
            <div class="card-head"><h3><i data-lucide="file-spreadsheet" style="width:15px"></i> خروجی اکسل (CSV)</h3></div>
            <div class="card-body">
                <div class="grid g2">
                    <?php foreach ($exports as [$type, $label, $icon]): ?>
                        <a class="btn" href="<?= admin_url('tools/export', ['type' => $type]) ?>"
                           style="justify-content:flex-start">
                            <i data-lucide="<?= e($icon) ?>" style="width:14px"></i> <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <div class="hint mt">فایل‌ها با کدگذاری UTF-8 BOM ساخته می‌شوند و مستقیماً در اکسل فارسی باز می‌شوند.</div>
            </div>
        </div>

        <!-- جداول -->
        <div class="card">
            <div class="card-head">
                <h3>وضعیت جداول پایگاه داده</h3>
                <?= action_button(admin_url('tools/optimize'), 'بهینه‌سازی جداول', [
                    'class' => 'btn btn-sm', 'confirm' => 'همه جداول بهینه‌سازی شوند؟',
                ]) ?>
            </div>
            <div class="table-wrap" style="max-height:340px;overflow-y:auto">
                <table>
                    <thead><tr><th>جدول</th><th>تعداد ردیف (تقریبی)</th><th>حجم</th></tr></thead>
                    <tbody>
                        <?php foreach ($tables as $t): ?>
                            <tr>
                                <td class="mono" style="font-size:11px"><?= e($t['name']) ?></td>
                                <td><?= money($t['rows_count'] ?? 0) ?></td>
                                <td class="hint"><?= e($t['size']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- سلامت سیستم -->
    <div class="card">
        <div class="card-head"><h3><i data-lucide="activity" style="width:15px"></i> بررسی سلامت سیستم</h3></div>
        <div class="card-body">
            <?php foreach ($health as $h): ?>
                <div class="flex between items-center" style="padding:8px 0;border-bottom:1px solid var(--line);gap:10px">
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:700"><?= e($h['label']) ?></div>
                        <div class="hint"><?= e($h['detail']) ?></div>
                    </div>
                    <span class="badge <?= $h['ok'] ? 'b-green' : 'b-red' ?>"><?= $h['ok'] ? '✓' : '✕' ?></span>
                </div>
            <?php endforeach; ?>

            <div class="hint mt" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:10px">
                <b>یادآوری امنیتی:</b> پس از اتمام نصب و مهاجرت، فایل‌های
                <span class="mono">admin/make-admin.php</span> و
                <span class="mono">admin/migrate.php</span> را از روی سرور حذف کنید.
            </div>
        </div>
    </div>
</div>
