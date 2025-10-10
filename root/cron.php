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

function queueWorkerProcessIsRunning(int $pid): bool
{
    if ($pid <= 0) {
        return false;
    }

    if (function_exists('posix_kill')) {
        return @posix_kill($pid, 0);
    }

    $procPath = '/proc/' . $pid;
    return @is_dir($procPath);
}

/**
 * Determine if the queue worker lock indicates an active worker.
 */
function queueWorkerIsActive(): bool
{
    $lockPath = queueWorkerLockPath();
    if (!is_file($lockPath)) {
        return false;
    }

    $pidContents = @file_get_contents($lockPath);
    if ($pidContents === false) {
        return false;
    }

    return queueWorkerProcessIsRunning((int) trim($pidContents));
}

function queueWorkerLockPath(): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'socialrss-queue-worker.lock';
}

function queueWorkerGuardCanLaunch(): bool
{
    $lockPath = queueWorkerLockPath();
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
    if ($pid > 0 && queueWorkerProcessIsRunning($pid)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    ftruncate($handle, 0);
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

    if ($jobType === 'run-queue') {
        if (!queueWorkerGuardCanLaunch()) {
            echo "Queue worker already running.\n";
            exit(0);
        }

        launchQueueWorker($jobType);
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

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorManager::handle(function () use ($jobType) {
    $service = new QueueService();

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
