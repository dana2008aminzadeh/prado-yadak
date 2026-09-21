<?php use Admin\core\Auth; ?>

<form method="POST" action="<?= $article['id'] ? admin_url('articles/edit/' . $article['id']) : admin_url('articles/create') ?>">
    <input type="hidden" name="_csrf" value="<?= e(Auth::csrf()) ?>">

    <div class="grid g2" style="grid-template-columns:2.2fr 1fr;align-items:start">
        <div class="card">
            <div class="card-head"><h3>محتوای مقاله</h3></div>
            <div class="card-body">
                <div class="field"><label class="fl">عنوان *</label><input type="text" name="title" value="<?= e($article['title']) ?>" required></div>
                <div class="field"><label class="fl">اسلاگ</label><input type="text" name="slug" class="mono" value="<?= e($article['slug']) ?>" placeholder="خودکار"></div>
                <div class="field"><label class="fl">خلاصه</label><textarea name="summary" rows="3"><?= e($article['summary']) ?></textarea></div>
                <div class="field"><label class="fl">متن کامل (HTML مجاز)</label>
                    <textarea name="content" rows="22" style="font-family:ui-monospace,monospace;font-size:12.5px"><?= e($article['content']) ?></textarea>
                </div>
            </div>
        </div>

        <div>
            <div class="card mb">
                <div class="card-head"><h3>انتشار</h3></div>
                <div class="card-body">
                    <div class="field"><label class="fl">وضعیت</label>
                        <select name="status">
                            <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>منتشرشده</option>
                            <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>پیش‌نویس</option>
                        </select>
                    </div>
                    <div class="field"><label class="fl">نویسنده</label><input type="text" name="author" value="<?= e($article['author']) ?>"></div>
                    <div class="field"><label class="fl">زمان مطالعه (دقیقه)</label><input type="number" name="reading_time" min="1" value="<?= (int) $article['reading_time'] ?>"></div>
                    <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center"><i data-lucide="save" style="width:15px"></i> ذخیره مقاله</button>
                    <?php if ($article['id']): ?>
                        <a class="btn" style="width:100%;justify-content:center;margin-top:8px" target="_blank" href="/blog/<?= e($article['slug']) ?>">مشاهده در سایت</a>
                        <a class="btn btn-danger" style="width:100%;justify-content:center;margin-top:8px" href="<?= admin_url('articles/delete/' . $article['id']) ?>" onclick="return confirmDelete()">حذف مقاله</a>
                        <div class="hint mt">بازدید: <?= money($article['views']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>دسته‌بندی و تصویر</h3></div>
                <div class="card-body">
                    <div class="field"><label class="fl">کلید دسته</label>
                        <select name="category">
                            <?php foreach (['genuine' => 'قطعات اصل', 'technical' => 'فنی', 'maintenance' => 'نگهداری'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $article['category'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field"><label class="fl">برچسب نمایشی دسته</label><input type="text" name="category_label" value="<?= e($article['category_label']) ?>"></div>
                    <div class="field"><label class="fl">آیکون (Lucide)</label><input type="text" name="icon" class="mono" value="<?= e($article['icon']) ?>" placeholder="wrench"></div>
                    <div class="field"><label class="fl">تصویر کاور (URL یا file_id)</label><input type="text" name="cover_image" class="mono" value="<?= e($article['cover_image']) ?>"></div>
                </div>
            </div>
        </div>
    </div>
</form>
