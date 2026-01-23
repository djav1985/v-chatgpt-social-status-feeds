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

    public function testFillQueueSchedulesSameDaySlotsWithoutDuplicates(): void
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

        $this->assertSame(1, $service->clearAllJobsCount, 'Fill queue should clear existing jobs before scheduling.');
        $this->assertCount(3, $service->storedJobs, 'Fill queue should schedule same-day hours after clearing existing jobs.');

        $firstStored = $service->storedJobs[0];
        $this->assertSame('job-1', $firstStored['id']);
        $this->assertSame('owner', $firstStored['username']);
        $this->assertSame('acct', $firstStored['account']);
        $this->assertSame(strtotime('2024-01-01 08:00:00'), $firstStored['scheduledAt']);
        $this->assertSame('pending', $firstStored['status']);

        $secondStored = $service->storedJobs[1];
        $this->assertSame('job-2', $secondStored['id']);
        $this->assertSame('owner', $secondStored['username']);
        $this->assertSame('acct', $secondStored['account']);
        $this->assertSame(strtotime('2024-01-01 14:00:00'), $secondStored['scheduledAt']);
        $this->assertSame('pending', $secondStored['status']);

        $thirdStored = $service->storedJobs[2];
        $this->assertSame('job-3', $thirdStored['id']);
        $this->assertSame('owner', $thirdStored['username']);
        $this->assertSame('acct', $thirdStored['account']);
        $this->assertSame(strtotime('2024-01-01 18:00:00'), $thirdStored['scheduledAt']);
        $this->assertSame('pending', $thirdStored['status']);
    }

    public function testFillQueueClearsExistingJobs(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00');
        $service->accounts = [
            (object) [
                'username' => 'owner',
                'account' => 'acct',
                'cron' => '08',
                'days' => 'everyday',
            ],
        ];
        $scheduledAt = strtotime('2024-01-01 08:00:00');
        $service->seedExistingJob('owner', 'acct', $scheduledAt);

        $service->fillQueue();

        $this->assertSame(1, $service->clearAllJobsCount, 'Fill queue should clear existing jobs.');
        $this->assertCount(1, $service->storedJobs);
        $this->assertSame(strtotime('2024-01-01 08:00:00'), $service->storedJobs[0]['scheduledAt']);
        $this->assertTrue($service->hasExistingJob('owner', 'acct', $scheduledAt));
    }

    public function testEnqueueRemainingJobsSkipsPastHourForToday(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00'); // Monday in UTC
        $service->enqueueRemainingJobs('owner', 'acct', '08', 'monday,tuesday');

        $this->assertCount(0, $service->storedJobs, 'Past hours should not be scheduled.');
    }

    public function testEnqueueRemainingJobsSchedulesFutureHourToday(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00'); // Monday in UTC

        $service->enqueueRemainingJobs('owner', 'acct', '14', 'monday');

        $this->assertCount(1, $service->storedJobs);
        $this->assertSame(strtotime('2024-01-01 14:00:00'), $service->storedJobs[0]['scheduledAt']);
    }

    public function testScheduledTimestampReturnsSameDaySlotWhenHourHasPassed(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 08:30:00');

        $scheduled = $service->callScheduledTimestampForHourSameDay(8, $service->fakeNow);

        $this->assertSame(strtotime('2024-01-01 08:00:00'), $scheduled);
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
        $this->assertSame([], $service->dueJobs);
        $this->assertTrue($service->lockReleased);
    }

    public function testRunQueueProcessesAllPendingJobsInSingleInvocation(): void
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

        $service->runQueue();

        $this->assertSame(5, $service->statusGenerations);
        $this->assertCount(5, $service->deletedIds);
        $this->assertContains('job-1', $service->deletedIds);
        $this->assertContains('job-2', $service->deletedIds);
        $this->assertContains('job-3', $service->deletedIds);
        $this->assertContains('job-4', $service->deletedIds);
        $this->assertContains('job-5', $service->deletedIds);
        $this->assertSame([], $service->dueJobs);
        $this->assertTrue($service->lockReleased);
    }

    public function testRunQueueSkipsWhenLockUnavailable(): void
    {
        $service = new TestableQueueService();
        $service->lockAvailable = false;

        $service->runQueue();

        $this->assertFalse($service->lockReleased);
        $this->assertSame(0, $service->statusGenerations);
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
        $this->assertSame('retry', $service->dueJobs[0]['status'] ?? null);
        $this->assertTrue($service->lockReleased);
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
        $this->assertSame([], $service->dueJobs);
        $this->assertTrue($service->lockReleased);
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
        $this->assertSame([], $service->dueJobs);
        $this->assertTrue($service->lockReleased);
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
        $this->assertTrue($service->lockReleased);
    }

    public function testScheduledTimestampKeepsPastHoursOnSameDay(): void
    {
        $service = new TestableQueueService();
        $reference = strtotime('2024-01-01 12:00:00');

        $this->assertSame(
            strtotime('2024-01-01 08:00:00'),
            $service->callScheduledTimestampForHourSameDay(8, $reference)
        );
    }

    public function testRescheduleAccountJobsClearsFutureJobsBeforeEnqueueing(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 12:00:00'); // Monday in UTC

        $service->rescheduleAccountJobs('owner', 'acct', '14', 'monday');

        $this->assertSame(['removeFutureJobs', 'enqueueRemainingJobs'], $service->callSequence);
        $this->assertCount(1, $service->futureRemovals);
        $this->assertCount(1, $service->storedJobs);
        $this->assertSame(strtotime('2024-01-01 14:00:00'), $service->storedJobs[0]['scheduledAt']);
    }

    public function testRunQueueReleasesStaleJobsBeforeClaiming(): void
    {
        $service = new TestableQueueService();
        $service->fakeNow = strtotime('2024-01-01 13:00:00');
        $service->fakeReleasedCount = 2;

        $service->runQueue();

        $this->assertSame(2, $service->releaseCallCount, 'Release should run once per status bucket.');
        $this->assertSame([
            $service->fakeNow,
            $service->fakeNow,
        ], $service->releaseTimestamps);
        $this->assertTrue($service->lockReleased);
    }

    public function testRunQueueIncrementsApiUsageOnSuccess(): void
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
        $service->setUserQuota('owner', 1, 5);

        $service->runQueue();

        $this->assertSame(1, $service->statusGenerations);
        $this->assertSame(['job-1'], $service->deletedIds);
        $this->assertSame(2, $service->updatedUsedApiCalls['owner'] ?? 0);
        $this->assertSame(2, $service->userInfoMap['owner']->used_api_calls);
        $this->assertSame([], $service->dueJobs);
        $this->assertTrue($service->lockReleased);
    }

    public function testRunQueueRespectsApiQuotaAndMarksForRetry(): void
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
        $service->setUserQuota('owner', 3, 3);

        $service->runQueue();

        $this->assertSame(0, $service->statusGenerations, 'Quota exhaustion should short-circuit generation.');
        $this->assertArrayHasKey('job-1', $service->markedStatuses);
        $this->assertSame('retry', $service->markedStatuses['job-1']);
        $this->assertArrayHasKey('owner', $service->limitEmailUpdates);
        $this->assertTrue($service->limitEmailUpdates['owner']);
        $this->assertSame(['owner'], $service->sentLimitEmails);
        $this->assertArrayNotHasKey('owner', $service->updatedUsedApiCalls, 'Usage should not increase when over the limit.');
        $this->assertNotContains('job-1', $service->deletedIds);
        $this->assertSame('retry', $service->dueJobs[0]['status'] ?? null);
        $this->assertTrue($service->lockReleased);
    }

}
