<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Request.php
 * Description: Simple HTTP request representation.
 */

namespace App\Core;

class Request
{
    /**
     * @param string               $method HTTP method.
     * @param string               $path   Request path (without query string).
     * @param array<string, mixed> $get    Query string parameters.
     * @param array<string, mixed> $post   POST body parameters.
     * @param array<string, mixed> $server Server variables.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $get = [],
        public readonly array $post = [],
        public readonly array $server = [],
    ) {
    }

    /**
     * Build a Request from PHP globals.
     */
    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        if (!is_string($path)) {
            $path = '/';
        }

        return new self($_SERVER['REQUEST_METHOD'] ?? 'GET', $path, $_GET, $_POST, $_SERVER);
    }
}
