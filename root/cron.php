<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: cron.php
 * Description: Handles scheduled tasks such as resetting API usage, running status updates, clearing the IP blacklist, and purging old images.
 * License: MIT
 */

ini_set('max_execution_time', 0); // Unlimited execution time
set_time_limit(0);                // For compatibility
ini_set('memory_limit', '512M'); // Adjust as needed

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/lib/status-lib.php';

// Instantiate the ErrorHandler to register handlers
new ErrorHandler();

// Increase the maximum execution time to prevent timeouts
set_time_limit(0);

$validJobTypes = ['reset_usage', 'run_status', 'clear_list', 'cleanup', 'purge_images'];
$jobType = $argv[1] ?? 'run_status'; // Default job type is 'run_status'
if (!in_array($jobType, $validJobTypes)) {
    die("Invalid job type specified.");
}

switch ($jobType) {
    case 'reset_usage':
        if (!resetApi()) die(1);
        break;
    case 'run_status':
        if (!runStatusUpdateJobs()) die(1);
        break;
    case 'clear_list':
        if (!clearList()) die(1);
        break;
    case 'cleanup':
        if (!cleanupStatuses()) die(1);
        break;
    case 'purge_images':
        if (!purgeImages()) die(1);
        break;
    default:
        ErrorHandler::logMessage("Invalid job type specified: $jobType", 'error');
        die(1);
}

/**
 * Run status update jobs for all accounts.
 * This function checks the current time and day, and runs status updates for accounts scheduled at the current time.
 * It ensures that the status is not posted more than once per scheduled hour and that the user has not exceeded their API call limit.
 */
function runStatusUpdateJobs(): bool
{
    $accounts = AccountHandler::getAllAccounts();
    if ($accounts === false) {
        ErrorHandler::logMessage("CRON: Failed to get accounts.", 'error');
        return false;
    }

    $currentHour = date('H');
    $currentDay = strtolower(date('l'));

    foreach ($accounts as $account) {
        $accountOwner = $account->username;
        $accountName = $account->account;
        $cron = explode(',', $account->cron);
        $days = explode(',', $account->days);

        foreach ($cron as $scheduledHour) {
            $scheduledHour = sprintf('%02d', (int)$scheduledHour);

            if ($scheduledHour === $currentHour && (in_array('everyday', $days) || in_array($currentDay, $days))) {
                if (!StatusHandler::hasStatusBeenPosted($accountName, $accountOwner, $scheduledHour)) {
                    $userInfo = UserHandler::getUserInfo($accountOwner);

                    // Check if the user's subscription has expired
                    $now = new DateTime();
                    $expires = new DateTime($userInfo->expires);
                    if ($now > $expires) {
                        $userInfo->max_api_calls = 0;
                        UserHandler::updateMaxApiCalls($accountOwner, 0);
                    }

                    if ($userInfo->used_api_calls < $userInfo->max_api_calls) {
                        $userInfo->used_api_calls += 1;
                        UserHandler::updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);
                        generateStatus($accountName, $accountOwner);
                    }
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
    $accounts = AccountHandler::getAllAccounts();
    if ($accounts === false) {
        ErrorHandler::logMessage("CRON: Failed to get accounts.", 'error');
        return false;
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        $statusCount = StatusHandler::countStatuses($accountName);

        if ($statusCount > MAX_STATUSES) {
            $deleteCount = $statusCount - MAX_STATUSES;
            if (!StatusHandler::deleteOldStatuses($accountName, $deleteCount)) {
                ErrorHandler::logMessage("CRON: Failed to delete old statuses for account: $accountName", 'error');
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
                    ErrorHandler::logMessage("CRON: Failed to delete image: $filePath", 'error');
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
    if (!UserHandler::resetAllApiUsage()) {
        ErrorHandler::logMessage("CRON: Failed to reset API usage.", 'error');
        return false;
    }
    return true;
}

/**
 * Clear the IP blacklist.
 * This function clears the IP blacklist, removing all entries.
 * This is typically run periodically to ensure that the blacklist does not grow indefinitely.
 */
function clearList(): bool
{
    if (!UtilityHandler::clearIpBlacklist()) {
        ErrorHandler::logMessage("CRON: Failed to clear IP blacklist.", 'error');
        return false;
    }
    return true;
}
