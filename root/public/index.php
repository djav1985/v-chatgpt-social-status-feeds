<?php

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

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $staticExtensions = ['css', 'js', 'mjs', 'map', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'eot'];

    if (is_string($requestPath) && in_array(strtolower(pathinfo($requestPath, PATHINFO_EXTENSION)), $staticExtensions, true)) {
        $publicRoot = realpath(__DIR__);
        $requestedFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . ltrim(rawurldecode($requestPath), '/\\'));
        $publicRootPrefix = $publicRoot === false ? '' : rtrim($publicRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (
            $publicRootPrefix !== ''
            && $requestedFile !== false
            && is_file($requestedFile)
            && str_starts_with($requestedFile, $publicRootPrefix)
        ) {
            return false;
        }
    }
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\ErrorManager;
use App\Core\Request;
use App\Core\Router;

ErrorManager::handle(function (): void {
    // Build router
    $router = new Router();

    // Dispatch request through router
    $request = Request::fromGlobals();
    $response = $router->dispatch($request);
    $response->send();
});
