<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: cron.php
 * Description: Entry point for maintenance tasks and queue worker
 */

// This script is intended for CLI use only. If accessed via a web server,
// return HTTP 403 Forbidden.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

// Validate arguments before loading config
$validJobTypes = ['run-queue', 'fill-queue', 'daily', 'monthly'];

/**
 * @return never
 */
function printUsage(): void
{
    echo "Usage:\n";
    echo "  php cron.php {run-queue|fill-queue|daily|monthly}\n";
    echo "  php cron.php worker {run-queue|fill-queue|daily|monthly}\n";
    echo "\n";
    echo "Tasks:\n";
    echo "  run-queue  - Process queued jobs with scheduled_at <= now\n";
    echo "  fill-queue - Add future job slots without truncating existing jobs\n";
    echo "  daily      - Run daily cleanup (purge statuses, images, IPs)\n";
    echo "  monthly    - Reset API usage counters\n";
    exit(1);
}



/**
 * Get the lock file path for a specific job type.
 * This is a standalone function that doesn't require autoloading.
 */
function workerLockPath(string $jobType): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
        . DIRECTORY_SEPARATOR
        . 'socialrss-worker-' . $jobType . '.lock';
}

/**
 * Check if a process with the given PID is currently running.
 * This is a standalone function that doesn't require autoloading.
 */
function workerProcessIsRunning(int $pid): bool
{
    if ($pid <= 0) {
        return false;
    }

    if (function_exists('posix_kill')) {
        return @posix_kill($pid, 0);
    }

    $procPath = '/proc/' . $pid;
    if (!@is_dir($procPath)) {
        return false;
    }

    $cmdlineFile = $procPath . '/cmdline';
    if (is_readable($cmdlineFile)) {
        $cmd = @file_get_contents($cmdlineFile);
        if ($cmd !== false) {
            $cmd = str_replace("\0", ' ', $cmd);
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
 */
function workerCanLaunch(string $jobType): bool
{
    $lockPath = workerLockPath($jobType);
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
    
    $canLaunch = !($pid > 0 && workerProcessIsRunning($pid));

    flock($handle, LOCK_UN);
    fclose($handle);

    // Clean up stale lock file
    if ($canLaunch && $pid > 0) {
        @unlink($lockPath);
    }

    return $canLaunch;
}

/**
 * Claim the lock and write PID to the lock file.
 */
function workerClaimLock(string $jobType): bool
{
    $lockPath = workerLockPath($jobType);
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
    
    if ($pid > 0 && workerProcessIsRunning($pid)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
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

    flock($handle, LOCK_UN);
    fclose($handle);

    return true;
}

function launchQueueWorker(string $jobType): void
{
    if (getenv('CRON_DISABLE_WORKER_SPAWN') !== false) {
        return;
    }

    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($jobType) . ' > /dev/null 2>&1 &';
    exec($command);
}

array_shift($argv);
$args = array_values(array_filter($argv, static function ($arg) {
    return $arg !== '--once';
}));

if (empty($args)) {
    printUsage();
}

$jobType = '';

if ($args[0] === 'worker') {
    if (count($args) !== 2) {
        printUsage();
    }

    $jobType = $args[1];
    if (!in_array($jobType, $validJobTypes, true)) {
        printUsage();
    }

    // For run-queue, spawn a worker process that will claim its own lock
    if ($jobType === 'run-queue') {
        if (!workerCanLaunch($jobType)) {
            echo 'Worker "' . $jobType . '" already running.' . PHP_EOL;
            exit(0);
        }
        launchQueueWorker($jobType);
        exit(0);
    }
    
    // For fill-queue, daily, and monthly, we just check if another instance is running
    // The actual lock will be claimed by QueueService
    if (!workerCanLaunch($jobType)) {
        echo 'Worker "' . $jobType . '" already running.' . PHP_EOL;
        exit(0);
    }

} else {
    if (count($args) !== 1) {
        printUsage();
    }

    $jobType = $args[0];
    if (!in_array($jobType, $validJobTypes, true)) {
        printUsage();
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorManager;
use App\Services\QueueService;
use App\Helpers\WorkerHelper;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorManager::handle(function () use ($jobType) {
    $service = new QueueService($jobType);

    switch ($jobType) {
        case 'run-queue':
            $service->runQueue();
            break;
        case 'fill-queue':
            $service->fillQueue();
            break;
        case 'daily':
            $service->runDaily();
            break;
        case 'monthly':
            $service->runMonthly();
            break;
    }
});
