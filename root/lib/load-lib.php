<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../lib/load-lib.php
 * Description: ChatGPT API Status Generator
 */

// Get the user's IP address from the server variable
$ip = $_SERVER['REMOTE_ADDR'];

// Check if the user's IP address is blacklisted
if (is_blacklisted($ip)) {
    // Stop the script and show an error message if the IP is blacklisted
    http_response_code(403); // Set HTTP status code to 403 Forbidden
    echo "Your IP address has been blacklisted. If you believe this is an error, please contact us.";
    exit(); // Terminate the script execution
} elseif (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // The user is not logged in; redirect them to the login page
    header('Location: login.php'); // Redirect to login page
    exit(); // Terminate the script execution after redirection
} elseif (isset($_GET['page'])) { // Check if a 'page' parameter is provided in the URL
    $page = htmlspecialchars($_GET['page']); // Sanitize the page parameter to prevent XSS

    $_SESSION['timeout'] = time(); // Update the session timeout to the current time

    // Fetch user information using a common function based on the logged-in username
    $user = getUserInfo($_SESSION['username']);

    // Construct the file path for the page helper file
    $helperFile = "../app/helpers/" . $page . "-helper.php";
    // Check if the helper file exists
    if (file_exists($helperFile)) {
        // Only include the helper file if the user is an admin or if it's not the users-helper.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            require_once($helperFile); // Include the helper file
        }
    }

    // Construct the file path for the page forms file
    $formsFile = "../app/forms/" . $page . "-forms.php";
    // Check if the forms file exists
    if (file_exists($formsFile)) {
        // Only include the forms file if the user is an admin or if it's not the users-forms.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            require_once($formsFile); // Include the forms file
        }
    }

    // Construct the file path for the main page file
    $pageFile = "../app/pages/" . $page . ".php";
    // Check if the page file exists
    if (file_exists($pageFile)) {
        // Only set the pageOutput if the user is an admin or if it's not the users.php
        if ($page !== 'users' || ($user && $user->admin == 1)) {
            $pageOutput = $pageFile; // Assign the page file path to pageOutput variable
        }
    }
}
