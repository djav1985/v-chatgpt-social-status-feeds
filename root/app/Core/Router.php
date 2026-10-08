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
use App\Models\BlacklistModel;
use function FastRoute\simpleDispatcher;

class Router
{
    private Dispatcher $dispatcher;

    /**
     * Builds the route dispatcher and registers application routes.
     */
    public function __construct()
    {
        $this->dispatcher = simpleDispatcher(function (RouteCollector $r): void {
            // Redirect the root URL to the home page for convenience
            $r->addRoute('GET', '/', fn() => Response::redirect('/home'));

            // Register routes for GET and POST requests separately
            $r->addRoute('GET', '/login', [\App\Controllers\LoginController::class, 'handleRequest']);
            $r->addRoute('POST', '/login', [\App\Controllers\LoginController::class, 'handleSubmission']);

            $r->addRoute('GET', '/accounts', [\App\Controllers\AccountsController::class, 'handleRequest']);
            $r->addRoute('POST', '/accounts', [\App\Controllers\AccountsController::class, 'handleSubmission']);

            $r->addRoute('GET', '/users', [\App\Controllers\UsersController::class, 'handleRequest']);
            $r->addRoute('POST', '/users', [\App\Controllers\UsersController::class, 'handleSubmission']);

            $r->addRoute('GET', '/info', [\App\Controllers\InfoController::class, 'handleRequest']);
            $r->addRoute('POST', '/info', [\App\Controllers\InfoController::class, 'handleSubmission']);

            $r->addRoute('GET', '/home', [\App\Controllers\HomeController::class, 'handleRequest']);
            $r->addRoute('POST', '/home', [\App\Controllers\HomeController::class, 'handleSubmission']);

            // Feed routes
            $r->addRoute('GET', '/feeds/{user}/{account}', [\App\Controllers\FeedController::class, 'handleRequest']);
        });
    }

    /**
     * Dispatches the request to the appropriate controller action and returns the Response.
     *
     * Actions that handle output themselves (header/echo/exit) and return nothing
     * yield an empty 200 Response.
     *
     * @param Request $request The incoming request.
     */
    public function dispatch(Request $request): Response
    {
        if (BlacklistModel::isCurrentRequestIpBlacklisted()) {
            return new Response(403);
        }

        SessionManager::getInstance()->ensureCsrfToken();

        $route = $request->path;

        $routeInfo = $this->dispatcher->dispatch($request->method, $route);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return Response::view('404', [], 404);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return new Response(405);

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;

                    // Feed routes are publicly accessible; everything else requires auth.
                    $isFeed = str_starts_with($route, '/feeds/');
                    if ($route !== '/login' && !$isFeed && !SessionManager::getInstance()->requireAuth()) {
                        return Response::redirect('/login');
                    }

                    $result = call_user_func_array([new $class(), $action], $vars);
                } elseif (is_callable($handler)) {
                    $result = call_user_func_array($handler, $vars);
                } else {
                    $result = null;
                }

                return $result instanceof Response ? $result : new Response();
        }

        return new Response(500);
    }
}
