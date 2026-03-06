<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: MaintenanceService.php
 * Description: Service for scheduled maintenance tasks (daily and monthly).
 */

namespace App\Services;

use App\Core\ErrorManager;
use App\Models\UserModel;
use App\Models\StatusModel;
use App\Core\Mailer;
use App\Models\BlacklistModel;
use App\Helpers\WorkerHelper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Service for scheduled maintenance tasks (daily and monthly).
 */
class MaintenanceService
{
    /** @var array|null */
    private $workerLock = null;

    private ?string $jobType = null;

    public function __construct(?string $jobType = null)
    {
        $this->jobType = $jobType;
    }

    /**
     * Run daily cleanup: purgeStatuses(), purgeImages(), purgeIps().
     */
    public function runDaily(): void
    {
        if (!$this->claimWorkerLock()) {
            ErrorManager::getInstance()->log('[MaintenanceService] Daily worker already running; skipping runDaily invocation.', 'info');
            return;
        }

        try {
            $this->purgeStatuses();
            $this->purgeImages();
            $this->purgeIps();
        } finally {
            $this->releaseWorkerLock();
        }
    }

    /**
     * Run monthly maintenance: only resetApi().
     */
    public function runMonthly(): void
    {
        if (!$this->claimWorkerLock()) {
            ErrorManager::getInstance()->log('[MaintenanceService] Monthly worker already running; skipping runMonthly invocation.', 'info');
            return;
        }

        try {
            $this->resetApi();
        } finally {
            $this->releaseWorkerLock();
        }
    }

    /**
     * Clean up old statuses for all accounts.
     */
    public function purgeStatuses(): bool
    {
        $overLimitAccounts = StatusModel::getOverLimitAccounts(MAX_STATUSES);

        if (empty($overLimitAccounts)) {
            return true;
        }

        foreach ($overLimitAccounts as $account) {
            $account = (object) $account;
            $accountName = (string) ($account->account ?? '');
            $accountOwner = (string) ($account->username ?? '');
            $statusCount = (int) ($account->status_count ?? 0);

            if ($accountName === '' || $accountOwner === '') {
                continue;
            }

            $deleteCount = $statusCount - MAX_STATUSES;
            if ($deleteCount <= 0) {
                continue;
            }

            if (!StatusModel::deleteOldStatuses($accountName, $accountOwner, $deleteCount)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Purge old images from the public/images directory.
     */
    public function purgeImages(): bool
    {
        $imageDir = rtrim(__DIR__ . '/../../public/images/', DIRECTORY_SEPARATOR);
        if ($imageDir === '') {
            return true;
        }

        if (!is_dir($imageDir)) {
            $dirMode = defined('DIR_MODE') ? (int) constant('DIR_MODE') : 0755;
            if (!@mkdir($imageDir, $dirMode, true) && !is_dir($imageDir)) {
                return true;
            }
        }

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
        if (!UserModel::resetAllApiUsage()) {
            return false;
        }
        $users = UserModel::getAllUsers();
        foreach ($users as $user) {
            $user = (object)$user;
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
        return BlacklistModel::clearIpBlacklist();
    }

    private function claimWorkerLock(): bool
    {
        $jobType = $this->jobType ?? 'maintenance';
        $this->workerLock = WorkerHelper::claimLock($jobType);

        if ($this->workerLock === null) {
            return false;
        }

        register_shutdown_function(function (): void {
            $this->releaseWorkerLock();
        });

        return true;
    }

    private function releaseWorkerLock(): void
    {
        WorkerHelper::releaseLock($this->workerLock);
        $this->workerLock = null;
    }
}
