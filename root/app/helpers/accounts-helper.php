<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../app/helpers/accounts-helper.php
 * Description: ChatGPT API Status Generator
 */

function generateAccountDetails()
{
    $accountOwner = $_SESSION['username'];

    // Use the common function to get user info
    $userInfo = getUserInfo($accountOwner);

    if ($userInfo) {
        // Extract the required fields from the user info
        $totalAccounts = $userInfo->total_accounts;
        $maxApiCalls = $userInfo->max_api_calls;
        $usedApiCalls = $userInfo->used_api_calls;

        // Format the data into a nice box
        $output = "<div class=\"account-details\">";
        $output .= "<p>Max Accounts: " . htmlentities($totalAccounts) . "</p>";
        $output .= "<p>Max API Calls: " . htmlentities($maxApiCalls) . "</p>";
        $output .= "<p>Used API Calls: " . htmlentities($usedApiCalls) . "</p>";
        $output .= "</div>";
    } else {
        $output = "<div class=\"account-details\">No account details available.</div>";
    }
    return $output;
}
