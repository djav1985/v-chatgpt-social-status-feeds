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
use App\Core\AuthMiddleware;
use App\Models\Account;
use App\Models\User;
use App\Models\JobQueue;

class AccountsController extends Controller
{
    public static function handleRequest(): void
    {
        AuthMiddleware::check();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                $_SESSION['messages'][] = 'Invalid CSRF token. Please try again.';
                header('Location: /accounts');
                exit;
            }

            if (isset($_POST['edit_account'])) {
                $accountOwner = $_SESSION['username'];
                $accountName = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', trim($_POST['account']))));
                $prompt = trim($_POST['prompt']);
                $platform = trim($_POST['platform']);
                $hashtags = isset($_POST['hashtags']) ? (int) $_POST['hashtags'] : 0;
                $link = trim($_POST['link']);
                $cron = '';
                if (isset($_POST['cron']) && is_array($_POST['cron'])) {
                    $cron = (count($_POST['cron']) === 1 && $_POST['cron'][0] === 'null') ? 'null' : implode(',', $_POST['cron']);
                }
                $days = '';
                if (isset($_POST['days']) && is_array($_POST['days'])) {
                    $days = (count($_POST['days']) === 1 && $_POST['days'][0] === 'everyday') ? 'everyday' : implode(',', $_POST['days']);
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
                            mkdir($acctImagePath, 0755, true);
                            $indexFilePath = $acctImagePath . '/index.php';
                            file_put_contents(
                                $indexFilePath, '<?php die(); ?>'
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
            } elseif (isset($_POST['delete_account'])) {
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
        }

        $daysOptions = self::generateDaysOptions();
        $cronOptions = self::generateCronOptions();
        $accountList = self::generateAccountList();
        $calendarOverview = self::generateCalendarOverview();

        (new self())->render('accounts', [
            'daysOptions' => $daysOptions,
            'cronOptions' => $cronOptions,
            'accountList' => $accountList,
            'calendarOverview' => $calendarOverview,
        ]);
    }

    /**
     * Generate a calendar overview of scheduled posts grouped by day and time slot.
     */
    public static function generateCalendarOverview(): string
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
     * Convert hour in 24h format to human readable 12h with a.m./p.m.
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

    public static function generateDaysOptions(): string
    {
        $days = ['everyday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $options = '';
        foreach ($days as $day) {
            $options .= "<option value=\"$day\">" . ucfirst($day) . "</option>";
        }
        return $options;
    }

    public static function generateCronOptions(): string
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

    public static function generateAccountList(): string
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

            $output .= "<div class=\"column col-6 col-xl-6 col-md-12 col-sm-12\">";
            $output .= "<div class=\"card account-list-card\">";
            $output .= "<div class=\"card-header account-card\">";
            $output .= "<div class=\"card-title h5\">#" . htmlspecialchars($accountName) . "</div><br>";
            $output .= "<p><strong>Prompt:</strong> " . htmlspecialchars($accountData->prompt) . "</p>";
            $output .= "<p><strong>Days:</strong> " . htmlspecialchars($daysStr) . "</p>";
            $output .= "<p><strong>Times:</strong> " . htmlspecialchars($timesStr) . "</p>";
            $output .= "<p><strong>Link:</strong> <a href=\"" . htmlspecialchars($accountData->link) . "\" target=\"_blank\">" . htmlspecialchars($accountData->link) . "</a></p>";
            $output .= "</div>";
            $output .= "<div class=\"card-body button-group\">";
            $output .= "<button class=\"btn btn-primary\" id=\"update-button\" {$dataAttributes}>Update</button>";
            $output .= "<form class=\"delete-account-form\" action=\"/accounts\" method=\"POST\">";
            $output .= "<input type=\"hidden\" name=\"account\" value=\"" . htmlspecialchars($accountName) . "\">";
            $output .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . $_SESSION['csrf_token'] . "\">";
            $output .= "<button class=\"btn btn-error\" name=\"delete_account\">Delete</button>";
            $output .= "</form>";
            $output .= "</div></div></div>";
        }
        return $output;
    }
}
