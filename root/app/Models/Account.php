<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Account.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Exception;
use App\Core\DatabaseManager;
use App\Core\ErrorManager;

class Account
{
    /**
     * Cached account lookups keyed by "username|account".
     *
     * @var array<string, array<string, mixed>|false>
     */
    private static array $accountInfoCache = [];

    private static function accountCacheKey(string $username, string $account): string
    {
        return trim($username) . '|' . trim($account);
    }

    private static function rememberAccountInfo(string $username, string $account, array|false $info): array|false
    {
        self::$accountInfoCache[self::accountCacheKey($username, $account)] = $info;

        return $info;
    }

    private static function fetchAccountInfo(string $username, string $account): array|false
    {
        $cacheKey = self::accountCacheKey($username, $account);
        if (array_key_exists($cacheKey, self::$accountInfoCache)) {
            return self::$accountInfoCache[$cacheKey];
        }

        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM accounts WHERE username = :username AND account = :account");
            $db->bind(':username', $username);
            $db->bind(':account', $account);

            return self::rememberAccountInfo($username, $account, $db->single());
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving account info: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private static function clearAccountCacheEntry(?string $username = null, ?string $account = null): void
    {
        if ($username === null) {
            self::$accountInfoCache = [];
            return;
        }

        $username = trim($username);

        if ($account === null) {
            $prefix = $username . '|';
            foreach (array_keys(self::$accountInfoCache) as $key) {
                if (str_starts_with($key, $prefix)) {
                    unset(self::$accountInfoCache[$key]);
                }
            }
            return;
        }

        unset(self::$accountInfoCache[self::accountCacheKey($username, $account)]);
    }

    /**
     * Clear cached account lookups for a specific account or user.
     */
    public static function clearAccountCache(?string $username = null, ?string $account = null): void
    {
        self::clearAccountCacheEntry($username, $account);
    }

    /**
     * Get all accounts from the database.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAllAccounts(): array
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM accounts");
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving all accounts: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get all user accounts for a specific user.
     *
     * @param string $username
     * @return array<int, array<string, mixed>>
     */
    public static function getAllUserAccts(string $username): array
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT account FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving all user accounts: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if an account exists in the database.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @return bool
     */
    public static function accountExists(string $accountOwner, string $accountName): bool
    {
        return self::getAcctInfo($accountOwner, $accountName) !== false;
    }

    /**
     * Get account information from the database.
     *
     * @param string $username
     * @param string $account
     * @return array<string, mixed>|false
     */
    public static function getAcctInfo(string $username, string $account): array|false
    {
        return self::fetchAccountInfo($username, $account);
    }

    /**
     * Get the account link for a specific user and account.
     *
     * Returns the sanitized account link or an empty string when the account
     * is not found.
     *
     * @param string $username
     * @param string $account
     * @return string
     */
    public static function getAccountLink(string $username, string $account): string
    {
        $acctInfo = self::getAcctInfo($username, $account);

        if (is_array($acctInfo) && isset($acctInfo['link'])) {
            return (string) $acctInfo['link'];
        }

        return '';
    }

    /**
     * Update an account in the database.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @param string $prompt
     * @param string $platform
     * @param int $hashtags
     * @param string $link
     * @param string $cron
     * @param string $days
     * @return bool
     */
    public static function updateAccount(string $accountOwner, string $accountName, string $prompt, string $platform, int $hashtags, string $link, string $cron, string $days): bool
    {
        $db = DatabaseManager::getInstance();
        $db->beginTransaction();
        try {
            $db->query("UPDATE accounts SET prompt = :prompt, platform = :platform, hashtags = :hashtags, link = :link, cron = :cron, days = :days WHERE username = :accountOwner AND account = :accountName");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->bind(':prompt', $prompt);
            $db->bind(':platform', $platform);
            $db->bind(':hashtags', $hashtags);
            $db->bind(':link', $link);
            $db->bind(':cron', $cron);
            $db->bind(':days', $days);
            $db->execute();
            $db->commit();

            self::clearAccountCache($accountOwner, $accountName);

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorManager::getInstance()->log("Error updating account: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Insert a new account into the database.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @param string $prompt
     * @param string $platform
     * @param int $hashtags
     * @param string $link
     * @param string $cron
     * @param string $days
     * @return bool
     */
    public static function createAccount(string $accountOwner, string $accountName, string $prompt, string $platform, int $hashtags, string $link, string $cron, string $days): bool
    {
        $db = DatabaseManager::getInstance();
        $db->beginTransaction();
        try {
            $db->query("INSERT INTO accounts (username, account, prompt, platform, hashtags, link, cron, days) VALUES (:accountOwner, :accountName, :prompt, :platform, :hashtags, :link, :cron, :days)");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->bind(':prompt', $prompt);
            $db->bind(':platform', $platform);
            $db->bind(':hashtags', $hashtags);
            $db->bind(':link', $link);
            $db->bind(':cron', $cron);
            $db->bind(':days', $days);
            $db->execute();
            $db->commit();

            self::clearAccountCache($accountOwner, $accountName);

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorManager::getInstance()->log("Error creating account: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Delete an account and its related statuses from the database.
     *
     * @param string $accountOwner
     * @param string $accountName
     * @return bool
     */
    public static function deleteAccount(string $accountOwner, string $accountName): bool
    {
        $db = DatabaseManager::getInstance();
        $db->beginTransaction();
        try {
            $db->query("DELETE FROM status_updates WHERE username = :accountOwner AND account = :accountName");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->execute();

            $db->query("DELETE FROM accounts WHERE username = :accountOwner AND account = :accountName");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            $db->execute();

            $db->commit();

            self::clearAccountCache($accountOwner, $accountName);

            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorManager::getInstance()->log("Error deleting account: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
