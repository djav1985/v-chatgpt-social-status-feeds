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
use App\Models\JobQueue;
use App\Core\Csrf;

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

        $this->render('accounts', [
            'daysOptions' => $daysOptions,
            'cronOptions' => $cronOptions,
            'accountList' => $accountList,
            'calendarOverview' => $calendarOverview,
        ]);
    }

    /**
     * Handle create/update and delete account form submissions.
     *
     * @return void
     */
    public function handleSubmission(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
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
     * Create a new account or update an existing one for the current user.
     *
     * @return void
     */
    private static function createOrUpdateAccount(): void
    {
        $accountOwner = $_SESSION['username'];
        $accountName = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST['account']))));
        $prompt = trim($_POST['prompt']);
        $platform = trim($_POST['platform']);
        $hashtags = isset($_POST['hashtags']) ? (int) $_POST['hashtags'] : 0;
        $link = trim($_POST['link']);
        $cron = 'null';
        $invalidCron = false;
        if (isset($_POST['cron']) && is_array($_POST['cron'])) {
            $hours = [];
            foreach ($_POST['cron'] as $hour) {
                if ($hour === 'null') {
                    continue;
                }
                if (ctype_digit($hour) && (int)$hour >= 0 && (int)$hour <= 23) {
                    $hours[] = str_pad((string)(int)$hour, 2, '0', STR_PAD_LEFT);
                } else {
                    $invalidCron = true;
                }
            }
            if (!empty($hours)) {
                $cron = implode(',', $hours);
            }
        }
        $days = '';
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            $days = (count($_POST['days']) === 1 && $_POST['days'][0] === 'everyday') ? 'everyday' : implode(',', $_POST['days']);
        }

        if ($invalidCron) {
            $_SESSION['messages'][] = 'Invalid cron hour(s) supplied. Hours must be between 0 and 23.';
        }
        if (empty($cron) || empty($days) || empty($platform) || !isset($hashtags)) {
            $_SESSION['messages'][] = 'Error processing input.';
        }
        if (empty($prompt)) {
            $_SESSION['messages'][] = 'Missing required field(s).';
        }
        if (!preg_match('/^[a-z0-9-]{8,18}$/', $accountName)) {
            $_SESSION['messages'][] = 'Account name must be 8-18 characters long, alphanumeric and hyphens only.';
        }
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $_SESSION['messages'][] = 'Link must be a valid URL starting with https://.';
        }

        if (!empty($_SESSION['messages'])) {
            header('Location: /accounts');
            exit;
        }

        try {
            if (Account::accountExists($accountOwner, $accountName)) {
                Account::updateAccount($accountOwner, $accountName, $prompt, $platform, $hashtags, $link, $cron, $days);
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
            }
            JobQueue::fillQueryJobs();
            $_SESSION['messages'][] = 'Account has been created or modified.';
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Failed to create or modify account: ' . $e->getMessage();
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
        $accountName = trim($_POST['account']);
        $accountOwner = $_SESSION['username'];
        try {
            Account::deleteAccount($accountOwner, $accountName);
            JobQueue::fillQueryJobs();
            $_SESSION['messages'][] = 'Account Deleted.';
        } catch (\Exception $e) {
            $_SESSION['messages'][] = 'Failed to delete account: ' . $e->getMessage();
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
        $username = $_SESSION['username'];
        $accounts = User::getAllUserAccts($username);

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
            $acctInfo = Account::getAcctInfo($username, $acct->account);
            if (!$acctInfo) {
                continue;
            }
            $acctDays = array_map('trim', explode(',', (string) $acctInfo->days));
            if (in_array('everyday', $acctDays, true)) {
                $acctDays = $daysOfWeek;
            }

            $cronTimes = array_filter(
                array_map('trim', explode(',', (string) $acctInfo->cron)),
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
                    if ($hour >= 5 && $hour <= 12) {
                        $slot = 'morning';
                    } elseif ($hour >= 13 && $hour <= 17) {
                        $slot = 'afternoon';
                    }
                    $overview[$day][$slot][] = self::formatHour($hour) . ': ' . $acct->account;
                }
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
     * @param array $overview    Organized array of posts
     * @param array $daysOfWeek  Days of the week in order
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
                    $html .= '<li>' . htmlspecialchars($entry) . '</li>';
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
        $username = $_SESSION['username'];
        $accounts = User::getAllUserAccts($username);
        $output = '';
        foreach ($accounts as $account) {
            $accountName = $account->account;
            $accountData = Account::getAcctInfo($username, $accountName);

            $daysArr = array_map('ucfirst', array_map('trim', explode(',', $accountData->days)));
            $daysStr = implode(', ', $daysArr);
            $cronArr = array_filter(
                array_map('trim', explode(',', $accountData->cron)),
                function (string $hour): bool {
                    return is_numeric($hour) && $hour !== '';
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

            $dataAttributes  = "data-account-name=\"{$accountName}\" ";
            $dataAttributes .= "data-prompt=\"" . htmlspecialchars($accountData->prompt) . "\" ";
            $dataAttributes .= "data-link=\"" . htmlspecialchars($accountData->link) . "\" ";
            $dataAttributes .= "data-hashtags=\"" . ($accountData->hashtags ? '1' : '0') . "\" ";
            $dataAttributes .= "data-cron=\"" . htmlspecialchars(implode(',', explode(',', $accountData->cron))) . "\" ";
            $dataAttributes .= "data-days=\"" . htmlspecialchars(implode(',', explode(',', $accountData->days))) . "\" ";
            $dataAttributes .= "data-platform=\"" . htmlspecialchars($accountData->platform) . "\"";

            ob_start();
            $viewData = [
                'accountName' => $accountName,
                'accountData' => $accountData,
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
}
