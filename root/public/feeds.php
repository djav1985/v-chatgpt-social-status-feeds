<?php
/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: feeds.php
 * Description: Generates an RSS feed for the ChatGPT API based on user accounts.
 * License: MIT
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../lib/rss-lib.php';
// Instantiate the ErrorHandler to register handlers
new ErrorHandler();

/**
 * Check if the required query parameters 'user' and 'acct' are present.
 * If not, display an error message and exit.
 */
if (!isset($_GET['user']) || !isset($_GET['acct'])) {
    echo 'Error: Missing required parameters';
    die(1);
}

// Sanitize and store the parameters
$accountOwner = htmlspecialchars($_GET['user'], ENT_QUOTES, 'UTF-8');
$accountName = htmlspecialchars($_GET['acct'], ENT_QUOTES, 'UTF-8');

// Validate the sanitized parameters
if (empty($accountOwner) || empty($accountName)) {
    echo 'Error: Invalid parameters';
    die(1);
}

/**
 * Output the RSS feed for the given account owner and name.
 * Catch any exceptions and display an error message.
 */
try {
    outputRssFeed($accountName, $accountOwner);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
    die(1);
}
