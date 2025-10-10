<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Exception;

final class CronCliTest extends TestCase
{
    private function runCronScript(array $args): array
    {
        $scriptPath = __DIR__ . '/../root/cron.php';
        $cmd = 'php ' . escapeshellarg($scriptPath) . ' ' . implode(' ', array_map('escapeshellarg', $args));

        // Set minimal required env vars to avoid config errors
        $env = [
            'DB_HOST' => 'localhost',
            'DB_USER' => 'test',
            'DB_NAME' => 'test',
            'API_KEY' => 'test-key'
        ];

        // Set environment variables for the current process
        foreach ($env as $key => $value) {
            putenv("$key=$value");
        }

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        // Clean up environment variables
        foreach ($env as $key => $value) {
            putenv($key);
        }

        return [
            'output' => implode("\n", $output),
            'exitCode' => $exitCode
        ];
    }

    public function testCronShowsHelpForNoArguments(): void
    {
        $result = $this->runCronScript([]);

        $this->assertEquals(1, $result['exitCode']);
        $this->assertStringContainsString('Usage: php cron.php', $result['output']);
        $this->assertStringContainsString('run-queue', $result['output']);
        $this->assertStringContainsString('fill-queue', $result['output']);
        $this->assertStringContainsString('daily', $result['output']);
        $this->assertStringContainsString('monthly', $result['output']);
    }

    public function testCronShowsHelpForInvalidArgument(): void
    {
        $result = $this->runCronScript(['invalid-command']);

        $this->assertEquals(1, $result['exitCode']);
        $this->assertStringContainsString('Usage: php cron.php', $result['output']);
    }

    public function testCronAcceptsValidTargets(): void
    {
        $validTargets = ['run-queue', 'fill-queue', 'daily', 'monthly'];

        foreach ($validTargets as $target) {
            $result = $this->runCronScript([$target]);

            // We expect it to run but may fail due to missing DB/config
            // The important thing is it doesn't show the usage help
            $this->assertStringNotContainsString('Usage: php cron.php', $result['output']);
        }
    }

    public function testCronRejectsOldTargets(): void
    {
        $oldTargets = ['hourly', 'worker'];

        foreach ($oldTargets as $target) {
            $result = $this->runCronScript([$target]);

            $this->assertEquals(1, $result['exitCode']);
            $this->assertStringContainsString('Usage: php cron.php', $result['output']);
        }
    }

    public function testLegacyOnceFlagIsIgnored(): void
    {
        $result = $this->runCronScript(['run-queue', '--once']);

        $this->assertStringNotContainsString('Usage: php cron.php', $result['output']);
    }
}
