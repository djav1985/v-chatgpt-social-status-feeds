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
        $now = time();
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

            if (StatusJob::exists($username, $account, $scheduledAt)) {
                continue;
            }

            $this->storeJob($username, $account, $scheduledAt, 'pending');
        }
    }

    public function removeFutureJobs(string $username, string $account): void
    {
        StatusJob::deleteFutureJobs($username, $account, time());
    }

    public function removeAllJobs(string $username, string $account): void
    {
        StatusJob::deleteAllForAccount($username, $account);
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
            $count = StatusJob::resetAllProcessingFlags();
            if ($count > 0) {
                ErrorManager::getInstance()->log(
                    sprintf('[QueueService] Reset %d stuck processing flag(s) from previous worker run.', $count),
                    'info'
                );
            }

            do {
                $processedAny = false;

                $retryJobs = $this->filterUnattemptedJobs(
                    $this->claimDueJobsByStatus(time(), 'retry'),
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
                    $this->claimDueJobsByStatus(time(), 'pending'),
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

                // Add idle sleep when no jobs were processed to avoid tight loops
                if (!$processedAny) {
                    sleep(5);
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
            StatusJob::clearAllPendingJobs();
            $accounts = Account::getAllAccounts();
            $now = time();

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

                    if (StatusJob::exists($username, $acct, $scheduledAt)) {
                        continue;
                    }

                    $this->storeJob($username, $acct, $scheduledAt, 'pending');
                }
            }
        } finally {
            $this->releaseWorkerLock();
        }
    }

    private function claimWorkerLock(): bool
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

    private function releaseWorkerLock(): void
    {
        WorkerHelper::releaseLock($this->workerLock);
        $this->workerLock = null;
    }

    /**
     * Atomically claim jobs with a specific status for processing.
     * Filters jobs by the given status parameter and prevents concurrent execution.
     * 
     * @param int $now Current timestamp.
     * @param string $status Job status to filter by (e.g., 'pending' or 'retry').
     * @return array<int, array<string, mixed>> Array of successfully claimed jobs.
     */
    private function claimDueJobsByStatus(int $now, string $status): array
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

    private function releaseStaleProcessingJobs(int $now): int
    {
        $timeout = defined('STATUS_JOB_STALE_AFTER') ? (int) constant('STATUS_JOB_STALE_AFTER') : 3600;
        return StatusJob::releaseStaleJobs($now, $timeout);
    }

    private function generateStatusesForJob(array $job, int $count): void
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
                    User::setLimitEmailSent($username, true);
                    $limitEmailSent = true;
                }

                throw new RuntimeException(sprintf('API limit reached for %s.', $username));
            }

            $result = StatusService::generateStatus($account, $username);
            if (isset($result['error'])) {
                throw new RuntimeException((string) $result['error']);
            }

            $usedApiCalls++;
            User::updateUsedApiCalls($username, $usedApiCalls);

            if (property_exists($user, 'used_api_calls')) {
                $user->used_api_calls = $usedApiCalls;
            }
        }
    }

    /**
     * Wrapper for user info retrieval to allow test stubbing.
     * This method exists to enable TestableQueueService to provide
     * fake user data during testing.
     *
     * @param string $username
     * @return object|null
     */
    private function getUserInfo(string $username): ?object
    {
        $info = User::getUserInfo($username);

        if ($info === null) {
            return null;
        }

        return is_object($info) ? $info : (object) $info;
    }

    /**
     * Wrapper for sending limit email to allow test stubbing.
     * This method exists to enable TestableQueueService to verify
     * email notifications without actually sending emails during testing.
     *
     * @param object $user
     * @return void
     */
    private function sendLimitEmail(object $user): void
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
    private function scheduledTimestampForHour(int $hour, int $reference): int
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $referenceTime = (new DateTimeImmutable('@' . $reference))->setTimezone($tz);
        $scheduled = $referenceTime->setTime($hour, 0, 0);

        return (int) $scheduled->format('U');
    }

    private function generateJobId(): string
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
                StatusJob::deleteById($job['id']);
                continue;
            }

            try {
                $this->processJobPayload($job);
                StatusJob::deleteById($job['id']);
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
                    StatusJob::deleteById($job['id']);
                } else {
                    // Pending jobs that fail should be marked for retry
                    StatusJob::markStatusAndProcessing($job['id'], 'retry', false);
                }

                // Add 10-second backoff after failed status generation to avoid tight retry loops
                sleep(10);
            }
        }
    }

    private function storeJob(string $username, string $account, int $scheduledAt, string $status): void
    {
        StatusJob::insert($this->generateJobId(), $username, $account, $scheduledAt, $status);
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
