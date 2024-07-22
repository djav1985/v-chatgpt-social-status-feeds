<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: cron.php
 * Description: Handles scheduled tasks such as resetting API usage, running status updates, clearing the IP blacklist, and purging old images.
 */

// Including necessary configuration and library files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/common-lib.php';
require_once __DIR__ . '/lib/status-lib.php';

// Define the constant for image age
define('IMG_AGE', 30); // For example, set it to 30 days

// Checking for command line arguments to determine the job type
$jobType = $argv[1] ?? 'run_status'; // Default to 'run_status' if no argument provided

// Execute the appropriate function based on the job type
if ($jobType == 'reset_usage') {
    resetAllApiUsage(); // Function to reset all API usage counters
} elseif ($jobType == 'run_status') {
    runStatusUpdateJobs(); // Run tasks related to updating statuses
} elseif ($jobType == 'clear_list') {
    clearIpBlacklist(); // Clear the IP blacklist
} elseif ($jobType == 'cleanup') {
    cleanupStatuses(); // Clean up old statuses
} elseif ($jobType == 'purge_images') {
    purgeImages(); // Purge old images from the server
}

// Function to run status update jobs
function runStatusUpdateJobs()
{
    // Fetch all accounts from the database
    $accounts = getAllAccounts();
    $currentHour = date('H'); // Gets the current hour in 24-hour format
    $currentDay = strtolower(date('l')); // Gets the current day in lowercase
    $currentMinute = date('i'); // Gets the current minute
    $currentTimeSlot = sprintf("%02d", $currentHour) . ':' . $currentMinute; // Current time slot in HH:MM format

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
    // Fetch all accounts from the database
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

// Function to purge old images
function purgeImages()
{
    $imageDir = __DIR__ . '/public/images/'; // Directory containing the images
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imageDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    // Get the current timestamp
    $now = time();

    // Iterate through all files in the directory
    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') { // Only consider PNG files
            $filePath = $fileinfo->getRealPath(); // Get the full path of the file
            $fileAge = ($now - $fileinfo->getMTime()) / 86400; // Convert file age to days

            if ($fileAge > IMG_AGE) {
                unlink($filePath); // Delete the file if it's older than IMG_AGE days
            }
        }
    }
}
