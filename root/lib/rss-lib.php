<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../lib/rss-lib.php
 * Description: ChatGPT API Status Generator
 */

function outputRssFeed($accountName, $accountOwner) {
    // Initialize a new database connection
    $db = new Database();
    // Initialize an array to hold statuses
    $statuses = [];
    // Check if the request is for all accounts
    $isAllAccounts = ($accountName === 'all');

    if ($isAllAccounts) {
        // Fetch all accounts associated with the user
        $accounts = getAllUserAccts($accountOwner);

        // Iterate through each account
        foreach ($accounts as $account) {
            $currentAccountName = $account->account;

            // Fetch link information for the current account
            $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $currentAccountName);
            $acctInfo = $db->single();
            $accountLink = $acctInfo->link;

            // Fetch status updates for the current account
            $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
            $db->bind(':accountName', $currentAccountName);
            $db->bind(':accountOwner', $accountOwner);
            $statusInfo = $db->resultSet();

            // Append account link to each status update
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
        // Fetch account link information for the specified account
        $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
        $db->bind(':username', $accountOwner);
        $db->bind(':account', $accountName);
        $acctInfo = $db->single();
        $accountLink = $acctInfo->link;

        // Query to retrieve all status updates for the given account
        $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
        $db->bind(':accountName', $accountName);
        $db->bind(':accountOwner', $accountOwner);
        $statuses = $db->resultSet();

        // Append account link to each status
        foreach ($statuses as $status) {
            $status->accountLink = $accountLink;
        }
    }

    // Set the content type header for RSS XML
    header('Content-Type: application/rss+xml; charset=utf-8');
    // Construct the feed URL
    $rssUrl = DOMAIN . '/feeds.php?user=' . $accountOwner . '&amp;acct=' . ($isAllAccounts ? 'all' : $accountName);

    // Start generating the RSS XML
    echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    echo '<title>' . htmlspecialchars($accountOwner) . ' status feed</title>' . PHP_EOL;
    echo '<link>' . $rssUrl . '</link>' . PHP_EOL;
    echo '<atom:link href="' . $rssUrl . '" rel="self" type="application/rss+xml" /> ' . PHP_EOL;
    echo '<description>Status feed for ' . htmlspecialchars($accountName) . '</description>' . PHP_EOL;
    echo '<language>en-us</language>' . PHP_EOL;

    // Loop through each status and generate the corresponding RSS item
    foreach ($statuses as $status) {
        $enclosureTag = '';
        if (!empty($status->status_image)) {
            // Construct the image URL and file path
            $imageUrl = DOMAIN . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            // Get the file size of the image
            $imageFileSize = filesize($imageFilePath);
            // Create the enclosure tag for the image
            $enclosureTag = '<enclosure url="' . $imageUrl . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
        }

        $description = htmlspecialchars($status->status);
        // Generate the RSS item for the status
        echo '<item>' . PHP_EOL;
        echo '<guid isPermaLink="false">' . md5($status->status) . '</guid>' . PHP_EOL;
        echo '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL;
        echo '<title>' . htmlspecialchars($status->account) . '</title>' . PHP_EOL;
        echo '<link>' . htmlspecialchars($status->accountLink) . '</link>' . PHP_EOL;
        echo '<description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
        echo '<content:encoded><![CDATA[' . $description . ']]></content:encoded>' . PHP_EOL;
        echo $enclosureTag;
        echo '<category>' . htmlspecialchars($status->account) . '</category>' . PHP_EOL;
        echo '</item>' . PHP_EOL;
    }

    // Close the RSS channel and RSS tags
    echo '</channel>' . PHP_EOL;
    echo '</rss>';
}
