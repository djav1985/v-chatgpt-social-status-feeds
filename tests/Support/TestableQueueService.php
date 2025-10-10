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

    protected function fetchDueJobs(int $now): array
    {
        return $this->dueJobs;
    }

    public function claimDueJobs(int $now): array
    {
        // For testing, simulate atomic claiming by setting processing flag
        $claimedJobs = [];
        foreach ($this->dueJobs as $job) {
            $claimedJob = $job;
            $claimedJob['processing'] = true;
            $claimedJobs[] = $claimedJob;
        }
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
    }

    protected function markJobStatus(string $id, string $status): void
    {
        $this->markedStatuses[$id] = $status;
    }

    protected function markJobStatusAndProcessing(string $id, string $status, bool $processing): void
    {
        $this->markedStatuses[$id] = $status;
        // In testing, we don't need to track the processing flag separately
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

    protected function processJobPayload(array $job): void
    {
        if (($this->jobOutcomes[$job['id']] ?? 'success') === 'fail') {
            throw new \RuntimeException('fail');
        }
    }

    protected function generateJobId(): string
    {
        return sprintf('job-%d', count($this->storedJobs) + 1);
    }

    public function seedExistingJob(string $username, string $account, int $scheduledAt): void
    {
        $this->addExistingJob($username, $account, $scheduledAt);
    }

    protected function addExistingJob(string $username, string $account, int $scheduledAt): void
    {
        $this->existingJobs[$this->key($username, $account, $scheduledAt)] = true;
    }

    private function key(string $username, string $account, int $scheduledAt): string
    {
        return $username . '|' . $account . '|' . $scheduledAt;
    }
}
