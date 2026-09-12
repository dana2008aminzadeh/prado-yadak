<?php
namespace App\controllers;

class HomeController extends Controller
{
    public function index()
    {
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

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'pradoyadak.com';
        $hostUrl = $protocol . "://" . $host;

        $faqSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(function ($item) {
                return [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['a']
                    ]
                ];
            }, $faqs)
        ];

        $breadcrumbSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'صفحه اصلی',
                    'item' => $hostUrl . '/'
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'قوانین، مقررات و ضمانت اصالت کالا',
                    'item' => $hostUrl . '/terms'
                ]
            ]
        ];

        $schemaMarkup = "<script type=\"application/ld+json\">\n" . json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>\n";
        $schemaMarkup .= "<script type=\"application/ld+json\">\n" . json_encode($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "\n</script>";

        require_once VIEWS_PATH . '/terms.php';
    }
}