<?php
namespace Admin\controllers;

use Admin\core\Model;
use Admin\core\Uploader;

class ArticleController extends BaseController
{
    protected string $section = 'articles';

    protected array $permissions = [
        'create' => 'articles.edit',
        'save'   => 'articles.edit',
        'delete' => 'articles.edit',
        'toggle' => 'articles.edit',
    ];

    public function index($id = 0): void
    {
        $q = trim((string) param('q', ''));
        $status = param('status', '');

        $where = ['1'];
        $params = [];
        if ($q !== '') { $where[] = '(title LIKE ? OR summary LIKE ? OR slug LIKE ?)'; $like = "%$q%"; array_push($params, $like, $like, $like); }
        if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM articles WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $articles = Model::all("SELECT * FROM articles WHERE $w ORDER BY created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $this->view('articles/index', compact('articles', 'pg', 'q', 'status'), 'مدیریت مقالات', money($total) . ' مقاله');
    }

    public function create($id = 0): void
    {
        $article = ['id' => 0, 'title' => '', 'slug' => '', 'category' => 'technical', 'category_label' => 'فنی',
            'icon' => 'wrench', 'cover_image' => '', 'summary' => '', 'content' => '', 'reading_time' => 5,
            'author' => 'تیم فنی پرادو یدک', 'views' => 0, 'status' => 'published'];
        $this->view('articles/form', compact('article'), 'نگارش مقاله جدید');
    }

    public function edit($id = 0): void
    {
        $article = Model::find('articles', (int) $id);
        if (!$article) {
            flash('error', 'مقاله یافت نشد.');
            redirect(admin_url('articles'));
        }
        $this->view('articles/form', compact('article'), 'ویرایش: ' . $article['title']);
    }

    public function save($id = 0): void
    {
        $aid = (int) post('id', $id);
        $title = trim((string) post('title'));

        if ($title === '') {
            flash('error', 'عنوان مقاله الزامی است.');
            back(admin_url('articles'));
        }

        $old = $aid ? Model::find('articles', $aid) : null;
        $content = (string) post('content');
        $plain = trim(strip_tags($content));
        $words = max(1, count(preg_split('/\s+/u', $plain) ?: []));

        $slug = trim((string) post('slug')) ?: make_slug($title);
        if (Model::one('SELECT id FROM articles WHERE slug = ? AND id <> ?', [$slug, $aid])) {
            $slug .= '-' . random_int(100, 999);
        }

        $data = [
            'title'          => mb_substr($title, 0, 255),
            'slug'           => $slug,
            'category'       => trim((string) post('category')) ?: 'technical',
            'category_label' => trim((string) post('category_label')) ?: 'فنی',
            'icon'           => trim((string) post('icon')) ?: 'wrench',
            'summary'        => (string) post('summary'),
            'content'        => $content,
            'reading_time'   => (int) post('reading_time') ?: max(1, (int) round($words / 200)),
            'author'         => trim((string) post('author')) ?: 'تیم فنی پرادو یدک',
            'status'         => post('status') === 'draft' ? 'draft' : 'published',
        ];

        // آپلود کاور در صورت ارسال فایل
        if (!empty($_FILES['cover_file']['name'])) {
            $res = Uploader::image($_FILES['cover_file'], 'articles');
            if ($res['success']) {
                $data['cover_image'] = $res['telegram_file_id'] ?? $res['path'];
            } else {
                flash('error', 'آپلود کاور ناموفق: ' . $res['message']);
            }
        } elseif (post('cover_image') !== '') {
            $data['cover_image'] = trim((string) post('cover_image'));
        }

        if ($aid && $old) {
            Model::update('articles', $aid, $data);
            $this->audit('article.update', 'article', $aid, 'ویرایش مقاله: ' . $title, $old, $data);
            flash('success', 'مقاله به‌روزرسانی شد.');
        } else {
            $aid = Model::insert('articles', $data);
            $this->audit('article.create', 'article', $aid, 'انتشار مقاله جدید: ' . $title);
            flash('success', 'مقاله ایجاد شد.');
        }

        redirect(admin_url('articles/edit/' . $aid));
    }

    public function delete($id = 0): void
    {
        $aid = (int) post('article_id', $id);
        $a = Model::find('articles', $aid);
        if ($a) {
            Model::delete('articles', $aid);
            $this->audit('article.delete', 'article', $aid, 'حذف مقاله: ' . $a['title'], $a, null);
            flash('success', 'مقاله حذف شد.');
        }
        redirect(admin_url('articles'));
    }

    public function toggle($id = 0): void
    {
        $aid = (int) post('article_id', $id);
        $a = Model::find('articles', $aid);
        if ($a) {
            Model::exec("UPDATE articles SET status = IF(status='published','draft','published') WHERE id = ?", [$aid]);
            $this->audit('article.update', 'article', $aid, 'تغییر وضعیت انتشار مقاله: ' . $a['title']);
            flash('success', 'وضعیت انتشار تغییر کرد.');
        }
        back(admin_url('articles'));
    }
}
