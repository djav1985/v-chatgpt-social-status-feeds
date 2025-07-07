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

use App\Core\AuthMiddleware;

class Router
{
    public function dispatch(string $uri): void
    {
        $route = strtok($uri, '?');

        if ($route !== '/login') {
            AuthMiddleware::check();
        }

        if (preg_match('#^/feeds(?:/[^/]+/[^/]+)?$#', $route)) {
            \App\Controllers\FeedController::handleRequest();
            return;
        }

        switch ($route) {
        case '/login':
            \App\Controllers\AuthController::handleRequest();
            break;
        case '/accounts':
            \App\Controllers\AccountsController::handleRequest();
            break;
        case '/users':
            \App\Controllers\UsersController::handleRequest();
            break;
        case '/info':
            \App\Controllers\InfoController::handleRequest();
            break;
        case '/':
        case '/home':
        default:
            \App\Controllers\HomeController::handleRequest();
        }
    }
}
