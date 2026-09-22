<?php
namespace Admin\controllers;

use Admin\core\Model;
use Core\Seo;
use Core\SeoAnalyzer;

/**
 * دیده‌بان سئو (Site-Wide SEO Audit)
 * ---------------------------------------------------------------------------
 * یک نمای واحد از سلامت سئوی کل فروشگاه:
 *   • قطعات بدون عکس / بدون متن جایگزین
 *   • قطعات فاقد شماره فنی OEM
 *   • صفحاتی با توضیحات کمتر از ۱۰۰ کاراکتر
 *   • کالاهای ناموجود پربازدید بدون جایگزین
 *   • لاگ خطاهای ۴۰۴ و مدیریت ریدایرکت‌های ۳۰۱
 *   • مدیریت لندینگ‌پیج‌های سئو
 */
class SeoController extends BaseController
{
    protected string $section = 'seo';

    protected array $permissions = [
        'index'          => 'seo.view',
        'issues'         => 'seo.view',
        'redirects'      => 'seo.view',
        'saveRedirect'   => 'seo.manage',
        'deleteRedirect' => 'seo.manage',
        'notfound'       => 'seo.view',
        'resolve404'     => 'seo.manage',
        'clear404'       => 'seo.manage',
        'landing'        => 'seo.view',
        'saveLanding'    => 'seo.manage',
        'deleteLanding'  => 'seo.manage',
        'rescan'         => 'seo.manage',
        'export'         => 'seo.view',
    ];

    // ---------------------------------------------------------------- پیشخوان

    public function index($id = 0): void
    {
        if (!$this->ready()) {
            return;
        }

        $stats = [
            'products'        => Model::count('products'),
            'no_image'        => $this->countNoImage(),
            'no_alt'          => $this->countNoAlt(),
            'no_oem'          => Model::count('products', "oem_code IS NULL OR oem_code = ''"),
            'thin'            => Model::count('products', "CHAR_LENGTH(COALESCE(description,'')) < 100"),
            'no_meta'         => Model::count('products', "meta_title IS NULL OR meta_title = ''"),
            'orphan_out'      => $this->countOrphanOutOfStock(),
            'noindex'         => Model::count('products', "robots_directive = 'noindex'"),
            'low_score'       => Model::count('products', 'seo_score < 50'),
            'articles'        => Model::count('articles'),
            'articles_nolink' => $this->countArticlesWithoutProducts(),
            'redirects'       => Model::hasTable('seo_redirects') ? Model::count('seo_redirects', 'is_active = 1') : 0,
            'errors404'       => Model::hasTable('seo_404_logs') ? Model::count('seo_404_logs', 'resolved = 0') : 0,
            'landing'         => Model::hasTable('seo_landing_pages') ? Model::count('seo_landing_pages') : 0,
        ];

        $stats['score'] = $this->healthScore($stats);

        // میانگین امتیاز سئوی محتوا
        $stats['avg_score'] = (int) round((float) Model::scalar('SELECT COALESCE(AVG(seo_score),0) FROM products'));

        $worst = Model::all(
            "SELECT id, name, slug, seo_score, oem_code,
                    CHAR_LENGTH(COALESCE(description,'')) AS desc_len,
                    (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images
             FROM products p ORDER BY seo_score ASC, id DESC LIMIT 12"
        );

        $top404 = Model::hasTable('seo_404_logs')
            ? Model::all('SELECT * FROM seo_404_logs WHERE resolved = 0 ORDER BY hits DESC LIMIT 10')
            : [];

        $this->view('seo/index', compact('stats', 'worst', 'top404'),
            'دیده‌بان سئو', 'گزارش سلامت سئوی کل فروشگاه');
    }

    // ---------------------------------------------------------------- خطاها

    /** فهرست تفکیکی مشکلات */
    public function issues($id = 0): void
    {
        if (!$this->ready()) {
            return;
        }

        $type = (string) param('type', 'no_image');
        $page = $this->page();
        $per = 30;

        [$where, $label, $hint] = $this->issueQuery($type);

        $total = (int) Model::scalar("SELECT COUNT(*) FROM products p WHERE $where");
        $pg = paginate($total, $page, $per);

        $rows = Model::all(
            "SELECT p.id, p.name, p.slug, p.oem_code, p.price, p.in_stock, p.stock_qty, p.seo_score,
                    CHAR_LENGTH(COALESCE(p.description,'')) AS desc_len,
                    (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images
             FROM products p WHERE $where
             ORDER BY p.id DESC LIMIT {$per} OFFSET {$pg['offset']}"
        );

        $types = $this->issueTypes();

        $this->view('seo/issues', compact('rows', 'pg', 'type', 'types', 'label', 'hint'),
            'خطاهای سئو: ' . $label, money($total) . ' مورد یافت شد');
    }

    /** خروجی CSV از یک دسته خطا برای اصلاح دسته‌جمعی */
    public function export($id = 0): void
    {
        $type = (string) param('type', 'no_image');
        [$where, $label] = $this->issueQuery($type);

        $rows = Model::all(
            "SELECT p.id, p.name, p.slug, p.oem_code, p.price, p.seo_score,
                    CHAR_LENGTH(COALESCE(p.description,'')) AS desc_len,
                    (SELECT COUNT(*) FROM product_images pi WHERE pi.product_id = p.id) AS images
             FROM products p WHERE $where ORDER BY p.id"
        );

        $data = array_map(fn($r) => [
            $r['id'], $r['name'], $r['slug'], $r['oem_code'] ?? '—',
            $r['price'], $r['images'], $r['desc_len'], $r['seo_score'],
        ], $rows);

        $this->streamCsv('seo-' . $type . '-' . date('Y-m-d') . '.csv',
            ['شناسه', 'نام قطعه', 'اسلاگ', 'کد فنی', 'قیمت', 'تعداد عکس', 'طول توضیحات', 'امتیاز سئو'],
            $data);
    }

    /** بازمحاسبه امتیاز سئوی همه محصولات (دسته‌ای) */
    public function rescan($id = 0): void
    {
        $siteName = $GLOBALS['settings']['site_title'] ?? 'پرادو یدک';
        $offset = max(0, (int) post('offset', 0));
        $limit = 500;

        $rows = Model::all("SELECT * FROM products ORDER BY id LIMIT {$limit} OFFSET {$offset}");
        $done = 0;

        foreach ($rows as $p) {
            $images = Model::all('SELECT alt_text FROM product_images WHERE product_id = ?', [(int) $p['id']]);
            $res = SeoAnalyzer::analyzeProduct($p, ['site_name' => $siteName, 'images' => $images]);
            Model::exec('UPDATE products SET seo_score = ? WHERE id = ?', [(int) $res['score'], (int) $p['id']]);
            $done++;
        }

        $remaining = max(0, Model::count('products') - ($offset + $done));
        $this->audit('seo.rescan', 'product', null, "بازمحاسبه امتیاز سئوی {$done} محصول");

        if ($remaining > 0) {
            flash('info', $done . ' محصول بررسی شد؛ ' . money($remaining) . ' مورد باقی مانده — دوباره بزنید.');
            redirect(admin_url('seo', ['next' => $offset + $done]));
        }

        flash('success', 'امتیاز سئوی همه محصولات بازمحاسبه شد.');
        redirect(admin_url('seo'));
    }

    // ---------------------------------------------------------------- ریدایرکت

    public function redirects($id = 0): void
    {
        if (!$this->ready()) {
            return;
        }

        $q = trim((string) param('q', ''));
        $where = '1';
        $params = [];
        if ($q !== '') {
            $where = '(from_path LIKE ? OR to_path LIKE ?)';
            $params = ["%$q%", "%$q%"];
        }

        $total = (int) Model::scalar("SELECT COUNT(*) FROM seo_redirects WHERE $where", $params);
        $pg = paginate($total, $this->page(), 40);
        $rows = Model::all("SELECT * FROM seo_redirects WHERE $where ORDER BY id DESC LIMIT 40 OFFSET {$pg['offset']}", $params);

        $this->view('seo/redirects', compact('rows', 'pg', 'q'),
            'مدیریت ریدایرکت‌ها', money($total) . ' قانون ثبت شده');
    }

    public function saveRedirect($id = 0): void
    {
        $rid = (int) post('id', 0);
        $from = \App\models\Redirect::normalize((string) post('from_path'));
        $to   = \App\models\Redirect::normalize((string) post('to_path'));
        $code = (int) post('status_code', 301);
        if (!in_array($code, [301, 302, 307, 308, 410], true)) {
            $code = 301;
        }

        if ($from === '/' || $from === $to) {
            flash('error', 'مسیر مبدأ و مقصد نمی‌توانند یکسان (یا صفحه اصلی) باشند.');
            back(admin_url('seo/redirects'));
        }

        if ($rid) {
            Model::update('seo_redirects', $rid, [
                'from_path'   => $from,
                'to_path'     => $to,
                'status_code' => $code,
                'is_active'   => post('is_active') ? 1 : 0,
                'note'        => mb_substr(trim((string) post('note')), 0, 255, 'UTF-8') ?: null,
            ]);
            flash('success', 'قانون ریدایرکت به‌روزرسانی شد.');
        } else {
            \App\models\Redirect::add($from, $to, [
                'status_code' => $code,
                'source'      => 'manual',
                'note'        => (string) post('note'),
            ]);
            flash('success', 'ریدایرکت ' . $code . ' از ' . $from . ' به ' . $to . ' ثبت شد.');
        }

        $this->audit('seo.redirect', 'redirect', $rid ?: null, 'ثبت/ویرایش ریدایرکت ' . $from . ' → ' . $to);
        redirect(admin_url('seo/redirects'));
    }

    public function deleteRedirect($id = 0): void
    {
        $rid = (int) post('redirect_id', $id);
        $r = Model::find('seo_redirects', $rid);
        if ($r) {
            Model::delete('seo_redirects', $rid);
            $this->audit('seo.redirect', 'redirect', $rid, 'حذف ریدایرکت ' . $r['from_path']);
            flash('success', 'ریدایرکت حذف شد.');
        }
        redirect(admin_url('seo/redirects'));
    }

    // ---------------------------------------------------------------- ۴۰۴

    public function notfound($id = 0): void
    {
        if (!$this->ready()) {
            return;
        }

        $showResolved = param('resolved', '') === '1';
        $where = $showResolved ? '1' : 'resolved = 0';

        $total = (int) Model::scalar("SELECT COUNT(*) FROM seo_404_logs WHERE $where");
        $pg = paginate($total, $this->page(), 40);
        $rows = Model::all("SELECT * FROM seo_404_logs WHERE $where ORDER BY hits DESC, last_seen_at DESC LIMIT 40 OFFSET {$pg['offset']}");

        $this->view('seo/notfound', compact('rows', 'pg', 'showResolved'),
            'خطاهای ۴۰۴ ورودی', money($total) . ' آدرس خراب');
    }

    /** تبدیل یک ۴۰۴ به ریدایرکت ۳۰۱ با یک کلیک */
    public function resolve404($id = 0): void
    {
        $logId = (int) post('log_id', $id);
        $target = \App\models\Redirect::normalize((string) post('to_path'));
        $log = Model::find('seo_404_logs', $logId);

        if (!$log) {
            flash('error', 'رکورد یافت نشد.');
            back(admin_url('seo/notfound'));
        }

        if ($target === '' || $target === '/') {
            // بدون مقصد، فقط به‌عنوان «نادیده گرفته‌شده» علامت می‌خورد
            Model::update('seo_404_logs', $logId, ['resolved' => 1]);
            flash('success', 'این آدرس نادیده گرفته شد.');
            back(admin_url('seo/notfound'));
        }

        \App\models\Redirect::add($log['path'], $target, [
            'source' => 'manual',
            'note'   => 'ساخته‌شده از گزارش ۴۰۴',
        ]);
        Model::update('seo_404_logs', $logId, ['resolved' => 1]);

        $this->audit('seo.redirect', 'redirect', null, 'رفع ۴۰۴: ' . $log['path'] . ' → ' . $target);
        flash('success', 'ریدایرکت ۳۰۱ ساخته شد: ' . $log['path'] . ' → ' . $target);
        back(admin_url('seo/notfound'));
    }

    public function clear404($id = 0): void
    {
        Model::exec('DELETE FROM seo_404_logs WHERE resolved = 1');
        flash('success', 'رکوردهای حل‌شده پاک شدند.');
        redirect(admin_url('seo/notfound'));
    }

    // ---------------------------------------------------------------- لندینگ

    public function landing($id = 0): void
    {
        if (!$this->ready()) {
            return;
        }

        $pages = Model::all('SELECT * FROM seo_landing_pages ORDER BY id DESC');
        $edit = $id ? Model::find('seo_landing_pages', (int) $id) : null;
        $categories = Model::all('SELECT slug, name FROM categories ORDER BY name');
        $carModels = Model::all('SELECT slug, name FROM car_models ORDER BY name');
        $brands = Model::all("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand");

        $this->view('seo/landing', compact('pages', 'edit', 'categories', 'carModels', 'brands'),
            'لندینگ‌پیج‌های سئو', 'آدرس تمیز برای ترکیب‌های پرجستجو');
    }

    public function saveLanding($id = 0): void
    {
        $lid = (int) post('id', 0);
        $h1 = trim((string) post('h1'));
        if ($h1 === '') {
            flash('error', 'عنوان اصلی (H1) الزامی است.');
            back(admin_url('seo/landing'));
        }

        $slug = trim((string) post('slug')) ?: make_slug($h1);
        if (Model::one('SELECT id FROM seo_landing_pages WHERE slug = ? AND id <> ?', [$slug, $lid])) {
            $slug .= '-' . random_int(10, 99);
        }

        $old = $lid ? Model::find('seo_landing_pages', $lid) : null;

        $robots = (string) post('robots_directive', 'default');
        if (!in_array($robots, ['default', 'index', 'noindex'], true)) {
            $robots = 'default';
        }

        $data = [
            'slug'             => $slug,
            'h1'               => mb_substr($h1, 0, 200, 'UTF-8'),
            'meta_title'       => mb_substr(trim((string) post('meta_title')), 0, 255, 'UTF-8') ?: null,
            'meta_description' => mb_substr(trim((string) post('meta_description')), 0, 320, 'UTF-8') ?: null,
            'focus_keyword'    => mb_substr(trim((string) post('focus_keyword')), 0, 120, 'UTF-8') ?: null,
            'intro_html'       => (string) post('intro_html'),
            'outro_html'       => (string) post('outro_html'),
            'filter_category'  => trim((string) post('filter_category')) ?: null,
            'filter_model'     => trim((string) post('filter_model')) ?: null,
            'filter_brand'     => trim((string) post('filter_brand')) ?: null,
            'robots_directive' => $robots,
            'is_active'        => post('is_active') ? 1 : 0,
        ];

        if ($lid && $old) {
            Model::update('seo_landing_pages', $lid, $data);
            if ($old['slug'] !== $slug) {
                \App\models\Redirect::add('/parts/' . $old['slug'], '/parts/' . $slug, [
                    'entity_type' => 'landing', 'entity_id' => $lid, 'source' => 'auto',
                ]);
            }
            flash('success', 'لندینگ‌پیج به‌روزرسانی شد.');
        } else {
            $lid = Model::insert('seo_landing_pages', $data);
            flash('success', 'لندینگ‌پیج ساخته شد: /parts/' . $slug);
        }

        $this->audit('seo.landing', 'landing', $lid, 'ذخیره لندینگ‌پیج ' . $slug);
        redirect(admin_url('seo/landing/' . $lid));
    }

    public function deleteLanding($id = 0): void
    {
        $lid = (int) post('landing_id', $id);
        $l = Model::find('seo_landing_pages', $lid);
        if ($l) {
            Model::delete('seo_landing_pages', $lid);
            $this->audit('seo.landing', 'landing', $lid, 'حذف لندینگ‌پیج ' . $l['slug']);
            flash('success', 'لندینگ‌پیج حذف شد.');
        }
        redirect(admin_url('seo/landing'));
    }

    // ---------------------------------------------------------------- داخلی

    /** اگر مهاجرت سئو اجرا نشده باشد، پیام راهنما نمایش داده می‌شود */
    private function ready(): bool
    {
        $ok = Model::hasColumn('products', 'meta_title')
            && Model::hasTable('seo_redirects')
            && Model::hasTable('seo_404_logs')
            && Model::hasTable('article_products')
            && Model::hasTable('seo_landing_pages');

        if (!$ok) {
            $this->view('seo/setup', [], 'راه‌اندازی دیده‌بان سئو', 'مهاجرت پایگاه داده لازم است');
        }
        return $ok;
    }

    private function issueTypes(): array
    {
        return [
            'no_image'   => ['قطعات بدون عکس', 'بدون تصویر، صفحه محصول در نتایج خرید گوگل نمایش داده نمی‌شود.'],
            'no_alt'     => ['تصاویر بدون متن جایگزین', 'alt خالی یعنی از دست دادن ترافیک Google Images.'],
            'no_oem'     => ['قطعات فاقد کد فنی OEM', 'حیاتی‌ترین عبارت جستجوی خریدار قطعه تویوتا.'],
            'thin'       => ['توضیحات کمتر از ۱۰۰ کاراکتر', 'محتوای نازک (Thin Content) کیفیت کل دامنه را پایین می‌آورد.'],
            'no_meta'    => ['بدون متاتایتل دستی', 'این صفحات با فرمول خودکار سرو می‌شوند؛ برای صفحات مهم متا دستی بنویسید.'],
            'orphan_out' => ['ناموجودهای پربازدید بدون جایگزین', 'کاربر وارد صفحه ناموجود می‌شود و بدون پیشنهاد جایگزین خارج می‌شود.'],
            'low_score'  => ['امتیاز سئوی کمتر از ۵۰', 'صفحاتی که در چند شاخص هم‌زمان ضعیف‌اند.'],
            'noindex'    => ['صفحات Noindex شده', 'بررسی کنید noindex بودنشان عمدی باشد.'],
        ];
    }

    private function issueQuery(string $type): array
    {
        $types = $this->issueTypes();
        $label = $types[$type][0] ?? 'خطاهای سئو';
        $hint  = $types[$type][1] ?? '';

        $where = match ($type) {
            'no_alt'     => "EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id
                                     AND (pi.alt_text IS NULL OR pi.alt_text = ''))",
            'no_oem'     => "p.oem_code IS NULL OR p.oem_code = ''",
            'thin'       => "CHAR_LENGTH(COALESCE(p.description,'')) < 100",
            'no_meta'    => "p.meta_title IS NULL OR p.meta_title = ''",
            'orphan_out' => "(p.in_stock = 0 OR p.stock_qty <= 0)
                             AND NOT EXISTS (SELECT 1 FROM products s WHERE s.category_id = p.category_id
                                             AND s.id <> p.id AND s.in_stock = 1)",
            'low_score'  => 'p.seo_score < 50',
            'noindex'    => "p.robots_directive = 'noindex'",
            default      => "NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)",
        };

        return [$where, $label, $hint];
    }

    private function countNoImage(): int
    {
        return (int) Model::scalar(
            'SELECT COUNT(*) FROM products p WHERE NOT EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)'
        );
    }

    private function countNoAlt(): int
    {
        return (int) Model::scalar(
            "SELECT COUNT(DISTINCT p.id) FROM products p JOIN product_images pi ON pi.product_id = p.id
             WHERE pi.alt_text IS NULL OR pi.alt_text = ''"
        );
    }

    private function countOrphanOutOfStock(): int
    {
        return (int) Model::scalar(
            "SELECT COUNT(*) FROM products p WHERE (p.in_stock = 0 OR p.stock_qty <= 0)
             AND NOT EXISTS (SELECT 1 FROM products s WHERE s.category_id = p.category_id
                             AND s.id <> p.id AND s.in_stock = 1)"
        );
    }

    private function countArticlesWithoutProducts(): int
    {
        if (!Model::hasTable('article_products')) {
            return 0;
        }
        return (int) Model::scalar(
            'SELECT COUNT(*) FROM articles a WHERE NOT EXISTS (SELECT 1 FROM article_products ap WHERE ap.article_id = a.id)'
        );
    }

    /** نمره کلی سلامت دامنه ۰ تا ۱۰۰ */
    private function healthScore(array $s): int
    {
        $total = max(1, (int) $s['products']);
        $penalty = 0;
        $penalty += ($s['no_image'] / $total) * 25;
        $penalty += ($s['no_oem'] / $total) * 20;
        $penalty += ($s['thin'] / $total) * 20;
        $penalty += ($s['no_alt'] / $total) * 15;
        $penalty += ($s['orphan_out'] / $total) * 10;
        $penalty += min(10, $s['errors404'] / 20);

        return max(0, min(100, (int) round(100 - $penalty)));
    }
}
