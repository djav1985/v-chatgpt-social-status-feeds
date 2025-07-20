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

use PDO;
use DateTime;
use Exception;
use App\Models\Database;
use App\Core\ErrorMiddleware;

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
    public static function insert(string $username, string $account, string $runAt, string $status = 'pending', ?string $payload = null): bool
    {
        try {
            $db = new Database();
            $db->query("INSERT INTO status_jobs (username, account, run_at, status, payload) VALUES (:u, :a, :r, :s, :p)");
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
            $db->bind(':l', $limit, PDO::PARAM_INT);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error retrieving jobs: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Remove future jobs for an account.
     *
     * @param string $username
     * @param string $account
     * @return bool
     */
    public static function removeFuture(string $username, string $account): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_jobs WHERE username = :u AND account = :a AND run_at > NOW()");
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
     * Populate the queue with jobs for the next 24 hours.
     */
    public static function fillQueryJobs(): bool
    {
        $now = new DateTime();
        $end = (clone $now)->modify('+24 hours');

        $accounts = Account::getAllAccounts();
        if (empty($accounts)) {
            return true;
        }

        foreach ($accounts as $account) {
            $hours = array_filter(array_map('trim', explode(',', $account->cron)), 'strlen');
            if (empty($hours)) {
                continue;
            }
            $days = array_map('strtolower', array_map('trim', explode(',', $account->days)));
            foreach ($hours as $hour) {
                if (!is_numeric($hour)) {
                    continue;
                }
                $runTime = new DateTime();
                $runTime->setTime((int) $hour, 0);
                if ($runTime < $now) {
                    $runTime->modify('+1 day');
                }
                if ($runTime > $end) {
                    continue;
                }
                $dayName = strtolower($runTime->format('l'));
                if (!in_array('everyday', $days) && !in_array($dayName, $days)) {
                    continue;
                }
                $runAt = $runTime->format('Y-m-d H:i:s');
                $db = new Database();
                $db->query('SELECT id FROM status_jobs WHERE username = :u AND account = :a AND run_at = :r LIMIT 1');
                $db->bind(':u', $account->username);
                $db->bind(':a', $account->account);
                $db->bind(':r', $runAt);
                if (!$db->single()) {
                    self::insert($account->username, $account->account, $runAt, 'pending');
                }
            }
        }
        return true;
    }
}

