<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: HeaderViewTest.php
 * Description: Shared header rendering tests.
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class HeaderViewTest extends TestCase
{
    public function testMobileMenuToggleNamesTheActiveSectionAndShowsAnExpandIndicator(): void
    {
        $sessionWasActive = session_status() === PHP_SESSION_ACTIVE;
        $originalSession = $_SESSION ?? [];
        $session = \App\Core\SessionManager::getInstance();
        $session->set('username', 'demo');
        $session->set('csrf_token', 'csrf');
        $requestPath = '/accounts';

        ob_start();
        try {
            require dirname(__DIR__) . '/root/app/Views/layouts/header.php';
            $output = (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        } finally {
            if (!$sessionWasActive && session_status() === PHP_SESSION_ACTIVE) {
                session_abort();
            }
            $_SESSION = $originalSession;
        }

        self::assertStringContainsString('data-menu-section="Accts"', $output);
        self::assertStringContainsString('Open Accts navigation menu', $output);
        self::assertStringContainsString('aria-expanded="false"', $output);
        self::assertStringContainsString('class="mobile-menu-chevron"', $output);
        self::assertStringContainsString('>Accts</span>', $output);
    }
}
