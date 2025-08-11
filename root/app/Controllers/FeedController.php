<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: FeedController.php
 * Description: AI Social Status Generator
 */

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Account;
use App\Models\Feed;
use App\Core\ErrorHandler;

class FeedController extends Controller
{
    /**
     * Entry point for RSS feed display for the given account.
     *
     * @param string $user    Account owner username
     * @param string $account Account name or "all"
     * @return void
     */
    public function index(string $user, string $account): void
    {
        try {
            self::outputRssFeed($account, $user);
        } catch (\Exception $e) {
            ErrorHandler::getInstance()->log('RSS feed generation failed: ' . $e->getMessage(), 'error');
            echo 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Validate parameters and output the requested feed.
     *
     * @param string|null $user    Owner username
     * @param string|null $account Account name or "all"
     * @return void
     */
    public function handleRequest(?string $user = null, ?string $account = null): void
    {
        if (!$user || !$account) {
            http_response_code(400);
            echo 'Bad Request: Missing user or account parameter.';
            return;
        }

        $this->index($user, $account);
    }


    /**
     * Output an RSS feed for a specific account or all accounts.
     *
     * @param string $accountName  Account name or "all"
     * @param string $accountOwner Username owning the account(s)
     * @return void
     */
    private static function outputRssFeed(string $accountName, string $accountOwner): void
    {
        // Sanitize input to prevent XSS attacks
        $accountName = htmlspecialchars(strip_tags($accountName));
        $accountOwner = htmlspecialchars(strip_tags($accountOwner));

        $statuses = [];
        $isAllAccounts = ($accountName === 'all');

        // Fetch statuses for all accounts if 'all' is specified
        if ($isAllAccounts) {
            $accounts = Account::getAllUserAccts($accountOwner);

            foreach ($accounts as $account) {
                $currentAccountName = htmlspecialchars($account->account);

                // Retrieve account link
                $accountLink = Account::getAccountLink($accountOwner, $currentAccountName);

                // Retrieve status updates for the account
                $statusInfo = Feed::getStatusUpdates($accountOwner, $currentAccountName);

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
            $accountLink = Account::getAccountLink($accountOwner, $accountName);

            // Retrieve status updates for the account
            $statuses = Feed::getStatusUpdates($accountOwner, $accountName);

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
                $imageUrl = DOMAIN . "/images/" . htmlspecialchars($accountOwner) . "/" . htmlspecialchars($status->account) . "/" . htmlspecialchars($status->status_image);

                // Images are stored under root/public/images/<owner>/<account>/
                $pathOwner   = basename($accountOwner);
                $pathAccount = basename($status->account);
                $pathImage   = basename($status->status_image);

                $imageFilePath = __DIR__ . '/../../public/images/' . $pathOwner . '/' . $pathAccount . '/' . $pathImage;
                $imageFileSize = file_exists($imageFilePath) ? filesize($imageFilePath) : 0;
                $enclosureTag  = '<enclosure url="' . $imageUrl . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
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
}
