<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusesRequestTest.php
 * Description: Statuses-specific tests for request defaults.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class StatusesRequestTest extends TestCase
{
    public function testFromGlobalsUsesRootPathWhenRequestUriIsMissing(): void
    {
        $originalServer = $_SERVER;

        try {
            unset($_SERVER['REQUEST_URI']);
            $_SERVER['REQUEST_METHOD'] = 'GET';

            $request = Request::fromGlobals();

            self::assertSame('GET', $request->getMethod());
            self::assertSame('/', $request->getUri()->getPath());
        } finally {
            $_SERVER = $originalServer;
        }
    }
}
