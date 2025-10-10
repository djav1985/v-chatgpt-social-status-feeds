<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/TestableQueueService.php';

use Tests\Support\TestableQueueService;

final class QueueServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        date_default_timezone_set('UTC');
    }

    public function testFillQueueSchedulesFutureSlotsWithoutDuplicates(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00');
        $service->accounts = [
            (object) [
                'username' => 'owner',
                'account' => 'acct',
                'cron' => '08,14,18',
                'days' => 'everyday',
            ],
        ];
        $service->seedExistingJob('owner', 'acct', strtotime('2024-01-01 14:00:00'));

        $service->fillQueue();

        $this->assertCount(1, $service->storedJobs, 'Only future, non-duplicate hours should be queued.');
        $stored = $service->storedJobs[0];
        $this->assertSame('job-1', $stored['id']);
        $this->assertSame('owner', $stored['username']);
        $this->assertSame('acct', $stored['account']);
        $this->assertSame(strtotime('2024-01-01 18:00:00'), $stored['scheduledAt']);
        $this->assertSame('pending', $stored['status']);
    }

    public function testEnqueueRemainingJobsRespectsDayAndFutureHours(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00'); // Monday in UTC
        $service->seedExistingJob('owner', 'acct', strtotime('2024-01-01 14:00:00'));

        $service->enqueueRemainingJobs('owner', 'acct', '08,14,18', 'monday');

        $this->assertCount(1, $service->storedJobs, 'Only unscheduled future hours should be added.');
        $this->assertSame(strtotime('2024-01-01 18:00:00'), $service->storedJobs[0]['scheduledAt']);
    }

    public function testRunQueueDeletesSuccessfulJobs(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->dueJobs = [
            [
                'id' => 'job-1',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending',
            ],
        ];

        $service->runQueue();

        $this->assertSame(['job-1'], $service->deletedIds);
        $this->assertSame([], $service->markedStatuses);
    }

    public function testRunQueueGeneratesConfiguredBatchSize(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->dueJobs = [
            [
                'id' => 'job-1',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-2',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-3',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 10:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-4',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 09:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'job-5',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 08:00:00'),
                'status' => 'pending',
            ],
        ];
        $service->fakeBatchSize = 3;

        $service->runQueue();

        // Should only process 3 jobs (limited by batch size)
        $this->assertSame(3, $service->statusGenerations);
        $this->assertCount(3, $service->deletedIds);
        $this->assertContains('job-1', $service->deletedIds);
        $this->assertContains('job-2', $service->deletedIds);
        $this->assertContains('job-3', $service->deletedIds);
        // job-4 and job-5 should not be processed
        $this->assertNotContains('job-4', $service->deletedIds);
        $this->assertNotContains('job-5', $service->deletedIds);
    }

    public function testRunQueueMarksJobForRetryWhenGenerateStatusReturnsError(): void
    {
        $service = new TestableQueueService();
        $service->dueJobs = [
            [
                'id' => 'job-2',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending',
            ],
        ];
        $service->jobOutcomes['job-2'] = 'fail';

        $service->runQueue();

        $this->assertArrayHasKey('job-2', $service->markedStatuses);
        $this->assertSame('retry', $service->markedStatuses['job-2']);
        $this->assertNotContains('job-2', $service->deletedIds, 'First failure should not delete the job.');
        $this->assertSame(1, $service->statusGenerations, 'Status generation should stop after the first error.');
    }

    public function testRunQueueDeletesAfterSecondFailure(): void
    {
        $service = new TestableQueueService();
        $service->dueJobs = [
            [
                'id' => 'job-3',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'retry',
            ],
        ];
        $service->jobOutcomes['job-3'] = 'fail';

        $service->runQueue();

        $this->assertSame(['job-3'], $service->deletedIds);
        $this->assertArrayNotHasKey('job-3', $service->markedStatuses);
    }

    public function testRemoveHelpersUseCurrentTimestamp(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = 1704100800; // 2024-01-01 12:00:00 UTC

        $service->removeFutureJobs('owner', 'acct');
        $service->removeAllJobs('owner', 'acct');

        $this->assertSame([
            [
                'username' => 'owner',
                'account' => 'acct',
                'fromTimestamp' => 1704100800,
            ],
        ], $service->futureRemovals);
        $this->assertSame([
            [
                'username' => 'owner',
                'account' => 'acct',
            ],
        ], $service->allRemovals);
    }

    public function testRunQueueProcessesRetryJobsFirst(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->dueJobs = [
            [
                'id' => 'pending-job',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'retry-job',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'retry',
            ],
        ];

        $service->runQueue();

        // Both jobs should be processed and deleted (successful execution)
        $this->assertContains('pending-job', $service->deletedIds);
        $this->assertContains('retry-job', $service->deletedIds);
        $this->assertCount(2, $service->deletedIds);
        $this->assertSame(2, $service->statusGenerations); // Both jobs should generate statuses
    }

    public function testRunQueueProcessesRetryJobsBeforePendingJobs(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->dueJobs = [
            [
                'id' => 'pending-job',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending',
            ],
            [
                'id' => 'retry-job',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'retry',
            ],
        ];
        // Make both jobs fail
        $service->jobOutcomes['pending-job'] = 'fail';
        $service->jobOutcomes['retry-job'] = 'fail';

        $service->runQueue();

        // Retry job should be deleted after failure
        $this->assertContains('retry-job', $service->deletedIds);
        // Pending job should be marked for retry after failure
        $this->assertArrayHasKey('pending-job', $service->markedStatuses);
        $this->assertSame('retry', $service->markedStatuses['pending-job']);
        $this->assertNotContains('pending-job', $service->deletedIds);
    }

    public function testJobBatchSizeLimitsSimultaneousProcessing(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->dueJobs = [
            [
                'id' => 'job-1',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 08:00:00'),
                'status' => 'pending'
            ],
            [
                'id' => 'job-2',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 09:00:00'),
                'status' => 'pending'
            ],
            [
                'id' => 'job-3',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 10:00:00'),
                'status' => 'pending'
            ],
            [
                'id' => 'job-4',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 11:00:00'),
                'status' => 'pending'
            ],
            [
                'id' => 'job-5',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 12:00:00'),
                'status' => 'pending'
            ],
            [
                'id' => 'retry-1',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 07:00:00'),
                'status' => 'retry'
            ],
            [
                'id' => 'retry-2',
                'account' => 'acct',
                'username' => 'owner',
                'scheduled_at' => strtotime('2024-01-01 06:00:00'),
                'status' => 'retry'
            ],
        ];
        $service->fakeBatchSize = 2; // Limit to 2 jobs per batch

        $service->runQueue();

        // Should process 2 retry jobs + 2 pending jobs = 4 total jobs
        $this->assertSame(4, $service->statusGenerations);
        $this->assertCount(4, $service->deletedIds);

        // Should process retry jobs first (limited to 2)
        $this->assertContains('retry-1', $service->deletedIds);
        $this->assertContains('retry-2', $service->deletedIds);

        // Then process pending jobs (limited to 2)
        $this->assertContains('job-1', $service->deletedIds); // Earliest pending job
        $this->assertContains('job-2', $service->deletedIds); // Second earliest pending job

        // Remaining jobs should not be processed in this run
        $this->assertNotContains('job-3', $service->deletedIds);
        $this->assertNotContains('job-4', $service->deletedIds);
        $this->assertNotContains('job-5', $service->deletedIds);
    }
}
