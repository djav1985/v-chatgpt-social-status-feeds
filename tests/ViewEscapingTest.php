<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class ViewEscapingTest extends TestCase
{
    public function testLoginViewEscapesCsrfToken(): void
    {
        $view = file_get_contents(__DIR__ . '/../root/app/Views/login.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString('csrf_token', $view);
        $this->assertStringContainsString('htmlspecialchars', $view);
        $this->assertStringContainsString('ENT_QUOTES', $view);
    }

    public function testInfoViewEscapesSystemMessageParts(): void
    {
        $view = file_get_contents(__DIR__ . '/../root/app/Views/info.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString('htmlspecialchars($systemMsg', $view);
        $this->assertStringContainsString('ENT_QUOTES', $view);
    }

    public function testAccountsSummaryEscapesStats(): void
    {
        $view = file_get_contents(__DIR__ . '/../root/app/Views/accounts.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString('htmlspecialchars((string) $totalAccounts', $view);
        $this->assertStringContainsString('ENT_QUOTES', $view);
    }

    public function testAccountListItemEscapesUserFields(): void
    {
        $view = file_get_contents(__DIR__ . '/../root/app/Views/partials/account-list-item.php');

        $this->assertNotFalse($view);
        $this->assertStringContainsString('htmlspecialchars($accountName, ENT_QUOTES', $view);
        $this->assertStringContainsString('htmlspecialchars($accountData->prompt, ENT_QUOTES', $view);
        $this->assertStringContainsString('htmlspecialchars($daysStr, ENT_QUOTES', $view);
        $this->assertStringContainsString('htmlspecialchars($timesStr, ENT_QUOTES', $view);
        $this->assertStringContainsString('htmlspecialchars($accountData->link, ENT_QUOTES', $view);
    }

    public function testAccountListItemUsesSecureTargetBlank(): void
    {
        $view = file_get_contents(__DIR__ . '/../root/app/Views/partials/account-list-item.php');

        $this->assertNotFalse($view);
        // Verify that links with target="_blank" also have rel="noopener noreferrer"
        $this->assertMatchesRegularExpression(
            '/target="_blank"\s+rel="noopener noreferrer"/',
            $view,
            'Links with target="_blank" must include rel="noopener noreferrer" to prevent reverse-tabnabbing'
        );
    }
}
