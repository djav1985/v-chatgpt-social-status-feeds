<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: RequestContext.php
 * Description: Shares parsed PSR-7 request data with controller actions.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\RequestInput;
use Psr\Http\Message\ServerRequestInterface;

/** Gives controller actions consistent access to parsed request and server data. */
trait RequestContext
{
    /** @var array<string, mixed> */
    protected array $requestBody = [];

    /** @var array<string, mixed> */
    protected array $requestServer = [];

    protected function captureRequest(ServerRequestInterface $request): void
    {
        $this->requestBody = RequestInput::parsedBody($request);
        $this->requestServer = RequestInput::serverParams($request);
    }
}
