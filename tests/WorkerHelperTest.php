<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Helpers\WorkerHelper;

final class WorkerHelperTest extends TestCase
{
    private string $testJobType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testJobType = 'test-worker-' . bin2hex(random_bytes(8));
        $this->cleanupLockFile();
    }

    protected function tearDown(): void
    {
        $this->cleanupLockFile();
        parent::tearDown();
    }

    private function cleanupLockFile(): void
    {
        $lockPath = WorkerHelper::getLockPath($this->testJobType);
        if (file_exists($lockPath)) {
            @unlink($lockPath);
        }
    }

    public function testGetLockPathReturnsCorrectPath(): void
    {
        $lockPath = WorkerHelper::getLockPath('run-queue');

        $this->assertStringContainsString('socialrss-worker-run-queue.lock', $lockPath);
        $this->assertStringStartsWith(sys_get_temp_dir(), $lockPath);
    }

    public function testGetLockPathReturnsDifferentPathsForDifferentJobTypes(): void
    {
        $runQueuePath = WorkerHelper::getLockPath('run-queue');
        $fillQueuePath = WorkerHelper::getLockPath('fill-queue');
        $dailyPath = WorkerHelper::getLockPath('daily');
        $monthlyPath = WorkerHelper::getLockPath('monthly');

        $this->assertNotEquals($runQueuePath, $fillQueuePath);
        $this->assertNotEquals($runQueuePath, $dailyPath);
        $this->assertNotEquals($runQueuePath, $monthlyPath);
        $this->assertNotEquals($fillQueuePath, $dailyPath);
        $this->assertNotEquals($fillQueuePath, $monthlyPath);
        $this->assertNotEquals($dailyPath, $monthlyPath);
    }

    public function testIsProcessRunningReturnsFalseForInvalidPid(): void
    {
        $this->assertFalse(WorkerHelper::isProcessRunning(0));
        $this->assertFalse(WorkerHelper::isProcessRunning(-1));
        $this->assertFalse(WorkerHelper::isProcessRunning(-999));
    }

    public function testIsProcessRunningReturnsTrueForWorkerProcess(): void
    {
        $this->withRunningWorkerProcess(function (int $pid): void {
            $this->assertTrue(WorkerHelper::isProcessRunning($pid));
        });
    }

    public function testIsProcessRunningReturnsFalseForNonExistentProcess(): void
    {
        // Use a very high PID that's unlikely to exist
        $nonExistentPid = PHP_INT_MAX;
        $this->assertFalse(WorkerHelper::isProcessRunning($nonExistentPid));
    }

    public function testCanLaunchReturnsTrueWhenNoLockExists(): void
    {
        $this->assertTrue(WorkerHelper::canLaunch($this->testJobType));
    }

    public function testCanLaunchReturnsFalseWhenLockExistsWithRunningProcess(): void
    {
        $this->withRunningWorkerProcess(function (int $pid): void {
            file_put_contents(WorkerHelper::getLockPath($this->testJobType), (string) $pid);

            $this->assertFalse(WorkerHelper::canLaunch($this->testJobType));
        });
    }

    public function testCanLaunchReturnsTrueWhenLockExistsWithDeadProcess(): void
    {
        $lockPath = WorkerHelper::getLockPath($this->testJobType);
        // Use a PID that's very unlikely to be running
        file_put_contents($lockPath, (string) PHP_INT_MAX);

        $this->assertTrue(WorkerHelper::canLaunch($this->testJobType));
        // Stale lock should be cleaned up
        $this->assertFileDoesNotExist($lockPath);
    }

    public function testClaimLockSucceedsWhenNoLockExists(): void
    {
        $lockInfo = WorkerHelper::claimLock($this->testJobType);

        $this->assertIsArray($lockInfo);
        $this->assertArrayHasKey('handle', $lockInfo);
        $this->assertArrayHasKey('path', $lockInfo);
        $this->assertIsResource($lockInfo['handle']);
        $this->assertFileExists($lockInfo['path']);

        WorkerHelper::releaseLock($lockInfo);
    }

    public function testClaimLockFailsWhenLockAlreadyClaimed(): void
    {
        $lockInfo1 = WorkerHelper::claimLock($this->testJobType);
        $this->assertIsArray($lockInfo1);

        $lockInfo2 = WorkerHelper::claimLock($this->testJobType);
        $this->assertNull($lockInfo2);

        WorkerHelper::releaseLock($lockInfo1);
    }

    public function testClaimLockWritesPidToFile(): void
    {
        $lockInfo = WorkerHelper::claimLock($this->testJobType);
        $this->assertIsArray($lockInfo);

        rewind($lockInfo['handle']);
        $contents = stream_get_contents($lockInfo['handle']);
        $pid = (int) trim($contents);

        $this->assertGreaterThan(0, $pid);

        WorkerHelper::releaseLock($lockInfo);
    }

    public function testReleaseLockRemovesLockFile(): void
    {
        $lockInfo = WorkerHelper::claimLock($this->testJobType);
        $this->assertIsArray($lockInfo);

        $lockPath = $lockInfo['path'];
        $this->assertFileExists($lockPath);

        WorkerHelper::releaseLock($lockInfo);
        $this->assertFileDoesNotExist($lockPath);
    }

    public function testReleaseLockHandlesNullLockInfo(): void
    {
        $this->expectNotToPerformAssertions();

        WorkerHelper::releaseLock(null);
    }

    public function testClaimLockAndWritePidSucceeds(): void
    {
        $result = WorkerHelper::claimLockAndWritePid($this->testJobType);

        $this->assertTrue($result);

        $lockPath = WorkerHelper::getLockPath($this->testJobType);
        $this->assertFileExists($lockPath);

        $contents = file_get_contents($lockPath);
        $pid = (int) trim($contents);
        $this->assertGreaterThan(0, $pid);
    }

    public function testClaimLockAndWritePidFailsWhenLockAlreadyClaimedByRunningProcess(): void
    {
        $this->withRunningWorkerProcess(function (int $pid): void {
            file_put_contents(WorkerHelper::getLockPath($this->testJobType), (string) $pid);

            $this->assertFalse(WorkerHelper::claimLockAndWritePid($this->testJobType));
        });
    }

    public function testDifferentJobTypesCanRunSimultaneously(): void
    {
        $suffix = bin2hex(random_bytes(8));
        $runQueueLock = WorkerHelper::claimLock('run-queue-' . $suffix);
        $fillQueueLock = WorkerHelper::claimLock('fill-queue-' . $suffix);
        $dailyLock = WorkerHelper::claimLock('daily-' . $suffix);
        $monthlyLock = WorkerHelper::claimLock('monthly-' . $suffix);

        $this->assertIsArray($runQueueLock);
        $this->assertIsArray($fillQueueLock);
        $this->assertIsArray($dailyLock);
        $this->assertIsArray($monthlyLock);

        WorkerHelper::releaseLock($runQueueLock);
        WorkerHelper::releaseLock($fillQueueLock);
        WorkerHelper::releaseLock($dailyLock);
        WorkerHelper::releaseLock($monthlyLock);
    }

    public function testSameJobTypeCannotRunSimultaneously(): void
    {
        $lock1 = WorkerHelper::claimLock($this->testJobType);
        $this->assertIsArray($lock1);

        $lock2 = WorkerHelper::claimLock($this->testJobType);
        $this->assertNull($lock2);

        WorkerHelper::releaseLock($lock1);

        // After releasing, should be able to claim again
        $lock3 = WorkerHelper::claimLock($this->testJobType);
        $this->assertIsArray($lock3);

        WorkerHelper::releaseLock($lock3);
    }

    private function withRunningWorkerProcess(callable $test): void
    {
        if (!function_exists('posix_kill') && !is_dir('/proc')) {
            $this->markTestSkipped('Process liveness checks are not supported on this platform.');
        }

        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR
            . 'socialrss-test-worker-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($directory));
        $script = $directory . DIRECTORY_SEPARATOR . 'cron.php';
        file_put_contents($script, "<?php sleep(30);");

        $process = proc_open(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            unlink($script);
            rmdir($directory);
            $this->fail('Unable to start a process for the liveness check.');
        }

        try {
            $status = proc_get_status($process);
            $this->assertTrue($status['running']);
            $this->assertIsInt($status['pid']);
            $test($status['pid']);
        } finally {
            proc_terminate($process);
            foreach ($pipes as $pipe) {
                fclose($pipe);
            }
            proc_close($process);
            unlink($script);
            rmdir($directory);
        }
    }
}
