<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
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

    public function testRunQueueMarksFirstFailureAsRetry(): void
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
}
