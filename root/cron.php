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
 * Description: AI Social Status Generator
 */

// This script is intended for CLI use only. If accessed via a web server,
// return HTTP 403 Forbidden.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorManager;
use App\Services\QueueService;
use App\Services\CronService;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorManager::handle(function () {
    global $argv;
    $service = new CronService();

$validJobTypes = [
    'daily',
    'hourly',
];
$jobType = $argv[1] ?? 'hourly'; // Default job type is 'hourly'

if (!in_array($jobType, $validJobTypes)) {
    die("Invalid job type specified.");
}

// Run tasks for the selected job type
switch ($jobType) {
    case 'daily': {
        $resetOk = true;
        if (date('j') === '1') {
            $resetOk = $service->resetApi();
        }
        $purgeIpsOk = $service->purgeIps();
        if ((date('j') === '1' && !$resetOk) || !$purgeIpsOk) {
            die(1);
        }

        $queue = new QueueService();
        $queue->clearQueue();
        $queue->enqueueDailyJobs();
        break;
    }
    case 'hourly': {
        $statusOk = $service->purgeStatuses();
        $imagesOk = $service->purgeImages();
        if (!$statusOk || !$imagesOk) {
            die(1);
        }
        $queue = new QueueService();
        $queue->runQueue();
        break;
    }
    default:
        die(1);
}
});
