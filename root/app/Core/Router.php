<?php

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

use App\Helpers\MessageHelper;
use App\Helpers\RequestInput;
use App\Helpers\ValidationHelper;
use App\Models\BlacklistModel;
use FastRoute\ConfigureRoutes;
use FastRoute\Dispatcher;
use FastRoute\FastRoute;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Router
{
    private Dispatcher $dispatcher;

    public function __construct()
    {
        $fastRoute = FastRoute::recommendedSettings(function (ConfigureRoutes $r): void {
            $r->addRoute('GET', '/', static function (ServerRequestInterface $request): Response {
                return Response::redirect('/home');
            });

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
            $r->addRoute('GET', '/feeds/{user}/{account}', [\App\Controllers\FeedController::class, 'handleRequest']);
        }, 'app_routes')->disableCache();

        $this->dispatcher = $fastRoute->dispatcher();
    }

    public function dispatch(ServerRequestInterface $request): Response
    {
        $path = $request->getUri()->getPath() ?: '/';
        $server = $request->getServerParams();
        ErrorManager::logRequest($request->getMethod(), $path, [
            'ip' => RequestInput::string($server, 'REMOTE_ADDR') ?? 'unknown',
        ]);

        return $this->dispatchRoute($request);
    }

    private function dispatchRoute(ServerRequestInterface $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath() ?: '/';

        $blacklistResponse = $this->guardBlacklist($request);
        if ($blacklistResponse !== null) {
            return $this->logAndReturn($method, $path, $blacklistResponse);
        }

        $routeInfo = $this->dispatcher->dispatch($method, $path);
        if ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            return $this->logAndReturn($method, $path, Response::view('404', [], 404));
        }

        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->logAndReturn($method, $path, new Response(405));
        }

        $guard = $this->guard($request);
        if ($guard !== null) {
            return $this->logAndReturn($method, $path, $guard);
        }

        $response = $this->invokeHandler($routeInfo[1], $routeInfo[2], $request);
        return $this->logAndReturn($method, $path, $response->withViewData(['requestPath' => $path]));
    }

    private function guardBlacklist(ServerRequestInterface $request): ?Response
    {
        $server = $request->getServerParams();
        $ip = filter_var(RequestInput::string($server, 'REMOTE_ADDR') ?? '', FILTER_VALIDATE_IP);
        if ($ip !== false && BlacklistModel::isBlacklisted($ip)) {
            return new Response(403);
        }

        return null;
    }

    private function guard(ServerRequestInterface $request): ?Response
    {
        $server = $request->getServerParams();

        $session = SessionManager::getInstance();
        $session->ensureCsrfToken();

        $path = $request->getUri()->getPath() ?: '/';
        $isFeed = str_starts_with($path, '/feeds/');
        $userAgent = RequestInput::string($server, 'HTTP_USER_AGENT') ?? '';
        if ($path !== '/login' && $path !== '/' && !$isFeed && !$session->requireAuth($userAgent)) {
            return Response::redirect('/login');
        }

        if (!in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $post = RequestInput::parsedBody($request);
            $token = RequestInput::string($post, 'csrf_token') ?? '';
            if (!ValidationHelper::validateCsrfToken($token)) {
                MessageHelper::addMessage('Invalid CSRF token. Please try again.');
                return Response::redirect($path);
            }
        }

        return null;
    }

    /**
     * @param mixed $handler
     * @param array<string, string> $vars
     */
    private function invokeHandler(mixed $handler, array $vars, ServerRequestInterface $request): Response
    {
        $arguments = array_values($vars);
        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            $controller = new $class();
            $response = $controller->{$action}($request, ...$arguments);
            return $this->normalizeResponse($response, 'Controller action must return a ResponseInterface');
        }

        if (is_callable($handler)) {
            $response = $handler($request, ...$arguments);
            return $this->normalizeResponse($response, 'Route callback must return a ResponseInterface');
        }

        throw new \RuntimeException('Invalid route handler');
    }

    private function normalizeResponse(mixed $response, string $errorMessage): Response
    {
        if (!$response instanceof ResponseInterface) {
            throw new \RuntimeException($errorMessage);
        }

        return $response instanceof Response
            ? $response
            : new Response(
                $response->getStatusCode(),
                $response->getHeaders(),
                $response->getBody(),
                $response->getReasonPhrase()
            );
    }

    private function logAndReturn(string $method, string $path, Response $response): Response
    {
        ErrorManager::logResponse($method, $path, $response->getStatusCode());
        return $response;
    }
}
