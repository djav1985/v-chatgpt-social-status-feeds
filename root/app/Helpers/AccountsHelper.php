<?php
namespace App\Helpers;

use DateTime;
use App\Models\UserHandler;
use App\Models\AccountHandler;

/**
 * Helper functions for account-related operations.
 */
class AccountsHelper
{
    public static function generateAccountDetails(): string
    {
        $accountOwner = $_SESSION['username'];
        $userInfo = UserHandler::getUserInfo($accountOwner);
        if ($userInfo) {
            $expiresFormatted = $userInfo->expires ? (new DateTime($userInfo->expires))->format('d/m/Y') : 'N/A';
            $output  = "<div class=\"card\"><div class=\"card-body\">";
            $output .= "<p><strong>Max Accounts:</strong> " . htmlentities($userInfo->total_accounts) . "</p>";
            $output .= "<p><strong>Max API Calls:</strong> " . htmlentities($userInfo->max_api_calls) . "</p>";
            $output .= "<p><strong>Used API Calls:</strong> " . htmlentities($userInfo->used_api_calls) . "</p>";
            $output .= "<p><strong>Expires:</strong> " . htmlentities($expiresFormatted) . "</p>";
            $output .= "</div></div>";
            return $output;
        }
        return "<div class=\"card\"><div class=\"card-body\">No account details available.</div></div>";
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
        $accounts = UserHandler::getAllUserAccts($username);
        $output = '';
        foreach ($accounts as $account) {
            $accountName = $account->account;
            $accountData = AccountHandler::getAcctInfo($username, $accountName);
            $daysArr = array_map('ucfirst', array_map('trim', explode(',', $accountData->days)));
            $daysStr = implode(', ', $daysArr);
            $cronArr = array_filter(array_map('trim', explode(',', $accountData->cron)), function (string $hour): bool {
                return is_numeric($hour) && $hour !== '';
            });
            $timesStr = 'Off';
            if (!empty($cronArr)) {
                $times = array_map(function (string $hour): string {
                    $hour = (int)$hour;
                    if ($hour === 0) {
                        return '12 am';
                    }
                    if ($hour === 12) {
                        return '12 pm';
                    }
                    $amPm = ($hour < 12) ? 'am' : 'pm';
                    $displayHour = ($hour <= 12) ? $hour : $hour - 12;
                    return $displayHour . ' ' . $amPm;
                }, $cronArr);
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
