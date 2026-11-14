<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: AccountsController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use App\Core\Response;
use App\Core\ErrorManager;
use App\Models\AccountModel;
use App\Models\UserModel;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;
use App\Services\QueueService;

class AccountsController
{
    /**
     * Show the accounts management page with schedule overview.
     *
     * @return Response
     */
    public function handleRequest(ServerRequestInterface $request): Response
    {
        $daysOptions = self::generateDaysOptions();
        $cronOptions = self::generateCronOptions();
        $accountList = self::generateAccountList();
        $calendarOverview = self::generateCalendarOverview();
        $userStats = self::buildUserStats();

        return Response::view('accounts', [
            'daysOptions' => $daysOptions,
            'cronOptions' => $cronOptions,
            'accountList' => $accountList,
            'calendarOverview' => $calendarOverview,
            'userStats' => $userStats,
        ]);
    }

    /**
     * Handle create/update and delete account form submissions.
     *
     * @return Response
     */
    public function handleSubmission(ServerRequestInterface $request): Response
    {
        $requestBody = $request->getParsedBody();
        $requestBody = is_array($requestBody) ? $requestBody : [];
        if (isset($requestBody['edit_account'])) {
            $this->createOrUpdateAccount($requestBody);
            return Response::redirect('/accounts');
        }

        if (isset($requestBody['delete_account'])) {
            $this->deleteAccount($requestBody);
            return Response::redirect('/accounts');
        }

        return Response::redirect('/accounts');
    }

    /**
     * Build structured schedule data for the accounts view.
     *
     * @return array{
     *     days: list<string>,
     *     schedule: array<string, array<string, list<array{hour: int, account: string, time: string}>>>
     * }
     */
    private static function generateCalendarOverview(): array
    {
        $username = SessionManager::getInstance()->get('username');
        $accounts = array_map(fn($account) => self::hydrateAccountRow($account), UserModel::getAllUserAccts($username));
        $days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $overview = [];
        foreach ($days as $day) {
            $overview[$day] = ['morning' => [], 'afternoon' => [], 'night' => []];
        }
        foreach ($accounts as $acct) {
            if ($acct->account === '') {
                continue;
            }
            $accountDays = array_map('trim', explode(',', $acct->days));
            if (in_array('everyday', $accountDays, true)) {
                $accountDays = $days;
            }
            $cronTimes = array_values(array_filter(array_map('trim', explode(',', $acct->cron)), 'is_numeric'));
            sort($cronTimes, SORT_NUMERIC);
            foreach ($cronTimes as $time) {
                $hour = (int) $time;
                $slot = $hour >= 5 && $hour < 12 ? 'morning' : ($hour >= 12 && $hour <= 17 ? 'afternoon' : 'night');
                foreach ($accountDays as $day) {
                    $day = strtolower($day);
                    if (isset($overview[$day][$slot])) {
                        $overview[$day][$slot][] = [
                            'hour' => $hour,
                            'account' => $acct->account,
                            'time' => self::formatHour($hour),
                        ];
                    }
                }
            }
        }
        return ['days' => $days, 'schedule' => self::sortCalendarOverview($overview)];
    }

    /**
     * Sort each aggregated day and time slot by hour after accounts have been combined.
     *
     * @param array<string, array<string, list<array{hour: int, account: string, time: string}>>> $overview
     * @return array<string, array<string, list<array{hour: int, account: string, time: string}>>>
     */
    private static function sortCalendarOverview(array $overview): array
    {
        foreach ($overview as &$slots) {
            foreach ($slots as &$entries) {
                usort($entries, static fn(array $left, array $right): int => $left['hour'] <=> $right['hour']);
            }
            unset($entries);
        }
        unset($slots);

        return $overview;
    }

    /**
     * Convert an hour in 24-hour format to a human readable string.
     *
     * @param int $hour Hour of day in 24-hour format
     * @return string Formatted hour with a.m./p.m.
     */
    private static function formatHour(int $hour): string
    {
        $period = $hour >= 12 ? 'p.m.' : 'a.m.';
        $displayHour = $hour % 12;
        $displayHour = $displayHour === 0 ? 12 : $displayHour;
        return $displayHour . ':00 ' . $period;
    }

    /** @return list<string> Available days for account schedules. */
    private static function generateDaysOptions(): array
    {
        return ['everyday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    }

    /** @return list<array{value: string, label: string}> Available schedule hour values and labels. */
    private static function generateCronOptions(): array
    {
        $options = [['value' => 'null', 'label' => 'Off']];
        for ($hour = 6; $hour <= 22; $hour++) {
            $options[] = [
                'value' => str_pad((string) $hour, 2, '0', STR_PAD_LEFT),
                'label' => ($hour <= 12 ? $hour : $hour - 12) . ' ' . ($hour < 12 ? 'am' : 'pm'),
            ];
        }
        return $options;
    }

    /** @return list<object> Account data for rendering in the view. */
    private static function generateAccountList(): array
    {
        $username = SessionManager::getInstance()->get('username');
        return array_values(array_filter(
            array_map(fn($row) => self::hydrateAccountRow($row), UserModel::getAllUserAccts($username)),
            fn($row) => $row->account !== ''
        ));
    }

    /**
     * Ensure account rows from the database provide consistent property access.
     *
     * @param array<string, mixed>|object $accountRow
     */
    private static function hydrateAccountRow(array|object $accountRow): object
    {
        if (is_array($accountRow)) {
            $accountRow = (object) $accountRow;
        }

        $accountRow->account = (string) ($accountRow->account ?? '');
        $accountRow->username = (string) ($accountRow->username ?? '');
        $accountRow->prompt = (string) ($accountRow->prompt ?? '');
        $accountRow->platform = (string) ($accountRow->platform ?? '');
        $accountRow->link = (string) ($accountRow->link ?? '');
        $accountRow->cron = (string) ($accountRow->cron ?? '');
        $accountRow->days = (string) ($accountRow->days ?? '');
        $accountRow->hashtags = isset($accountRow->hashtags) ? (int) $accountRow->hashtags : 0;

        return $accountRow;
    }

    /**
     * Build user-level account and API usage stats for display.
     *
     * @return array{
     *     totalAccounts: int,
     *     maxApiCallsLabel: string,
     *     usedApiCalls: int,
     *     expiresLabel: string
     * }
     */
    private static function buildUserStats(): array
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');

        $userInfo = UserModel::getUserInfo($username);
        $accountCount = count(UserModel::getAllUserAccts($username));

        $maxApiCallsRaw = $userInfo->max_api_calls ?? null;
        $usedApiCalls = (int) ($userInfo->used_api_calls ?? 0);
        $expiresRaw = $userInfo->expires ?? '';

        return [
            'totalAccounts' => $accountCount,
            'maxApiCallsLabel' => self::formatMaxApiCalls($maxApiCallsRaw),
            'usedApiCalls' => $usedApiCalls,
            'expiresLabel' => self::formatExpiryDate($expiresRaw),
        ];
    }

    /**
     * Format the maximum API call allowance for display.
     */
    private static function formatMaxApiCalls(int|string|null $maxApiCalls): string
    {
        if ($maxApiCalls === null) {
            return 'Off';
        }

        $maxValue = (int) $maxApiCalls;

        if ($maxValue <= 0 || $maxValue >= 9999999999) {
            return 'Off';
        }

        return number_format($maxValue);
    }

    /**
     * Format an expiration date as mm/dd/yyyy or return a sensible fallback.
     */
    private static function formatExpiryDate(?string $expires): string
    {
        if (empty($expires) || $expires === '0000-00-00') {
            return 'N/A';
        }

        try {
            $date = new \DateTime($expires);
            return $date->format('m/d/Y');
        } catch (\Exception) {
            return (string) $expires;
        }
    }

    /**
     * Create a new account or update an existing one for the current user.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function createOrUpdateAccount(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $accountOwner = $session->get('username');
        // Fix: Handle space-to-dash conversion and use alphanumeric-dash mode
        $rawAccountName = $requestBody['account'] ?? '';
        $normalizedAccountName = str_replace(' ', '-', $rawAccountName);
        $accountName = ValidationHelper::sanitizeString($normalizedAccountName, 'alphanumeric-dash');
        // Preserve spaces and punctuation in the user-authored prompt.
        $prompt = trim((string) ($requestBody['prompt'] ?? ''));
        $platform = ValidationHelper::sanitizeString($requestBody['platform'] ?? '');
        // Fix: Handle null return from validateInteger
        $hashtags = ValidationHelper::validateInteger($requestBody['hashtags'] ?? 0);
        if ($hashtags === null) {
            $hashtags = 0;
            MessageHelper::addMessage('Invalid hashtags value supplied. Defaulting to 0.');
        }
        // URLs contain punctuation that the generic sanitizer would strip.
        $link = trim((string) ($requestBody['link'] ?? ''));

        // Validate cron array and process if valid
        $cronErrors = ValidationHelper::validateCronArray($requestBody['cron'] ?? []);
        foreach ($cronErrors as $err) {
            MessageHelper::addMessage($err);
        }

        // Process cron hours into comma-separated string
        $cron = 'null';
        if (empty($cronErrors) && isset($requestBody['cron']) && is_array($requestBody['cron'])) {
            $hours = [];
            foreach ($requestBody['cron'] as $hour) {
                if ($hour === 'null') {
                    continue;
                }
                if (ctype_digit($hour) && (int)$hour >= 0 && (int)$hour <= 23) {
                    $hours[] = str_pad((string)(int)$hour, 2, '0', STR_PAD_LEFT);
                }
            }
            if (!empty($hours)) {
                $cron = implode(',', $hours);
            }
        }

        // Validate days array and process if valid
        $daysErrors = ValidationHelper::validateDaysArray($requestBody['days'] ?? []);
        foreach ($daysErrors as $err) {
            MessageHelper::addMessage($err);
        }

        // Process days into comma-separated string or 'everyday'
        $days = '';
        if (empty($daysErrors) && isset($requestBody['days'])) {
            if ($requestBody['days'] === 'everyday') {
                $days = 'everyday';
            } elseif (is_array($requestBody['days'])) {
                $days = (count($requestBody['days']) === 1 && $requestBody['days'][0] === 'everyday')
                    ? 'everyday'
                    : implode(',', $requestBody['days']);
            }
        }

        if ($cron === 'null' || empty($days) || empty($platform)) {
            MessageHelper::addMessage('Error processing input.');
        }
        if (empty($prompt)) {
            MessageHelper::addMessage('Missing required field(s).');
        }

        // Centralized validation
        $accountValidationErrors = ValidationHelper::validateAccount([
            'accountName' => $accountName,
            'link' => $link,
            'cronArr' => isset($requestBody['cron']) && is_array($requestBody['cron'])
                ? $requestBody['cron']
                : [],
        ]);

        foreach ($accountValidationErrors as $err) {
            MessageHelper::addMessage($err);
        }

        if (!empty($session->get('messages'))) {
            return;
        }

        try {
            $queue = new QueueService();
            if (AccountModel::accountExists($accountOwner, $accountName)) {
                $oldInfo = AccountModel::getAcctInfo($accountOwner, $accountName);
                if (is_array($oldInfo)) {
                    $oldInfo = (object)$oldInfo;
                }
                AccountModel::updateAccount(
                    $accountOwner,
                    $accountName,
                    $prompt,
                    $platform,
                    $hashtags,
                    $link,
                    $cron,
                    $days
                );
                if ($oldInfo && ($oldInfo->cron !== $cron || $oldInfo->days !== $days)) {
                    $queue->rescheduleAccountJobs($accountOwner, $accountName, $cron, $days);
                }
            } else {
                AccountModel::createAccount(
                    $accountOwner,
                    $accountName,
                    $prompt,
                    $platform,
                    $hashtags,
                    $link,
                    $cron,
                    $days
                );
                $acctImagePath = __DIR__ . '/../../public/images/' . $accountOwner . '/' . $accountName;
                if (!file_exists($acctImagePath)) {
                    mkdir(
                        $acctImagePath,
                        defined('DIR_MODE') ? DIR_MODE : 0755,
                        true
                    );
                    $indexFilePath = $acctImagePath . '/index.php';
                    file_put_contents(
                        $indexFilePath,
                        '<?php die(); ?>'
                    );
                }
                $queue->enqueueRemainingJobs($accountOwner, $accountName, $cron, $days);
            }
            MessageHelper::addMessage('Account has been created or modified.');
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Failed to create or modify account: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to create or modify account. Please try again.');
        }
    }

    /**
     * Delete an account owned by the current user.
     *
     * @param array<string, mixed> $requestBody
     * @return void
     */
    private function deleteAccount(array $requestBody): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($requestBody['account'] ?? '', 'alphanumeric-dash');
        $accountOwner = $session->get('username');
        try {
            AccountModel::deleteAccount($accountOwner, $accountName);
            $queueService = new QueueService();
            $queueService->removeAllJobs($accountOwner, $accountName);
            MessageHelper::addMessage('Account Deleted.');
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('Failed to delete account: ' . $e->getMessage(), 'error');
            MessageHelper::addMessage('Failed to delete account. Please try again.');
        }
    }
}
