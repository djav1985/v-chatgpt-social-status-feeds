<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: cron.php
 * Description: Handles scheduled tasks such as resetting API usage, running status updates, clearing the IP blacklist, and purging old images.
 */

// Including necessary configuration and library files for database access and utility functions
require_once __DIR__ . '/config.php'; // Load application configuration
require_once __DIR__ . '/db.php'; // Load database connection
require_once __DIR__ . '/lib/common-lib.php'; // Load common utility functions
require_once __DIR__ . '/lib/status-lib.php'; // Load functions related to status updates

// Define the constant for image age; images older than this will be purged
define('IMG_AGE', 30); // Set it to 30 days for cleanup purposes

// Checking for command line arguments to determine the job type
$jobType = $argv[1] ?? 'run_status'; // Default to 'run_status' if no argument provided

// Execute the appropriate function based on the job type received as an argument
if ($jobType == 'reset_usage') {
    resetAllApiUsage(); // Call the function to reset all API usage counters
} elseif ($jobType == 'run_status') {
    runStatusUpdateJobs(); // Call to run status update jobs
} elseif ($jobType == 'clear_list') {
    clearIpBlacklist(); // Call to clear entries in the IP blacklist
} elseif ($jobType == 'cleanup') {
    cleanupStatuses(); // Call to clean up old statuses from the database
} elseif ($jobType == 'purge_images') {
    purgeImages(); // Call to purge old images from the server storage
}

// Function to run status update jobs for each account
function runStatusUpdateJobs()
{
    // Fetch all accounts from the database
    $accounts = getAllAccounts();

    // Get the current hour, day of the week, and minute for scheduling
    $currentHour = date('H'); // Gets the current hour in 24-hour format
    $currentDay = strtolower(date('l')); // Gets the current day in lowercase
    $currentMinute = date('i'); // Gets the current minute
    $currentTimeSlot = sprintf("%02d", $currentHour) . ':' . $currentMinute; // Current time slot in HH:MM format

    // Iterate over each account to check their scheduling
    foreach ($accounts as $account) {
        // Extract account details
        $accountOwner = $account->username;
        $accountName = $account->account;
        $cron = explode(',', $account->cron); // Split cron schedule into an array
        $days = explode(',', $account->days); // Split allowed days into an array

        // Check if the current time and day match any scheduled task
        foreach ($cron as $scheduledHour) {
            if ($currentHour == $scheduledHour && (in_array('everyday', $days) || in_array($currentDay, $days))) {
                // Only proceed if a status hasn't been generated for this time slot
                if (!hasStatusBeenPosted($accountName, $accountOwner, $scheduledHour)) {
                    // Retrieve account and user information
                    $acctInfo = getAcctInfo($accountOwner, $accountName);
                    $userInfo = getUserInfo($accountOwner);

                    // Check if the user's account has expired
                    $currentDateTime = new DateTime();
                    $expiresDateTime = new DateTime($userInfo->expires);

                    if ($currentDateTime > $expiresDateTime) {
                        // Set max_api_calls to 0 if the account has expired
                        $userInfo->max_api_calls = 0;
                        updateMaxApiCalls($accountOwner, 0);
                    }

                    // Only proceed if the user has remaining API calls
                    if ($userInfo && $userInfo->used_api_calls < $userInfo->max_api_calls) {
                        $userInfo->used_api_calls += 1; // Increment used API calls for the user

                        // Update user's used API calls in the database
                        updateUsedApiCalls($accountOwner, $userInfo->used_api_calls);

                        // Generate the status update for the account
                        generateStatus($accountName, $accountOwner);
                    }
                }
            }
        }
    }
}

// New function to cleanup old statuses based on a defined maximum
function cleanupStatuses()
{
    // Fetch all accounts from the database
    $accounts = getAllAccounts();

    // Iterate over each account to check for status count
    foreach ($accounts as $account) {
        $accountName = $account->account;
        $accountOwner = $account->username;

        // Count the current number of statuses for the account
        $statusCount = countStatuses($accountName);

        // Check if the number of statuses exceeds the maximum allowed
        if ($statusCount > MAX_STATUSES) {
            // Calculate how many statuses need to be deleted
            $deleteCount = $statusCount - MAX_STATUSES;

            // Delete the oldest statuses to maintain limit
            deleteOldStatuses($accountName, $deleteCount);
        }
    }
}

// Function to purge old images from the server based on age
function purgeImages()
{
    $imageDir = __DIR__ . '/public/images/'; // Directory containing the images
    // Create an iterator to go through the directory recursively
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($imageDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    // Get the current timestamp for age comparison
    $now = time();

    // Iterate through all files in the image directory
    foreach ($files as $fileinfo) {
        if ($fileinfo->isFile() && $fileinfo->getExtension() == 'png') { // Process only PNG files
            $filePath = $fileinfo->getRealPath(); // Get the full path of the file
            $fileAge = ($now - $fileinfo->getMTime()) / 86400; // Convert file age to days

            // Delete the file if it's older than the defined IMG_AGE
            if ($fileAge > IMG_AGE) {
                unlink($filePath); // Remove the file
            }
        }
    }
}
