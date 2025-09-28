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
$jobType = $argv[1] ?? '';
if (!in_array($jobType, $validJobTypes, true)) {
    echo "Usage: php cron.php {run-queue|fill-queue|daily|monthly}\n";
    echo "  run-queue  - Process queued jobs with scheduled_time <= now\n";
    echo "  fill-queue - Add future job slots without truncating existing jobs\n";
    echo "  daily      - Run daily cleanup (purge statuses, images, IPs)\n";
    echo "  monthly    - Reset API usage counters\n";
    exit(1);
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
