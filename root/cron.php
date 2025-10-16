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

// Load autoloader early so we can use WorkerHelper
require_once __DIR__ . '/vendor/autoload.php';

use App\Helpers\WorkerHelper;

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
        if (!WorkerHelper::canLaunch($jobType)) {
            echo 'Worker "' . $jobType . '" already running.' . PHP_EOL;
            exit(0);
        }
        launchQueueWorker($jobType);
        exit(0);
    }
    
    // For fill-queue, daily, and monthly, we just check if another instance is running
    // The actual lock will be claimed by QueueService
    if (!WorkerHelper::canLaunch($jobType)) {
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

use App\Core\ErrorManager;
use App\Services\QueueService;
use App\Services\MaintenanceService;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorManager::handle(function () use ($jobType) {
    switch ($jobType) {
        case 'run-queue':
            $service = new QueueService($jobType);
            $service->runQueue();
            break;
        case 'fill-queue':
            $service = new QueueService($jobType);
            $service->fillQueue();
            break;
        case 'daily':
            $service = new MaintenanceService($jobType);
            $service->runDaily();
            break;
        case 'monthly':
            $service = new MaintenanceService($jobType);
            $service->runMonthly();
            break;
    }
});
