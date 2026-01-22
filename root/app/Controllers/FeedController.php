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
use App\Models\Status;
use App\Core\ErrorManager;

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
            ErrorManager::getInstance()->log('RSS feed generation failed: ' . $e->getMessage(), 'error');
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
    protected static function outputRssFeed(string $accountName, string $accountOwner): void
    {
        // Sanitize input to prevent XSS attacks while preserving characters for lookups
        $accountName = trim(strip_tags($accountName));
        $accountOwner = trim(strip_tags($accountOwner));

        $statuses = [];
        $isAllAccounts = ($accountName === 'all');

        // Fetch statuses for all accounts if 'all' is specified
        if ($isAllAccounts) {
            $accounts = static::getAllAccountsForOwner($accountOwner);

            foreach ($accounts as $account) {
                $account = (object)$account;
                if (!isset($account->account)) {
                    continue;
                }

                $currentAccountName = (string)$account->account;

                // Retrieve account link
                $accountLink = static::getAccountLinkForOwner($accountOwner, $currentAccountName);

                // Retrieve status updates for the account
                $statusInfo = static::getStatusUpdatesForAccount($accountOwner, $currentAccountName);

                foreach ($statusInfo as $status) {
                    $status = (object)$status;
                    if (!isset($status->account)) {
                        $status->account = $currentAccountName;
                    }
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
            $accountLink = static::getAccountLinkForOwner($accountOwner, $accountName);

            // Retrieve status updates for the account
            $statuses = static::getStatusUpdatesForAccount($accountOwner, $accountName);

            foreach ($statuses as &$status) {
                $status = (object)$status;
                if (!isset($status->account)) {
                    $status->account = $accountName;
                }
                $status->accountLink = $accountLink;
            }
            unset($status);
        }

        // Set the content type to RSS XML
        // Force correct content type + charset
        header('Content-Type: application/rss+xml; charset=UTF-8');
        
        // Kill caching (IFTTT poison-cache prevention)
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Disable compression at PHP level
        ini_set('zlib.output_compression', 'Off');
        
        // Defensive: clear any existing buffer content without discarding the active buffer
        if (ob_get_level() > 0) {
            ob_clean();
        }

        $rssUrl = DOMAIN . '/feeds/' . urlencode($accountOwner) . '/' . ($isAllAccounts ? 'all' : urlencode($accountName));
        $escapedRssUrl = htmlspecialchars($rssUrl, ENT_QUOTES, 'UTF-8');
        $escapedAccountOwner = htmlspecialchars($accountOwner, ENT_QUOTES, 'UTF-8');
        $escapedAccountName = htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8');

        // Output RSS feed
        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<rss version="2.0" xmlns:atom="https://www.w3.org/2005/Atom" xmlns:content="https://purl.org/rss/1.0/modules/content/">' . PHP_EOL;
        
        echo '<channel>' . PHP_EOL;
        echo '<title>' . $escapedAccountOwner . ' status feed</title>' . PHP_EOL;
        
        echo '<link>' . $escapedRssUrl . '</link>' . PHP_EOL;
        echo '<atom:link href="' . $escapedRssUrl . '" rel="self" type="application/rss+xml" />' . PHP_EOL;
        
        echo '<description>Status feed for ' . $escapedAccountName . '</description>' . PHP_EOL;
        echo '<language>en-us</language>' . PHP_EOL;
        
        // Strongly recommended for IFTTT stability
        echo '<generator>Vontainment Feed Engine</generator>' . PHP_EOL;
        echo '<lastBuildDate>' . gmdate(DATE_RSS) . '</lastBuildDate>' . PHP_EOL;
        echo '<ttl>10</ttl>' . PHP_EOL;

        // Output each status as an RSS item
        foreach ($statuses as $status) {
            $status = (object)$status;
            $enclosureTag = '';

            $escapedStatusAccount = htmlspecialchars((string)($status->account ?? ''), ENT_QUOTES, 'UTF-8');
            $escapedAccountLink = htmlspecialchars((string)($status->accountLink ?? ''), ENT_QUOTES, 'UTF-8');

            // Include image enclosure if available
            if (!empty($status->status_image)) {
                $imageUrl = DOMAIN . '/images/' . $accountOwner . '/' . (string)$status->account . '/' . (string)$status->status_image;

                // Images are stored under root/public/images/<owner>/<account>/
                $pathOwner   = basename($accountOwner);
                $pathAccount = basename($status->account);
                $pathImage   = basename($status->status_image);

                $imageFilePath = __DIR__ . '/../../public/images/' . $pathOwner . '/' . $pathAccount . '/' . $pathImage;
                $imageFileSize = file_exists($imageFilePath) ? filesize($imageFilePath) : 0;
                $enclosureTag  = '<enclosure url="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
            }

            $description = htmlspecialchars((string)$status->status, ENT_QUOTES, 'UTF-8');
            echo '<item>' . PHP_EOL;
            $guidSource = isset($status->id)
                ? 'status:' . (string)$status->id
                : ($status->account ?? '') . '|' . ($status->created_at ?? '') . '|' . ($status->status ?? '');
            $escapedGuid = htmlspecialchars((string)$guidSource, ENT_QUOTES, 'UTF-8');

            echo '<guid isPermaLink="false">' . $escapedGuid . '</guid>' . PHP_EOL;
            echo '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL;
            echo '<title>' . $escapedStatusAccount . '</title>' . PHP_EOL;
            echo '<link>' . $escapedAccountLink . '</link>' . PHP_EOL;
            echo '<description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
            echo '<content:encoded><![CDATA[' . $description . ']]></content:encoded>' . PHP_EOL;
            echo $enclosureTag;
            echo '<category>' . $escapedStatusAccount . '</category>' . PHP_EOL;
            echo '</item>' . PHP_EOL;
        }

        echo '</channel>' . PHP_EOL;
        echo '</rss>';
    }

    /**
     * Retrieve all accounts for a given owner.
     *
     * @param string $accountOwner
     * @return array<int, array<string, mixed>>
     */
    protected static function getAllAccountsForOwner(string $accountOwner): array
    {
        return Account::getAllUserAccts($accountOwner);
    }

    /**
     * Retrieve an account link for the owner/account pair.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @return string
     */
    protected static function getAccountLinkForOwner(string $accountOwner, string $accountName): string
    {
        return Account::getAccountLink($accountOwner, $accountName);
    }

    /**
     * Retrieve status updates for the owner/account pair.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @return array<int, array<string, mixed>>
     */
    protected static function getStatusUpdatesForAccount(string $accountOwner, string $accountName): array
    {
        return Status::getStatusUpdates($accountOwner, $accountName);
    }
}
