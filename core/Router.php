<?php
namespace Core;

class Router
{
    protected $routes = [];

    public function get($uri, $controller)
    {
        $this->routes['GET'][$uri] = $controller;
    }

    public function post($uri, $controller)
    {
        $this->routes['POST'][$uri] = $controller;
    }

    public function dispatch($uri)
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $this->defineRoutes();

        if (array_key_exists($uri, $this->routes[$method])) {
            $controllerAction = $this->routes[$method][$uri];

            list($controller, $action) = explode('@', $controllerAction);

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
        // === مسیرهای صفحات اصلی سایت ===
        $this->get('/', 'HomeController@index');
        $this->get('/index', 'HomeController@index');
        $this->get('/parts', 'PartController@index');
        $this->get('/product', 'PartController@show');

        $this->get('/blog', 'BlogController@index');
        $this->get('/blog-detail', 'BlogController@show');

        $this->get('/terms', 'HomeController@terms');
        $this->get('/login', 'AuthController@loginForm');
        $this->get('/profile', 'UserController@profile');
        $this->get('/checkout', 'OrderController@checkout');
        $this->post('/cart/add', 'CartController@add');

        // === مسیر اختصاصی کش و نمایش تصاویر تلگرام ===
        $this->get('/image', 'ImageController@show');

        // === مسیرهای API سیستم احراز هویت یکپارچه (ورود/ثبت‌نام) ===
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