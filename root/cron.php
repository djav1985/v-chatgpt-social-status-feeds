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
use App\Services\CronService;
use App\Services\QueueService;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorManager::handle(function () {
    global $argv;

    $service = new CronService();

    $validJobTypes = ['daily', 'hourly'];
    $jobType = $argv[1] ?? 'hourly';
    if (!in_array($jobType, $validJobTypes, true)) {
        throw new \InvalidArgumentException('Invalid job type specified.');
    }

    switch ($jobType) {
        case 'daily':
            $service->runDaily();
            break;
        case 'hourly':
            $service->runHourly();
            $queue = new QueueService();
            $queue->processLoop(true);
            break;
    }
});
