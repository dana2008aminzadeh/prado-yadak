<?php
use Admin\core\Auth;
use Admin\core\Uploader;
$aid = (int) $article['id'];
$coverUrl = $article['cover_image']
    ? (str_starts_with((string) $article['cover_image'], 'uploads/')
        ? '/' . $article['cover_image']
        : (str_starts_with((string) $article['cover_image'], 'http') || str_starts_with((string) $article['cover_image'], '/')
            ? $article['cover_image']
            : '/image?id=' . urlencode((string) $article['cover_image'])))
    : '/assets/logo/logo.webp';
?>

<form method="POST" action="<?= admin_url('articles/save') ?>" enctype="multipart/form-data">
    <?= Auth::csrfField() ?>
    <input type="hidden" name="id" value="<?= $aid ?>">

    <div class="grid g2" style="grid-template-columns:2.2fr 1fr;align-items:start">
        <div class="card">
            <div class="card-head"><h3>محتوای مقاله</h3></div>
            <div class="card-body">
                <div class="field"><label class="fl">عنوان *</label>
                    <input type="text" name="title" value="<?= e($article['title']) ?>" required></div>
                <div class="field"><label class="fl">اسلاگ</label>
                    <input type="text" name="slug" class="mono" value="<?= e($article['slug']) ?>" placeholder="خودکار"></div>
                <div class="field"><label class="fl">خلاصه</label>
                    <textarea name="summary" rows="3"><?= e($article['summary']) ?></textarea></div>
                <div class="field"><label class="fl">متن کامل (HTML مجاز)</label>
                    <textarea name="content" rows="20" class="mono" style="font-size:12px"><?= e($article['content']) ?></textarea></div>
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
                        </select></div>
                    <div class="field"><label class="fl">نویسنده</label>
                        <input type="text" name="author" value="<?= e($article['author']) ?>"></div>
                    <div class="field"><label class="fl">زمان مطالعه (دقیقه)</label>
                        <input type="number" name="reading_time" min="1" value="<?= (int) $article['reading_time'] ?>">
                        <div class="hint">خالی بگذارید تا خودکار محاسبه شود.</div></div>
                    <button class="btn btn-primary btn-block" type="submit">
                        <i data-lucide="save" style="width:15px"></i> ذخیره مقاله
                    </button>
                    <?php if ($aid): ?>
                        <a class="btn btn-block mt" target="_blank" rel="noopener" href="/blog/<?= e($article['slug']) ?>">مشاهده در سایت</a>
                        <div class="hint mt">بازدید: <?= money($article['views']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb">
                <div class="card-head"><h3>دسته‌بندی</h3></div>
                <div class="card-body">
                    <div class="field"><label class="fl">کلید دسته</label>
                        <select name="category">
                            <?php foreach (['genuine' => 'قطعات اصل', 'technical' => 'فنی', 'maintenance' => 'نگهداری'] as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $article['category'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="field"><label class="fl">برچسب نمایشی</label>
                        <input type="text" name="category_label" value="<?= e($article['category_label']) ?>"></div>
                    <div class="field"><label class="fl">آیکون (Lucide)</label>
                        <input type="text" name="icon" class="mono" value="<?= e($article['icon']) ?>" placeholder="wrench"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h3>تصویر کاور</h3></div>
                <div class="card-body">
                    <img src="<?= e($coverUrl) ?>" alt="" style="width:100%;height:150px;object-fit:cover;
                         border-radius:11px;border:1px solid var(--line);margin-bottom:10px">
                    <div class="field">
                        <label class="fl">آپلود تصویر جدید</label>
                        <input type="file" name="cover_file" accept="image/*">
                    </div>
                    <div class="field">
                        <label class="fl">یا آدرس / file_id دستی</label>
                        <input type="text" name="cover_image" class="mono" value="<?= e($article['cover_image']) ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($aid && can('articles.edit')): ?>
    <div class="card mt">
        <div class="card-body text-left">
            <form method="POST" action="<?= admin_url('articles/delete') ?>" onsubmit="return confirmDelete('حذف این مقاله؟')">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="article_id" value="<?= $aid ?>">
                <button class="btn btn-danger" type="submit"><i data-lucide="trash-2" style="width:14px"></i> حذف مقاله</button>
            </form>
        </div>
    </div>
<?php endif; ?>
