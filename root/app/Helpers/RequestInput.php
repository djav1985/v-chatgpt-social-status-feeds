<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: RequestInput.php
 * Description: Safely reads scalar values from PSR-7 requests.
 */

declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ServerRequestInterface;

/** Safely read scalar string values from untrusted request parameter arrays. */
final class RequestInput
{
    /** @param array<string, mixed> $input */
    public static function string(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;
        return is_string($value) ? $value : null;
    }

    /** @return array<string, mixed> */
    public static function parsedBody(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }

    /** @return array<string, mixed> */
    public static function serverParams(ServerRequestInterface $request): array
    {
        return $request->getServerParams();
    }
}
