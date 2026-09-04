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
            $route = $this->routes[$method][$uri];

            // === اجرای Middleware ها قبل از ورود به کنترلر ===
            foreach ($route['middleware'] as $mw) {
                // نام کلاس را بر اساس نام داده شده می‌سازیم (مثلا Auth یا Guest)
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
        }

        $this->abort();
    }

    private function defineRoutes()
    {
        // مسیرهای عمومی (بدون محدودیت)
        $this->get('/', 'HomeController@index');
        $this->get('/index', 'HomeController@index');
        $this->get('/parts', 'PartController@index');
        $this->get('/product', 'PartController@show');
        $this->get('/blog', 'BlogController@index');
        $this->get('/blog-detail', 'BlogController@show');
        $this->get('/terms', 'HomeController@terms');
        $this->get('/image', 'ImageController@show');

        // === مسیرهای Guest (فقط کاربران لاگین‌نکرده) ===
        $this->get('/login', 'AuthController@loginForm', ['guest']);
        
        // === مسیرهای Auth (فقط کاربران لاگین‌کرده) ===
        $this->get('/profile', 'UserController@profile', ['auth']);
        $this->get('/checkout', 'OrderController@checkout', ['auth']);

        // مسیرهای API (می‌توانید برای این‌ها هم بعداً middleware تعیین کنید)
        $this->post('/cart/add', 'CartController@add');
        $this->post('/api/auth/check', 'AuthController@checkUser');
        $this->post('/api/auth/login-password', 'AuthController@loginPassword');
        $this->post('/api/auth/send-otp', 'AuthController@sendOtp');
        $this->post('/api/auth/verify-otp', 'AuthController@verifyOtp');
        $this->post('/api/logout', 'AuthController@logout');
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