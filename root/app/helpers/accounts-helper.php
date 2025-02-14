<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: accounts-helper.php
 * Description: Helper functions for account-related operations.
 * License: MIT
 */

/**
 * Generates the account details for the logged-in user.
 *
 * @return string HTML output containing account details or an error message.
 */
function generateAccountDetails()
{
    // Get the username of the logged-in user
    $accountOwner = $_SESSION['username'];
    // Fetch user information from the database
    $userInfo = UserHandler::getUserInfo($accountOwner);

    if ($userInfo) {
        // Extract user information
        $totalAccounts = $userInfo->total_accounts;
        $maxApiCalls = $userInfo->max_api_calls;
        $usedApiCalls = $userInfo->used_api_calls;
        $expires = $userInfo->expires;
        $expiresFormatted = $expires ? (new DateTime($expires))->format('d/m/Y') : 'N/A';

        // Generate HTML for account details
        $output = "<div class=\"card\">";
        $output .= "<div class=\"card-body\">";
        $output .= "<p><strong>Max Accounts:</strong> " . htmlentities($totalAccounts) . "</p>";
        $output .= "<p><strong>Max API Calls:</strong> " . htmlentities($maxApiCalls) . "</p>";
        $output .= "<p><strong>Used API Calls:</strong> " . htmlentities($usedApiCalls) . "</p>";
        $output .= "<p><strong>Expires:</strong> " . htmlentities($expiresFormatted) . "</p>";
        $output .= "</div></div>";
    } else {
        // Display message if no account details are available
        $output = "<div class=\"card\"><div class=\"card-body\">No account details available.</div></div>";
    }
    return $output;
}

/**
 * Generates the options for the days dropdown.
 *
 * @return string HTML options for the days dropdown.
 */
function generateDaysOptions()
{
    // Define the days of the week
    $days = ['everyday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $options = '';
    foreach ($days as $day) {
        // Generate HTML option for each day
        $options .= "<option value=\"$day\">" . ucfirst($day) . "</option>";
    }
    return $options;
}

/**
 * Generates the options for the cron dropdown.
 *
 * @return string HTML options for the cron dropdown.
 */
function generateCronOptions()
{
    // Start with the "Off" option
    $options = '<option value="null" selected>Off</option>';
    // Generate options for hours from 6 AM to 10 PM
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
 * Generates the account list for the logged-in user.
 *
 * @return string HTML output containing the account list.
 */
function generateAccountList()
{
    // Get the username of the logged-in user
    $username = $_SESSION['username'];
    // Fetch all accounts for the user
    $accounts =  UserHandler::getAllUserAccts($username);

    $output = '';
    foreach ($accounts as $account) {
        $accountName = $account->account;
        // Fetch account information
        $accountData = AccountHandler::getAcctInfo($username, $accountName);

        // Prepare data attributes for each account to be used in the HTML
        $dataAttributes = "data-account-name=\"{$accountName}\" ";
        $dataAttributes .= "data-prompt=\"" . htmlspecialchars($accountData->prompt) . "\" ";
        $dataAttributes .= "data-link=\"" . htmlspecialchars($accountData->link) . "\" ";
        $dataAttributes .= "data-hashtags=\"" . ($accountData->hashtags ? '1' : '0') . "\" ";
        $dataAttributes .= "data-cron=\"" . htmlspecialchars(implode(',', explode(',', $accountData->cron))) . "\" ";
        $dataAttributes .= "data-days=\"" . htmlspecialchars(implode(',', explode(',', $accountData->days))) . "\" ";
        $dataAttributes .= "data-platform=\"" . htmlspecialchars($accountData->platform) . "\"";

        // Generate HTML for each account card
        $output .= "<div class=\"column col-6 col-xl-6 col-md-12 col-sm-12\">";
        $output .= "<div class=\"card account-list-card\">";
        $output .= "<div class=\"card-header account-card\">";
        $output .= "<div class=\"card-title h5\">#" . htmlspecialchars($accountName) . "</div>";
        $output .= "<br>";
        $output .= "<p><strong>Prompt:</strong> " . htmlspecialchars($accountData->prompt) . "</p>";
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
