<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: feeds.php
 * Description: This file generates an RSS feed for the ChatGPT API based on user accounts.
 */

// Include necessary files from the base directory
require_once __DIR__ . '/../config.php'; // Configuration settings
require_once __DIR__ . '/../db.php'; // Database functions
require_once __DIR__ . '/../lib/common-lib.php'; // Common utility functions
require_once __DIR__ . '/../lib/rss-lib.php'; // RSS feed generation library

// Check if the required query parameters 'user' and 'acct' are present in the URL
if (!isset($_GET['user']) || !isset($_GET['acct'])) {
    // Output an error message if the required parameters are missing
    echo 'Error: Missing required parameters';
    exit();
} elseif (isset($_GET['user']) && isset($_GET['acct'])) {
    // Sanitize and store the parameters to prevent security issues such as XSS
    $accountOwner = htmlspecialchars($_GET['user']);
    $accountName = htmlspecialchars($_GET['acct']);

    // Call the function to output the RSS feed for the given account owner and name
    outputRssFeed($accountName, $accountOwner);
}
