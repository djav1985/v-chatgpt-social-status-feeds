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
use App\Core\Response;
use App\Models\AccountModel;
use App\Models\StatusModel;
use App\Core\ErrorManager;
use App\Services\CacheService;

class FeedController extends Controller
{
    /**
     * Entry point for RSS feed display for the given account.
     *
     * @param string $user    Account owner username
     * @param string $account Account name or "all"
     * @return Response
     */
    public function index(string $user, string $account): Response
    {
        try {
            return self::outputRssFeed($account, $user);
        } catch (\Exception $e) {
            ErrorManager::getInstance()->log('RSS feed generation failed: ' . $e->getMessage(), 'error');
            return Response::text('Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Validate parameters and output the requested feed.
     *
     * @param string|null $user    Owner username
     * @param string|null $account Account name or "all"
     * @return Response
     */
    public function handleRequest(?string $user = null, ?string $account = null): Response
    {
        if (!$user || !$account) {
            return Response::text('Bad Request: Missing user or account parameter.', 400);
        }

        return $this->index($user, $account);
    }


    /**
     * Output an RSS feed for a specific account or all accounts.
     *
     * @param string $accountName  Account name or "all"
     * @param string $accountOwner Username owning the account(s)
     * @return Response
     */
    protected static function outputRssFeed(string $accountName, string $accountOwner): Response
    {
        // Sanitize input to prevent XSS attacks while preserving characters for lookups
        $accountName = trim(strip_tags($accountName));
        $accountOwner = trim(strip_tags($accountOwner));

        // Check cache for complete RSS XML output
        if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
            $cacheKey = "rss:xml:{$accountOwner}:{$accountName}";
            $cachedXml = CacheService::getInstance()->get($cacheKey);
            if ($cachedXml !== null) {
                return self::buildRssResponse($cachedXml);
            }
        }

        $statuses = [];
        $isAllAccounts = ($accountName === 'all');

        // Fetch statuses for all accounts if 'all' is specified
        if ($isAllAccounts) {
            $accounts = AccountModel::getAllUserAccts($accountOwner);

            foreach ($accounts as $account) {
                $account = (object)$account;
                if (!isset($account->account)) {
                    continue;
                }

                $currentAccountName = (string)$account->account;

                // Retrieve account link
                $accountLink = AccountModel::getAccountLink($accountOwner, $currentAccountName);

                // Retrieve status updates for the account
                $statusInfo = StatusModel::getStatusUpdates($accountOwner, $currentAccountName);

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
            $accountLink = AccountModel::getAccountLink($accountOwner, $accountName);

            // Retrieve status updates for the account
            $statuses = StatusModel::getStatusUpdates($accountOwner, $accountName);

            foreach ($statuses as &$status) {
                $status = (object)$status;
                if (!isset($status->account)) {
                    $status->account = $accountName;
                }
                $status->accountLink = $accountLink;
            }
            unset($status);
        }

        $rssUrl = DOMAIN . '/feeds/' . rawurlencode($accountOwner) . '/' . ($isAllAccounts ? 'all' : rawurlencode($accountName));
        $escapedRssUrl = htmlspecialchars($rssUrl, ENT_QUOTES, 'UTF-8');
        $escapedAccountOwner = htmlspecialchars($accountOwner, ENT_QUOTES, 'UTF-8');
        $escapedAccountName = htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8');

        $xmlOutput = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xmlOutput .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">' . PHP_EOL;

        $xmlOutput .= '<channel>' . PHP_EOL;
        $xmlOutput .= '<title>' . $escapedAccountOwner . ' status feed</title>' . PHP_EOL;

        $xmlOutput .= '<link>' . $escapedRssUrl . '</link>' . PHP_EOL;
        $xmlOutput .= '<atom:link href="' . $escapedRssUrl . '" rel="self" type="application/rss+xml" />' . PHP_EOL;

        $xmlOutput .= '<description>Status feed for ' . $escapedAccountName . '</description>' . PHP_EOL;
        $xmlOutput .= '<language>en-us</language>' . PHP_EOL;

        // Strongly recommended for IFTTT stability
        $xmlOutput .= '<generator>Vontainment Feed Engine</generator>' . PHP_EOL;
        $xmlOutput .= '<lastBuildDate>' . gmdate(DATE_RSS) . '</lastBuildDate>' . PHP_EOL;
        $xmlOutput .= '<ttl>10</ttl>' . PHP_EOL;

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

                $imageFileSize = self::getImageFileSize($pathOwner, $pathAccount, $pathImage);
                $enclosureTag  = '<enclosure url="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" length="' . $imageFileSize . '" type="image/png" />' . PHP_EOL;
            }

            $description = htmlspecialchars((string)$status->status, ENT_QUOTES, 'UTF-8');
            $xmlOutput .= '<item>' . PHP_EOL;
            $guidSource = isset($status->id)
                ? 'status:' . (string)$status->id
                : ($status->account ?? '') . '|' . ($status->created_at ?? '') . '|' . ($status->status ?? '');
            $escapedGuid = htmlspecialchars((string)$guidSource, ENT_QUOTES, 'UTF-8');

            $xmlOutput .= '<guid isPermaLink="false">' . $escapedGuid . '</guid>' . PHP_EOL;
            $xmlOutput .= '<pubDate>' . date('r', strtotime($status->created_at)) . '</pubDate>' . PHP_EOL;
            $xmlOutput .= '<title>' . $escapedStatusAccount . '</title>' . PHP_EOL;
            $xmlOutput .= '<link>' . $escapedAccountLink . '</link>' . PHP_EOL;
            $xmlOutput .= '<description><![CDATA[' . $description . ']]></description>' . PHP_EOL;
            $xmlOutput .= '<content:encoded><![CDATA[' . $description . ']]></content:encoded>' . PHP_EOL;
            $xmlOutput .= $enclosureTag;
            $xmlOutput .= '<category>' . $escapedStatusAccount . '</category>' . PHP_EOL;
            $xmlOutput .= '</item>' . PHP_EOL;
        }

        $xmlOutput .= '</channel>' . PHP_EOL;
        $xmlOutput .= '</rss>';

        // Store XML output in cache
        if (defined('CACHE_ENABLED') && CACHE_ENABLED) {
            $cacheKey = "rss:xml:{$accountOwner}:{$accountName}";
            $ttl = defined('CACHE_TTL_FEED') ? CACHE_TTL_FEED : 180;
            CacheService::getInstance()->set($cacheKey, $xmlOutput, $ttl);
        }

        return self::buildRssResponse($xmlOutput);
    }

    /**
     * Build a standardized RSS XML response.
     */
    private static function buildRssResponse(string $xml): Response
    {
        return (new Response(200, [], $xml))
            ->withHeader('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0');
    }

    /**
     * Get image file size with caching to reduce filesystem I/O.
     *
     * @param string $pathOwner   Owner directory name
     * @param string $pathAccount Account directory name
     * @param string $pathImage   Image filename
     * @return int File size in bytes, or 0 if file doesn't exist
     */
    private static function getImageFileSize(string $pathOwner, string $pathAccount, string $pathImage): int
    {
        if (!defined('CACHE_ENABLED') || !CACHE_ENABLED) {
            $imageFilePath = __DIR__ . '/../../public/images/' . $pathOwner . '/' . $pathAccount . '/' . $pathImage;
            return file_exists($imageFilePath) ? filesize($imageFilePath) : 0;
        }

        $cacheKey = "image:size:{$pathOwner}:{$pathAccount}:{$pathImage}";
        $ttl = defined('CACHE_TTL_FEED') ? CACHE_TTL_FEED : 600;

        return CacheService::getInstance()->remember($cacheKey, $ttl, function () use ($pathOwner, $pathAccount, $pathImage) {
            $imageFilePath = __DIR__ . '/../../public/images/' . $pathOwner . '/' . $pathAccount . '/' . $pathImage;
            return file_exists($imageFilePath) ? filesize($imageFilePath) : 0;
        });
    }
}
