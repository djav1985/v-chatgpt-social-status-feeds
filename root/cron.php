<?php
// This script is intended for CLI use only. If accessed via a web server,
// return HTTP 403 Forbidden.
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}
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

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorMiddleware;
use App\Controllers\StatusController;
use App\Models\Account;
use App\Models\User;
use App\Models\Feed;
use App\Core\Mailer;
use App\Models\JobQueue;
use App\Models\Security;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Run the job logic within the error middleware handler
ErrorMiddleware::handle(function () use (&$jobType) {

// Debug logging handled through ErrorMiddleware when debug mode is enabled

$validJobTypes = [
                  'reset_usage',
                  'purge_ips',
                  'purge_statuses',
                  'purge_images',
                  'fill_query',
                  'run_query',
                 ];
$jobType = $argv[1] ?? 'run_query'; // Default job type is 'run_query'

// Check for debug mode
$debugMode = isset($argv[2]) && $argv[2] === 'debug';
if ($debugMode) {
    ErrorMiddleware::logMessage("Starting cron job with job type: $jobType", 'info');
}

if (!in_array($jobType, $validJobTypes)) {
    die("Invalid job type specified.");
}

// Update switch cases to include logging
switch ($jobType) {
    case 'reset_usage':
        if (!resetApi()) {
            die(1);
        }
        break;
    case 'purge_ips':
        if (!purgeIps()) {
            die(1);
        }
        break;
    case 'purge_statuses':
        if (!purgeStatuses()) {
            die(1);
        }
        break;
    case 'purge_images':
        if (!purgeImages()) {
            die(1);
        }
        break;
    case 'fill_query':
        if (!JobQueue::fillQueryJobs()) {
            die(1);
        }
        break;
    case 'run_query':
        if (!updateJobs()) {
            die(1);
        }
        break;
    default:
        die(1);
}
});


/**
 * Clean up old statuses for all accounts.
 * This function checks the number of statuses for each account and deletes the oldest ones if they exceed the maximum allowed.
 * This helps to manage storage and keep the database performant.
 */
function purgeStatuses(): bool
{
    $accounts = Account::getAllAccounts();
    if (empty($accounts)) {
        return true; // Return true as this is not necessarily an error condition
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        $accountOwner = $account->username;
        $statusCount = Feed::countStatuses($accountName, $accountOwner);

        if ($statusCount > MAX_STATUSES) {
            $deleteCount = $statusCount - MAX_STATUSES;
            if (!Feed::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                
                return false;
            }
        }
    }
    return true;
}

/**
 * Purge old images from the public/images directory.
 * This function deletes image files that are older than the defined image age.
 * This helps to free up disk space and remove potentially outdated or unused images.
 */
function purgeImages(): bool
{
    $imageDir = __DIR__ . '/public/images/';
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imageDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $now = time();

    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') {
            $filePath = $fileinfo->getRealPath();
            $fileAge = ($now - $fileinfo->getMTime()) / 86400;

            if ($fileAge > IMG_AGE) {
                if (!unlink($filePath)) {
                    
                    return false;
                }
            }
        }
    }
    return true;
}

/**
 * Reset API usage for all users.
 * This function resets the API usage count for all users to zero.
 * This is typically run at the start of a new billing cycle.
 */
function resetApi(): bool
{
    if (!User::resetAllApiUsage()) {
        
        return false;
    }
    $users = User::getAllUsers();
    foreach ($users as $user) {
        Mailer::sendTemplate(
            $user->email,
            'API Usage Reset',
            'api_usage_reset',
            ['username' => $user->username]
        );
    }
    return true;
}

/**
 * Purge old entries from the IP blacklist.
 * This function calls Security::clearIpBlacklist() to remove expired IP addresses.
 * Schedule periodically so the blacklist does not grow indefinitely.
 */
function purgeIps(): bool
{
    if (!Security::clearIpBlacklist()) {
        
        return false;
    }
    return true;
}


/**
 * Run queued jobs and generate statuses.
 */
function updateJobs(): bool
{
    $limit = defined('CRON_QUEUE_LIMIT') ? (int) CRON_QUEUE_LIMIT : 10;
    $jobs = JobQueue::claimPending($limit);

    if (empty($jobs)) {
        return true;
    }

    foreach ($jobs as $job) {
        processJob($job);
    }

    JobQueue::cleanupOld();

    return true;
}

/**
 * Process an individual status job.
 */
function processJob(object $job): void
{
    $result = StatusController::generateStatus($job->account, $job->username);
    if (isset($result['success'])) {
        JobQueue::markCompleted($job->id);
        
    } else {
        JobQueue::markFailed($job->id);
    }
}
