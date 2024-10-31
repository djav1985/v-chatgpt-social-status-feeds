<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../lib/common.php
 * Description: ChatGPT API Status Generator
 */

// --- Utility Functions ---

/**
 * Sanitize user input to prevent security vulnerabilities.
 *
 * @param string $data The user input data to sanitize.
 * @return string The sanitized input data.
 */
function sanitize_input($data)
{
    $data = trim(strip_tags($data));
    $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    $data = str_replace(array("<?", "?>", "<%", "%>"), "", $data);
    $data = str_replace(array("<script", "</script"), "", $data);
    $data = str_replace(array("/bin/sh", "exec(", "system(", "passthru(", "shell_exec(", "phpinfo("), "", $data);
    return $data;
}

/**
 * Check if a string contains any disallowed characters.
 *
 * @param string $str The string to check.
 * @return bool True if disallowed characters are present, false otherwise.
 */
function contains_disallowed_chars($str)
{
    global $disallowed_chars;
    foreach ($disallowed_chars as $char) {
        if (strpos($str, $char) !== false) return true;
    }
    return false;
}

/**
 * Check if a string contains any disallowed patterns.
 *
 * @param string $str The string to check.
 * @return bool True if disallowed patterns are present, false otherwise.
 */
function contains_disallowed_patterns($str)
{
    global $disallowed_patterns;
    foreach ($disallowed_patterns as $pattern) {
        if (strpos($str, $pattern) !== false) return true;
    }
    return false;
}

// --- Session and Message Handling ---

/**
 * Display messages stored in the session and clear them afterwards.
 */
function display_and_clear_messages()
{
    if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
        echo '<div class="messages">';
        foreach ($_SESSION['messages'] as $message) {
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
        echo '</div>';
        unset($_SESSION['messages']);
    }
}

// --- IP Blacklist Management ---

/**
 * Update login attempts for an IP address and handle blacklisting if necessary.
 *
 * @param string $ip The IP address to update.
 */
function update_failed_attempts($ip)
{
    $db = new Database();
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        $attempts = $result['login_attempts'] + 1;
        $is_blacklisted = ($attempts >= 3) ? true : false;
        $timestamp = ($is_blacklisted) ? time() : $result['timestamp'];
        $db->query("UPDATE ip_blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :timestamp WHERE ip_address = :ip");
        $db->bind(':attempts', $attempts);
        $db->bind(':blacklisted', $is_blacklisted);
        $db->bind(':timestamp', $timestamp);
    } else {
        $db->query("INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, FALSE, :timestamp)");
        $db->bind(':ip', $ip);
        $db->bind(':timestamp', time());
    }
    $db->execute();
}

/**
 * Check if an IP address is blacklisted.
 *
 * @param string $ip The IP address to check.
 * @return bool True if the IP is blacklisted, false otherwise.
 */
function is_blacklisted($ip)
{
    $db = new Database();
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        if (time() - $result['timestamp'] > (3 * 24 * 60 * 60)) {
            $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
            $db->bind(':ip', $ip);
            $db->execute();
            return false;
        }
        return true;
    }
    return false;
}

/**
 * Clear all entries from the IP blacklist.
 */
function clearIpBlacklist()
{
    $db = new Database();
    $db->query("DELETE FROM ip_blacklist");
    $db->execute();
}

// --- User Information Management ---

/**
 * Retrieve user information based on username.
 *
 * @param string $username The username to look up.
 * @return object The user information object.
 */
function getUserInfo($username)
{
    $db = new Database();
    $db->query("SELECT * FROM users WHERE username = :username");
    $db->bind(':username', $username);
    return $db->single();
}

/**
 * Retrieve all users from the database.
 *
 * @return array An array of user objects.
 */
function getAllUsers()
{
    $db = new Database();
    $db->query("SELECT * FROM users");
    return $db->resultSet();
}

// --- Account Information Management ---

/**
 * Get account information for a specific user and account.
 *
 * @param string $username The username to look up.
 * @param string $account The account name to look up.
 * @return object The account information object.
 */
function getAcctInfo($username, $account)
{
    $db = new Database();
    $db->query("SELECT * FROM accounts WHERE username = :username AND account = :account");
    $db->bind(':username', $username);
    $db->bind(':account', $account);
    return $db->single();
}

/**
 * Retrieve all accounts associated with a specific user.
 *
 * @param string $username The username to look up.
 * @return array An array of account objects.
 */
function getAllUserAccts($username)
{
    $db = new Database();
    $db->query("SELECT account FROM accounts WHERE username = :username");
    $db->bind(':username', $username);
    return $db->resultSet();
}

/**
 * Retrieve all accounts from the database.
 *
 * @return array An array of account objects.
 */
function getAllAccounts()
{
    $db = new Database();
    $db->query("SELECT * FROM accounts");
    return $db->resultSet();
}

// --- Status Information Management ---

/**
 * Retrieve status updates for a specific user and account, ordered by created date.
 *
 * @param string $username The username to look up.
 * @param string $account The account name to look up.
 * @return array An array of status updates.
 */
function getStatusInfo($username, $account)
{
    $db = new Database();
    $db->query("SELECT * FROM status_updates WHERE username = :username AND account = :account ORDER BY created_at DESC");
    $db->bind(':username', $username);
    $db->bind(':account', $account);
    return $db->resultSet();
}

/**
 * Count the number of status updates for a specific account.
 *
 * @param string $accountName The account name to check.
 * @return int The count of status updates associated with the account.
 */
function countStatuses($accountName)
{
    $db = new Database();
    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account");
    $db->bind(':account', $accountName);
    return $db->single();
}

/**
 * Delete the oldest status updates for a specific account, limited by deleteCount.
 *
 * @param string $accountName The account to delete statuses from.
 * @param int $deleteCount The number of oldest statuses to delete.
 */
function deleteOldStatuses($accountName, $deleteCount)
{
    $db = new Database();
    $db->query("DELETE FROM status_updates WHERE account = :account ORDER BY created_at ASC LIMIT :deleteCount");
    $db->bind(':account', $accountName);
    $db->bind(':deleteCount', $deleteCount);
    $db->execute();
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
    $db = new Database();
    $startTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' -15 minutes'));
    $endTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' +15 minutes'));

    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :startTime AND :endTime");
    $db->bind(':username', $accountOwner);
    $db->bind(':account', $accountName);
    $db->bind(':startTime', $startTime);
    $db->bind(':endTime', $endTime);

    $result = $db->single();
    return $result->count > 0;
}

/**
 * Save a status update to the database.
 *
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @param string $status_content The content of the status update.
 * @param string $image_name The name of the associated image file.
 */
function saveStatus($accountName, $accountOwner, $status_content, $image_name)
{
    $db = new Database(); // Instantiate the database object.

    // SQL query to insert a new status update.
    $sql = "INSERT INTO status_updates (username, account, status, created_at, status_image) VALUES (:username, :account, :status, NOW(), :status_image)";
    $db->query($sql);

    // Bind parameters to the query for secure execution.
    $db->bind(':username', $accountOwner);
    $db->bind(':account', $accountName);
    $db->bind(':status', $status_content);
    $db->bind(':status_image', $image_name);

    // Execute the query to save the status update to the database.
    $db->execute();
}


// --- API Usage Tracking ---

/**
 * Reset the used API call counts for all users and clear token usage, retries, and costs in the logs table.
 */
function resetAllApiUsage()
{
    $db = new Database(); // Instantiate database object.

    // Reset used API calls for all users to zero
    $db->query("UPDATE users SET used_api_calls = 0");
    $db->execute();

    // Reset token usage, retries, and cost for all accounts in the logs table
    $db->query("UPDATE logs SET input_tokens = 0, output_tokens = 0, image_retries = 0, cost = 0.00000000");
    $db->execute();
}

/**
 * Update the used API calls count for a specific user.
 *
 * @param string $username The username of the user.
 * @param int $usedApiCalls The number of API calls to set.
 */
function updateUsedApiCalls($username, $usedApiCalls)
{
    $db = new Database(); // Instantiate database object.
    $db->query("UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username");
    $db->bind(':used_api_calls', $usedApiCalls);
    $db->bind(':username', $username);
    $db->execute(); // Execute the update.
}

/**
 * Update the maximum API calls allowed for a specific user.
 *
 * @param string $username The username of the user.
 * @param int $maxApiCalls The maximum number of API calls allowed.
 */
function updateMaxApiCalls($username, $maxApiCalls)
{
    $db = new Database(); // Instantiate database object.
    $db->query("UPDATE users SET max_api_calls = :max_api_calls WHERE username = :username");
    $db->bind(':max_api_calls', $maxApiCalls);
    $db->bind(':username', $username);
    $db->execute(); // Execute the update.
}

/**
 * Update token counts and cost for a given account.
 *
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 * @param int $prompt_tokens Number of prompt tokens.
 * @param int $completion_tokens Number of completion tokens.
 */
function updateTokens($accountName, $accountOwner, $prompt_tokens, $completion_tokens)
{
    $db = new Database(); // Instantiate database object.
    $cost = number_format((0.00000015 * $prompt_tokens) + (0.0000006 * $completion_tokens), 8, '.', ''); // Calculate cost.

    // SQL statement for inserting or updating the log with token and cost details.
    $upsert_sql = "
        INSERT INTO logs (account, username, input_tokens, output_tokens, cost)
        VALUES (:account, :username, :prompt_tokens, :completion_tokens, :cost)
        ON DUPLICATE KEY UPDATE
            input_tokens = input_tokens + VALUES(input_tokens),
            output_tokens = output_tokens + VALUES(output_tokens),
            cost = cost + VALUES(cost)
    ";

    $db->query($upsert_sql);
    $db->bind(':account', $accountName);
    $db->bind(':username', $accountOwner);
    $db->bind(':prompt_tokens', $prompt_tokens ?? 0);
    $db->bind(':completion_tokens', $completion_tokens ?? 0);
    $db->bind(':cost', $cost);
    $db->execute(); // Execute the upsert.
}

/**
 * Increment cost by a fixed amount for a specified account.
 * Inserts a row if the account and username do not exist.
 *
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 */
function updateCost($accountName, $accountOwner)
{
    $db = new Database(); // Instantiate database object.

    // Upsert query to increment cost or insert a new row if it doesn't exist
    $upsert_sql = "
        INSERT INTO logs (account, username, cost)
        VALUES (:account, :username, 0.080)
        ON DUPLICATE KEY UPDATE
        cost = cost + 0.080
    ";
    $db->query($upsert_sql);
    $db->bind(':account', $accountName);
    $db->bind(':username', $accountOwner);
    $db->execute(); // Execute the upsert.
}

/**
 * Increment image retries for an account.
 * Inserts a row if the account and username do not exist.
 *
 * @param string $accountName The name of the account.
 * @param string $accountOwner The owner of the account.
 */
function updateImageRetries($accountName, $accountOwner)
{
    $db = new Database(); // Instantiate database object.

    // Upsert query to increment image_retries or insert a new row if it doesn't exist
    $upsert_sql = "
        INSERT INTO logs (account, username, image_retries)
        VALUES (:account, :username, 1)
        ON DUPLICATE KEY UPDATE
        image_retries = image_retries + 1
    ";
    $db->query($upsert_sql);
    $db->bind(':account', $accountName);
    $db->bind(':username', $accountOwner);
    $db->execute(); // Execute the upsert.
}

/**
 * Retrieve the total cost incurred by a specific user.
 *
 * @param string $accountOwner The username of the account owner.
 * @return float The total cost incurred.
 */
function getTotalCostByUsername($accountOwner)
{
    $db = new Database(); // Instantiate database object.
    $query = "SELECT SUM(cost) as total_cost FROM logs WHERE username = :username";
    $db->query($query);
    $db->bind(':username', $accountOwner);
    $result = $db->single();
    return $result->total_cost;
}

// --- External API Communication ---

/**
 * Execute an API request to the specified endpoint with provided data.
 *
 * @param string $endpoint The API endpoint URL.
 * @param array $data The data to be sent in the request.
 * @return string The API response.
 */
function executeApiRequest($endpoint, $data)
{
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . API_KEY,
    ];

    // Initialize cURL session for the API request.
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch); // Close cURL session.

    return $response; // Return the API response.
}
