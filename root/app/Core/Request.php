<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Request.php
 * Description: PSR-7 HTTP request factory.
 */

declare(strict_types=1);

namespace App\Core;

use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

/** Factory for PSR-7 server requests. */
final class Request
{
    public static function fromGlobals(): ServerRequestInterface
    {
        $request = ServerRequest::fromGlobals();
        if ($request->getUri()->getPath() === '') {
            return $request->withUri($request->getUri()->withPath('/'));
        }

        return $request;
    }
}
