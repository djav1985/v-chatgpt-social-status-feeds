<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: ../lib/rss-lib.php
 * Description: ChatGPT API Status Generator
 */

/**
 * Outputs an RSS feed for a user's status updates.
 *
 * @param string $accountName   The name of the account for which to generate the feed.
 * @param string $accountOwner  The owner of the account (username) that owns the statuses.
 */
function outputRssFeed($accountName, $accountOwner)
{
    // Create a new instance of the Database class to interact with the database
    $db = new Database();

    // Initialize an array to hold status updates
    $statuses = [];

    // Determine if the request is for all accounts or a specific one
    $isAllAccounts = ($accountName === 'all');

    if ($isAllAccounts) {
        // Fetch all accounts associated with the account owner
        $accounts = getAllUserAccts($accountOwner);

        // Iterate through each account to fetch their statuses
        foreach ($accounts as $account) {
            $currentAccountName = $account->account;

            // Query to retrieve link information for the current account
            $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $currentAccountName);
            $acctInfo = $db->single();
            $accountLink = $acctInfo->link;

            // Query to fetch statuses for the current account
            $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
            $db->bind(':accountName', $currentAccountName);
            $db->bind(':accountOwner', $accountOwner);
            $statusInfo = $db->resultSet();

            // Append account link to each fetched status and store it in the statuses array
            foreach ($statusInfo as $status) {
                $status->accountLink = $accountLink;
                $statuses[] = $status;
            }
        }

        // Sort statuses by creation date in descending order
        usort($statuses, function ($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
    } else {
        // For a specific account, retrieve its link information
        $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
        $db->bind(':username', $accountOwner);
        $db->bind(':account', $accountName);
        $acctInfo = $db->single();
        $accountLink = $acctInfo->link;

        // Query to retrieve all status updates for the specified account
        $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
        $db->bind(':accountName', $accountName);
        $db->bind(':accountOwner', $accountOwner);
        $statuses = $db->resultSet();

        // Append the account link to each status
        foreach ($statuses as $status) {
            $status->accountLink = $accountLink;
        }
    }

    // Set the content type for the output to be RSS XML
    header('Content-Type: application/rss+xml; charset=utf-8');

    // Construct the RSS feed URL
    $rssUrl = DOMAIN . '/feeds.php?user=' . $accountOwner . '&amp;acct=' . ($isAllAccounts ? 'all' : $accountName);

    // Output the beginning of the RSS feed with XML declaration
    echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    echo '<title>' . htmlspecialchars($accountOwner) . ' status feed</title>' . PHP_EOL;
    echo '<link>' . $rssUrl . '</link>' . PHP_EOL;
    echo '<atom:link href="' . $rssUrl . '" rel="self" type="application/rss+xml" /> ' . PHP_EOL;
    echo '<description>Status feed for ' . htmlspecialchars($accountName) . '</description>' . PHP_EOL;
    echo '<language>en-us</language>' . PHP_EOL;

    // Loop through each status and build the corresponding RSS item
    foreach ($statuses as $status) {
        $enclosureTag = '';

        // If there is an image associated with the status, prepare the enclosure tag
        if (!empty($status->status_image)) {
            $imageUrl = DOMAIN . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            $imageFileSize = filesize($imageFilePath);
            $enclosureTag = '<enclosure url="' . $imageUrl . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
        }

        // Escape the status description for safe XML output
        $description = htmlspecialchars($status->status);
        echo '<item>' . PHP_EOL;
        echo '<guid isPermaLink="false">' . md5($status->status) . '</guid>' . PHP_EOL; // Generate a unique identifier for the status
        echo '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL; // Publish date
        echo '<title>' . htmlspecialchars($status->account) . '</title>' . PHP_EOL; // Title of the status
        echo '<link>' . htmlspecialchars($status->accountLink) . '</link>' . PHP_EOL; // Link to the account
        echo '<description><![CDATA[' . $description . ']]></description>' . PHP_EOL; // Description as CDATA
        echo '<content:encoded><![CDATA[' . $description . ']]></content:encoded>' . PHP_EOL; // Encoded content
        echo $enclosureTag; // Include the enclosure tag if applicable
        echo '<category>' . htmlspecialchars($status->account) . '</category>' . PHP_EOL; // Category based on account name
        echo '</item>' . PHP_EOL; // End of the item
    }

    echo '</channel>' . PHP_EOL; // Close the channel
    echo '</rss>'; // Close the RSS feed
}
