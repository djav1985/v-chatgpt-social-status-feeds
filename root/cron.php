<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: cron.php
 * Description: Handles scheduled tasks such as resetting API usage, running status updates, and clearing the IP blacklist.
 */

// Including necessary configuration and library files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/common-lib.php';
require_once __DIR__ . '/lib/status-lib.php';

// Checking for command line arguments to determine the job type
$jobType = $argv[1] ?? 'run_status'; // Default to 'run_status' if no argument provided

// Execute the appropriate function based on the job type
if ($jobType == 'reset_usage') {
    resetAllApiUsage();
} elseif ($jobType == 'run_status') {
    runStatusUpdateJobs();
} elseif ($jobType == 'clear_list') {
    clearIpBlacklist();
} elseif ($jobType == 'cleanup') {
    cleanupStatuses();
}

// Function to run status update jobs
function runStatusUpdateJobs()
{
    // Fetch all accounts from the database
    $accounts = getAllAccounts();
    $currentHour = date('H'); // Gets the current hour in 24-hour format
    $currentDay = strtolower(date('l')); // Gets the current day in lowercase
    $currentMinute = date('i'); // Gets the current minute
    $currentTimeSlot = sprintf("%02d", $currentHour) . ':' . $currentMinute;

    foreach ($accounts as $account) {
        // Account details
        $accountOwner = $account->username;
        $accountName = $account->account;
        $cron = explode(',', $account->cron); // Split cron schedule into an array
        $days = explode(',', $account->days); // Split days into an array

        // Check if the current time slot matches any cron schedule and if the day is included
        foreach ($cron as $scheduledHour) {
            if ($currentHour == $scheduledHour && (in_array('everyday', $days) || in_array($currentDay, $days))) {
                // Only proceed if a status hasn't been generated for this time slot
                if (!hasStatusBeenPosted($accountName, $accountOwner, $scheduledHour)) {
                    // Retrieve account and user information
                    $acctInfo = getAcctInfo($accountOwner, $accountName);
                    $userInfo = getUserInfo($accountOwner);

                    // Only proceed if the user has remaining API calls
                    if ($userInfo && $userInfo->used_api_calls < $userInfo->max_api_calls) {
                        $userInfo->used_api_calls += 1; // Increment used API calls

                        // Update user's used API calls in the database
                        updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);

                        generateStatus($accountName, $accountOwner); // Generate the status update
                    }
                }
            }
        }
    }
}

// New function to cleanup old statuses
function cleanupStatuses()
{
    $accounts = getAllAccounts();
    foreach ($accounts as $account) {
        $accountName = $account->account;
        $accountOwner = $account->username;

        // Count the current number of statuses
        $result = countStatuses($accountName);
        $statusCount = $result->count;

        // Check if the number of statuses exceeds the maximum allowed
        if ($statusCount > MAX_STATUSES) {
            // Calculate how many statuses to delete
            $deleteCount = $statusCount - MAX_STATUSES;

            // Delete the oldest statuses
            deleteOldStatuses($accountName, $deleteCount);
        }
    }
}
