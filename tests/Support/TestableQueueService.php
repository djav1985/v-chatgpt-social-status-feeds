<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\QueueService;

final class TestableQueueService extends QueueService
{
    public int $fakeNow = 0;
    /** @var array<int, object> */
    public array $accounts = [];
    /** @var array<int, array<string, mixed>> */
    public array $dueJobs = [];
    /** @var array<int, array<string, mixed>> */
    public array $storedJobs = [];
    /** @var array<string, string> */
    public array $markedStatuses = [];
    /** @var array<int, string> */
    public array $deletedIds = [];
    /** @var array<int, array<string, mixed>> */
    public array $futureRemovals = [];
    /** @var array<int, array<string, string>> */
    public array $allRemovals = [];
    /** @var array<string, string> */
    public array $jobOutcomes = [];
    public int $statusGenerations = 0;
    public int $releaseCallCount = 0;
    /** @var array<int, int> */
    public array $releaseTimestamps = [];
    public int $fakeReleasedCount = 0;
    public int $resetAllProcessingCount = 0;
    /** @var array<string, object> */
    public array $userInfoMap = [];
    /** @var array<string, int> */
    public array $updatedUsedApiCalls = [];
    /** @var array<string, bool> */
    public array $limitEmailUpdates = [];
    /** @var array<int, string> */
    public array $sentLimitEmails = [];
    /** @var array<int, string> */
    public array $callSequence = [];
    public int $clearAllJobsCount = 0;

    public bool $lockAvailable = true;
    public bool $lockReleased = false;

    private ?string $currentJobId = null;

    /** @var array<string, bool> */
    private array $existingJobs = [];

    protected function now(): int
    {
        return $this->fakeNow;
    }

    protected function getAccounts(): array
    {
        return $this->accounts;
    }

    public function removeFutureJobs(string $username, string $account): void
    {
        $this->callSequence[] = 'removeFutureJobs';
        parent::removeFutureJobs($username, $account);
    }

    public function enqueueRemainingJobs(string $username, string $account, string $cron, string $days): void
    {
        $this->callSequence[] = 'enqueueRemainingJobs';
        parent::enqueueRemainingJobs($username, $account, $cron, $days);
    }

    protected function fetchDueJobs(int $now): array
    {
        return $this->dueJobs;
    }

    public function claimDueJobs(int $now): array
    {
        $this->releaseStaleProcessingJobs($now);

        // For testing, simulate atomic claiming by setting processing flag
        $claimedJobs = [];
        foreach ($this->dueJobs as &$job) {
            $processing = (bool) ($job['processing'] ?? false);
            if ($processing) {
                continue;
            }

            $job['processing'] = true;
            $claimedJobs[] = $job;
        }
        unset($job);

        return $claimedJobs;
    }

    public function claimDueJobsByStatus(int $now, string $status): array
    {
        $this->releaseStaleProcessingJobs($now);

        // For testing, simulate atomic claiming by filtering and setting processing flag
        $claimedJobs = [];
        foreach ($this->dueJobs as &$job) {
            $jobStatus = (string) ($job['status'] ?? 'pending');
            $processing = (bool) ($job['processing'] ?? false);

            if ($jobStatus === $status && !$processing) {
                $job['processing'] = true;
                $claimedJobs[] = $job;
            }
        }
        unset($job);
        return $claimedJobs;
    }

    protected function insertJobInStorage(
        string $id,
        string $username,
        string $account,
        int $scheduledAt,
        string $status
    ): void {
        $this->storedJobs[] = [
            'id' => $id,
            'username' => $username,
            'account' => $account,
            'scheduledAt' => $scheduledAt,
            'status' => $status,
        ];
        $this->existingJobs[$this->key($username, $account, $scheduledAt)] = true;
    }

    protected function jobExistsInStorage(string $username, string $account, int $scheduledAt): bool
    {
        return $this->existingJobs[$this->key($username, $account, $scheduledAt)] ?? false;
    }

    protected function deleteJobById(string $id): void
    {
        $this->deletedIds[] = $id;

        foreach ($this->dueJobs as $index => $job) {
            if (($job['id'] ?? '') === $id) {
                unset($this->dueJobs[$index]);
            }
        }

        $this->dueJobs = array_values($this->dueJobs);
    }

    protected function markJobStatus(string $id, string $status): void
    {
        $this->markedStatuses[$id] = $status;
        foreach ($this->dueJobs as &$job) {
            if (($job['id'] ?? '') === $id) {
                $job['status'] = $status;
            }
        }
        unset($job);
    }

    protected function markJobStatusAndProcessing(string $id, string $status, bool $processing): void
    {
        $this->markedStatuses[$id] = $status;
        foreach ($this->dueJobs as &$job) {
            if (($job['id'] ?? '') === $id) {
                $job['status'] = $status;
                $job['processing'] = $processing;
            }
        }
        unset($job);
    }

    protected function deleteFutureJobs(string $username, string $account, int $fromTimestamp): void
    {
        $this->futureRemovals[] = [
            'username' => $username,
            'account' => $account,
            'fromTimestamp' => $fromTimestamp,
        ];
    }

    protected function deleteAllJobsForAccount(string $username, string $account): void
    {
        $this->allRemovals[] = [
            'username' => $username,
            'account' => $account,
        ];
    }

    protected function generateJobId(): string
    {
        return sprintf('job-%d', count($this->storedJobs) + 1);
    }

    protected function generateStatusesForJob(array $job, int $count): void
    {
        $previousJobId = $this->currentJobId;
        $this->currentJobId = $job['id'] ?? null;

        try {
            parent::generateStatusesForJob($job, $count);
        } finally {
            $this->currentJobId = $previousJobId;
        }
    }

    protected function callStatusServiceGenerateStatus(string $account, string $username): ?array
    {
        $this->statusGenerations++;

        $jobId = $this->currentJobId ?? '';
        $outcome = $this->jobOutcomes[$jobId] ?? 'success';

        if ($outcome === 'exception') {
            throw new \RuntimeException('Simulated exception for job ' . ($jobId !== '' ? $jobId : 'unknown'));
        }

        if ($outcome === 'fail') {
            $message = $jobId !== ''
                ? sprintf('Simulated failure for job %s', $jobId)
                : 'Simulated failure';

            return ['error' => $message];
        }

        return ['success' => true];
    }

    public function seedExistingJob(string $username, string $account, int $scheduledAt): void
    {
        $this->addExistingJob($username, $account, $scheduledAt);
    }

    protected function releaseStaleProcessingJobs(int $now): int
    {
        $this->releaseCallCount++;
        $this->releaseTimestamps[] = $now;

        return $this->fakeReleasedCount;
    }

    protected function resetAllProcessingFlags(): int
    {
        $this->resetAllProcessingCount++;
        
        // Reset all processing flags in the test jobs
        $count = 0;
        foreach ($this->dueJobs as &$job) {
            if (isset($job['processing']) && $job['processing']) {
                $job['processing'] = false;
                $count++;
            }
        }
        unset($job);
        
        return $count;
    }

    protected function getUserInfo(string $username): ?object
    {
        if (!array_key_exists($username, $this->userInfoMap)) {
            $this->userInfoMap[$username] = (object) [
                'username' => $username,
                'email' => $username . '@example.test',
                'max_api_calls' => 999,
                'used_api_calls' => 0,
                'limit_email_sent' => false,
            ];
        }

        return $this->userInfoMap[$username];
    }

    protected function updateUsedApiCalls(string $username, int $usedApiCalls): void
    {
        $this->updatedUsedApiCalls[$username] = $usedApiCalls;
        $user = $this->getUserInfo($username);
        if ($user !== null) {
            $user->used_api_calls = $usedApiCalls;
            $this->userInfoMap[$username] = $user;
        }
    }

    protected function setLimitEmailSent(string $username, bool $sent): void
    {
        $this->limitEmailUpdates[$username] = $sent;
        $user = $this->getUserInfo($username);
        if ($user !== null) {
            $user->limit_email_sent = $sent;
            $this->userInfoMap[$username] = $user;
        }
    }

    protected function sendLimitEmail(object $user): void
    {
        $this->sentLimitEmails[] = (string) ($user->username ?? '');
    }

    public function setUserQuota(string $username, int $used, int $max, bool $limitEmailSent = false, string $email = 'user@example.test'): void
    {
        $this->userInfoMap[$username] = (object) [
            'username' => $username,
            'email' => $email,
            'max_api_calls' => $max,
            'used_api_calls' => $used,
            'limit_email_sent' => $limitEmailSent,
        ];
    }

    public function callScheduledTimestampForHour(int $hour, int $reference): int
    {
        return parent::scheduledTimestampForHour($hour, $reference);
    }

    public function callScheduledTimestampForHourSameDay(int $hour, int $reference): int
    {
        return parent::scheduledTimestampForHourSameDay($hour, $reference);
    }

    public function hasExistingJob(string $username, string $account, int $scheduledAt): bool
    {
        return $this->existingJobs[$this->key($username, $account, $scheduledAt)] ?? false;
    }

    protected function addExistingJob(string $username, string $account, int $scheduledAt): void
    {
        $this->existingJobs[$this->key($username, $account, $scheduledAt)] = true;
    }

    private function key(string $username, string $account, int $scheduledAt): string
    {
        return $username . '|' . $account . '|' . $scheduledAt;
    }

    protected function claimWorkerLock(): bool
    {
        return $this->lockAvailable;
    }

    protected function releaseWorkerLock(): void
    {
        $this->lockReleased = true;
    }

    protected function clearAllJobs(): void
    {
        $this->clearAllJobsCount++;
        $this->existingJobs = [];
    }
}
