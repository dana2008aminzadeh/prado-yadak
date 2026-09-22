<?php
namespace Core;

class Router
{
    protected $routes = [];

    // اضافه شدن پارامتر سوم برای پذیرش Middleware
    public function get($uri, $controller, $middleware = [])
    {
        $this->routes['GET'][$uri] = [
            'controller' => $controller,
            'middleware' => $middleware
        ];
    }

    public function post($uri, $controller, $middleware = [])
    {
        $this->routes['POST'][$uri] = [
            'controller' => $controller,
            'middleware' => $middleware
        ];
    }

    public function dispatch($uri)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $this->defineRoutes();

        if (array_key_exists($uri, $this->routes[$method])) {
            $this->executeRoute($this->routes[$method][$uri]);
            return;
        }

        foreach ($this->routes[$method] as $routeUri => $route) {
            if (strpos($routeUri, '{') !== false) {
                // نقطه و ممیز مجاز شد تا آدرس تصاویر سئوشده (مثلا نام-قطعه--id.jpg) هم بخورد
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_\-\.\x{0600}-\x{06FF}\s%]+)', $routeUri);
                $pattern = "@^" . $pattern . "$@u";

                if (preg_match($pattern, urldecode((string) $uri), $matches)) {
                    foreach ($matches as $key => $match) {
                        if (is_string($key)) {
                            $_GET[$key] = trim($match);
                        }
                    }
                    $this->executeRoute($route);
                    return;
                }
            }
        }

        // ------------------------------------------------------------------
        // میان‌افزار سئو: پیش از نمایش ۴۰۴، بررسی کن آیا این آدرس قبلاً
        // تغییر کرده است؛ در این صورت با ۳۰۱ دائمی به آدرس جدید منتقل شود.
        // ------------------------------------------------------------------
        if ($method === 'GET') {
            \App\models\Redirect::handle((string) $uri, (string) ($_SERVER['QUERY_STRING'] ?? ''));
            \App\models\Redirect::log404((string) $uri);
        }

        $this->abort();
    }

    private function executeRoute($route)
    {
        foreach ($route['middleware'] as $mw) {
            $middlewareClass = "Core\\Middleware\\" . ucfirst($mw);
            if (class_exists($middlewareClass)) {
                (new $middlewareClass)->handle();
            }
        }
        list($controller, $action) = explode('@', $route['controller']);
        $controllerClass = "App\\controllers\\" . $controller;
        if (class_exists($controllerClass)) {
            $controllerInstance = new $controllerClass();
            if (method_exists($controllerInstance, $action)) {
                return $controllerInstance->$action();
            }
        }
        $this->abort();
    }

    private function defineRoutes()
    {
        // صفحات اصلی
        $this->get('/', 'HomeController@index');
        $this->get('/terms', 'HomeController@terms');
        $this->get('/image', 'ImageController@show');

        // سرو تصاویر با آدرس سئوشده (به‌جای شناسه هش‌شده بی‌معنی)
        $this->get('/media/{file}', 'ImageController@seo');

        // ---- نقشه سایت پویا و شاخه‌ای ----
        $this->get('/sitemap.xml', 'SitemapController@index');
        $this->get('/sitemap-static.xml', 'SitemapController@statics');
        $this->get('/sitemap-products.xml', 'SitemapController@products');
        $this->get('/sitemap-categories.xml', 'SitemapController@categories');
        $this->get('/sitemap-models.xml', 'SitemapController@models');
        $this->get('/sitemap-brands.xml', 'SitemapController@brands');
        $this->get('/sitemap-landing.xml', 'SitemapController@landing');
        $this->get('/sitemap-articles.xml', 'SitemapController@articles');
        $this->get('/sitemap-images.xml', 'SitemapController@images');

        // کاتالوگ قطعات و محصولات
        $this->get('/parts', 'PartController@index');
        // لندینگ‌پیج‌های اختصاصی سئو با آدرس تمیز (مثلا /parts/لوازم-یدکی-کمری-لنت-ترمز)
        $this->get('/parts/{landing}', 'PartController@landing');
        $this->get('/product', 'PartController@show');
        $this->get('/product/{slug}', 'PartController@show');

        // وبلاگ و دانشنامه فنی (روت‌های تکراری حذف و استاندارد شدند)
        $this->get('/blog', 'BlogController@index');
        $this->get('/blog/{slug}', 'BlogController@show');
        $this->get('/blog-detail', 'BlogController@show');

        // سبد خرید
        $this->post('/cart/add', 'CartController@add');
        $this->post('/api/cart/sync', 'CartController@sync');
        $this->get('/api/cart/get', 'CartController@get');

        // احراز هویت و دسترسی کاربر
        $this->get('/login', 'AuthController@loginForm', ['guest']);
        $this->post('/api/auth/check', 'AuthController@checkUser');
        $this->post('/api/auth/login-password', 'AuthController@loginPassword');
        $this->post('/api/auth/send-otp', 'AuthController@sendOtp');
        $this->post('/api/auth/verify-otp', 'AuthController@verifyOtp');
        $this->post('/api/logout', 'AuthController@logout');

        // تسویه حساب و ثبت فاکتور
        $this->get('/checkout', 'OrderController@checkout', ['auth']);
        $this->post('/checkout/process', 'OrderController@processCheckout', ['auth']);
        $this->post('/api/checkout/validate-coupon', 'OrderController@apiValidateCoupon', ['auth']);
        $this->get('/order/success', 'OrderController@orderSuccess', ['auth']);

        // وب‌سرویس‌ها و APIهای عمومی
        $this->get('/api/parts', 'PartController@apiList');
        $this->get('/api/product', 'PartController@apiShow');
        $this->post('/api/submit-comment', 'PartController@submitComment');
        $this->post('/api/track-order', 'OrderController@trackOrder');
        $this->get('/api/locations/provinces', 'LocationController@provinces');
        $this->get('/api/locations/cities', 'LocationController@cities');

        $this->get('/profile', 'UserController@profile', ['auth']);
        $this->post('/api/profile/update', 'UserController@apiUpdateProfile', ['auth']);
        $this->post('/api/profile/change-password', 'UserController@apiChangePassword', ['auth']);

        // ---- API سفارش‌ها ----
        $this->get('/api/profile/orders', 'UserController@apiOrders', ['auth']);
        $this->get('/api/profile/orders/detail', 'UserController@apiOrderDetail', ['auth']);

        // ---- API آدرس‌ها ----
        $this->get('/api/profile/addresses', 'UserController@apiAddresses', ['auth']);
        $this->post('/api/profile/addresses/create', 'UserController@apiAddressCreate', ['auth']);
        $this->post('/api/profile/addresses/update', 'UserController@apiAddressUpdate', ['auth']);
        $this->post('/api/profile/addresses/delete', 'UserController@apiAddressDelete', ['auth']);
        $this->post('/api/profile/addresses/set-default', 'UserController@apiAddressSetDefault', ['auth']);

        // ---- API خودروها ----
        $this->get('/api/profile/vehicles', 'UserController@apiVehicles', ['auth']);
        $this->post('/api/profile/vehicles/create', 'UserController@apiVehicleCreate', ['auth']);
        $this->post('/api/profile/vehicles/update', 'UserController@apiVehicleUpdate', ['auth']);
        $this->post('/api/profile/vehicles/delete', 'UserController@apiVehicleDelete', ['auth']);
        $this->post('/api/profile/vehicles/set-primary', 'UserController@apiVehicleSetPrimary', ['auth']);

        // ---- API نشان‌شده‌ها ----
        $this->get('/api/profile/wishlist', 'UserController@apiWishlist', ['auth']);
        $this->post('/api/profile/wishlist/toggle', 'UserController@apiWishlistToggle', ['auth']);
        $this->post('/api/profile/wishlist/remove', 'UserController@apiWishlistRemove', ['auth']);

        // ---- API کیف پول ----
        $this->get('/api/profile/wallet', 'UserController@apiWallet', ['auth']);
        $this->post('/api/profile/wallet/charge', 'UserController@apiWalletCharge', ['auth']);

        // ---- API تیکت پشتیبانی ----
        $this->get('/api/profile/tickets', 'UserController@apiTickets', ['auth']);
        $this->get('/api/profile/tickets/detail', 'UserController@apiTicketDetail', ['auth']);
        $this->post('/api/profile/tickets/create', 'UserController@apiTicketCreate', ['auth']);
        $this->post('/api/profile/tickets/reply', 'UserController@apiTicketReply', ['auth']);
        $this->post('/api/profile/tickets/close', 'UserController@apiTicketClose', ['auth']);
    }

    private function abort($code = 404)
    {
        http_response_code($code);
        $file = VIEWS_PATH . "/{$code}.php";
        if (file_exists($file)) {
            require_once $file;
        } else {
            echo "خطای {$code} - صفحه پیدا نشد!";
        }
        die();
    }
}