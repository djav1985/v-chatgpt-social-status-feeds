<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SharedValidationHelperTest.php
 * Description: Shared tests for CSRF validation.
 */

declare(strict_types=1);

namespace Tests;

use App\Core\SessionManager;
use App\Helpers\ValidationHelper;
use PHPUnit\Framework\TestCase;

final class SharedValidationHelperTest extends TestCase
{
    public function testCsrfValidationAcceptsMatchingSessionToken(): void
    {
        SessionManager::getInstance()->set('csrf_token', 'test-token');

        self::assertTrue(ValidationHelper::validateCsrfToken('test-token'));
    }

    public function testCsrfValidationRejectsMismatchedToken(): void
    {
        SessionManager::getInstance()->set('csrf_token', 'test-token');

        self::assertFalse(ValidationHelper::validateCsrfToken('other-token'));
    }

    public function testCsrfValidationRejectsEmptyOrMissingSessionToken(): void
    {
        SessionManager::getInstance()->set('csrf_token', '');
        self::assertFalse(ValidationHelper::validateCsrfToken(''));

        SessionManager::getInstance()->destroy();
        self::assertFalse(ValidationHelper::validateCsrfToken('test-token'));
    }
}
