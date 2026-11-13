<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedRequestInputTest.php
 * Description: Shared tests for safe request input parsing.
 */

declare(strict_types=1);

namespace Tests;

use App\Helpers\RequestInput;
use PHPUnit\Framework\TestCase;

final class SharedRequestInputTest extends TestCase
{
    public function testStringInputAcceptsStringValues(): void
    {
        self::assertSame('example.com', RequestInput::string(['domain' => 'example.com'], 'domain'));
    }

    public function testStringInputReturnsNullForMissingValues(): void
    {
        self::assertNull(RequestInput::string([], 'domain'));
    }

    public function testStringInputRejectsNonStringValuesWithoutCoercion(): void
    {
        self::assertNull(RequestInput::string(['domain' => ['example.com']], 'domain'));
        self::assertNull(RequestInput::string(['domain' => 123], 'domain'));
    }
}
