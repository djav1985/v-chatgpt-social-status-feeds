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

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\ErrorMiddleware;
use App\Controllers\StatusController;
use App\Models\AccountHandler;
use App\Models\UserHandler;
use App\Models\StatusHandler;
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
                  'run_status',
                  'clear_list',
                  'cleanup',
                  'purge_images',
                 ];
$jobType = $argv[1] ?? 'run_status'; // Default job type is 'run_status'

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
    case 'run_status':
        logDebug("Executing run_status job.");
        if (!runStatusUpdateJobs()) {
            logDebug("run_status job failed.");
            die(1);
        }
        logDebug("run_status job completed successfully.");
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
    default:
        logDebug("Invalid job type specified: $jobType");
        ErrorMiddleware::logMessage("Invalid job type specified: $jobType", 'error');
        die(1);
}

/**
 * Run status update jobs for all accounts.
 * This function checks the current time and day, and runs status updates for accounts scheduled at the current time.
 * It ensures that the status is not posted more than once per scheduled hour and that the user has not exceeded their API call limit.
 */
function runStatusUpdateJobs(): bool
{
    global $debugMode;
    logDebug("Fetching all accounts for status update.");
    $accounts = AccountHandler::getAllAccounts();
    if (empty($accounts)) {
        logDebug("No accounts found or failed to get accounts.");
        ErrorMiddleware::logMessage("CRON: No accounts found or failed to get accounts.", 'warning');
        return true; // Return true as this is not necessarily an error condition
    }

    $currentHour = date('H');
    $currentDay = strtolower(date('l'));
    logDebug("Current hour: $currentHour, Current day: $currentDay.");

    foreach ($accounts as $account) {
        $accountOwner = $account->username;
        $accountName = $account->account;
        logDebug("Processing account: $accountName owned by $accountOwner.");

        $days = array_map('strtolower', array_map('trim', explode(',', $account->days)));
        // Check if this account should be processed today
        $shouldProcess = in_array('everyday', $days) || in_array($currentDay, $days);
        if (!$shouldProcess) {
            logDebug("Skipping account: $accountName, not scheduled for today.");
            continue;
        }

        // Check if cron field is null, empty, or the string 'null'
        if (is_null($account->cron) || trim($account->cron) === '' || strtolower(trim($account->cron)) === 'null') {
            logDebug("Skipping account: $accountName, cron field is null, empty, or 'null'.");
            continue;
        }

        $cron = array_filter(
            array_map('trim', explode(',', $account->cron)),
            function (string $hour): bool {
                return is_numeric($hour) && $hour !== '';
            }
        );
        if (empty($cron)) {
            logDebug("Skipping account: $accountName, cron field contains no valid hours.");
            continue;
        }
        foreach ($cron as $scheduledHour) {
            $scheduledHour = sprintf('%02d', (int)$scheduledHour);
            logDebug("Scheduled hour: $scheduledHour.");

            if ($scheduledHour === $currentHour) {
                logDebug("Scheduled time matches current time for account: $accountName.");

                if (!StatusHandler::hasStatusBeenPosted($accountName, $accountOwner, $scheduledHour)) {
                    logDebug("Status has not been posted yet for this hour.");
                    $userInfo = UserHandler::getUserInfo($accountOwner);

                    // Check if the user's subscription has expired
                    $now = new DateTime();
                    $expires = new DateTime($userInfo->expires);
                    if ($now > $expires) {
                        logDebug("User subscription expired. Setting max API calls to 0.");
                        $userInfo->max_api_calls = 0;
                        UserHandler::updateMaxApiCalls($accountOwner, 0);
                    }

                    if ($userInfo->used_api_calls < $userInfo->max_api_calls) {
                        logDebug("User has remaining API calls.");
                        $statusResult = StatusController::generateStatus($accountName, $accountOwner);
                        if (isset($statusResult['success'])) {
                            logDebug("Status generated for account: $accountName.");
                            $userInfo->used_api_calls += 1;
                            UserHandler::updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);
                        } else {
                            logDebug("Failed to generate status for account: $accountName.");
                        }
                    } else {
                        logDebug("User has exceeded their API call limit.");
                    }
                } else {
                    logDebug("Status has already been posted for this hour.");
                }
            }
        }
    }

    return true;
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
    $accounts = AccountHandler::getAllAccounts();
    if (empty($accounts)) {
        logDebug("No accounts found or failed to get accounts.");
        ErrorMiddleware::logMessage("CRON: No accounts found or failed to get accounts.", 'warning');
        return true; // Return true as this is not necessarily an error condition
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        logDebug("Processing account: $accountName for cleanup.");
        $statusCount = StatusHandler::countStatuses($accountName);

        if ($statusCount > MAX_STATUSES) {
            $deleteCount = $statusCount - MAX_STATUSES;
            logDebug("Deleting $deleteCount old statuses for account: $accountName.");
            if (!StatusHandler::deleteOldStatuses($accountName, $deleteCount)) {
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
    if (!UserHandler::resetAllApiUsage()) {
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
