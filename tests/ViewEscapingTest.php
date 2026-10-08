<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Response;
use App\Core\SessionManager;
use PHPUnit\Framework\TestCase;

final class ViewEscapingTest extends TestCase
{
    private array $originalSession;
    private array $originalServer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSession = $_SESSION ?? [];
        $this->originalServer = $_SERVER;
        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/info';
        SessionManager::getInstance()->set('username', 'test-user');
        SessionManager::getInstance()->set('csrf_token', 'test-token');
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->originalSession;
        $_SERVER = $this->originalServer;
        parent::tearDown();
    }

    public function testLoginViewEscapesCsrfToken(): void
    {
        $token = '"><script>alert(1)</script>';
        SessionManager::getInstance()->set('csrf_token', $token);

        $output = $this->renderView('login');

        $this->assertStringContainsString(htmlspecialchars($token, ENT_QUOTES, 'UTF-8'), $output);
        $this->assertStringNotContainsString($token, $output);
    }

    public function testInfoViewEscapesSystemMessageParts(): void
    {
        $message = '<script>alert("xss")</script>';

        $output = $this->renderView('info', [
            'profileData' => '',
            'systemMsg' => [
                'systemMsg' => $message,
                'hasProfile' => false,
            ],
        ]);

        $this->assertStringContainsString(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), $output);
        $this->assertStringNotContainsString($message, $output);
    }

    public function testAccountsSummaryEscapesStats(): void
    {
        $totalAccounts = '<script>alert(1)</script>';

        $output = $this->renderView('accounts', [
            'daysOptions' => '',
            'cronOptions' => '',
            'accountList' => '',
            'calendarOverview' => '',
            'userStats' => ['totalAccounts' => $totalAccounts],
        ]);

        $this->assertStringContainsString(
            htmlspecialchars($totalAccounts, ENT_QUOTES, 'UTF-8'),
            $output
        );
        $this->assertStringNotContainsString($totalAccounts, $output);
    }

    public function testAccountListItemEscapesUserFieldsAndSecuresExternalLink(): void
    {
        $fields = [
            'account' => '<script>account</script>',
            'prompt' => '<script>prompt</script>',
            'days' => '<script>days</script>',
            'times' => '<script>times</script>',
            'link' => 'https://example.test/?q="><script>link</script>',
        ];
        $accountData = (object) ['prompt' => $fields['prompt'], 'link' => $fields['link']];

        $output = $this->renderView('partials/account-list-item', [
            'accountName' => $fields['account'],
            'accountData' => $accountData,
            'daysStr' => $fields['days'],
            'timesStr' => $fields['times'],
            'dataAttributes' => '',
        ]);

        foreach ($fields as $field) {
            $this->assertStringContainsString(htmlspecialchars($field, ENT_QUOTES, 'UTF-8'), $output);
            $this->assertStringNotContainsString($field, $output);
        }
        $this->assertStringContainsString('target="_blank" rel="noopener noreferrer"', $output);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderView(string $view, array $data = []): string
    {
        ob_start();
        try {
            Response::view($view, $data)->send();
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
