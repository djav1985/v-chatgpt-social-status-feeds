<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: load-lib.php
 * Description: Handles loading of helper, forms, and page files based on user session and permissions.
 * License: MIT
 */

// Validate and sanitize the IP address
$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

// Check if the user's IP address is blacklisted
if ($ip && UtilityHandler::isBlacklisted($ip)) {
    http_response_code(403);
    echo "Your IP address has been blacklisted. If you believe this is an error, please contact us.";
    ErrorHandler::logMessage("Blacklisted IP attempted access: $ip", 'error');
    die(1);
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to login page if the user is not logged in
    header('Location: login.php');
    die(1);
} elseif (isset($_GET['page'])) {
    // Sanitize the page parameter to prevent XSS attacks
    $page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS);

    // Update session timeout
    $_SESSION['timeout'] = time();

    // Retrieve user information
    $user = UserHandler::getUserInfo($_SESSION['username']);

    if ($user) {
        // Load helper file if it exists and user has permission
        $helperFile = "../app/helpers/" . $page . "-helper.php";
        if (file_exists($helperFile)) {
            if ($page !== 'users' || ($user->admin == 1)) {
                require_once($helperFile);
            }
        }

        // Load forms file if it exists and user has permission
        $formsFile = "../app/forms/" . $page . "-forms.php";
        if (file_exists($formsFile)) {
            if ($page !== 'users' || ($user->admin == 1)) {
                require_once($formsFile);
            }
        }

        // Load page file if it exists and user has permission
        $pageFile = "../app/pages/" . $page . ".php";
        if (file_exists($pageFile)) {
            if ($page !== 'users' || ($user->admin == 1)) {
                $pageOutput = $pageFile;
            }
        }
    } else {
        ErrorHandler::logMessage("Failed to load user info for session user: " . $_SESSION['username'], 'error');
        die(1);
    }
}
