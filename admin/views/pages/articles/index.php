<div class="card mb">
    <div class="card-body">
        <form method="GET" action="<?= admin_url('articles') ?>" class="filters">
            <div class="f" style="flex:2"><label class="fl">جستجو</label><input type="search" name="q" value="<?= e($q) ?>" placeholder="عنوان یا خلاصه مقاله"></div>
            <div class="f"><label class="fl">وضعیت</label>
                <select name="status">
                    <option value="">همه</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>منتشرشده</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">فیلتر</button>
            <a class="btn btn-primary" href="<?= admin_url('articles/create') ?>"><i data-lucide="plus" style="width:14px"></i> مقاله جدید</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head"><h3>مقالات (<?= money($pg['total']) ?>)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>عنوان</th><th>دسته</th><th>نویسنده</th><th>بازدید</th><th>زمان مطالعه</th><th>وضعیت</th><th>تاریخ</th><th></th></tr></thead>
            <tbody>
                <?php if (!$articles): ?><tr><td colspan="8" class="empty">مقاله‌ای یافت نشد.</td></tr><?php endif; ?>
                <?php foreach ($articles as $a): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;max-width:320px"><?= e($a['title']) ?></div>
                            <div class="hint mono"><?= e($a['slug']) ?></div>
                        </td>
                        <td class="hint"><?= e($a['category_label']) ?></td>
                        <td class="hint"><?= e($a['author']) ?></td>
                        <td><?= money($a['views']) ?></td>
                        <td class="hint"><?= (int) $a['reading_time'] ?> دقیقه</td>
                        <td><span class="badge <?= $a['status'] === 'published' ? 'b-green' : 'b-amber' ?>"><?= $a['status'] === 'published' ? 'منتشرشده' : 'پیش‌نویس' ?></span></td>
                        <td class="hint"><?= e(toShamsi($a['created_at'])) ?></td>
                        <td class="text-left">
                            <div class="flex gap" style="justify-content:flex-end">
                                <a class="btn btn-sm" href="<?= admin_url('articles/toggle/' . $a['id']) ?>" title="تغییر وضعیت"><i data-lucide="eye" style="width:13px"></i></a>
                                <a class="btn btn-sm" href="<?= admin_url('articles/edit/' . $a['id']) ?>">ویرایش</a>
                                <a class="btn btn-sm btn-danger" href="<?= admin_url('articles/delete/' . $a['id']) ?>" onclick="return confirmDelete()">حذف</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php include ADMIN_PATH . '/views/layout/pagination.php'; ?>
</div>
