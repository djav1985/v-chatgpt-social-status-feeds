<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedEncryptionHelperTest.php
 * Description: Shared PHPUnit tests for EncryptionHelper::bytes().
 */

declare(strict_types=1);

use App\Helpers\EncryptionHelper;
use PHPUnit\Framework\TestCase;

final class SharedEncryptionHelperTest extends TestCase
{
    public function testBytesReturnsRequestedLength(): void
    {
        $bytes = EncryptionHelper::bytes(32);

        self::assertSame(32, strlen($bytes));
    }

    public function testBytesThrowsForNonPositiveLength(): void
    {
        $this->expectException(InvalidArgumentException::class);

        EncryptionHelper::bytes(0);
    }
}
