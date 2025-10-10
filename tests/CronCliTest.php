<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Exception;

final class CronCliTest extends TestCase
{
    private function runCronScript(array $args, array $extraEnv = []): array
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

        $env = array_merge($env, $extraEnv);

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
        $this->assertStringContainsString('Usage:', $result['output']);
        $this->assertStringContainsString('run-queue', $result['output']);
        $this->assertStringContainsString('fill-queue', $result['output']);
        $this->assertStringContainsString('daily', $result['output']);
        $this->assertStringContainsString('monthly', $result['output']);
        $this->assertStringContainsString('worker', $result['output']);
    }

    public function testCronShowsHelpForInvalidArgument(): void
    {
        $result = $this->runCronScript(['invalid-command']);

        $this->assertEquals(1, $result['exitCode']);
        $this->assertStringContainsString('Usage:', $result['output']);
    }

    public function testWorkerCommandRequiresTask(): void
    {
        $result = $this->runCronScript(['worker']);

        $this->assertEquals(1, $result['exitCode']);
        $this->assertStringContainsString('Usage:', $result['output']);
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
        $oldTargets = ['hourly'];

        foreach ($oldTargets as $target) {
            $result = $this->runCronScript([$target]);

            $this->assertEquals(1, $result['exitCode']);
            $this->assertStringContainsString('Usage:', $result['output']);
        }
    }

    public function testWorkerLaunchesRunQueueWhenNoExistingLock(): void
    {
        $lockPath = $this->queueWorkerLockPath();
        if (file_exists($lockPath)) {
            @unlink($lockPath);
        }

        $result = $this->runCronScript(['worker', 'run-queue'], ['CRON_DISABLE_WORKER_SPAWN' => '1']);

        $this->assertEquals(0, $result['exitCode']);
        $this->assertStringNotContainsString('Usage: php cron.php', $result['output']);
        $this->assertStringNotContainsString('Queue worker already running.', $result['output']);
        $this->assertFileExists($lockPath, 'Worker invocation should create a lock file.');

        @unlink($lockPath);
    }

    public function testWorkerCommandHonorsExistingLock(): void
    {
        $lockPath = $this->queueWorkerLockPath();
        file_put_contents($lockPath, (string) getmypid());

        $result = $this->runCronScript(['worker', 'run-queue'], ['CRON_DISABLE_WORKER_SPAWN' => '1']);

        $this->assertEquals(0, $result['exitCode']);
        $this->assertStringContainsString('Queue worker already running.', $result['output']);

        @unlink($lockPath);
    }

    public function testLegacyOnceFlagIsIgnored(): void
    {
        $result = $this->runCronScript(['run-queue', '--once']);

        $this->assertStringNotContainsString('Usage: php cron.php', $result['output']);
    }

    private function queueWorkerLockPath(): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'socialrss-queue-worker.lock';
    }
}
