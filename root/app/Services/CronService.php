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
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Util\JSON;
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

    /**
     * Truncate previous jobs and enqueue all of today's jobs.
     */
    public function scheduleDailyQueue(): void
    {
        $factory = new DbalConnectionFactory([
            'connection' => [
                'dbname' => DB_NAME,
                'user' => DB_USER,
                'password' => DB_PASSWORD,
                'host' => DB_HOST,
                'driver' => 'pdo_mysql',
                'charset' => 'utf8mb4',
            ],
            'table_name' => 'status_jobs',
            'lazy' => false,
        ]);

        $context = $factory->createContext();
        $context->getDbalConnection()->executeStatement('TRUNCATE TABLE status_jobs');

        $queue = $context->createQueue('status_generate');
        $producer = $context->createProducer();

        $accounts = Account::getAllAccounts();
        $dayName = strtolower(date('l'));

        foreach ($accounts as $account) {
            $days = array_map('strtolower', array_map('trim', explode(',', (string) $account->days)));
            if (!in_array('everyday', $days, true) && !in_array($dayName, $days, true)) {
                continue;
            }
            $hours = array_filter(array_map('trim', explode(',', (string) $account->cron)), 'strlen');
            foreach ($hours as $hour) {
                $payload = [
                    'username' => $account->username,
                    'account' => $account->account,
                    'hour' => (int) $hour,
                ];
                $message = $context->createMessage(JSON::encode($payload));
                $message->setContentType('application/json');
                $producer->send($queue, $message);
            }
        }
    }

    /**
     * Run all daily maintenance tasks.
     */
    public function runDaily(): void
    {
        if (date('j') === '1') {
            $this->resetApi();
        }
        $this->purgeIps();
        $this->purgeStatuses();
        $this->purgeImages();
        $this->scheduleDailyQueue();
    }
}
