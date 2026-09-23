<?php
namespace App\controllers;

use Core\Seo;

/**
 * کنترلر صفحات عمومی (خانه و قوانین)
 * ---------------------------------------------------------------------------
 * تمام آدرس‌های مطلق از Core\Seo::base() گرفته می‌شوند. الگوی قدیمی
 * «$protocol . '://' . SITE_URL» حذف شد، چون SITE_URL خودش شامل پروتکل است و
 * آن الگو آدرس‌های خرابی مثل https://https://pradoyadak.com تولید می‌کرد.
 * همچنین چند بلوک JSON-LD مجزا با یک گراف واحد (@graph) جایگزین شده‌اند.
 */
class HomeController extends Controller
{
    public function index()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';
        $phone = $settings['phone_number'] ?? '09189998852';
        $address = $settings['address'] ?? 'استان کردستان سقز جاده کانی جژنی صنوف آلاینده-2 پلاک 350 فروشگاه پرادو یدک';

        $hostUrl = Seo::base();

        $pageTitle = $siteName . ' | مرجع تخصصی قطعات اصلی تویوتا و لکسوس';
        $metaDescription = $settings['site_description']
            ?? 'فروشگاه تخصصی ' . $siteName . '؛ تامین قطعات اصلی جنیون پارت تویوتا و لکسوس با ضمانت ۱۰۰٪ اصالت، تطابق با شماره شاسی (VIN) و ارسال سریع به سراسر کشور.';

        // دریافت ۶ محصول جدیدتر از دیتابیس
        $latestProductsData = \App\models\Product::search([], 1, 6);
        $latestProducts = $latestProductsData['items'] ?? [];

        // ------------------------------------------------------------------
        // یک بلوک JSON-LD یکپارچه (@graph) — بدون گره تکراری
        // گره سازمان از Seo::organizationNode می‌آید و اینجا فقط اطلاعات
        // فیزیکی فروشگاه (آدرس و ساعات کاری) به آن افزوده می‌شود.
        // ------------------------------------------------------------------
        $canonicalUrl = $hostUrl . '/';

        $organization = Seo::organizationNode($settings ?? []);
        $organization['telephone'] = $phone;
        $organization['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $address,
            'addressLocality' => 'سقز',
            'addressRegion'   => 'کردستان',
            'postalCode'      => '6681898204',
            'addressCountry'  => 'IR',
        ];
        $organization['openingHoursSpecification'] = [
            [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'],
                'opens'     => '09:00',
                'closes'    => '18:00',
            ],
        ];

        // اسکیمای اختصاصی گوگل برای لیست جدیدترین محصولات (Rich Results)
        $itemListElements = [];
        foreach ($latestProducts as $idx => $prod) {
            $itemListElements[] = [
                '@type'    => 'ListItem',
                'position' => $idx + 1,
                'url'      => Seo::productUrl($prod['slug'] ?? '', true),
                'name'     => $prod['name'] ?? '',
            ];
        }

        $schemaMarkup = Seo::graph([
            $organization,
            Seo::websiteNode($settings ?? []),
            Seo::webPageNode($canonicalUrl, $pageTitle, $metaDescription, $hostUrl . '/assets/logo/logo.webp'),
            Seo::itemListNode(
                $canonicalUrl . '#latest-products',
                'جدیدترین قطعات یدکی تویوتا در ' . $siteName,
                $itemListElements
            ),
        ]);

        $latestArticles = \App\models\Article::getAll('published', null, 3);
        require_once VIEWS_PATH . '/index.php';
    }

    public function terms()
    {
        global $settings;
        $siteName = $settings['site_title'] ?? 'پرادو یدک';

        $pageTitle = 'قوانین، شرایط بازگشت کالا و ضمانت اصالت | ' . $siteName;
        $metaDescription = 'در مجموعه ' . $siteName . '، حفظ اعتماد شما و ارائه‌ی لوازم یدکی ۱۰۰٪ اصلی (Genuine Parts) اولویت اول ماست. کلیه شرایط تعویض، مرجوعی، تطابق شاسی و استرداد وجه با شفافیت کامل.';

        $faqs = [
            [
                'q' => 'چگونه مطمئن شوم قطعه انتخابی دقیقاً به تویوتا من می‌خورد؟',
                'a' => 'کافیست شماره ۱۷ رقمی شاسی خودرو (کد VIN درج شده روی کارت ماشین) را از طریق واتساپ یا تماس تلفنی به کارشناسان پرادو یدک اعلام کنید. ما قطعه را به وسیله نرم‌افزار رسمی تویوتا (Toyota EPC) تطبیق می‌دهیم و اصالت انتخاب را ۱۰۰٪ ضمانت می‌کنیم.'
            ],
            [
                'q' => 'اگر قطعه در تعمیرگاه روی خودرو نصب شد ولی مشکل حل نشد چه باید کرد؟',
                'a' => 'طبق قوانین صنف لوازم یدکی، قطعاتی که آثار بسته شدن، پیچ شدن یا استفاده شدن روی خودرو را دارند قابل بازگشت نیستند؛ زیرا عیب‌یابی نادرست تعمیرکار مربوط به تشخیص خودرو بوده و قطعه سلامت کامل دارد.'
            ],
            [
                'q' => 'پس از ثبت مرجوعی، پول خریدار چه زمانی عودت داده می‌شود؟',
                'a' => 'بلافاصله پس از رسیدن بسته به انبار مرکزی پرادو یدک و تایید سلامت فیزیکی کارتن و کالا، حداکثر ظرف مدت ۲۴ تا ۴۸ ساعت کاری، کل مبلغ فاکتور به شماره شبا یا کارت اعلامی خریدار واریز می‌گردد.'
            ],
            [
                'q' => 'آیا ضمانت اصالت جنیون پارت کتبی است؟',
                'a' => 'بله، عبارت "اصلی جنیون پارت با ضمانت کتبی مرجوعی و اصالت" به صورت صریح در فاکتور رسمی سایت پرادو یدک صادر شده همراه با مهر فروشگاه درج می‌شود.'
            ]
        ];

        $canonicalUrl = Seo::absolute('/terms');

        // یک بلوک JSON-LD یکپارچه (@graph) — بدون گره تکراری
        $schemaMarkup = Seo::graph([
            Seo::organizationNode($settings ?? []),
            Seo::websiteNode($settings ?? []),
            Seo::webPageNode($canonicalUrl, $pageTitle, $metaDescription),
            Seo::breadcrumbNode([
                ['name' => 'صفحه اصلی', 'url' => '/'],
                ['name' => 'قوانین، مقررات و ضمانت اصالت کالا', 'url' => '/terms'],
            ], $canonicalUrl),
            [
                '@type'      => 'FAQPage',
                '@id'        => $canonicalUrl . '#faq',
                'mainEntity' => array_map(static function ($item) {
                    return [
                        '@type'          => 'Question',
                        'name'           => $item['q'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text'  => $item['a'],
                        ],
                    ];
                }, $faqs),
            ],
        ]);

        require_once VIEWS_PATH . '/terms.php';
    }
}