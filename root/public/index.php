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
