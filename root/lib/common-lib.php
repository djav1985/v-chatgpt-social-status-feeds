<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../lib/common.php
 * Description: ChatGPT API Status Generator
 */

// Utility Functions

/**
 * Sanitize user input to prevent security vulnerabilities.
 *
 * @param string $data The user input data to sanitize.
 * @return string The sanitized input data.
 */
function sanitize_input($data)
{
    // Trim whitespace and remove HTML tags from the input
    $data = trim(strip_tags($data));

    // Filter the input using appropriate filters to prevent XSS
    $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

    // Add additional security measures by removing potentially harmful code snippets
    $data = str_replace(array("<?", "?>", "<%", "%>"), "", $data);
    $data = str_replace(array("<script", "</script"), "", $data);
    $data = str_replace(array("/bin/sh", "exec(", "system(", "passthru(", "shell_exec(", "phpinfo("), "", $data);

    return $data; // Return the cleaned data
}

/**
 * Check if a string contains any disallowed characters.
 *
 * @param string $str The string to check.
 * @return bool True if disallowed characters are present, false otherwise.
 */
function contains_disallowed_chars($str)
{
    global $disallowed_chars; // Access the global disallowed characters array
    foreach ($disallowed_chars as $char) {
        // Check for existence of each disallowed character in the string
        if (strpos($str, $char) !== false) {
            return true; // Found disallowed character
        }
    }
    return false; // No disallowed characters found
}

/**
 * Check if a string contains any disallowed patterns.
 *
 * @param string $str The string to check.
 * @return bool True if disallowed patterns are present, false otherwise.
 */
function contains_disallowed_patterns($str)
{
    global $disallowed_patterns; // Access the global disallowed patterns array
    foreach ($disallowed_patterns as $pattern) {
        // Check for existence of each disallowed pattern in the string
        if (strpos($str, $pattern) !== false) {
            return true; // Found disallowed pattern
        }
    }
    return false; // No disallowed patterns found
}

// Session and Message Handling Functions

/**
 * Display messages stored in the session and clear them afterwards.
 */
function display_and_clear_messages()
{
    // Check if there are any messages to display
    if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
        echo '<div class="messages">'; // Start message container
        foreach ($_SESSION['messages'] as $message) {
            // Display each message safely
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
        echo '</div>'; // End message container

        // Clear messages after displaying
        unset($_SESSION['messages']);
    }
}

// IP Blacklist Management Functions

/**
 * Update login attempts for an IP address and handle blacklisting if necessary.
 *
 * @param string $ip The IP address to update.
 */
function update_failed_attempts($ip)
{
    $db = new Database(); // Instantiate database object

    // Check if the IP already exists in the blacklist table
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        // If IP exists, increment login attempts and check for blacklisting
        $attempts = $result['login_attempts'] + 1;
        $is_blacklisted = ($attempts >= 3) ? true : false; // Set blacklisting status based on attempts
        $timestamp = ($is_blacklisted) ? time() : $result['timestamp']; // Update timestamp if blacklisted

        // Update the attempts and blacklist status in the database
        $db->query("UPDATE ip_blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :timestamp WHERE ip_address = :ip");
        $db->bind(':attempts', $attempts);
        $db->bind(':blacklisted', $is_blacklisted);
        $db->bind(':timestamp', $timestamp);
    } else {
        // If IP does not exist, insert it as a new entry with initial login attempt
        $db->query("INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, FALSE, :timestamp)");
        $db->bind(':ip', $ip);
        $db->bind(':timestamp', time());
    }
    $db->execute(); // Execute the database query
}

/**
 * Check if an IP address is blacklisted.
 *
 * @param string $ip The IP address to check.
 * @return bool True if the IP is blacklisted, false otherwise.
 */
function is_blacklisted($ip)
{
    $db = new Database(); // Instantiate database object
    // Query the blacklist for the given IP address
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        // Check if the blacklist timestamp is older than three days
        if (time() - $result['timestamp'] > (3 * 24 * 60 * 60)) {
            // Update to remove the IP from the blacklist after three days
            $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
            $db->bind(':ip', $ip);
            $db->execute();
            return false; // IP is no longer blacklisted
        }
        return true; // IP is still blacklisted
    }
    return false; // IP is not blacklisted
}

/**
 * Clear all entries from the IP blacklist.
 */
function clearIpBlacklist()
{
    $db = new Database(); // Instantiate database object
    $db->query("DELETE FROM ip_blacklist"); // Delete all entries from the IP blacklist
    $db->execute(); // Execute the delete query
}

// User Information Functions

/**
 * Retrieve user information based on username.
 *
 * @param string $username The username to look up.
 * @return object The user information object.
 */
function getUserInfo($username)
{
    $db = new Database(); // Instantiate database object
    // Query the users table for the specified username
    $db->query("SELECT * FROM users WHERE username = :username");
    $db->bind(':username', $username);
    return $db->single(); // Return the user information
}

/**
 * Retrieve all users from the database.
 *
 * @return array An array of user objects.
 */
function getAllUsers()
{
    $db = new Database(); // Instantiate database object
    $db->query("SELECT * FROM users"); // Select all users
    return $db->resultSet(); // Return an array of user objects
}

// Account Information Functions

/**
 * Get account information for a specific user and account.
 *
 * @param string $username The username to look up.
 * @param string $account The account name to look up.
 * @return object The account information object.
 */
function getAcctInfo($username, $account)
{
    $db = new Database(); // Instantiate database object
    // Query the accounts table for the specified username and account
    $db->query("SELECT * FROM accounts WHERE username = :username AND account = :account");
    $db->bind(':username', $username);
    $db->bind(':account', $account);
    return $db->single(); // Return the account information
}

/**
 * Retrieve all accounts associated with a specific user.
 *
 * @param string $username The username to look up.
 * @return array An array of account objects.
 */
function getAllUserAccts($username)
{
    $db = new Database(); // Instantiate database object
    // Query the accounts table for all accounts belonging to the specified user
    $db->query("SELECT account FROM accounts WHERE username = :username");
    $db->bind(':username', $username);
    return $db->resultSet(); // Return an array of account objects
}

/**
 * Retrieve all accounts from the database.
 *
 * @return array An array of account objects.
 */
function getAllAccounts()
{
    $db = new Database(); // Instantiate database object
    $db->query("SELECT * FROM accounts"); // Select all accounts
    return $db->resultSet(); // Return the result set
}

// Status Information Functions

/**
 * Retrieve status updates for a specific user and account, ordered by created date.
 *
 * @param string $username The username to look up.
 * @param string $account The account name to look up.
 * @return array An array of status updates.
 */
function getStatusInfo($username, $account)
{
    $db = new Database(); // Instantiate database object to interact with the database

    // Query the status_updates table for the specified username and account,
    // ordering the results by the creation date in descending order
    $db->query("SELECT * FROM status_updates WHERE username = :username AND account = :account ORDER BY created_at DESC");
    $db->bind(':username', $username); // Bind the username parameter to the query
    $db->bind(':account', $account); // Bind the account parameter to the query

    return $db->resultSet(); // Return the result set of status updates as an array
}

/**
 * Count the number of status updates for a specific account.
 *
 * @param string $accountName The account name to check.
 * @return int The count of status updates associated with the account.
 */
function countStatuses($accountName)
{
    $db = new Database(); // Instantiate database object

    // Query to count the number of status updates for the specified account
    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account");
    $db->bind(':account', $accountName); // Bind the account parameter to the query

    return $db->single(); // Return the single count result
}

/**
 * Delete the oldest status updates for a specific account, limited by deleteCount.
 *
 * @param string $accountName The account to delete statuses from.
 * @param int $deleteCount The number of oldest statuses to delete.
 */
function deleteOldStatuses($accountName, $deleteCount)
{
    $db = new Database(); // Instantiate database object

    // Query to delete the oldest status updates for the specified account,
    // ordering by creation date in ascending order and limiting the number of deletions
    $db->query("DELETE FROM status_updates WHERE account = :account ORDER BY created_at ASC LIMIT :deleteCount");
    $db->bind(':account', $accountName); // Bind the account parameter to the query
    $db->bind(':deleteCount', $deleteCount); // Bind the limit parameter to the query

    $db->execute(); // Execute the delete query
}

/**
 * Check if a status has been posted within a specific time frame.
 *
 * @param string $accountName The account name to check.
 * @param string $accountOwner The owner of the account.
 * @param string $currentTimeSlot The reference time to check against.
 * @return bool True if a status has been posted within the time frame, false otherwise.
 */
function hasStatusBeenPosted($accountName, $accountOwner, $currentTimeSlot)
{
    $db = new Database(); // Instantiate database object

    // Calculate the time window of +/- 15 minutes around the current time slot
    $startTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' -15 minutes'));
    $endTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' +15 minutes'));

    // Query to check for existing status updates within the calculated time window
    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :startTime AND :endTime");
    $db->bind(':username', $accountOwner); // Bind the account owner parameter to the query
    $db->bind(':account', $accountName); // Bind the account parameter to the query
    $db->bind(':startTime', $startTime); // Bind the start time parameter to the query
    $db->bind(':endTime', $endTime); // Bind the end time parameter to the query

    $result = $db->single(); // Get the single count result

    return $result->count > 0; // Return true if a status has been posted; false otherwise
}

// API Usage Functions

/**
 * Reset the used API call counts for all users to zero.
 */
function resetAllApiUsage()
{
    $db = new Database(); // Instantiate database object

    // Update all users to reset their used API calls to 0
    $db->query("UPDATE users SET used_api_calls = 0");
    $db->execute(); // Execute the update query
}

/**
 * Update the used API calls count for a specific user.
 *
 * @param string $username The username of the user to update.
 * @param int $usedApiCalls The number of used API calls to set.
 */
function updateUsedApiCalls($username, $usedApiCalls)
{
    $db = new Database(); // Instantiate database object

    // Update the user's used API calls with the provided count
    $db->query("UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username");
    $db->bind(':used_api_calls', $usedApiCalls); // Bind the used API calls parameter to the query
    $db->bind(':username', $username); // Bind the username parameter to the query

    $db->execute(); // Execute the update query
}


/**
 * Update the maximum API calls count for a specific user.
 *
 * @param string $username The username of the user to update.
 * @param int $maxApiCalls The maximum number of API calls to set.
 */
function updateMaxApiCalls($username, $maxApiCalls)
{
    $db = new Database(); // Instantiate database object

    // Update the user's maximum API calls with the provided count
    $db->query("UPDATE users SET max_api_calls = :max_api_calls WHERE username = :username");
    $db->bind(':max_api_calls', $maxApiCalls); // Bind the maximum API calls parameter to the query
    $db->bind(':username', $username); // Bind the username parameter to the query

    $db->execute(); // Execute the update query
}
