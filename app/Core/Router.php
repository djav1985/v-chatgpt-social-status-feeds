<?php
namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH);
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            $pattern = '@^' . preg_replace('@\{([^/]+)\}@', '(?P<$1>[^/]+)', $route) . '$@';
            if (preg_match($pattern, $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array($handler, $params);
                return;
            }
        }
        http_response_code(404);
        echo 'Not Found';
    }
}
