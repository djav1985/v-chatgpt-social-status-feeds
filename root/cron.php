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

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use App\Core\ErrorMiddleware;
use App\Controllers\StatusController;
use App\Models\Account;
use App\Models\User;
use App\Models\Feed;
use App\Models\Database;
use App\Models\JobQueue;
use App\Core\Utility;

// Apply configured runtime limits after loading settings
ini_set('max_execution_time', (string) (defined('CRON_MAX_EXECUTION_TIME') ? CRON_MAX_EXECUTION_TIME : 0));
ini_set('memory_limit', defined('CRON_MEMORY_LIMIT') ? CRON_MEMORY_LIMIT : '512M');

// Register error handlers
ErrorMiddleware::register();

// Add a helper function for logging
function logDebug(string $message): void
{
    global $debugMode;
    if ($debugMode) {
        $logFile = __DIR__ . '/cron.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }
}

$validJobTypes = [
                  'reset_usage',
                  'clear_list',
                  'cleanup',
                  'purge_images',
                  'fill_query',
                  'run_query',
                 ];
$jobType = $argv[1] ?? 'run_query'; // Default job type is 'run_query'

// Check for debug mode
$debugMode = isset($argv[2]) && $argv[2] === 'debug';
logDebug("Starting cron job with job type: $jobType");

if (!in_array($jobType, $validJobTypes)) {
    logDebug("Invalid job type specified: $jobType");
    die("Invalid job type specified.");
}

// Update switch cases to include logging
switch ($jobType) {
    case 'reset_usage':
        logDebug("Executing reset_usage job.");
        if (!resetApi()) {
            logDebug("reset_usage job failed.");
            die(1);
        }
        logDebug("reset_usage job completed successfully.");
        break;
    case 'clear_list':
        logDebug("Executing clear_list job.");
        if (!clearList()) {
            logDebug("clear_list job failed.");
            die(1);
        }
        logDebug("clear_list job completed successfully.");
        break;
    case 'cleanup':
        logDebug("Executing cleanup job.");
        if (!cleanupStatuses()) {
            logDebug("cleanup job failed.");
            die(1);
        }
        logDebug("cleanup job completed successfully.");
        break;
    case 'purge_images':
        logDebug("Executing purge_images job.");
        if (!purgeImages()) {
            logDebug("purge_images job failed.");
            die(1);
        }
        logDebug("purge_images job completed successfully.");
        break;
    case 'fill_query':
        logDebug("Executing fill_query job.");
        if (!JobQueue::fillQueryJobs()) {
            logDebug("fill_query job failed.");
            die(1);
        }
        logDebug("fill_query job completed successfully.");
        break;
    case 'run_query':
        logDebug("Executing run_query job.");
        if (!runStatusUpdateJobs()) {
            logDebug("run_query job failed.");
            die(1);
        }
        logDebug("run_query job completed successfully.");
        break;
    default:
        logDebug("Invalid job type specified: $jobType");
        ErrorMiddleware::logMessage("Invalid job type specified: $jobType", 'error');
        die(1);
}


/**
 * Clean up old statuses for all accounts.
 * This function checks the number of statuses for each account and deletes the oldest ones if they exceed the maximum allowed.
 * This helps to manage storage and keep the database performant.
 */
function cleanupStatuses(): bool
{
    global $debugMode;
    logDebug("Fetching all accounts for cleanup.");
    $accounts = Account::getAllAccounts();
    if (empty($accounts)) {
        logDebug("No accounts found or failed to get accounts.");
        ErrorMiddleware::logMessage("CRON: No accounts found or failed to get accounts.", 'warning');
        return true; // Return true as this is not necessarily an error condition
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        logDebug("Processing account: $accountName for cleanup.");
        $statusCount = Feed::countStatuses($accountName);

        if ($statusCount > MAX_STATUSES) {
            $deleteCount = $statusCount - MAX_STATUSES;
            logDebug("Deleting $deleteCount old statuses for account: $accountName.");
            if (!Feed::deleteOldStatuses($accountName, $deleteCount)) {
                logDebug("Failed to delete old statuses for account: $accountName.");
                ErrorMiddleware::logMessage("CRON: Failed to delete old statuses for account: $accountName", 'error');
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
    global $debugMode;
    logDebug("Purging old images.");
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
                logDebug("Deleting image: $filePath.");
                if (!unlink($filePath)) {
                    logDebug("Failed to delete image: $filePath.");
                    ErrorMiddleware::logMessage("CRON: Failed to delete image: $filePath", 'error');
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
    global $debugMode;
    logDebug("Resetting API usage for all users.");
    if (!User::resetAllApiUsage()) {
        logDebug("Failed to reset API usage.");
        ErrorMiddleware::logMessage("CRON: Failed to reset API usage.", 'error');
        return false;
    }
    logDebug("API usage reset successfully.");
    return true;
}

/**
 * Clear the IP blacklist.
 * This function clears the IP blacklist, removing all entries.
 * This is typically run periodically to ensure that the blacklist does not grow indefinitely.
 */
function clearList(): bool
{
    global $debugMode;
    logDebug("Clearing IP blacklist.");
    if (!Utility::clearIpBlacklist()) {
        logDebug("Failed to clear IP blacklist.");
        ErrorMiddleware::logMessage("CRON: Failed to clear IP blacklist.", 'error');
        return false;
    }
    logDebug("IP blacklist cleared successfully.");
    return true;
}


/**
 * Run queued jobs and generate statuses.
 */
function runStatusUpdateJobs(): bool
{
    global $debugMode;
    $limit = defined('CRON_QUEUE_LIMIT') ? (int) CRON_QUEUE_LIMIT : 10;
    $jobs = JobQueue::claimPending($limit);

    if (empty($jobs)) {
        logDebug('No queued jobs to run.');
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
    logDebug('Running job ID ' . $job->id . ' for ' . $job->account);
    $result = StatusController::generateStatus($job->account, $job->username);
    if (isset($result['success'])) {
        JobQueue::markCompleted($job->id);
        logDebug('Job ' . $job->id . ' completed');
    } else {
        JobQueue::markFailed($job->id);
        logDebug('Job ' . $job->id . ' failed');
    }
}
