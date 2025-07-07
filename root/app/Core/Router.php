<?php

namespace App\Core;

/**
 * Minimal router with GET and POST route handling.
 */
class Router
{
    /**
     * @var array<string,array<string,callable>>
     */
    private array $routes = ['GET' => [], 'POST' => []];

    /**
     * Register a GET route.
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Register a POST route.
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Dispatch the request to the matching route.
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $route => $handler) {
            $pattern = '#^' . preg_replace('#\{[^/]+\}#', '([^/]+)', $route) . '$#';
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);

                if ($route !== '/login') {
                    AuthMiddleware::check();
                }

                call_user_func_array($handler, $matches);
                return;
            }
        }

        http_response_code(404);
        echo '404 Not Found';
    }
}
