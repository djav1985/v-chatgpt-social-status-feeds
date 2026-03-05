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
    private static ?Router $instance = null;

    /**
     * Builds the route dispatcher and registers application routes.
     */
    private function __construct()
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
     * Returns the shared Router instance.
     */
    public static function getInstance(): Router
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Dispatches the request to the appropriate controller action.
     *
     * The URI is normalised before dispatch: any query string is stripped so
     * that e.g. /home?foo=bar dispatches to /home.
     *
     * If a controller action returns a Response instance the Router emits it
     * via sendResponse(). Actions that handle output themselves (header/echo/exit)
     * continue to work unchanged.
     *
     * @param string $method HTTP method of the incoming request.
     * @param string $uri    The requested URI path (may include query string).
     */
    public function dispatch(string $method, string $uri): void
    {
        // Strip the query string so /path?foo=bar dispatches as /path.
        $route = strtok($uri, '?') ?: $uri;

        $routeInfo = $this->dispatcher->dispatch($method, $route);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                $this->sendResponse((new Response(404))->withView('404'));
                break;

            case Dispatcher::METHOD_NOT_ALLOWED:
                $this->sendResponse(new Response(405));
                break;

            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars    = $routeInfo[2];

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;

                    // Feed routes are publicly accessible; everything else requires auth.
                    // str_starts_with() is available on PHP 8.0+ (project requires ^8.2).
                    $isFeed = str_starts_with($route, '/feeds/');
                    if ($route !== '/login' && !$isFeed) {
                        if (!SessionManager::getInstance()->requireAuth()) {
                            $this->sendResponse(Response::redirect('/login'));
                            return;
                        }
                    }

                    $result = call_user_func_array([new $class(), $action], $vars);
                    if ($result instanceof Response) {
                        $this->sendResponse($result);
                    }
                } elseif (is_callable($handler)) {
                    $result = call_user_func_array($handler, $vars);
                    if ($result instanceof Response) {
                        $this->sendResponse($result);
                    }
                }
                break;
        }
    }

    /**
     * Emit a Response to the client.
     *
     * Handles three output modes in order of priority:
     *  1. View  — requires the named view file and extracts view data into scope.
     *  2. File  — delegates to Response::send() which calls readfile().
     *  3. Body  — delegates to Response::send() which echoes the body string.
     *
     * @param Response $response The response to emit.
     */
    private function sendResponse(Response $response): void
    {
        if ($response->getView() !== null) {
            if (!headers_sent()) {
                http_response_code($response->getStatusCode());

                foreach ($response->getHeaders() as $name => $values) {
                    $replace = true;
                    foreach ($values as $value) {
                        header($name . ': ' . $value, $replace);
                        $replace = false;
                    }
                }
            }

            $data = $response->getViewData();
            extract($data);
            require __DIR__ . '/../Views/' . $response->getView() . '.php';
            return;
        }

        // File streaming and plain body output are both handled by send().
        $response->send();
    }
}
