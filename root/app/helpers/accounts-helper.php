<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/helpers/accounts-helper.php
 * Description: ChatGPT API Status Generator
 */

/**
 * Generates the account details for the logged-in user.
 *
 * Retrieves information about the user's account, including the total number of accounts,
 * maximum allowed API calls, and the number of API calls used. If no user information is available,
 * a message indicating the absence of account details will be displayed.
 *
 * @return string HTML output containing account details or an error message.
 */
function generateAccountDetails()
{
    $accountOwner = $_SESSION['username']; // Retrieve the username of the logged-in user from the session

    // Use the common function to get user info based on the account owner
    $userInfo = getUserInfo($accountOwner); // Fetch user information

    if ($userInfo) { // Check if user information was retrieved successfully
        // Extract the required fields from the user info object
        $totalAccounts = $userInfo->total_accounts; // Total number of accounts associated with the user
        $maxApiCalls = $userInfo->max_api_calls; // Maximum number of API calls allowed for the user
        $usedApiCalls = $userInfo->used_api_calls; // Number of API calls that have been used by the user
        $expires = $userInfo->expires; // Expiration date of the user's account

        // Format the expiration date to DD/MM/YYYY
        $expiresFormatted = $expires ? (new DateTime($expires))->format('d/m/Y') : 'N/A';

        // Format the data into a visually appealing box
        $output = "<div class=\"account-details\">"; // Start of the account details container
        $output .= "<p>Max Accounts: " . htmlentities($totalAccounts) . "</p>"; // Display the maximum accounts
        $output .= "<p>Max API Calls: " . htmlentities($maxApiCalls) . "</p>"; // Display the max API calls
        $output .= "<p>Used API Calls: " . htmlentities($usedApiCalls) . "</p>"; // Display the used API calls
        $output .= "<p>Expires: " . htmlentities($expiresFormatted) . "</p>"; // Display the expiration date
        $output .= "</div>"; // End of the account details container
    } else {
        // If userInfo is not found, set a message indicating no account details are available
        $output = "<div class=\"account-details\">No account details available.</div>";
    }
    return $output; // Return the generated HTML content
}
