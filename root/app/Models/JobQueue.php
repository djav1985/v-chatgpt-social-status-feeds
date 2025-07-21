<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: JobQueue.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Doctrine\DBAL\ParameterType;
use DateTime;
use Exception;
use App\Models\Database;
use App\Core\ErrorMiddleware;
use App\Models\Account;

class JobQueue
{
    /**
     * Insert a job into the queue.
     *
     * @param string $username
     * @param string $account
     * @param string $runAt
     * @param string $status
     * @param string|null $payload
     * @return bool
     */
    public static function insert(string $username, string $account, string $runAt, string $status = 'pending', ?string $payload = null, ?Database $db = null): bool
    {
        $close = false;
        try {
            if ($db === null) {
                $db = new Database();
                $close = true;
            }
            $db->query("INSERT IGNORE INTO status_jobs (username, account, run_at, status, payload) VALUES (:u, :a, :r, :s, :p)");
            $db->bind(':u', $username);
            $db->bind(':a', $account);
            $db->bind(':r', $runAt);
            $db->bind(':s', $status);
            $db->bind(':p', $payload);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error inserting job: ' . $e->getMessage(), 'error');
            throw $e;
        } finally {
            if ($close) {
                unset($db);
            }
        }
    }

    /**
     * Retrieve pending jobs ready to run.
     *
     * @param int $limit
     * @return array
     */
    public static function getPending(int $limit): array
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM status_jobs WHERE status = 'pending' AND run_at <= NOW() ORDER BY run_at ASC LIMIT :l");
            $db->bind(':l', $limit, ParameterType::INTEGER);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error retrieving jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Atomically claim pending jobs for processing.
     *
     * @param int $limit
     * @return array
     */
    public static function claimPending(int $limit): array
    {
        $db = new Database();
        $db->beginTransaction();
        try {
            $db->query("SELECT * FROM status_jobs WHERE status = 'pending' AND run_at <= NOW() ORDER BY run_at ASC LIMIT :l FOR UPDATE");
            $db->bind(':l', $limit, ParameterType::INTEGER);
            $jobs = $db->resultSet();

            // Prepare the update statement once and reuse it for each job
            $db->query("UPDATE status_jobs SET status = 'processing' WHERE id = :id");
            foreach ($jobs as $job) {
                $db->bind(':id', $job->id);
                $db->execute();
            }

            $db->commit();
            return $jobs;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorMiddleware::logMessage('Error claiming jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Mark a job as completed.
     *
     * @param int $id
     * @return bool
     */
    public static function markCompleted(int $id): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE status_jobs SET status = 'completed' WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error marking job completed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Mark a job as failed.
     *
     * @param int $id
     * @return bool
     */
    public static function markFailed(int $id): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE status_jobs SET status = 'failed' WHERE id = :id");
            $db->bind(':id', $id);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error marking job failed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove completed jobs older than the given number of days.
     *
     * @param int $days
     * @return bool
     */
    public static function cleanupOld(int $days = 7): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_jobs WHERE status IN ('completed','failed') AND run_at < DATE_SUB(NOW(), INTERVAL :d DAY)");
            $db->bind(':d', $days, ParameterType::INTEGER);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error cleaning up jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove all jobs from the queue.
     */
    public static function clearAll(): bool
    {
        try {
            $db = new Database();
            $db->query('DELETE FROM status_jobs');
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error clearing jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove jobs for the remainder of today for an account.
     *
     * @param string $username
     * @param string $account
     * @return bool
     */
    public static function removeRemainingToday(string $username, string $account): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_jobs WHERE username = :u AND account = :a AND run_at >= NOW() AND run_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
            $db->bind(':u', $username);
            $db->bind(':a', $account);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error removing jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove all jobs for an account.
     *
     * @param string $username
     * @param string $account
     * @return bool
     */
    public static function removeAccount(string $username, string $account): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_jobs WHERE username = :u AND account = :a");
            $db->bind(':u', $username);
            $db->bind(':a', $account);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error removing jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove all jobs for a user.
     *
     * @param string $username
     * @return bool
     */
    public static function removeUser(string $username): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_jobs WHERE username = :u");
            $db->bind(':u', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error removing jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Populate the queue with jobs for the current day.
     * Existing entries are cleared first so midnight jobs are not missed.
     */
    public static function fillQueryJobs(): bool
    {
        self::clearAll();

        $start = new DateTime('today');
        $end = (clone $start)->modify('+1 day');

        $accounts = Account::getAllAccounts();
        if (empty($accounts)) {
            return true;
        }

        $db = new Database();
        foreach ($accounts as $account) {
            self::scheduleAccount($db, $account, $start, $end);
        }

        return true;
    }

    /**
     * Populate remaining jobs today for a specific account.
     */
    public static function fillRemainingToday(string $username, string $account): bool
    {
        $start = new DateTime();
        $end = (clone $start)->setTime(0, 0)->modify('+1 day');
        $acct = Account::getAcctInfo($username, $account);
        if (!$acct) {
            return false;
        }
        $db = new Database();
        self::scheduleAccount($db, $acct, $start, $end);
        return true;
    }

    /**
     * Helper to schedule an account's jobs within a time range.
     */
    private static function scheduleAccount(Database $db, $account, DateTime $start, DateTime $end): void
    {
        $hours = array_filter(array_map('trim', explode(',', $account->cron)), 'strlen');
        if (empty($hours)) {
            return;
        }
        $days = array_map('strtolower', array_map('trim', explode(',', $account->days)));

        foreach ($hours as $hour) {
            if (!is_numeric($hour)) {
                continue;
            }
            $hour = (int) $hour;
            if ($hour < 0 || $hour > 23) {
                continue;
            }
            $runTime = (clone $start)->setTime($hour, 0);
            if ($runTime < $start) {
                $runTime->modify('+1 day');
            }
            if ($runTime >= $end) {
                continue;
            }
            $dayName = strtolower($runTime->format('l'));
            if (!in_array('everyday', $days) && !in_array($dayName, $days)) {
                continue;
            }
            $runAt = $runTime->format('Y-m-d H:i:s');
            self::insert($account->username, $account->account, $runAt, 'pending', null, $db);
        }
    }
}

