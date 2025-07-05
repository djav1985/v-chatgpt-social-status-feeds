<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: UtilityHandler.php
 * Description: Handles IP blacklist operations such as updating failed attempts, checking blacklist status, and clearing the blacklist.
 * License: MIT
 */

class UtilityHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Update the failed login attempts for an IP address.
     *
     * @param string $ip
     * @return void
     */
    public static function updateFailedAttempts(string $ip): void
    {
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if ($result) {
                $attempts = $result->login_attempts + 1;
                // Use MAX_LOGIN_ATTEMPTS from config.php
                $is_blacklisted = ($attempts >= (defined('MAX_LOGIN_ATTEMPTS') ? MAX_LOGIN_ATTEMPTS : 3));
                $timestamp = ($is_blacklisted) ? time() : $result->timestamp;
                $db->query("UPDATE ip_blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :timestamp WHERE ip_address = :ip");
                $db->bind(':attempts', $attempts);
                $db->bind(':blacklisted', $is_blacklisted);
                $db->bind(':timestamp', $timestamp);
                $db->bind(':ip', $ip);
            } else {
                $db->query("INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, FALSE, :timestamp)");
                $db->bind(':ip', $ip);
                $db->bind(':timestamp', time());
            }
            $db->execute();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error updating failed attempts: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if an IP address is blacklisted.
     *
     * @param string $ip
     * @return bool
     */
    public static function isBlacklisted(string $ip): bool
    {
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if ($result) {
                // Use IP_BLACKLIST_DURATION_SECONDS from config.php
                $blacklistDuration = defined('IP_BLACKLIST_DURATION_SECONDS') ? IP_BLACKLIST_DURATION_SECONDS : (3 * 24 * 60 * 60);
                if (time() - $result->timestamp > $blacklistDuration) {
                    $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
                    $db->bind(':ip', $ip);
                    $db->execute();
                    return false;
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error checking blacklist status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Clear the IP blacklist.
     * This function clears the IP blacklist, removing entries older than 3 days.
     *
     * @return bool True on success, false on failure
     */
    public static function clearIpBlacklist(): bool
    {
        try {
            $db = new DatabaseHandler();
            // Use IP_BLACKLIST_DURATION_SECONDS from config.php
            $blacklistDuration = defined('IP_BLACKLIST_DURATION_SECONDS') ? IP_BLACKLIST_DURATION_SECONDS : (3 * 24 * 60 * 60);
            $olderThanTimestamp = time() - $blacklistDuration;
            $db->query("DELETE FROM ip_blacklist WHERE timestamp < :olderThanTimestamp AND blacklisted = FALSE"); // Only clear non-blacklisted old entries, or all old entries?
                                                                                              // Assuming we want to clear entries that are old AND no longer actively blacklisted,
                                                                                              // or simply all entries older than the duration.
                                                                                              // The original logic deleted any entry older than 3 days.
                                                                                              // Let's stick to deleting any entry older than the duration.
            $db->query("DELETE FROM ip_blacklist WHERE timestamp < :olderThanTimestamp");
            $db->bind(':olderThanTimestamp', $olderThanTimestamp);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error clearing IP blacklist: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Display and clear session messages.
     *
     * @return void
     */
    public static function displayAndClearMessages(): void
    {
        if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
            foreach ($_SESSION['messages'] as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            unset($_SESSION['messages']);
        }
    }

    /**
     * Outputs an RSS feed for a user's status updates.
     *
     * @param string $accountName  The account name for which to generate the feed.
     * @param string $accountOwner The username that owns the statuses.
     *
     * @return void
     */
    public static function outputRssFeed(string $accountName, string $accountOwner): void
    {
        // Sanitize input to prevent XSS attacks
        $accountName = htmlspecialchars(strip_tags($accountName));
        $accountOwner = htmlspecialchars(strip_tags($accountOwner));

        $statuses = [];
        $isAllAccounts = ($accountName === 'all');

        // Fetch statuses for all accounts if 'all' is specified
        if ($isAllAccounts) {
            $accounts = AccountHandler::getAllUserAccts($accountOwner);

            foreach ($accounts as $account) {
                $currentAccountName = htmlspecialchars($account->account);

                // Retrieve account link
                $accountLink = AccountHandler::getAccountLink($accountOwner, $currentAccountName);

                // Retrieve status updates for the account
                $statusInfo = StatusHandler::getStatusUpdates($accountOwner, $currentAccountName);

                foreach ($statusInfo as $status) {
                    $status->accountLink = $accountLink;
                    $statuses[] = $status;
                }
            }

            // Sort statuses by creation date in descending order
            usort(
                $statuses,
                function (object $a, object $b): int {
                    return strtotime($b->created_at) - strtotime($a->created_at);
                }
            );
        } else {
            // Retrieve account link
            $accountLink = AccountHandler::getAccountLink($accountOwner, $accountName);

            // Retrieve status updates for the account
            $statuses = StatusHandler::getStatusUpdates($accountOwner, $accountName);

            foreach ($statuses as $status) {
                $status->accountLink = $accountLink;
            }
        }

        // Set the content type to RSS XML
        header('Content-Type: application/rss+xml; charset=utf-8');

        $rssUrl = DOMAIN . '/feeds/' . urlencode($accountOwner) . '/' . ($isAllAccounts ? 'all' : urlencode($accountName));

        // Output RSS feed
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<rss version="2.0" xmlns:atom="https://www.w3.org/2005/Atom" xmlns:content="https://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
        echo '<channel>' . PHP_EOL;
        echo '<title>' . htmlspecialchars($accountOwner) . ' status feed</title>' . PHP_EOL;
        echo '<link>' . $rssUrl . '</link>' . PHP_EOL;
        echo '<atom:link href="' . $rssUrl . '" rel="self" type="application/rss+xml" /> ' . PHP_EOL;
        echo '<description>Status feed for ' . htmlspecialchars($accountName) . '</description>' . PHP_EOL;
        echo '<language>en-us</language>' . PHP_EOL;

        // Output each status as an RSS item
        foreach ($statuses as $status) {
            $enclosureTag = '';

            // Include image enclosure if available
            if (!empty($status->status_image)) {
                // Use rawurlencode for URL path segments
                $encodedAccountOwner = rawurlencode($accountOwner); // $accountOwner is already htmlspecialchars'd earlier, but rawurlencode is for path
                $encodedStatusAccount = rawurlencode($status->account); // $status->account comes from DB, assumed safe, but encode for URL path
                $encodedStatusImage = rawurlencode($status->status_image); // $status->status_image is filename, should be safe, encode for consistency

                $imageUrl = DOMAIN . "/images/" . $encodedAccountOwner . "/" . $encodedStatusAccount . "/" . $encodedStatusImage;

                // For file_exists, use raw values as htmlspecialchars might alter them if they contained & etc.
                // However, $accountOwner was htmlspecialchar'd at the start of the function.
                // $status->account and $status->status_image are direct from DB.
                // It's safer to use the original values for filesystem paths if they are guaranteed safe for paths.
                // Given $accountOwner is htmlspecialchars'd at the start, and others are from DB (likely clean),
                // using them directly for file path construction is okay, but be mindful.
                // The variables $encodedAccountOwner etc. are for the URL.
                $imageFilePath = __DIR__ . '/../public/images/' . $accountOwner . '/' . $status->account . '/' . $status->status_image;
                $imageFileSize = file_exists($imageFilePath) ? filesize($imageFilePath) : 0;
                $enclosureTag = '<enclosure url="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
            }

            $description = htmlspecialchars($status->status);
            echo '<item>' . PHP_EOL;
            echo '<guid isPermaLink="false">' . md5($status->status) . '</guid>' . PHP_EOL;
            echo '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL;
            echo '<title>' . htmlspecialchars($status->account) . '</title>' . PHP_EOL;
            echo '<link>' . htmlspecialchars($status->accountLink) . '</link>' . PHP_EOL;
            // Output htmlspecialchars-encoded description directly. CDATA is not necessary here.
            echo '<description>' . $description . '</description>' . PHP_EOL;
            echo '<content:encoded>' . $description . '</content:encoded>' . PHP_EOL;
            echo $enclosureTag;
            echo '<category>' . htmlspecialchars($status->account) . '</category>' . PHP_EOL;
            echo '</item>' . PHP_EOL;
        }

        echo '</channel>' . PHP_EOL;
        echo '</rss>';
    }
}
