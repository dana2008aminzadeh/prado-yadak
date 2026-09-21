<?php
namespace Admin\controllers;

use Admin\core\Model;

class ArticleController extends BaseController
{
    protected string $section = 'articles';

    public function index($id = 0): void
    {
        $q = trim((string) param('q', ''));
        $status = param('status', '');
        $where = ['1']; $params = [];
        if ($q !== '') { $where[] = '(title LIKE ? OR summary LIKE ? OR slug LIKE ?)'; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if ($status !== '') { $where[] = 'status = ?'; $params[] = $status; }
        $w = implode(' AND ', $where);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM articles WHERE $w", $params);
        $pg = paginate($total, $this->page(), $this->perPage);
        $articles = Model::all("SELECT * FROM articles WHERE $w ORDER BY created_at DESC LIMIT {$this->perPage} OFFSET {$pg['offset']}", $params);

        $this->view('articles/index', compact('articles', 'pg', 'q', 'status'), 'مدیریت مقالات', money($total) . ' مقاله');
    }

    public function create($id = 0): void
    {
        if ($this->isPost()) $this->save(0);
        $article = ['id' => 0, 'title' => '', 'slug' => '', 'category' => 'technical', 'category_label' => 'فنی',
            'icon' => 'wrench', 'cover_image' => '', 'summary' => '', 'content' => '', 'reading_time' => 5,
            'author' => 'تیم فنی پرادو یدک', 'views' => 0, 'status' => 'published'];
        $this->view('articles/form', compact('article'), 'نگارش مقاله جدید');
    }

    public function edit($id = 0): void
    {
        $article = Model::find('articles', (int) $id);
        if (!$article) { flash('error', 'مقاله یافت نشد.'); redirect(admin_url('articles')); }
        if ($this->isPost()) $this->save((int) $id);
        $this->view('articles/form', compact('article'), 'ویرایش: ' . $article['title']);
    }

    private function save(int $id): void
    {
        $title = trim((string) post('title'));
        if ($title === '') {
            flash('error', 'عنوان مقاله الزامی است.');
            redirect($id ? admin_url('articles/edit/' . $id) : admin_url('articles/create'));
        }
        $content = (string) post('content');
        $words = max(1, str_word_count(strip_tags($content)) + mb_substr_count(strip_tags($content), ' '));

        $data = [
            'title'          => $title,
            'slug'           => trim((string) post('slug')) ?: make_slug($title),
            'category'       => trim((string) post('category')) ?: 'technical',
            'category_label' => trim((string) post('category_label')) ?: 'فنی',
            'icon'           => trim((string) post('icon')) ?: 'wrench',
            'cover_image'    => trim((string) post('cover_image')) ?: null,
            'summary'        => (string) post('summary'),
            'content'        => $content,
            'reading_time'   => (int) post('reading_time') ?: max(1, (int) round($words / 200)),
            'author'         => trim((string) post('author')) ?: 'تیم فنی پرادو یدک',
            'status'         => post('status') === 'draft' ? 'draft' : 'published',
        ];
        if (Model::one('SELECT id FROM articles WHERE slug = ? AND id <> ?', [$data['slug'], $id])) {
            $data['slug'] .= '-' . random_int(100, 999);
        }

        if ($id) {
            Model::update('articles', $id, $data);
            flash('success', 'مقاله به‌روزرسانی شد.');
        } else {
            $id = Model::insert('articles', $data);
            flash('success', 'مقاله منتشر شد.');
        }
        redirect(admin_url('articles/edit/' . $id));
    }

    public function delete($id = 0): void
    {
        Model::delete('articles', (int) $id);
        flash('success', 'مقاله حذف شد.');
        redirect(admin_url('articles'));
    }

    public function toggle($id = 0): void
    {
        Model::exec("UPDATE articles SET status = IF(status='published','draft','published') WHERE id = ?", [(int) $id]);
        flash('success', 'وضعیت انتشار تغییر کرد.');
        redirect(admin_url('articles'));
    }
}
