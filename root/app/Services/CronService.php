<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: CronService.php
 * Description: Maintenance tasks for the cron script
 */

namespace App\Services;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use App\Models\Account;
use App\Models\User;
use App\Models\Status;
use App\Core\Mailer;
use App\Models\Blacklist;

class CronService
{
    /**
     * Clean up old statuses for all accounts.
     */
    public function purgeStatuses(): bool
    {
        $accounts = Account::getAllAccounts();
        if (empty($accounts)) {
            return true;
        }

        foreach ($accounts as $account) {
            $accountName = $account->account;
            $accountOwner = $account->username;
            $statusCount = Status::countStatuses($accountName, $accountOwner);

            if ($statusCount > MAX_STATUSES) {
                $deleteCount = $statusCount - MAX_STATUSES;
                if (!Status::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Purge old images from the public/images directory.
     */
    public function purgeImages(): bool
    {
        $imageDir = __DIR__ . '/../../public/images/';
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
     */
    public function resetApi(): bool
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
     */
    public function purgeIps(): bool
    {
        return Blacklist::clearIpBlacklist();
    }
}
