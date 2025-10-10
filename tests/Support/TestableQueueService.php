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
    public ?int $fakeBatchSize = null;

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

    public function claimDueJobsByStatus(int $now, string $status): array
    {
        // For testing, simulate atomic claiming by filtering and setting processing flag
        $claimedJobs = [];
        $batchSize = $this->fakeBatchSize ?? $this->getJobBatchSize();
        $count = 0;

        foreach ($this->dueJobs as $job) {
            if (($job['status'] ?? 'pending') === $status && $count < $batchSize) {
                $claimedJob = $job;
                $claimedJob['processing'] = true;
                $claimedJobs[] = $claimedJob;
                $count++;
            }
        }
        return $claimedJobs;
    }

    protected function getJobBatchSize(): int
    {
        return $this->fakeBatchSize ?? 3; // Default for testing
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

    protected function statusesPerJob(): int
    {
        if ($this->fakeBatchSize !== null) {
            return max(1, $this->fakeBatchSize);
        }

        return parent::statusesPerJob();
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
