<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Router.php
 * Description: AI Social Status Generator
 */

namespace App\Core;

use FastRoute\RouteCollector;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            // Redirect the root URL to the home page for convenience
            $r->addRoute('GET', '/', function (): void {
                header('Location: /home');
                exit();
            });

            $r->addRoute('GET', '/login', [\App\Controllers\AuthController::class, 'handleRequest']);
            $r->addRoute('GET', '/accounts', [\App\Controllers\AccountsController::class, 'handleRequest']);
            $r->addRoute('GET', '/users', [\App\Controllers\UsersController::class, 'handleRequest']);
            $r->addRoute('GET', '/info', [\App\Controllers\InfoController::class, 'handleRequest']);
            $r->addRoute('GET', '/home', [\App\Controllers\HomeController::class, 'handleRequest']);

            // Feed routes
            $r->addRoute('GET', '/feeds/{user}/{account}', [\App\Controllers\FeedController::class, 'index']);
        });
    }

    public function dispatch(string $method, string $uri): void
    {
        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                header('HTTP/1.0 404 Not Found');
                require __DIR__ . '/../Views/404.php';
                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                header('HTTP/1.0 405 Method Not Allowed');
                break;
            case Dispatcher::FOUND:
                [$class, $action] = $routeInfo[1];
                $vars = $routeInfo[2];
                if ($uri !== '/login') {
                    AuthMiddleware::check();
                }
                call_user_func_array([new $class(), $action], $vars);
                break;
        }
    }
}
