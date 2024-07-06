<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: load-lib.php
 * Description: ChatGPT API Status Generator
 */

session_start();

$ip = $_SERVER['REMOTE_ADDR'];
if (is_blacklisted($ip)) {
    // Stop the script and show an error if the IP is blacklisted
    http_response_code(403); // Optional: Set HTTP status code to 403 Forbidden
    echo "Your IP address has been blacklisted. If you believe this is an error, please contact us.";
    exit();
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // The user is not logged in, redirect to the login page
    header('Location: login.php');
    exit();
} elseif (isset($_GET['page'])) {
    $page = htmlspecialchars($_GET['page']);

    $_SESSION['timeout'] = time();

    // Fetch user information using the common function
    $user = getUserInfo($_SESSION['username']);

    // Check if $page-helper.php exists
    $helperFile = "../app/helpers/" . $page . "-helper.php";
    if (file_exists($helperFile)) {
        // Only include the file if the user is an admin or if it's not the users-helper.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            require_once($helperFile);
        }
    }

    // Check if $page-forms.php exists
    $formsFile = "../app/forms/" . $page . "-forms.php";
    if (file_exists($formsFile)) {
        // Only include the file if the user is an admin or if it's not the users-forms.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            require_once($formsFile);
        }
    }

    // Check if $page.php exists
    $pageFile = "../app/pages/" . $page . ".php";
    if (file_exists($pageFile)) {
        // Only set the pageOutput if the user is an admin or if it's not the users.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            $pageOutput = $pageFile;
        }
    }
}
