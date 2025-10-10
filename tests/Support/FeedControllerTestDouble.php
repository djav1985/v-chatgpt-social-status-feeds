<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Controllers\FeedController;

class FeedControllerTestDouble extends FeedController
{
    /** @var array<string, array<int, array<string, mixed>>> */
    public static array $accountsByOwner = [];

    /** @var array<string, array<string, string>> */
    public static array $linksByOwner = [];

    /** @var array<string, array<string, array<int, array<string, mixed>>>> */
    public static array $statusesByOwner = [];

    /** @var array<int, array<string, string>> */
    public static array $requestedAccountLinks = [];

    /** @var array<int, array<string, string>> */
    public static array $requestedStatusLookups = [];

    public static function reset(): void
    {
        self::$accountsByOwner = [];
        self::$linksByOwner = [];
        self::$statusesByOwner = [];
        self::$requestedAccountLinks = [];
        self::$requestedStatusLookups = [];
    }

    public static function renderFeed(string $accountName, string $accountOwner): string
    {
        ob_start();
        static::outputRssFeed($accountName, $accountOwner);
        return (string) ob_get_clean();
    }

    /**
     * @param string $accountOwner
     * @return array<int, array<string, mixed>>
     */
    protected static function getAllAccountsForOwner(string $accountOwner): array
    {
        return self::$accountsByOwner[$accountOwner] ?? [];
    }

    protected static function getAccountLinkForOwner(string $accountOwner, string $accountName): string
    {
        self::$requestedAccountLinks[] = ['owner' => $accountOwner, 'account' => $accountName];

        return self::$linksByOwner[$accountOwner][$accountName] ?? '';
    }

    /**
     * @param string $accountOwner
     * @param string $accountName
     * @return array<int, array<string, mixed>>
     */
    protected static function getStatusUpdatesForAccount(string $accountOwner, string $accountName): array
    {
        self::$requestedStatusLookups[] = ['owner' => $accountOwner, 'account' => $accountName];

        return self::$statusesByOwner[$accountOwner][$accountName] ?? [];
    }
}
