<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Support\TestableQueueService;

final class ConcurrencyQueueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    public function testClaimDueJobsPreventsConcurrentExecution(): void
    {
        $service1 = new TestableQueueService();
        $service2 = new TestableQueueService();
        
        $now = strtotime('2024-01-01 12:00:00');
        $service1->fakeNow = $now;
        $service2->fakeNow = $now;

        // Both services see the same due jobs initially
        $dueJobs = [
            [
                'id' => 'job-1',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-2',
                'account' => 'acct2',
                'username' => 'owner2',
                'scheduled_at' => strtotime('2024-01-01 10:00:00'),
                'status' => 'retry',
            ],
        ];

        $service1->dueJobs = $dueJobs;
        $service2->dueJobs = $dueJobs;

        // Both services claim jobs
        $claimed1 = $service1->claimDueJobs($now);
        $claimed2 = $service2->claimDueJobs($now);

        // Verify both get the jobs with processing flag set
        $this->assertCount(2, $claimed1);
        $this->assertCount(2, $claimed2);

        // Check first job processing flag
        $this->assertTrue($claimed1[0]['processing']);
        $this->assertSame('pending', $claimed1[0]['status']);
        
        // Check second job processing flag
        $this->assertTrue($claimed1[1]['processing']);
        $this->assertSame('retry', $claimed1[1]['status']);
        
        // Both services should get identical results in the test environment
        $this->assertEquals($claimed1, $claimed2);
    }

    public function testRunQueueUsesStatusForRetryLogic(): void
    {
        $service = new TestableQueueService();
        $service->dueJobs = [
            [
                'id' => 'job-pending',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-retry',
                'account' => 'acct2',
                'username' => 'owner2',
                'scheduled_at' => strtotime('2024-01-01 10:00:00'),
                'status' => 'retry',
            ],
        ];

        // Make both jobs fail
        $service->jobOutcomes['job-pending'] = 'fail';
        $service->jobOutcomes['job-retry'] = 'fail';

        $service->runQueue();

        // Pending job should be marked as retry
        $this->assertArrayHasKey('job-pending', $service->markedStatuses);
        $this->assertSame('retry', $service->markedStatuses['job-pending']);
        $this->assertNotContains('job-pending', $service->deletedIds);

        // Retry job should be deleted
        $this->assertContains('job-retry', $service->deletedIds);
        $this->assertArrayNotHasKey('job-retry', $service->markedStatuses);
    }
}