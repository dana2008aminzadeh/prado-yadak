<?php use Admin\core\Auth; $canEdit = can('articles.edit'); ?>

<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('articles') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label>
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="عنوان یا خلاصه مقاله"></div>
            <div class="f"><label class="fl">وضعیت</label>
                <select name="status">
                    <option value="">همه</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منتشرشده</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                </select></div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn" href="<?= admin_url('articles') ?>">حذف فیلتر</a>
            <?php if ($canEdit): ?>
                <a class="btn btn-primary" href="<?= admin_url('articles/create') ?>"><i data-lucide="plus" style="width:14px"></i> مقاله جدید</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>مقالات (<?= money($pg['total']) ?>)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>عنوان</th><th>دسته</th><th>نویسنده</th><th>بازدید</th><th>مطالعه</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
                <?php if (!$articles): ?><tr><td colspan="8" class="empty">مقاله‌ای یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($articles as $a): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;max-width:300px"><?= e($a['title']) ?></div>
                            <div class="hint mono" style="font-size:10px"><?= e($a['slug']) ?></div>
                        </td>
                        <td class="hint"><?= e($a['category_label']) ?></td>
                        <td class="hint"><?= e($a['author']) ?></td>
                        <td><?= money($a['views']) ?></td>
                        <td class="hint"><?= (int) $a['reading_time'] ?> دقیقه</td>
                        <td><span class="badge <?= $a['status'] === 'published' ? 'b-green' : 'b-amber' ?>"><?= $a['status'] === 'published' ? 'منتشرشده' : 'پیش‌نویس' ?></span></td>
                        <td class="hint"><?= e(toShamsi($a['created_at'])) ?></td>
                        <td class="text-left">
                            <div class="flex gap" style="justify-content:flex-end">
                                <?php if ($canEdit): ?>
                                    <?= action_button(admin_url('articles/toggle'), '', [
                                        'class' => 'btn btn-sm', 'icon' => 'eye', 'title' => 'تغییر وضعیت انتشار',
                                        'fields' => ['article_id' => $a['id']]]) ?>
                                <?php endif; ?>
                                <a class="btn btn-sm" href="<?= admin_url('articles/edit/' . $a['id']) ?>">ویرایش</a>
                                <?php if ($canEdit): ?>
                                    <?= action_button(admin_url('articles/delete'), 'حذف', [
                                        'class' => 'btn btn-sm btn-danger', 'confirm' => 'حذف این مقاله؟',
                                        'fields' => ['article_id' => $a['id']]]) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
