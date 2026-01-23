<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use App\Core\ErrorManager;
use App\Services\StatusService;
use App\Models\Account;
use App\Models\StatusJob;
use function random_bytes;
use App\Models\User;
use App\Core\Mailer;
use App\Helpers\WorkerHelper;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Service for queue operations (run-queue and fill-queue).
 */
class QueueService
{
    /** @var array|null */
    private $workerLock = null;

    private ?string $jobType = null;

    public function __construct(?string $jobType = null)
    {
        $this->jobType = $jobType;
    }

    public function enqueueRemainingJobs(string $username, string $account, string $cron, string $days): void
    {
        $now = $this->now();
        $daysArr = array_filter(
            array_map('strtolower', array_map('trim', explode(',', (string) $days))),
            fn($v) => strlen($v) > 0
        );

        foreach ($this->normalizeHours($cron) as $hour) {
            $scheduledAt = $this->scheduledTimestampForHour($hour, $now);

            if ($scheduledAt <= $now) {
                continue;
            }

            if (!$this->isScheduledDayAllowed($daysArr, $scheduledAt)) {
                continue;
            }

            if ($this->jobExistsInStorage($username, $account, $scheduledAt)) {
                continue;
            }

            $this->storeJob($username, $account, $scheduledAt, 'pending');
        }
    }

    public function removeFutureJobs(string $username, string $account): void
    {
        $this->deleteFutureJobs($username, $account, $this->now());
    }

    public function removeAllJobs(string $username, string $account): void
    {
        $this->deleteAllJobsForAccount($username, $account);
    }

    public function rescheduleAccountJobs(string $username, string $account, string $cron, string $days): void
    {
        $this->removeFutureJobs($username, $account);
        $this->enqueueRemainingJobs($username, $account, $cron, $days);
    }

    public function runQueue(): void
    {
        if (!$this->claimWorkerLock()) {
            ErrorManager::getInstance()->log('[QueueService] Queue worker already running; skipping runQueue invocation.', 'info');
            return;
        }

        $attemptedJobIds = [];

        try {
            // Since we have the worker lock, no other worker can be running.
            // Reset all processing flags from any previously crashed/interrupted workers.
            $count = $this->resetAllProcessingFlags();
            if ($count > 0) {
                ErrorManager::getInstance()->log(
                    sprintf('[QueueService] Reset %d stuck processing flag(s) from previous worker run.', $count),
                    'info'
                );
            }

            do {
                $processedAny = false;

                $retryJobs = $this->filterUnattemptedJobs(
                    $this->claimDueJobsByStatus($this->now(), 'retry'),
                    $attemptedJobIds
                );
                if (!empty($retryJobs)) {
                    $this->processJobBatch($retryJobs, true);
                    // Extract job IDs from processed jobs
                    foreach ($retryJobs as $job) {
                        $id = (string) ($job['id'] ?? '');
                        if ($id !== '') {
                            $attemptedJobIds[] = $id;
                        }
                    }
                    $processedAny = true;
                }

                $pendingJobs = $this->filterUnattemptedJobs(
                    $this->claimDueJobsByStatus($this->now(), 'pending'),
                    $attemptedJobIds
                );
                if (!empty($pendingJobs)) {
                    $this->processJobBatch($pendingJobs, false);
                    // Extract job IDs from processed jobs
                    foreach ($pendingJobs as $job) {
                        $id = (string) ($job['id'] ?? '');
                        if ($id !== '') {
                            $attemptedJobIds[] = $id;
                        }
                    }
                    $processedAny = true;
                }
            } while ($processedAny);
        } finally {
            $this->releaseWorkerLock();
        }
    }

    public function fillQueue(): void
    {
        if (!$this->claimWorkerLock()) {
            ErrorManager::getInstance()->log('[QueueService] Fill queue worker already running; skipping fillQueue invocation.', 'info');
            return;
        }

        try {
            $this->clearAllJobs();
            $accounts = $this->getAccounts();
            $now = $this->now();

            foreach ($accounts as $account) {
                $account = (object)$account;
                $days = array_filter(
                    array_map('strtolower', array_map('trim', explode(',', (string) ($account->days ?? '')))),
                    fn($v) => strlen($v) > 0
                );

                foreach ($this->normalizeHours((string) ($account->cron ?? '')) as $hour) {
                    $scheduledAt = $this->scheduledTimestampForHour($hour, $now);

                    $username = (string) ($account->username ?? '');
                    $acct = (string) ($account->account ?? '');

                    if ($username === '' || $acct === '') {
                        continue;
                    }

                    if (!$this->isScheduledDayAllowed($days, $scheduledAt)) {
                        continue;
                    }

                    if ($this->jobExistsInStorage($username, $acct, $scheduledAt)) {
                        continue;
                    }

                    $this->storeJob($username, $acct, $scheduledAt, 'pending');
                }
            }
        } finally {
            $this->releaseWorkerLock();
        }
    }

    protected function claimWorkerLock(): bool
    {
        $jobType = $this->jobType ?? 'run-queue';
        $this->workerLock = WorkerHelper::claimLock($jobType);

        if ($this->workerLock === null) {
            return false;
        }

        register_shutdown_function(function (): void {
            $this->releaseWorkerLock();
        });

        return true;
    }

    protected function releaseWorkerLock(): void
    {
        WorkerHelper::releaseLock($this->workerLock);
        $this->workerLock = null;
    }

    protected function now(): int
    {
        return time();
    }

    /**
     * Atomically claim jobs with a specific status for processing.
     * Filters jobs by the given status parameter and prevents concurrent execution.
     * 
     * @param int $now Current timestamp.
     * @param string $status Job status to filter by (e.g., 'pending' or 'retry').
     * @return array<int, array<string, mixed>> Array of successfully claimed jobs.
     */
    protected function claimDueJobsByStatus(int $now, string $status): array
    {
        $claimedJobs = [];

        $this->releaseStaleProcessingJobs($now);

        // First, get candidate job IDs for specific status
        $candidates = StatusJob::fetchDueJobsByStatus($now, $status);

        // Atomically claim each job individually by setting processing = TRUE
        foreach ($candidates as $candidate) {
            // Try to atomically claim this specific job
            if (StatusJob::claimJob($candidate['id'])) {
                $candidate['processing'] = true;
                $claimedJobs[] = $candidate;
            }
        }

        return $claimedJobs;
    }

    protected function releaseStaleProcessingJobs(int $now): int
    {
        $timeout = defined('STATUS_JOB_STALE_AFTER') ? (int) constant('STATUS_JOB_STALE_AFTER') : 3600;
        return StatusJob::releaseStaleJobs($now, $timeout);
    }

    /**
     * Reset all processing flags.
     * This is safe to call when holding the worker lock since no other worker can be running.
     */
    protected function resetAllProcessingFlags(): int
    {
        return StatusJob::resetAllProcessingFlags();
    }

    protected function clearAllJobs(): void
    {
        StatusJob::clearAllPendingJobs();
    }

    protected function jobExistsInStorage(string $username, string $account, int $scheduledAt): bool
    {
        return StatusJob::exists($username, $account, $scheduledAt);
    }

    protected function insertJobInStorage(string $id, string $username, string $account, int $scheduledAt, string $status): void
    {
        StatusJob::insert($id, $username, $account, $scheduledAt, $status);
    }

    protected function deleteFutureJobs(string $username, string $account, int $fromTimestamp): void
    {
        StatusJob::deleteFutureJobs($username, $account, $fromTimestamp);
    }

    protected function deleteAllJobsForAccount(string $username, string $account): void
    {
        StatusJob::deleteAllForAccount($username, $account);
    }

    protected function deleteJobById(string $id): void
    {
        StatusJob::deleteById($id);
    }

    protected function markJobStatus(string $id, string $status): void
    {
        StatusJob::markStatus($id, $status);
    }

    protected function markJobStatusAndProcessing(string $id, string $status, bool $processing): void
    {
        StatusJob::markStatusAndProcessing($id, $status, $processing);
    }

    /**
     * Get all accounts.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getAccounts(): array
    {
        return Account::getAllAccounts();
    }

    protected function generateStatusesForJob(array $job, int $count): void
    {
        $account = (string) ($job['account'] ?? '');
        $username = (string) ($job['username'] ?? '');

        if ($account === '' || $username === '') {
            return;
        }

        $user = $this->getUserInfo($username);
        if ($user === null) {
            throw new RuntimeException(sprintf('User %s not found for queued job.', $username));
        }

        $maxApiCalls = (int) ($user->max_api_calls ?? 0);
        $usedApiCalls = (int) ($user->used_api_calls ?? 0);
        $limitEmailSent = (bool) ($user->limit_email_sent ?? false);

        $attempts = max(1, $count);
        for ($i = 0; $i < $attempts; $i++) {
            if ($maxApiCalls > 0 && $usedApiCalls >= $maxApiCalls) {
                if (!$limitEmailSent) {
                    $this->sendLimitEmail($user);
                    $this->setLimitEmailSent($username, true);
                    $limitEmailSent = true;
                }

                throw new RuntimeException(sprintf('API limit reached for %s.', $username));
            }

            $result = $this->callStatusServiceGenerateStatus($account, $username);
            if (isset($result['error'])) {
                throw new RuntimeException((string) $result['error']);
            }

            $usedApiCalls++;
            $this->updateUsedApiCalls($username, $usedApiCalls);

            if (property_exists($user, 'used_api_calls')) {
                $user->used_api_calls = $usedApiCalls;
            }
        }
    }

    /**
     * Wrapper for status generation to allow test stubbing.
     * This method exists to enable TestableQueueService to override
     * the behavior during testing without calling the actual API.
     *
     * @param string $account
     * @param string $username
     * @return array|null
     */
    protected function callStatusServiceGenerateStatus(string $account, string $username): ?array
    {
        return StatusService::generateStatus($account, $username);
    }

    /**
     * Wrapper for user info retrieval to allow test stubbing.
     * This method exists to enable TestableQueueService to provide
     * fake user data during testing.
     *
     * @param string $username
     * @return object|null
     */
    protected function getUserInfo(string $username): ?object
    {
        $info = User::getUserInfo($username);

        if ($info === null) {
            return null;
        }

        return is_object($info) ? $info : (object) $info;
    }

    /**
     * Wrapper for updating API calls to allow test tracking.
     * This method exists to enable TestableQueueService to track
     * API usage updates during testing.
     *
     * @param string $username
     * @param int $usedApiCalls
     * @return void
     */
    protected function updateUsedApiCalls(string $username, int $usedApiCalls): void
    {
        User::updateUsedApiCalls($username, $usedApiCalls);
    }

    /**
     * Wrapper for setting limit email flag to allow test tracking.
     * This method exists to enable TestableQueueService to track
     * when limit emails are sent during testing.
     *
     * @param string $username
     * @param bool $sent
     * @return void
     */
    protected function setLimitEmailSent(string $username, bool $sent): void
    {
        User::setLimitEmailSent($username, $sent);
    }

    /**
     * Wrapper for sending limit email to allow test stubbing.
     * This method exists to enable TestableQueueService to verify
     * email notifications without actually sending emails during testing.
     *
     * @param object $user
     * @return void
     */
    protected function sendLimitEmail(object $user): void
    {
        if (!isset($user->email, $user->username)) {
            return;
        }

        Mailer::sendTemplate(
            (string) $user->email,
            'API Limit Reached',
            'api_limit_reached',
            ['username' => (string) $user->username]
        );
    }

    /**
     * Returns a timestamp for the specified hour on the reference day.
     * This always returns a timestamp on the same calendar day as the reference,
     * regardless of whether the hour has passed.
     *
     * @param int $hour The hour (0-23) to schedule.
     * @param int $reference The reference timestamp.
     * @return int The scheduled timestamp (always same day as reference).
     */
    protected function scheduledTimestampForHour(int $hour, int $reference): int
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $referenceTime = (new DateTimeImmutable('@' . $reference))->setTimezone($tz);
        $scheduled = $referenceTime->setTime($hour, 0, 0);

        return (int) $scheduled->format('U');
    }

    protected function generateJobId(): string
    {
        $data = \random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function processJobBatch(array $jobs, bool $isRetryBatch): void
    {
        foreach ($jobs as $job) {
            if (!isset($job['id'], $job['account'], $job['username'])) {
                continue;
            }

            $status = strtolower((string) ($job['status'] ?? 'pending'));
            if ($status !== 'pending' && $status !== 'retry') {
                $this->deleteJobById($job['id']);
                continue;
            }

            try {
                $this->processJobPayload($job);
                $this->deleteJobById($job['id']);
            } catch (\Throwable $e) {
                // On failure, reset processing flag and handle retry logic
                ErrorManager::getInstance()->log(
                    sprintf(
                        '[QueueService] Job %s for %s/%s (status: %s) failed: %s',
                        (string) ($job['id'] ?? 'unknown'),
                        (string) ($job['username'] ?? 'unknown'),
                        (string) ($job['account'] ?? 'unknown'),
                        $status,
                        $e->getMessage()
                    ),
                    'error'
                );
                
                if ($isRetryBatch || $status === 'retry') {
                    // Retry jobs that fail should be deleted
                    $this->deleteJobById($job['id']);
                } else {
                    // Pending jobs that fail should be marked for retry
                    $this->markJobStatusAndProcessing($job['id'], 'retry', false);
                }
            }
        }
    }

    private function storeJob(string $username, string $account, int $scheduledAt, string $status): void
    {
        $this->insertJobInStorage($this->generateJobId(), $username, $account, $scheduledAt, $status);
    }

    /**
     * @return int[]
     */
    private function normalizeHours(string $cron): array
    {
        $parts = array_filter(array_map('trim', explode(',', $cron)), fn($v) => strlen($v) > 0);
        $hours = [];
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                continue;
            }
            $int = (int) $part;
            if ($int < 0 || $int > 23) {
                continue;
            }
            $hours[] = $int;
        }
        return array_values(array_unique($hours));
    }

    /**
     * @param string[] $days
     */
    private function isScheduledDayAllowed(array $days, int $scheduledAt): bool
    {
        if ($days === [] || in_array('everyday', $days, true)) {
            return true;
        }

        $dayName = strtolower(date('l', $scheduledAt));

        return in_array($dayName, $days, true);
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @param array<int, string> $attemptedIds
     * @return array<int, array<string, mixed>>
     */
    private function filterUnattemptedJobs(array $jobs, array $attemptedIds): array
    {
        if ($attemptedIds === []) {
            return $jobs;
        }

        $seen = array_fill_keys($attemptedIds, true);
        $filtered = [];

        foreach ($jobs as $job) {
            $id = (string) ($job['id'] ?? '');
            if ($id === '' || isset($seen[$id])) {
                continue;
            }

            $filtered[] = $job;
        }

        return $filtered;
    }

    /**
     * Process a job payload.
     *
     * @param array<string, mixed> $job
     * @return void
     */
    private function processJobPayload(array $job): void
    {
        $this->generateStatusesForJob($job, 1); // Generate 1 status per job
    }
}
