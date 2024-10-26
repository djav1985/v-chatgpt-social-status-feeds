<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../app/helpers/users-helper.php
 * Description: ChatGPT API Status Generator
 */

/**
 * Generates the user list with data attributes for each user.
 *
 * @return string HTML output containing the user list.
 */
function generateUserList()
{
    $users = getAllUsers(); // Assuming this function fetches all users from the database
    $output = '';

    foreach ($users as $user) {
        $dataAttributes = 'data-username="' . htmlspecialchars($user->username) . '" ';
        $dataAttributes .= 'data-password="' . urlencode($user->password) . '" ';
        $dataAttributes .= 'data-admin="' . htmlspecialchars($user->admin) . '" ';
        $dataAttributes .= 'data-total-accounts="' . htmlspecialchars($user->total_accounts) . '" ';
        $dataAttributes .= 'data-max-api-calls="' . htmlspecialchars($user->max_api_calls) . '" ';
        $dataAttributes .= 'data-used-api-calls="' . htmlspecialchars($user->used_api_calls) . '" ';
        $dataAttributes .= 'data-expires="' . htmlspecialchars($user->expires) . '" ';

        // Generate the HTML for each user
        $output .= '<div class="item-box">';
        $output .= '<h3>' . htmlspecialchars($user->username) . '</h3>';
        $output .= '<button class="update-user-button green-button" id="update-btn" ' . $dataAttributes . '>Update</button>';
        $output .= '<form class="delete-user-form" action="/users" method="POST">';
        $output .= '<input type="hidden" name="username" value="' . htmlspecialchars($user->username) . '">';
        $output .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
        $output .= '<button class="delete-user-button red-button" name="delete_user">Delete</button>';
        $output .= '</form>';

        if ($user->username !== $_SESSION['username']) {
            $output .= '<form class="login-as-form" action="/users" method="POST">';
            $output .= '<input type="hidden" name="username" value="' . htmlspecialchars($user->username) . '">';
            $output .= '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
            $output .= '<button class="login-as-button blue-button" name="login_as">Login</button>';
            $output .= '</form>';
        }

        $output .= '</div>';
    }

    return $output;
}
