<?php
/*
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ../lib/rss-lib.php
 * Description: ChatGPT API Status Generator
 */

function outputRssFeed($accountName, $accountOwner)
{
    $db = new Database();
    // Fetch account link information from the accounts table
    $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
    $db->bind(':username', $accountOwner);
    $db->bind(':account', $accountName);
    $acctInfo = $db->single();

    // Query to retrieve all status updates for the given account
    $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
    $db->bind(':accountName', $accountName);
    $db->bind(':accountOwner', $accountOwner);
    $statusInfo = $db->resultSet();

    header('Content-Type: application/rss+xml; charset=utf-8');
    $rssUrl = DOMAIN . '/feeds.php?user=' . $accountOwner . '&acct=' . $accountName;
    echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    echo '<rss version="2.0"  xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
    echo '<channel>' . PHP_EOL;
    echo '<title>' . htmlspecialchars($accountOwner) . ' status feed</title>' . PHP_EOL;
    echo '<link>' . htmlspecialchars($rssUrl) . '</link>' . PHP_EOL;
    echo '<atom:link href="' . htmlspecialchars($rssUrl) . '" rel="self" type="application/rss+xml" /> ' . PHP_EOL;
    echo '<description>Status feed for ' . htmlspecialchars($accountName) . '</description>' . PHP_EOL;
    echo '<language>en-us</language>' . PHP_EOL;

    foreach ($statusInfo as $status) {
        $rssImageTag = '';
        if (!empty($status->status_image)) {
            $imageUrl = DOMAIN . "/images/{$accountOwner}/{$accountName}/" . htmlspecialchars($status->status_image);
            $rssImageTag = '<img src="' . $imageUrl . '"/>' . PHP_EOL;
        }

        $description = htmlspecialchars($status->status);
        echo '<item>' . PHP_EOL;
        echo '<guid isPermaLink="false">' . md5($status->status) . '</guid>' . PHP_EOL;
        echo '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL;
        echo '<link>' . htmlspecialchars($acctInfo->link) . '</link>' . PHP_EOL;
        echo '<title>' . htmlspecialchars($accountName) . '</title>' . PHP_EOL;
        echo '<description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
        echo '<content:encoded><![CDATA[' . $description .  $rssImageTag . ']]></content:encoded>' . PHP_EOL;
        echo '<category>' . htmlspecialchars($accountName) . '</category>' . PHP_EOL;
        echo '</item>' . PHP_EOL;
    }

    echo '</channel>' . PHP_EOL;
    echo '</rss>';
}
