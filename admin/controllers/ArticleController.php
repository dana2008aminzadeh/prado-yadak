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
        'suggestSeo' => 'articles.edit',
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
            'author' => 'تیم فنی پرادو یدک', 'views' => 0, 'status' => 'published',
            'meta_title' => '', 'meta_description' => '', 'focus_keyword' => '',
            'robots_directive' => 'default', 'canonical_url' => '', 'seo_score' => 0];
        $this->renderForm($article, 'نگارش مقاله جدید');
    }

    public function edit($id = 0): void
    {
        $article = Model::find('articles', (int) $id);
        if (!$article) {
            flash('error', 'مقاله یافت نشد.');
            redirect(admin_url('articles'));
        }
        $this->renderForm($article, 'ویرایش: ' . $article['title']);
    }

    private function renderForm(array $article, string $title): void
    {
        $aid = (int) $article['id'];

        // محصولات متصل به مقاله (ساختار سیلو)
        $linkedProducts = $aid && Model::hasTable('article_products')
            ? Model::all('SELECT p.id, p.name, p.slug, p.price, p.oem_code, p.in_stock
                          FROM article_products ap JOIN products p ON p.id = ap.product_id
                          WHERE ap.article_id = ? ORDER BY ap.sort_order, ap.id', [$aid])
            : [];

        $seo = \Core\SeoAnalyzer::analyzeArticle($article, [
            'site_name' => $GLOBALS['settings']['site_title'] ?? 'پرادو یدک',
            'related_products' => count($linkedProducts),
        ]);

        // وضعیت دروازه اجباری انتشار (کلمات، H2 و alt تصاویر)
        $publishGate = \Core\SeoAnalyzer::publishGate($article);

        $this->view('articles/form', compact('article', 'linkedProducts', 'seo', 'publishGate'), $title);
    }

    /** جستجوی زنده محصول برای انتخاب «محصولات مرتبط» در فرم مقاله */
    public function searchProducts($id = 0): void
    {
        $q = trim((string) param('q', ''));
        if (mb_strlen($q) < 2) {
            $this->json(['items' => []]);
        }
        $rows = Model::all(
            "SELECT id, name, slug, oem_code, price FROM products
             WHERE name LIKE ? OR oem_code LIKE ? ORDER BY name LIMIT 15",
            ["%$q%", "%$q%"]
        );
        $this->json(['items' => $rows]);
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

        // تغییر اسلاگ مقاله نیز باید با ریدایرکت ۳۰۱ پوشش داده شود
        $oldSlug = (string) ($old['slug'] ?? '');
        $slugChanged = $oldSlug !== '' && $oldSlug !== $slug;

        $robots = (string) post('robots_directive', 'default');
        if (!in_array($robots, ['default', 'index', 'noindex', 'noindex_nofollow'], true)) {
            $robots = 'default';
        }

        // =====================================================================
        // دروازه اجباری سئو پیش از انتشار
        // ---------------------------------------------------------------------
        // تعداد کلمات، تیترهای H2 و alt تصاویر فقط «نمایش امتیاز» نیستند؛ اگر
        // حداقل‌ها رعایت نشده باشد، مقاله منتشر نمی‌شود و به‌عنوان پیش‌نویس
        // ذخیره می‌ماند تا محتوای ناقص وارد ایندکس گوگل نشود.
        // =====================================================================
        $requestedStatus = post('status') === 'draft' ? 'draft' : 'published';
        $finalStatus = $requestedStatus;

        if ($requestedStatus === 'published') {
            $gate = \Core\SeoAnalyzer::publishGate([
                'title'            => $title,
                'content'          => $content,
                'summary'          => (string) post('summary'),
                'meta_description' => (string) post('meta_description'),
            ]);

            if (!$gate['passed']) {
                $finalStatus = 'draft';
                flash('error', 'انتشار انجام نشد؛ مقاله به‌صورت پیش‌نویس ذخیره شد. موارد زیر باید اصلاح شوند: '
                    . implode(' | ', $gate['errors']));
            }
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
            'author'         => trim((string) post('author')) ?: \Core\Seo::AUTHOR_FALLBACK,
            'status'         => $finalStatus,
            // ---- فیلدهای اختصاصی سئو ----
            'meta_title'       => mb_substr(trim((string) post('meta_title')), 0, 255, 'UTF-8') ?: null,
            'meta_description' => mb_substr(trim((string) post('meta_description')), 0, 320, 'UTF-8') ?: null,
            'focus_keyword'    => mb_substr(trim((string) post('focus_keyword')), 0, 120, 'UTF-8') ?: null,
            'robots_directive' => $robots,
            'canonical_url'    => mb_substr(trim((string) post('canonical_url')), 0, 255, 'UTF-8') ?: null,
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

        $data = Model::filterColumns('articles', $data);

        $statusNote = $finalStatus === 'published' ? ' و منتشر شد.' : ' (پیش‌نویس).';

        if ($aid && $old) {
            Model::update('articles', $aid, $data);
            $this->audit('article.update', 'article', $aid, 'ویرایش مقاله: ' . $title, $old, $data);
            if ($finalStatus === $requestedStatus) {
                flash('success', 'مقاله به‌روزرسانی شد' . $statusNote);
            }
        } else {
            $aid = Model::insert('articles', $data);
            $this->audit('article.create', 'article', $aid, 'ثبت مقاله جدید: ' . $title);
            if ($finalStatus === $requestedStatus) {
                flash('success', 'مقاله ایجاد شد' . $statusNote);
            }
        }

        // ---- ریدایرکت ۳۰۱ برای آدرس قبلی ----
        if ($slugChanged) {
            \App\models\Redirect::add('/blog/' . $oldSlug, '/blog/' . $slug, [
                'entity_type' => 'article',
                'entity_id'   => $aid,
                'source'      => 'auto',
                'note'        => 'تغییر خودکار اسلاگ مقاله: ' . $title,
            ]);
            flash('info', 'آدرس قبلی مقاله با ریدایرکت ۳۰۱ به آدرس جدید منتقل شد.');
        }

        // ---- محصولات مرتبط (ساختار سیلو) ----
        $this->syncRelatedProducts($aid);

        // ---- امتیاز سئو ----
        try {
            $fresh = Model::find('articles', $aid) ?? [];
            $res = \Core\SeoAnalyzer::analyzeArticle($fresh, [
                'site_name' => $GLOBALS['settings']['site_title'] ?? 'پرادو یدک',
                'related_products' => Model::hasTable('article_products')
                    ? Model::count('article_products', 'article_id = ?', [$aid]) : 0,
            ]);
            if (Model::hasColumn('articles', 'seo_score')) {
                Model::exec('UPDATE articles SET seo_score = ? WHERE id = ?', [(int) $res['score'], $aid]);
            }
        } catch (\Throwable $e) {
            // بی‌اهمیت
        }

        redirect(admin_url('articles/edit/' . $aid));
    }

    /** ذخیره پیوند مقاله ↔ محصولات */
    private function syncRelatedProducts(int $aid): void
    {
        if (!Model::hasTable('article_products')) {
            return;
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) post('related_product_ids', [])))));

        Model::exec('DELETE FROM article_products WHERE article_id = ?', [$aid]);
        $order = 0;
        foreach ($ids as $pid) {
            if (!Model::find('products', $pid)) {
                continue;
            }
            Model::insert('article_products', [
                'article_id' => $aid,
                'product_id' => $pid,
                'sort_order' => $order++,
            ]);
        }
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
            $goingPublic = ($a['status'] ?? '') !== 'published';

            // دروازه اجباری سئو هنگام انتشار از فهرست مقالات نیز اعمال می‌شود
            if ($goingPublic) {
                $gate = \Core\SeoAnalyzer::publishGate($a);
                if (!$gate['passed']) {
                    flash('error', 'این مقاله هنوز آماده انتشار نیست: ' . implode(' | ', $gate['errors']));
                    back(admin_url('articles'));
                }
            }

            Model::exec('UPDATE articles SET status = ? WHERE id = ?', [$goingPublic ? 'published' : 'draft', $aid]);
            $this->audit('article.update', 'article', $aid, 'تغییر وضعیت انتشار مقاله: ' . $a['title']);
            flash('success', $goingPublic ? 'مقاله منتشر شد.' : 'مقاله به پیش‌نویس منتقل شد.');
        }

        back(admin_url('articles'));
    }
}
