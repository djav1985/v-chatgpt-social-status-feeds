<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

final class CronCliTest extends TestCase
{
    private function runCronScript(array $args): array
    {
        $scriptPath = __DIR__ . '/../root/cron.php';
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($scriptPath)
            . ' ' . implode(' ', array_map('escapeshellarg', $args));

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

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

    public function testCronRejectsOldTargets(): void
    {
        $oldTargets = ['hourly'];

        foreach ($oldTargets as $target) {
            $result = $this->runCronScript([$target]);

            $this->assertEquals(1, $result['exitCode']);
            $this->assertStringContainsString('Usage:', $result['output']);
        }
    }

    public function testWorkerCommandHonorsExistingLock(): void
    {
        $this->assertWorkerLockBlocksLaunch('run-queue');
    }

    public function testWorkerCommandHonorsExistingLockForOtherTasks(): void
    {
        $this->assertWorkerLockBlocksLaunch('daily');
    }

    public function testWorkerCommandAllowsDifferentJobsToRunIndependently(): void
    {
        $this->withHeldWorkerLock('fill-queue', function (): void {
            $result = $this->runCronScript(['worker', 'run-queue']);

            $this->assertSame(0, $result['exitCode']);
            $this->assertStringNotContainsString('already running', $result['output']);
        });
    }

    private function assertWorkerLockBlocksLaunch(string $jobType): void
    {
        $this->withHeldWorkerLock($jobType, function () use ($jobType): void {
            $result = $this->runCronScript(['worker', $jobType]);

            $this->assertSame(0, $result['exitCode']);
            $this->assertStringContainsString(
                sprintf('Worker "%s" already running.', $jobType),
                $result['output']
            );
        });
    }

    private function withHeldWorkerLock(string $jobType, callable $test): void
    {
        $lockPath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'socialrss-worker-' . $jobType . '.lock';
        $fileExisted = file_exists($lockPath);
        $handle = fopen($lockPath, 'c+');
        $this->assertIsResource($handle);

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            $this->markTestSkipped(sprintf('Worker lock "%s" is already held.', $jobType));
        }

        rewind($handle);
        $originalContents = stream_get_contents($handle);

        try {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) getmypid());
            fflush($handle);
            $test();
        } finally {
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) $originalContents);
            fflush($handle);
            flock($handle, LOCK_UN);
            fclose($handle);

            if (!$fileExisted) {
                unlink($lockPath);
            }
        }
    }
}
