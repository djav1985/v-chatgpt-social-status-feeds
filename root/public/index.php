<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: index.php
 * Description: AI Social Status Generator
 */

require_once '../config.php';
require_once '../autoload.php';
require_once '../vendor/autoload.php';

use App\Core\Router;
use App\Core\ErrorMiddleware;

$secureFlag = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'path'     => '/',
    'httponly' => true,
    'secure'   => $secureFlag,
    'samesite' => 'Lax',
]);
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

ErrorMiddleware::handle(function (): void {
    $router = new Router();
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
});
