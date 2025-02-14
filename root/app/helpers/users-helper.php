<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: users-helper.php
 * Description: Helper functions for user-related operations.
 * License: MIT
 */

/**
 * Generates the user list with data attributes for each user.
 *
 * @return string HTML output containing the user list.
 */
function generateUserList()
{
    // Fetch all users from the database
    $users = UserHandler::getAllUsers();
    $output = "";

    foreach ($users as $user) {
        // Prepare data attributes for each user to be used in the HTML
        $dataAttributes = "data-username=\"" . htmlspecialchars($user->username) . "\" ";
        $dataAttributes .= "data-admin=\"" . htmlspecialchars($user->admin) . "\" ";
        $dataAttributes .= "data-total-accounts=\"" . htmlspecialchars($user->total_accounts) . "\" ";
        $dataAttributes .= "data-max-api-calls=\"" . htmlspecialchars($user->max_api_calls) . "\" ";
        $dataAttributes .= "data-used-api-calls=\"" . htmlspecialchars($user->used_api_calls) . "\" ";
        $dataAttributes .= "data-expires=\"" . htmlspecialchars($user->expires) . "\" ";

        // Generate HTML for each user card
        $output .= "<div class=\"column col-6 col-xl-12 col-md-12 col-sm-12\">";
        $output .= "<div class=\"card account-list-card\">";
        $output .= "<div class=\"card-header account-card\">";
        $output .= "<div class=\"card-title h5\">" . htmlspecialchars($user->username) . "</div>";
        $output .= "<br>";
        $output .= "<p><strong>Max API Calls:</strong> " . htmlspecialchars($user->max_api_calls) . "</p>";
        $output .= "<p><strong>Used API Calls:</strong> " . htmlspecialchars($user->used_api_calls) . "</p>";
        $output .= "<p><strong>Expires:</strong> " . htmlspecialchars($user->expires) . "</p>";
        $output .= '</div>';
        $output .= "<div class=\"card-body button-group\">";
        $output .= "<button class=\"btn btn-primary\" id=\"update-btn\" " . $dataAttributes . ">Update</button>";
        $output .= "<form class=\"delete-user-form\" action=\"/users\" method=\"POST\">";
        $output .= "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($user->username) . "\">";
        $output .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($_SESSION['csrf_token']) . "\">";
        $output .= "<button class=\"btn btn-error\" name=\"delete_user\">Delete</button>";
        $output .= "</form>";

        // Only show the "Login As" button if the user is not the current session user
        if ($user->username !== $_SESSION['username']) {
            $output .= "<form class=\"login-as-form\" action=\"/users\" method=\"POST\">";
            $output .= "<input type=\"hidden\" name=\"username\" value=\"" . htmlspecialchars($user->username) . "\">";
            $output .= "<input type=\"hidden\" name=\"csrf_token\" value=\"" . htmlspecialchars($_SESSION['csrf_token']) . "\">";
            $output .= "<button class=\"btn btn-primary\" name=\"login_as\">Login</button>";
            $output .= "</form>";
        }

        $output .= "</div></div></div>";
    }

    return $output;
}
