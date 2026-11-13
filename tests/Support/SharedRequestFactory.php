<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedRequestFactory.php
 * Description: Shared test-only PSR-7 request factory.
 */

declare(strict_types=1);

namespace Tests\Support;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/** Creates PSR-7 requests for tests without adding test conveniences to application code. */
final class SharedRequestFactory
{
    /** @param array<string, mixed> $query
     *  @param array<string, mixed> $parsedBody
     *  @param array<string, mixed> $server
     *  @param array<string, mixed> $cookies
     *  @param array<string, mixed> $uploadedFiles
     */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        array $parsedBody = [],
        array $server = [],
        array $cookies = [],
        array $uploadedFiles = []
    ): ServerRequestInterface {
        return (new ServerRequest($method, $path, [], null, '1.1', $server))
            ->withQueryParams($query)
            ->withParsedBody($parsedBody)
            ->withCookieParams($cookies)
            ->withUploadedFiles($uploadedFiles);
    }
}
