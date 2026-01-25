<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

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

use App\Core\Controller;
use App\Models\Account;
use App\Models\User;
use App\Core\SessionManager;
use App\Helpers\MessageHelper;
use App\Helpers\ValidationHelper;
use App\Services\QueueService;

class AccountsController extends Controller
{
    /**
     * Show the accounts management page with schedule overview.
     *
     * @return void
     */
    public function handleRequest(): void
    {
        $daysOptions = self::generateDaysOptions();
        $cronOptions = self::generateCronOptions();
        $accountList = self::generateAccountList();
        $calendarOverview = self::generateCalendarOverview();
            $userStats = self::buildUserStats();

        $this->render('accounts', [
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
     * @return void
     */
    public function handleSubmission(): void
    {
        $session = SessionManager::getInstance();
        if (!ValidationHelper::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            MessageHelper::addMessage('Invalid CSRF token. Please try again.');
            header('Location: /accounts');
            exit;
        }

        if (isset($_POST['edit_account'])) {
            self::createOrUpdateAccount();
            return;
        }

        if (isset($_POST['delete_account'])) {
            self::deleteAccount();
            return;
        }

        header('Location: /accounts');
        exit;
    }

    /**
     * Build a calendar overview of scheduled posts grouped by day and time slot.
     *
     * @return string HTML representation of the calendar
     */
    private static function generateCalendarOverview(): string
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        $accounts = array_map(
            fn($account) => self::hydrateAccountRow($account),
            User::getAllUserAccts($username)
        );

        $daysOfWeek = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $overview = [];
        foreach ($daysOfWeek as $day) {
            $overview[$day] = [
                'morning' => [],
                'afternoon' => [],
                'night' => [],
            ];
        }

        foreach ($accounts as $acct) {
            if ($acct->account === '') {
                continue;
            }
            $acctDays = array_map('trim', explode(',', (string) $acct->days));
            if (in_array('everyday', $acctDays, true)) {
                $acctDays = $daysOfWeek;
            }

            $cronTimes = array_filter(
                array_map('trim', explode(',', (string) $acct->cron)),
                fn($v) => $v !== '' && is_numeric($v)
            );

            foreach ($acctDays as $day) {
                $day = strtolower($day);
                if (!isset($overview[$day])) {
                    continue;
                }
                foreach ($cronTimes as $time) {
                    $hour = (int) $time;
                    $slot = 'night';
                    if ($hour >= 5 && $hour < 12) {
                        $slot = 'morning';
                    } elseif ($hour >= 12 && $hour <= 17) {
                        $slot = 'afternoon';
                    }
                    $overview[$day][$slot][] = [
                        'hour' => $hour,
                        'text' => self::formatHour($hour) . ': ' . $acct->account
                    ];
                }
            }
        }

        // Sort entries within each slot chronologically by hour
        foreach ($overview as $day => $slots) {
            foreach ($slots as $slot => $entries) {
                usort($overview[$day][$slot], fn($a, $b) => $a['hour'] <=> $b['hour']);
            }
        }

        return self::overviewToHtml($overview, $daysOfWeek);
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

    /**
     * Convert the overview array to an HTML grid.
     *
     * @param array<string, array<string, list<array{hour: int, text: string}|string>>> $overview    Organized array of posts
     * @param array<int, string> $daysOfWeek  Days of the week in order
     * @return string HTML table grid
     */
    private static function overviewToHtml(array $overview, array $daysOfWeek): string
    {
        $html = '<div class="calendar-grid">';
        foreach ($daysOfWeek as $day) {
            $html .= '<div class="calendar-day">';
            $html .= '<h4>' . ucfirst($day) . '</h4>';
            foreach (['morning', 'afternoon', 'night'] as $slot) {
                $html .= '<div class="calendar-slot">';
                $html .= '<strong>' . ucfirst($slot) . '</strong>';
                $html .= '<ul>';
                foreach ($overview[$day][$slot] as $entry) {
                    $entryText = is_array($entry) ? $entry['text'] : $entry;
                    $html .= '<li>' . htmlspecialchars((string)$entryText) . '</li>';
                }
                if (empty($overview[$day][$slot])) {
                    $html .= '<li>&nbsp;</li>';
                }
                $html .= '</ul></div>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Create select options for the days of the week.
     *
     * @return string HTML option tags
     */
    private static function generateDaysOptions(): string
    {
        $days = ['everyday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $options = '';
        foreach ($days as $day) {
            $options .= "<option value=\"$day\">" . ucfirst($day) . "</option>";
        }
        return $options;
    }

    /**
     * Generate hourly cron selection options for 6am through 10pm.
     *
     * @return string HTML option tags
     */
    private static function generateCronOptions(): string
    {
        $options = '<option value="null" selected>Off</option>';
        for ($hour = 6; $hour <= 22; $hour++) {
            $amPm = ($hour < 12) ? 'am' : 'pm';
            $displayHour = ($hour <= 12) ? $hour : $hour - 12;
            $displayTime = "{$displayHour} {$amPm}";
            $value = ($hour < 10) ? "0{$hour}" : "{$hour}";
            $options .= "<option value=\"{$value}\">{$displayTime}</option>";
        }
        return $options;
    }

    /**
     * Render the account list items for the accounts page.
     *
     * @return string HTML fragment of account rows
     */
    private static function generateAccountList(): string
    {
        $session = SessionManager::getInstance();
        $username = $session->get('username');
        $accounts = array_map(
            fn($account) => self::hydrateAccountRow($account),
            User::getAllUserAccts($username)
        );
        $output = '';
        foreach ($accounts as $account) {
            if ($account->account === '') {
                continue;
            }
            $accountName = $account->account;

            $daysArr = array_map('ucfirst', array_map('trim', explode(',', $account->days)));
            $daysStr = implode(', ', $daysArr);
            $cronArr = array_filter(
                array_map('trim', explode(',', $account->cron)),
                function (string $hour): bool {
                    return $hour !== '' && is_numeric($hour);
                }
            );
            $timesStr = 'Off';
            if (!empty($cronArr)) {
                $times = array_map(
                    function (string $hour): string {
                        $hour = (int) $hour;
                        if ($hour === 0) {
                            return '12 am';
                        }
                        if ($hour === 12) {
                            return '12 pm';
                        }
                        $amPm = ($hour < 12) ? 'am' : 'pm';
                        $displayHour = ($hour <= 12) ? $hour : $hour - 12;
                        return $displayHour . ' ' . $amPm;
                    },
                    $cronArr
                );
                $timesStr = implode(', ', $times);
            }

            $dataAttributes  = "data-account-name=\"" . self::escapeAttribute($accountName) . "\" ";
            $dataAttributes .= "data-prompt=\"" . self::escapeAttribute($account->prompt) . "\" ";
            $dataAttributes .= "data-link=\"" . self::escapeAttribute($account->link) . "\" ";
            $dataAttributes .= "data-hashtags=\"" . ($account->hashtags ? '1' : '0') . "\" ";
            $dataAttributes .= "data-cron=\"" . self::escapeAttribute(implode(',', explode(',', $account->cron))) . "\" ";
            $dataAttributes .= "data-days=\"" . self::escapeAttribute(implode(',', explode(',', $account->days))) . "\" ";
            $dataAttributes .= "data-platform=\"" . self::escapeAttribute($account->platform) . "\"";

            ob_start();
            $viewData = [
                'accountName' => $accountName,
                'accountData' => $account,
                'daysStr' => $daysStr,
                'timesStr' => $timesStr,
                'dataAttributes' => $dataAttributes,
            ];
            extract($viewData);
            include __DIR__ . '/../Views/partials/account-list-item.php';
            $output .= ob_get_clean();
        }
        return $output;
    }

    /**
     * Escape attribute values for safe HTML output.
     */
    private static function escapeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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

        $userInfo = User::getUserInfo($username);
        $accountCount = count(User::getAllUserAccts($username));

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
     * @return void
     */
    private static function createOrUpdateAccount(): void
    {
        $session = SessionManager::getInstance();
        $accountOwner = $session->get('username');
        // Fix: Handle space-to-dash conversion and use alphanumeric-dash mode
        $rawAccountName = $_POST['account'] ?? '';
        $normalizedAccountName = str_replace(' ', '-', $rawAccountName);
        $accountName = ValidationHelper::sanitizeString($normalizedAccountName, 'alphanumeric-dash');
        $prompt = ValidationHelper::sanitizeString($_POST['prompt'] ?? '');
        $platform = ValidationHelper::sanitizeString($_POST['platform'] ?? '');
        // Fix: Handle null return from validateInteger
        $hashtags = ValidationHelper::validateInteger($_POST['hashtags'] ?? 0);
        if ($hashtags === null) {
            $hashtags = 0;
            MessageHelper::addMessage('Invalid hashtags value supplied. Defaulting to 0.');
        }
        $link = ValidationHelper::sanitizeString($_POST['link'] ?? '');
        
        // Validate cron array and process if valid
        $cronErrors = ValidationHelper::validateCronArray($_POST['cron'] ?? []);
        foreach ($cronErrors as $err) {
            MessageHelper::addMessage($err);
        }
        
        // Process cron hours into comma-separated string
        $cron = 'null';
        if (empty($cronErrors) && isset($_POST['cron']) && is_array($_POST['cron'])) {
            $hours = [];
            foreach ($_POST['cron'] as $hour) {
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
        $daysErrors = ValidationHelper::validateDaysArray($_POST['days'] ?? []);
        foreach ($daysErrors as $err) {
            MessageHelper::addMessage($err);
        }
        
        // Process days into comma-separated string or 'everyday'
        $days = '';
        if (empty($daysErrors) && isset($_POST['days'])) {
            if ($_POST['days'] === 'everyday') {
                $days = 'everyday';
            } elseif (is_array($_POST['days'])) {
                $days = (count($_POST['days']) === 1 && $_POST['days'][0] === 'everyday') 
                    ? 'everyday' 
                    : implode(',', $_POST['days']);
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
            'cronArr' => isset($_POST['cron']) && is_array($_POST['cron']) ? $_POST['cron'] : [],
        ]);

        foreach ($accountValidationErrors as $err) {
            MessageHelper::addMessage($err);
        }

        if (!empty($session->get('messages'))) {
            header('Location: /accounts');
            exit;
        }

        try {
            $queue = new QueueService();
            if (Account::accountExists($accountOwner, $accountName)) {
                $oldInfo = Account::getAcctInfo($accountOwner, $accountName);
                if (is_array($oldInfo)) {
                    $oldInfo = (object)$oldInfo;
                }
                Account::updateAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
                if ($oldInfo && ($oldInfo->cron !== $cron || $oldInfo->days !== $days)) {
                    $queue->rescheduleAccountJobs($accountOwner, $accountName, $cron, $days);
                }
            } else {
                Account::createAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
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
            MessageHelper::addMessage('Failed to create or modify account: ' . $e->getMessage());
        }
        header('Location: /accounts');
        exit;
    }

    /**
     * Delete an account owned by the current user.
     *
     * @return void
     */
    private static function deleteAccount(): void
    {
        $session = SessionManager::getInstance();
        $accountName = ValidationHelper::sanitizeString($_POST['account'] ?? '');
        $accountOwner = $session->get('username');
        try {
            Account::deleteAccount($accountOwner, $accountName);
            $queueService = new QueueService();
            $queueService->removeAllJobs($accountOwner, $accountName);
            MessageHelper::addMessage('Account Deleted.');
        } catch (\Exception $e) {
            MessageHelper::addMessage('Failed to delete account: ' . $e->getMessage());
        }
        header('Location: /accounts');
        exit;
    }
}
