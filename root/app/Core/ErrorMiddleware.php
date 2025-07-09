<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: ErrorMiddleware.php
 * Description: AI Social Status Generator
 */

namespace App\Core;

use Throwable;

class ErrorMiddleware
{
    /**
     * Register the error handlers and execute the callback.
     *
     * @param callable $callback Code to execute within the middleware.
     * @return void
     */
    public static function handle(callable $callback): void
    {
        ErrorHandler::register();

        try {
            $callback();
        } catch (Throwable $exception) {
            ErrorHandler::handleException($exception);
        }
    }
}
