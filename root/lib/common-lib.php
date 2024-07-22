<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: /lib/common.php
 * Description: ChatGPT API Status Generator
 */

// Utility Functions
function sanitize_input($data)
{
    // Trim whitespace and remove HTML tags
    $data = trim(strip_tags($data));

    // Filter the input using appropriate filters
    $data = filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

    // Add additional security measures
    $data = str_replace(array("<?", "?>", "<%", "%>"), "", $data);
    $data = str_replace(array("<script", "</script"), "", $data);
    $data = str_replace(array("/bin/sh", "exec(", "system(", "passthru(", "shell_exec(", "phpinfo("), "", $data);

    return $data;
}

function contains_disallowed_chars($str)
{
    global $disallowed_chars;
    foreach ($disallowed_chars as $char) {
        if (strpos($str, $char) !== false) {
            return true;
        }
    }
    return false;
}

function contains_disallowed_patterns($str)
{
    global $disallowed_patterns;
    foreach ($disallowed_patterns as $pattern) {
        if (strpos($str, $pattern) !== false) {
            return true;
        }
    }
    return false;
}

// Session and Message Handling Functions
function display_and_clear_messages()
{
    if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
        echo '<div class="messages">';
        foreach ($_SESSION['messages'] as $message) {
            echo '<p>' . htmlspecialchars($message) . '</p>';
        }
        echo '</div>';
        // Clear messages after displaying
        unset($_SESSION['messages']);
    }
}

// IP Blacklist Management Functions
function update_failed_attempts($ip)
{
    $db = new Database();

    // Check if the IP already exists in the database
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        // IP exists, update login attempts and check for blacklisting
        $attempts = $result['login_attempts'] + 1;
        $is_blacklisted = ($attempts >= 3) ? true : false;
        $timestamp = ($is_blacklisted) ? time() : $result['timestamp'];

        $db->query("UPDATE ip_blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :timestamp WHERE ip_address = :ip");
        $db->bind(':attempts', $attempts);
        $db->bind(':blacklisted', $is_blacklisted);
        $db->bind(':timestamp', $timestamp);
    } else {
        // IP does not exist, insert new entry
        $db->query("INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, FALSE, :timestamp)");
        $db->bind(':ip', $ip);
        $db->bind(':timestamp', time());
    }
    $db->execute();
}

function is_blacklisted($ip)
{
    $db = new Database();
    $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
    $db->bind(':ip', $ip);
    $result = $db->single();

    if ($result) {
        // Check if the blacklist timestamp is older than three days
        if (time() - $result['timestamp'] > (3 * 24 * 60 * 60)) {
            // Update to remove the IP from the blacklist
            $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
            $db->bind(':ip', $ip);
            $db->execute();
            return false;
        }
        return true;
    }
    return false;
}

function clearIpBlacklist()
{
    $db = new Database();
    $db->query("DELETE FROM ip_blacklist"); // Delete all entries from the IP blacklist
    $db->execute();
}

// User Information Functions
function getUserInfo($username)
{
    $db = new Database();
    $db->query("SELECT * FROM users WHERE username = :username");
    $db->bind(':username', $username);
    return $db->single();
}

function getAllUsers()
{
    $db = new Database();
    $db->query("SELECT * FROM users");
    return $db->resultSet();  // Returns an array of user objects
}

// Account Information Functions
function getAcctInfo($username, $account)
{
    $db = new Database();
    $db->query("SELECT * FROM accounts WHERE username = :username AND account = :account");
    $db->bind(':username', $username);
    $db->bind(':account', $account);
    return $db->single();
}

function getAllUserAccts($username)
{
    $db = new Database();
    $db->query("SELECT account FROM accounts WHERE username = :username");
    $db->bind(':username', $username);
    return $db->resultSet();  // Returns an array of objects where each object contains account information
}

function getAllAccounts()
{
    $db = new Database();
    $db->query("SELECT * FROM accounts"); // Select all accounts
    return $db->resultSet(); // Return the result set
}

// Status Information Functions
function getStatusInfo($username, $account)
{
    $db = new Database();
    $db->query("SELECT * FROM status_updates WHERE username = :username AND account = :account ORDER BY created_at DESC");
    $db->bind(':username', $username);
    $db->bind(':account', $account);
    return $db->resultSet(); // Return the result set directly
}

function countStatuses($accountName)
{
    $db = new Database();
    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account");
    $db->bind(':account', $accountName);
    return $db->single();
}

function deleteOldStatuses($accountName, $deleteCount)
{
    $db = new Database();
    $db->query("DELETE FROM status_updates WHERE account = :account ORDER BY created_at ASC LIMIT :deleteCount");
    $db->bind(':account', $accountName);
    $db->bind(':deleteCount', $deleteCount);
    $db->execute();
}

function hasStatusBeenPosted($accountName, $accountOwner, $currentTimeSlot)
{
    $db = new Database();
    // Calculate time window +/- 15 minutes
    $startTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' -15 minutes'));
    $endTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' +15 minutes'));

    // Query to check for existing status updates within the time window
    $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :startTime AND :endTime");
    $db->bind(':username', $accountOwner);
    $db->bind(':account', $accountName);
    $db->bind(':startTime', $startTime);
    $db->bind(':endTime', $endTime);
    $result = $db->single();

    return $result->count > 0; // Return true if a status has been posted, false otherwise
}

// API Usage Functions
function resetAllApiUsage()
{
    $db = new Database();
    $db->query("UPDATE users SET used_api_calls = 0"); // Reset used API calls to 0
    $db->execute();
}

function updateUsedApiCalls($username, $usedApiCalls)
{
    $db = new Database();
    $db->query("UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username");
    $db->bind(':used_api_calls', $usedApiCalls);
    $db->bind(':username', $username);
    $db->execute();
}
