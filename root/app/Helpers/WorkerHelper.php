<?php

namespace App\Helpers;

/**
 * Centralized worker lock management for all job types.
 * 
 * Manages lock files for run-queue, fill-queue, daily, and monthly workers
 * to prevent multiple instances of the same worker from running simultaneously
 * while allowing different workers to run concurrently.
 */
class WorkerHelper
{
    /**
     * Get the lock file path for a specific job type.
     *
     * @param string $jobType The job type (run-queue, fill-queue, daily, monthly)
     * @return string The absolute path to the lock file
     */
    public static function getLockPath(string $jobType): string
    {
        return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'socialrss-worker-' . $jobType . '.lock';
    }

    /**
     * Check if a process with the given PID is currently running.
     *
     * @param int $pid The process ID to check
     * @return bool True if the process is running, false otherwise
     */
    public static function isProcessRunning(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        // Use posix_kill if available for better accuracy
        if (function_exists('posix_kill')) {
            return @posix_kill($pid, 0);
        }

        // Fallback to /proc filesystem check
        $procPath = '/proc/' . $pid;
        if (!@is_dir($procPath)) {
            return false;
        }

        // When /proc is available, double-check the process command line to reduce
        // false positives caused by PID reuse. We expect cron.php (or the PHP
        // binary invocation) to appear in the cmdline for a legitimate worker.
        $cmdlineFile = $procPath . '/cmdline';
        if (is_readable($cmdlineFile)) {
            $cmd = @file_get_contents($cmdlineFile);
            if ($cmd !== false) {
                $cmd = str_replace("\0", ' ', $cmd);
                // Check if this is a worker process by looking for cron.php or job type
                if (stripos($cmd, 'cron.php') !== false || 
                    stripos($cmd, 'run-queue') !== false ||
                    stripos($cmd, 'fill-queue') !== false ||
                    stripos($cmd, 'daily') !== false ||
                    stripos($cmd, 'monthly') !== false) {
                    return true;
                }
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a worker can be launched (no other instance running).
     * This method does NOT claim the lock - it only checks if launch is possible.
     *
     * @param string $jobType The job type to check
     * @return bool True if the worker can be launched, false if another instance is running
     */
    public static function canLaunch(string $jobType): bool
    {
        $lockPath = self::getLockPath($jobType);
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return false;
        }

        $lockAcquired = @flock($handle, LOCK_EX | LOCK_NB);
        if (!$lockAcquired) {
            fclose($handle);
            return false;
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $pid = (int) trim((string) $contents);
        
        $canLaunch = !($pid > 0 && self::isProcessRunning($pid));

        flock($handle, LOCK_UN);
        fclose($handle);

        // Clean up stale lock file
        if ($canLaunch && $pid > 0) {
            @unlink($lockPath);
        }

        return $canLaunch;
    }

    /**
     * Claim the worker lock for the current process.
     * This method acquires an exclusive lock and writes the current PID.
     * The lock handle must be stored and passed to releaseLock() later.
     *
     * @param string $jobType The job type to claim lock for
     * @return array|null Returns array with 'handle' and 'path' on success, null on failure
     */
    public static function claimLock(string $jobType): ?array
    {
        $lockPath = self::getLockPath($jobType);
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return null;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $existingPid = (int) trim((string) $contents);

        if ($existingPid > 0 && self::isProcessRunning($existingPid)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return null;
        }

        ftruncate($handle, 0);
        rewind($handle);

        $pid = getmypid();
        if (!is_int($pid) || $pid <= 0) {
            try {
                $pid = random_int(1, PHP_INT_MAX);
            } catch (\Throwable $exception) {
                $pid = mt_rand(1, PHP_INT_MAX);
            }
        }

        fwrite($handle, (string) $pid);
        fflush($handle);

        return [
            'handle' => $handle,
            'path' => $lockPath,
        ];
    }

    /**
     * Release a previously claimed worker lock.
     *
     * @param array|null $lockInfo The lock info array returned by claimLock()
     * @return void
     */
    public static function releaseLock(?array $lockInfo): void
    {
        if ($lockInfo === null) {
            return;
        }

        $handle = $lockInfo['handle'] ?? null;
        $lockPath = $lockInfo['path'] ?? null;

        if (is_resource($handle)) {
            ftruncate($handle, 0);
            fflush($handle);
            if ($lockPath !== null) {
                @unlink($lockPath);
            }
            flock($handle, LOCK_UN);
            fclose($handle);
        } elseif ($lockPath !== null) {
            @unlink($lockPath);
        }
    }

    /**
     * Claim a lock and write PID to the lock file, used by cron.php worker launcher.
     * This is a simplified version that writes the PID but doesn't keep the handle open.
     *
     * @param string $jobType The job type to claim lock for
     * @return bool True if lock was claimed successfully, false otherwise
     */
    public static function claimLockAndWritePid(string $jobType): bool
    {
        $lockPath = self::getLockPath($jobType);
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return false;
        }

        $lockAcquired = @flock($handle, LOCK_EX | LOCK_NB);
        if (!$lockAcquired) {
            fclose($handle);
            return false;
        }

        rewind($handle);
        $contents = stream_get_contents($handle);
        $pid = (int) trim((string) $contents);
        
        if ($pid > 0 && self::isProcessRunning($pid)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return false;
        }

        // Clear any stale PID from the lock file
        ftruncate($handle, 0);
        rewind($handle);

        $pid = getmypid();
        if (!is_int($pid) || $pid <= 0) {
            try {
                $pid = random_int(1, PHP_INT_MAX);
            } catch (\Throwable $exception) {
                $pid = mt_rand(1, PHP_INT_MAX);
            }
        }

        fwrite($handle, (string) $pid);
        fflush($handle);

        flock($handle, LOCK_UN);
        fclose($handle);

        return true;
    }
}
