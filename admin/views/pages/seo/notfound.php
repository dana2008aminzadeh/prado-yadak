<?php use Admin\core\Auth; ?>

<div class="card">
    <div class="card-head">
        <h3>آدرس‌های خراب ورودی (<?= money($pg['total']) ?>)</h3>
        <div class="flex gap wrap">
            <a class="btn btn-sm <?= $showResolved ? '' : 'btn-primary' ?>" href="<?= admin_url('seo/notfound') ?>">حل‌نشده</a>
            <a class="btn btn-sm <?= $showResolved ? 'btn-primary' : '' ?>" href="<?= admin_url('seo/notfound', ['resolved' => 1]) ?>">همه</a>
            <?php if (can('seo.manage')): ?>
                <?= action_button(admin_url('seo/clear404'), 'پاک‌سازی حل‌شده‌ها', [
                    'class' => 'btn btn-sm', 'icon' => 'eraser', 'confirm' => 'رکوردهای حل‌شده حذف شوند؟',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="hint">
            هر ردیف یعنی کاربر یا خزنده گوگل به آدرسی رفته که وجود ندارد. با وارد کردن آدرس مقصد،
            بلافاصله یک ریدایرکت ۳۰۱ ساخته می‌شود و اعتبار آن آدرس بازیابی می‌گردد.
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>آدرس خراب</th><th>تعداد</th><th>منبع ورود</th><th>ربات؟</th><th>آخرین بازدید</th><th style="min-width:300px">اقدام</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="mono" style="max-width:240px;overflow:hidden;text-overflow:ellipsis" title="<?= e($r['path']) ?>">
                            <?= e($r['path']) ?>
                        </td>
                        <td><span class="badge <?= (int) $r['hits'] > 10 ? 'b-red' : 'b-amber' ?>"><?= fa((int) $r['hits']) ?></span></td>
                        <td class="hint" style="max-width:180px;overflow:hidden;text-overflow:ellipsis">
                            <?= $r['last_referer'] ? e($r['last_referer']) : '—' ?>
                        </td>
                        <td><?= (int) $r['is_bot'] === 1 ? '<span class="badge b-blue">خزنده</span>' : '<span class="badge b-gray">کاربر</span>' ?></td>
                        <td class="hint"><?= e(timeAgo($r['last_seen_at'])) ?></td>
                        <td>
                            <?php if ((int) $r['resolved'] === 1): ?>
                                <span class="badge b-green">حل شده</span>
                            <?php elseif (can('seo.manage')): ?>
                                <form method="POST" action="<?= admin_url('seo/resolve404') ?>" class="flex gap">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="log_id" value="<?= (int) $r['id'] ?>">
                                    <input type="text" name="to_path" class="mono" placeholder="/product/slug-جدید" style="min-width:180px">
                                    <button class="btn btn-sm btn-primary" type="submit">ریدایرکت</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="empty">هیچ خطای ۴۰۴ ثبت نشده است. 👌</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php require ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
