<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: StatusJob.php
 * Description: Model for status job queue operations
 */

namespace App\Models;

use App\Core\DatabaseManager;
use App\Core\ErrorManager;
use Exception;

class StatusJob
{
    /**
     * Clear all pending jobs from the queue.
     *
     * @return bool True on success, false on failure.
     */
    public static function clearAllPendingJobs(): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("DELETE FROM status_jobs WHERE status = 'pending'");
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error clearing pending jobs: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Fetch due jobs by status.
     *
     * @param int $now Current timestamp.
     * @param string $status Job status (pending or retry).
     * @return array<int, array<string, mixed>> Array of job records.
     */
    public static function fetchDueJobsByStatus(int $now, string $status): array
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query(
                'SELECT id, account, username, scheduled_at, status 
                FROM status_jobs 
                WHERE status = :status AND scheduled_at <= :now AND processing = FALSE 
                ORDER BY scheduled_at ASC'
            );
            $db->bind(':status', $status);
            $db->bind(':now', $now);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error fetching due jobs: " . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Atomically claim a specific job by setting processing flag.
     *
     * @param string $jobId Job ID to claim.
     * @return bool True if claimed successfully, false otherwise.
     */
    public static function claimJob(string $jobId): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('UPDATE status_jobs SET processing = TRUE WHERE id = :id AND processing = FALSE');
            $db->bind(':id', $jobId);
            $db->execute();
            return $db->rowCount() === 1;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error claiming job {$jobId}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Release stale processing jobs that have timed out.
     *
     * @param int $now Current timestamp.
     * @param int $timeout Timeout in seconds.
     * @return int Number of jobs released.
     */
    public static function releaseStaleJobs(int $now, int $timeout): int
    {
        try {
            if ($timeout <= 0) {
                return 0;
            }

            $threshold = max(0, $now - $timeout);

            $db = DatabaseManager::getInstance();
            $db->query('UPDATE status_jobs SET processing = FALSE WHERE processing = TRUE AND scheduled_at <= :threshold');
            $db->bind(':threshold', $threshold);
            $db->execute();

            return $db->rowCount();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error releasing stale jobs: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Reset all processing flags.
     *
     * @return int Number of flags reset.
     */
    public static function resetAllProcessingFlags(): int
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('UPDATE status_jobs SET processing = FALSE WHERE processing = TRUE');
            $db->execute();
            return $db->rowCount();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error resetting processing flags: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Delete a job by ID.
     *
     * @param string $id Job ID.
     * @return bool True on success, false on failure.
     */
    public static function deleteById(string $id): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('DELETE FROM status_jobs WHERE id = :id');
            $db->bind(':id', $id);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error deleting job {$id}: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Mark a job's status.
     *
     * @param string $id Job ID.
     * @param string $status New status.
     * @return bool True on success, false on failure.
     */
    public static function markStatus(string $id, string $status): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('UPDATE status_jobs SET status = :status WHERE id = :id');
            $db->bind(':status', $status);
            $db->bind(':id', $id);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error marking job {$id} status: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Mark a job's status and processing flag.
     *
     * @param string $id Job ID.
     * @param string $status New status.
     * @param bool $processing Processing flag value.
     * @return bool True on success, false on failure.
     */
    public static function markStatusAndProcessing(string $id, string $status, bool $processing): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('UPDATE status_jobs SET status = :status, processing = :processing WHERE id = :id');
            $db->bind(':status', $status);
            $db->bind(':processing', $processing ? 1 : 0);
            $db->bind(':id', $id);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error marking job {$id} status and processing: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Check if a job exists in storage.
     *
     * @param string $username Account owner.
     * @param string $account Account name.
     * @param int $scheduledAt Scheduled timestamp.
     * @return bool True if exists, false otherwise.
     */
    public static function exists(string $username, string $account, int $scheduledAt): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query(
                'SELECT id FROM status_jobs 
                WHERE username = :username AND account = :account AND scheduled_at = :scheduled_at 
                LIMIT 1'
            );
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            $db->bind(':scheduled_at', $scheduledAt);
            return $db->single() !== false;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error checking job existence: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Insert a new job into storage.
     *
     * @param string $id Job ID.
     * @param string $username Account owner.
     * @param string $account Account name.
     * @param int $scheduledAt Scheduled timestamp.
     * @param string $status Job status.
     * @return bool True on success, false on failure.
     */
    public static function insert(string $id, string $username, string $account, int $scheduledAt, string $status): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query(
                'INSERT INTO status_jobs (id, scheduled_at, account, username, status) 
                VALUES (:id, :scheduled_at, :account, :username, :status)'
            );
            $db->bind(':id', $id);
            $db->bind(':scheduled_at', $scheduledAt);
            $db->bind(':account', $account);
            $db->bind(':username', $username);
            $db->bind(':status', $status);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error inserting job: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Delete future jobs for an account.
     *
     * @param string $username Account owner.
     * @param string $account Account name.
     * @param int $fromTimestamp Starting timestamp.
     * @return bool True on success, false on failure.
     */
    public static function deleteFutureJobs(string $username, string $account, int $fromTimestamp): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query(
                'DELETE FROM status_jobs 
                WHERE username = :username AND account = :account AND scheduled_at >= :scheduled_at'
            );
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            $db->bind(':scheduled_at', $fromTimestamp);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error deleting future jobs: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Delete all jobs for an account.
     *
     * @param string $username Account owner.
     * @param string $account Account name.
     * @return bool True on success, false on failure.
     */
    public static function deleteAllForAccount(string $username, string $account): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query('DELETE FROM status_jobs WHERE username = :username AND account = :account');
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error deleting all jobs for account: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
