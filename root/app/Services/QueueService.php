<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

namespace App\Services;

use App\Core\DatabaseManager;
use App\Services\StatusService;
use App\Models\Account;
use App\Models\User;
use App\Models\Status;
use App\Core\Mailer;
use App\Models\Blacklist;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Service for queue operations and scheduled maintenance tasks.
 */
class QueueService
{
    public function __construct()
    {
    }

    public function enqueueRemainingJobs(string $username, string $account, string $cron, string $days): void
    {
        $now = $this->now();
        $dayName = strtolower(date('l', $now));
        $daysArr = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) $days))), fn($v) => strlen($v) > 0);
        if (!empty($daysArr) && !in_array('everyday', $daysArr, true) && !in_array($dayName, $daysArr, true)) {
            return;
        }

        foreach ($this->normalizeHours($cron) as $hour) {
            $scheduledAt = $this->scheduledTimestampForHour($hour, $now);
            if ($scheduledAt <= $now) {
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

    /**
     * Clean up old statuses for all accounts.
     */
    public function purgeStatuses(): bool
    {
        $db = DatabaseManager::getInstance();
        $db->query(
            'SELECT username, account, COUNT(*) AS status_count '
            . 'FROM status_updates '
            . 'GROUP BY username, account '
            . 'HAVING COUNT(*) > :max_statuses'
        );
        $db->bind(':max_statuses', MAX_STATUSES);
        $overLimitAccounts = $db->resultSet();

        if (empty($overLimitAccounts)) {
            return true;
        }

        foreach ($overLimitAccounts as $account) {
            $account = (object) $account;
            $accountName = (string) ($account->account ?? '');
            $accountOwner = (string) ($account->username ?? '');
            $statusCount = (int) ($account->status_count ?? 0);

            if ($accountName === '' || $accountOwner === '') {
                continue;
            }

            $deleteCount = $statusCount - MAX_STATUSES;
            if ($deleteCount <= 0) {
                continue;
            }

            if (!Status::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Purge old images from the public/images directory.
     */
    public function purgeImages(): bool
    {
        $imageDir = rtrim($this->getImageDirectory(), DIRECTORY_SEPARATOR);
        if ($imageDir === '') {
            return true;
        }

        if (!is_dir($imageDir)) {
            $dirMode = defined('DIR_MODE') ? (int) constant('DIR_MODE') : 0755;
            if (!@mkdir($imageDir, $dirMode, true) && !is_dir($imageDir)) {
                return true;
            }
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($imageDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        $now = time();

        foreach ($files as $fileinfo) {
            if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') {
                $filePath = $fileinfo->getRealPath();
                $fileAge = ($now - $fileinfo->getMTime()) / 86400;

                if ($fileAge > IMG_AGE) {
                    if (!unlink($filePath)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Reset API usage for all users.
     */
    public function resetApi(): bool
    {
        if (!User::resetAllApiUsage()) {
            return false;
        }
        $users = User::getAllUsers();
        foreach ($users as $user) {
            $user = (object)$user;
            Mailer::sendTemplate(
                $user->email,
                'API Usage Reset',
                'api_usage_reset',
                ['username' => $user->username]
            );
        }
        return true;
    }

    protected function getImageDirectory(): string
    {
        return __DIR__ . '/../../public/images/';
    }

    /**
     * Purge old entries from the IP blacklist.
     */
    public function purgeIps(): bool
    {
        return Blacklist::clearIpBlacklist();
    }



    public function runQueue(): void
    {
        // First process retry jobs (jobs that have already failed once)
        $now = $this->now();
        $retryJobs = $this->claimDueJobsByStatus($now, 'retry');
        $this->processJobBatch($retryJobs, true);

        // Then process pending jobs (new jobs that haven't been tried yet)
        $pendingJobs = $this->claimDueJobsByStatus($now, 'pending');
        $this->processJobBatch($pendingJobs, false);
    }

    protected function processJobBatch(array $jobs, bool $isRetryBatch): void
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
        $accounts = $this->getAccounts();
        $now = $this->now();
        $dayName = strtolower(date('l', $now));

        foreach ($accounts as $account) {
            $account = (object)$account;
            $days = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) ($account->days ?? '')))), fn($v) => strlen($v) > 0);
            if (!empty($days) && !in_array('everyday', $days, true) && !in_array($dayName, $days, true)) {
                continue;
            }

            foreach ($this->normalizeHours((string) ($account->cron ?? '')) as $hour) {
                $scheduledAt = $this->scheduledTimestampForHour($hour, $now);
                if ($scheduledAt <= $now) {
                    continue;
                }

                $username = (string) ($account->username ?? '');
                $acct = (string) ($account->account ?? '');

                if ($username === '' || $acct === '') {
                    continue;
                }

                if ($this->jobExistsInStorage($username, $acct, $scheduledAt)) {
                    continue;
                }

                $this->storeJob($username, $acct, $scheduledAt, 'pending');
            }
        }
    }

    /**
     * Run daily cleanup: purgeStatuses(), purgeImages(), purgeIps().
     */
    public function runDaily(): void
    {
        $this->purgeStatuses();
        $this->purgeImages();
        $this->purgeIps();
    }

    /**
     * Run monthly maintenance: only resetApi().
     */
    public function runMonthly(): void
    {
        $this->resetApi();
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
        $batchSize = $this->getJobBatchSize(); // Use STATUS_JOB_BATCH_SIZE to limit concurrent jobs

        $this->releaseStaleProcessingJobs($now);

        // First, get candidate job IDs for specific status, limited by batch size
        $db->query('SELECT id, account, username, scheduled_at, status FROM status_jobs WHERE status = :status AND scheduled_at <= :now AND processing = FALSE ORDER BY scheduled_at ASC LIMIT :limit');
        $db->bind(':status', $status);
        $db->bind(':now', $now);
        $db->bind(':limit', $batchSize, \PDO::PARAM_INT);
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

    protected function storeJob(string $username, string $account, int $scheduledAt, string $status): void
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
    protected function processJobPayload(array $job): void
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
     * Wrapper for status generation to allow easier testing.
     */
    protected function callStatusServiceGenerateStatus(string $account, string $username): ?array
    {
        return StatusService::generateStatus($account, $username);
    }

    protected function getUserInfo(string $username): ?object
    {
        $info = User::getUserInfo($username);

        if ($info === null) {
            return null;
        }

        return is_object($info) ? $info : (object) $info;
    }

    protected function updateUsedApiCalls(string $username, int $usedApiCalls): void
    {
        User::updateUsedApiCalls($username, $usedApiCalls);
    }

    protected function setLimitEmailSent(string $username, bool $sent): void
    {
        User::setLimitEmailSent($username, $sent);
    }

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

    protected function statusesPerJob(): int
    {
        if (defined('STATUS_JOB_BATCH_SIZE')) {
            $batchSize = (int) constant('STATUS_JOB_BATCH_SIZE');
            if ($batchSize > 0) {
                return $batchSize;
            }
        }

        return 1;
    }

    protected function getJobBatchSize(): int
    {
        if (defined('STATUS_JOB_BATCH_SIZE')) {
            $batchSize = (int) constant('STATUS_JOB_BATCH_SIZE');
            if ($batchSize > 0) {
                return $batchSize;
            }
        }

        return 3; // Default batch size for concurrent job processing
    }

    protected function generateJobId(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @return int[]
     */
    protected function normalizeHours(string $cron): array
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

    protected function scheduledTimestampForHour(int $hour, int $reference): int
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $referenceTime = (new DateTimeImmutable('@' . $reference))->setTimezone($tz);
        $scheduled = $referenceTime->setTime($hour, 0, 0);

        if ((int) $scheduled->format('U') <= $reference) {
            $scheduled = $scheduled->add(new DateInterval('P1D'));
        }

        return (int) $scheduled->format('U');
    }
}
