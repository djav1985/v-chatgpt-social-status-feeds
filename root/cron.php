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
    if ($debugMode) {
        ErrorMiddleware::logMessage("Invalid job type specified: $jobType", 'info');
    }
    die("Invalid job type specified.");
}

// Update switch cases to include logging
switch ($jobType) {
    case 'reset_usage':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing reset_usage job.", 'info');
        }
        if (!resetApi()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("reset_usage job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("reset_usage job completed successfully.", 'info');
        }
        break;
    case 'purge_ips':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing purge_ips job.", 'info');
        }
        if (!purgeIps()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("purge_ips job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("purge_ips job completed successfully.", 'info');
        }
        break;
    case 'purge_statuses':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing purge_statuses job.", 'info');
        }
        if (!purgeStatuses()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("purge_statuses job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("purge_statuses job completed successfully.", 'info');
        }
        break;
    case 'purge_images':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing purge_images job.", 'info');
        }
        if (!purgeImages()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("purge_images job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("purge_images job completed successfully.", 'info');
        }
        break;
    case 'fill_query':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing fill_query job.", 'info');
        }
        if (!JobQueue::fillQueryJobs()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("fill_query job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("fill_query job completed successfully.", 'info');
        }
        break;
    case 'run_query':
        if ($debugMode) {
            ErrorMiddleware::logMessage("Executing run_query job.", 'info');
        }
        if (!updateJobs()) {
            if ($debugMode) {
                ErrorMiddleware::logMessage("run_query job failed.", 'info');
            }
            die(1);
        }
        if ($debugMode) {
            ErrorMiddleware::logMessage("run_query job completed successfully.", 'info');
        }
        break;
    default:
        if ($debugMode) {
            ErrorMiddleware::logMessage("Invalid job type specified: $jobType", 'info');
        }
        ErrorMiddleware::logMessage("Invalid job type specified: $jobType", 'error');
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
    global $debugMode;
    if ($debugMode) {
        ErrorMiddleware::logMessage("Fetching all accounts for status purge.", 'info');
    }
    $accounts = Account::getAllAccounts();
    if (empty($accounts)) {
        if ($debugMode) {
            ErrorMiddleware::logMessage("No accounts found or failed to get accounts.", 'info');
        }
        ErrorMiddleware::logMessage("CRON: No accounts found or failed to get accounts.", 'warning');
        return true; // Return true as this is not necessarily an error condition
    }

    foreach ($accounts as $account) {
        $accountName = $account->account;
        $accountOwner = $account->username;
        if ($debugMode) {
            ErrorMiddleware::logMessage("Processing account: $accountName for status purge.", 'info');
        }
        $statusCount = Feed::countStatuses($accountName, $accountOwner);

        if ($statusCount > MAX_STATUSES) {
            $deleteCount = $statusCount - MAX_STATUSES;
            if ($debugMode) {
                ErrorMiddleware::logMessage("Deleting $deleteCount old statuses for account: $accountName.", 'info');
            }
            if (!Feed::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                if ($debugMode) {
                    ErrorMiddleware::logMessage("Failed to delete old statuses for account: $accountName.", 'info');
                }
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
    if ($debugMode) {
        ErrorMiddleware::logMessage("Purging old images.", 'info');
    }
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
                if ($debugMode) {
                    ErrorMiddleware::logMessage("Deleting image: $filePath.", 'info');
                }
                if (!unlink($filePath)) {
                    if ($debugMode) {
                        ErrorMiddleware::logMessage("Failed to delete image: $filePath.", 'info');
                    }
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
    if ($debugMode) {
        ErrorMiddleware::logMessage("Resetting API usage for all users.", 'info');
    }
    if (!User::resetAllApiUsage()) {
        if ($debugMode) {
            ErrorMiddleware::logMessage("Failed to reset API usage.", 'info');
        }
        ErrorMiddleware::logMessage("CRON: Failed to reset API usage.", 'error');
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
    if ($debugMode) {
        ErrorMiddleware::logMessage("API usage reset successfully.", 'info');
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
    global $debugMode;
    if ($debugMode) {
        ErrorMiddleware::logMessage("Clearing IP blacklist.", 'info');
    }
    if (!Security::clearIpBlacklist()) {
        if ($debugMode) {
            ErrorMiddleware::logMessage("Failed to clear IP blacklist.", 'info');
        }
        ErrorMiddleware::logMessage("CRON: Failed to clear IP blacklist.", 'error');
        return false;
    }
    if ($debugMode) {
        ErrorMiddleware::logMessage("IP blacklist cleared successfully.", 'info');
    }
    return true;
}


/**
 * Run queued jobs and generate statuses.
 */
function updateJobs(): bool
{
    global $debugMode;
    $limit = defined('CRON_QUEUE_LIMIT') ? (int) CRON_QUEUE_LIMIT : 10;
    $jobs = JobQueue::claimPending($limit);

    if (empty($jobs)) {
        if ($debugMode) {
            ErrorMiddleware::logMessage('No queued jobs to run.', 'info');
        }
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
    global $debugMode;
    if ($debugMode) {
        ErrorMiddleware::logMessage('Running job ID ' . $job->id . ' for ' . $job->account, 'info');
    }
    $result = StatusController::generateStatus($job->account, $job->username);
    if (isset($result['success'])) {
        JobQueue::markCompleted($job->id);
        if ($debugMode) {
            ErrorMiddleware::logMessage('Job ' . $job->id . ' completed', 'info');
        }
    } else {
        JobQueue::markFailed($job->id);
        if ($debugMode) {
            ErrorMiddleware::logMessage('Job ' . $job->id . ' failed', 'info');
        }
    }
}
