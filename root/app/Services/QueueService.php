<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use App\Core\DatabaseManager;
use App\Services\StatusService;
use App\Models\Account;
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
            error_log('[QueueService] Queue worker already running; skipping runQueue invocation.');
            return;
        }

        $attemptedJobIds = [];

        try {
            // Since we have the worker lock, no other worker can be running.
            // Reset all processing flags from any previously crashed/interrupted workers.
            $this->resetAllProcessingFlags();

            do {
                $processedAny = false;

                $retryJobs = $this->filterUnattemptedJobs(
                    $this->claimDueJobsByStatus($this->now(), 'retry'),
                    $attemptedJobIds
                );
                if (!empty($retryJobs)) {
                    $this->processJobBatch($retryJobs, true);
                    $attemptedJobIds = array_merge($attemptedJobIds, $this->extractJobIds($retryJobs));
                    $processedAny = true;
                }

                $pendingJobs = $this->filterUnattemptedJobs(
                    $this->claimDueJobsByStatus($this->now(), 'pending'),
                    $attemptedJobIds
                );
                if (!empty($pendingJobs)) {
                    $this->processJobBatch($pendingJobs, false);
                    $attemptedJobIds = array_merge($attemptedJobIds, $this->extractJobIds($pendingJobs));
                    $processedAny = true;
                }
            } while ($processedAny);
        } finally {
            $this->releaseWorkerLock();
        }
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
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, string>
     */
    private function extractJobIds(array $jobs): array
    {
        $ids = [];

        foreach ($jobs as $job) {
            $id = (string) ($job['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
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
                error_log(sprintf(
                    '[QueueService] Job %s for %s/%s (status: %s) failed: %s',
                    (string) ($job['id'] ?? 'unknown'),
                    (string) ($job['username'] ?? 'unknown'),
                    (string) ($job['account'] ?? 'unknown'),
                    $status,
                    $e->getMessage()
                ));
                
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

    public function fillQueue(): void
    {
        if (!$this->claimWorkerLock()) {
            error_log('[QueueService] Fill queue worker already running; skipping fillQueue invocation.');
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

    protected function clearAllJobs(): void
    {
        $db = DatabaseManager::getInstance();
        $db->query("DELETE FROM status_jobs WHERE status = 'pending'");
        $db->execute();
    }


    protected function now(): int
    {
        return time();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchDueJobs(int $now): array
    {
        $db = DatabaseManager::getInstance();
        $db->query('SELECT id, account, username, scheduled_at, status FROM status_jobs WHERE status IN (\'pending\', \'retry\') AND scheduled_at <= :now ORDER BY scheduled_at ASC');
        $db->bind(':now', $now);
        return $db->resultSet();
    }

    /**
     * Atomically claim jobs for processing to prevent concurrent execution.
     * @return array<int, array<string, mixed>>
     */
    protected function claimDueJobs(int $now): array
    {
        $db = DatabaseManager::getInstance();
        $claimedJobs = [];

        $this->releaseStaleProcessingJobs($now);

        // First, get candidate job IDs
        $db->query('SELECT id, account, username, scheduled_at, status FROM status_jobs WHERE status IN (\'pending\', \'retry\') AND scheduled_at <= :now AND processing = FALSE ORDER BY scheduled_at ASC');
        $db->bind(':now', $now);
        $candidates = $db->resultSet();
        
        // Atomically claim each job individually by setting processing = TRUE
        foreach ($candidates as $candidate) {
            // Try to atomically claim this specific job
            $db->query('UPDATE status_jobs SET processing = TRUE WHERE id = :id AND processing = FALSE');
            $db->bind(':id', $candidate['id']);
            $db->execute();
            
            // If we successfully claimed it (rowCount = 1), add to our list
            if ($db->rowCount() === 1) {
                $candidate['processing'] = true;
                $claimedJobs[] = $candidate;
            }
        }
        
        return $claimedJobs;
    }

    /**
     * Atomically claim jobs with specific status for processing to prevent concurrent execution.
     * @return array<int, array<string, mixed>>
     */
    protected function claimDueJobsByStatus(int $now, string $status): array
    {
        $db = DatabaseManager::getInstance();
        $claimedJobs = [];

        // First, get candidate job IDs for specific status
        $db->query('SELECT id, account, username, scheduled_at, status FROM status_jobs WHERE status = :status AND scheduled_at <= :now AND processing = FALSE ORDER BY scheduled_at ASC');
        $db->bind(':status', $status);
        $db->bind(':now', $now);
        $candidates = $db->resultSet();

        // Atomically claim each job individually by setting processing = TRUE
        foreach ($candidates as $candidate) {
            // Try to atomically claim this specific job
            $db->query('UPDATE status_jobs SET processing = TRUE WHERE id = :id AND processing = FALSE');
            $db->bind(':id', $candidate['id']);
            $db->execute();
            
            // If we successfully claimed it (rowCount = 1), add to our list
            if ($db->rowCount() === 1) {
                $candidate['processing'] = true;
                $claimedJobs[] = $candidate;
            }
        }
        
        return $claimedJobs;
    }

    protected function releaseStaleProcessingJobs(int $now): int
    {
        $timeout = defined('STATUS_JOB_STALE_AFTER') ? (int) constant('STATUS_JOB_STALE_AFTER') : 3600;
        if ($timeout <= 0) {
            return 0;
        }

        $threshold = max(0, $now - $timeout);

        $db = DatabaseManager::getInstance();
        $db->query('UPDATE status_jobs SET processing = FALSE WHERE processing = TRUE AND scheduled_at <= :threshold');
        $db->bind(':threshold', $threshold);
        $db->execute();

        return $db->rowCount();
    }

    /**
     * Reset all processing flags.
     * This is safe to call when holding the worker lock since no other worker can be running.
     */
    protected function resetAllProcessingFlags(): int
    {
        $db = DatabaseManager::getInstance();
        $db->query('UPDATE status_jobs SET processing = FALSE WHERE processing = TRUE');
        $db->execute();

        $count = $db->rowCount();
        if ($count > 0) {
            error_log(sprintf('[QueueService] Reset %d stuck processing flag(s) from previous worker run.', $count));
        }

        return $count;
    }

    protected function deleteJobById(string $id): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('DELETE FROM status_jobs WHERE id = :id');
        $db->bind(':id', $id);
        $db->execute();
    }

    protected function markJobStatus(string $id, string $status): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('UPDATE status_jobs SET status = :status WHERE id = :id');
        $db->bind(':status', $status);
        $db->bind(':id', $id);
        $db->execute();
    }

    protected function markJobStatusAndProcessing(string $id, string $status, bool $processing): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('UPDATE status_jobs SET status = :status, processing = :processing WHERE id = :id');
        $db->bind(':status', $status);
        $db->bind(':processing', $processing ? 1 : 0);
        $db->bind(':id', $id);
        $db->execute();
    }

    protected function jobExistsInStorage(string $username, string $account, int $scheduledAt): bool
    {
        $db = DatabaseManager::getInstance();
        $db->query('SELECT id FROM status_jobs WHERE username = :username AND account = :account AND scheduled_at = :scheduled_at LIMIT 1');
        $db->bind(':username', $username);
        $db->bind(':account', $account);
        $db->bind(':scheduled_at', $scheduledAt);
        return $db->single() !== false;
    }

    private function storeJob(string $username, string $account, int $scheduledAt, string $status): void
    {
        $this->insertJobInStorage($this->generateJobId(), $username, $account, $scheduledAt, $status);
    }

    protected function insertJobInStorage(string $id, string $username, string $account, int $scheduledAt, string $status): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('INSERT INTO status_jobs (id, scheduled_at, account, username, status) VALUES (:id, :scheduled_at, :account, :username, :status)');
        $db->bind(':id', $id);
        $db->bind(':scheduled_at', $scheduledAt);
        $db->bind(':account', $account);
        $db->bind(':username', $username);
        $db->bind(':status', $status);
        $db->execute();
    }

    protected function deleteFutureJobs(string $username, string $account, int $fromTimestamp): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('DELETE FROM status_jobs WHERE username = :username AND account = :account AND scheduled_at >= :scheduled_at');
        $db->bind(':username', $username);
        $db->bind(':account', $account);
        $db->bind(':scheduled_at', $fromTimestamp);
        $db->execute();
    }

    protected function deleteAllJobsForAccount(string $username, string $account): void
    {
        $db = DatabaseManager::getInstance();
        $db->query('DELETE FROM status_jobs WHERE username = :username AND account = :account');
        $db->bind(':username', $username);
        $db->bind(':account', $account);
        $db->execute();
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

    private function statusesPerJob(): int
    {
        return 1;
    }

    protected function generateJobId(): string
    {
        $data = \random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
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
}
