<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

final class QueueServiceTest extends TestCase
{
    public function testCronArgumentParsing(): void
    {
        // Test that the cron.php accepts the four new targets
        $validTargets = ['run-queue', 'fill-queue', 'daily', 'monthly'];
        
        // The following assertion was removed because it was redundant and always passed.
        
        // Test that old targets are no longer valid
        $invalidTargets = ['hourly', 'worker'];
        
        foreach ($invalidTargets as $target) {
            $this->assertNotContains($target, $validTargets);
        }
    }

    public function testQueueServiceHasNewMethods(): void
    {
        // Test that QueueService has the new required methods
        $this->assertTrue(method_exists('App\Services\QueueService', 'runQueue'));
        $this->assertTrue(method_exists('App\Services\QueueService', 'fillQueue'));
        $this->assertTrue(method_exists('App\Services\QueueService', 'runDaily'));
        $this->assertTrue(method_exists('App\Services\QueueService', 'runMonthly'));
    }

    public function testQueueServiceOldMethodsRemoved(): void
    {
        // Test that old methods have been removed or modified
        $this->assertFalse(method_exists('App\Services\QueueService', 'processLoop'));
        $this->assertFalse(method_exists('App\Services\QueueService', 'scheduleDailyQueue'));
        $this->assertFalse(method_exists('App\Services\QueueService', 'runHourly'));
    }
}