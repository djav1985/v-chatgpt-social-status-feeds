<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: WorkerHelperTest.php
 * Description: PHPUnit tests for WorkerHelper.
 */

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Helpers\WorkerHelper;

final class WorkerHelperTest extends TestCase
{
    private string $testJobType;

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

}
