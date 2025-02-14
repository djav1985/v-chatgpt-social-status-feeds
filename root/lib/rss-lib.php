<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: rss-lib.php
 * Description: Outputs an RSS feed for a user's status updates.
 * License: MIT
 */

/**
 * Outputs an RSS feed for a user's status updates.
 *
 * @param string $accountName   The name of the account for which to generate the feed.
 * @param string $accountOwner  The owner of the account (username) that owns the statuses.
 */
function outputRssFeed(string $accountName, string $accountOwner): void
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
        usort($statuses, function ($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
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

    $rssUrl = DOMAIN . '/feeds.php?user=' . urlencode($accountOwner) . '&amp;acct=' . ($isAllAccounts ? 'all' : urlencode($accountName));

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
            $imageUrl = DOMAIN . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            $imageFilePath = $_SERVER['DOCUMENT_ROOT'] . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);
            $imageFileSize = filesize($imageFilePath);
            $enclosureTag = '<enclosure url="' . $imageUrl . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
        }

        $description = htmlspecialchars($status->status);
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

    echo '</channel>' . PHP_EOL;
    echo '</rss>';
}
