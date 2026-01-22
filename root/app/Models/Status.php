<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Status.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Exception;
use Doctrine\DBAL\ParameterType;
use App\Core\DatabaseManager;
use App\Core\ErrorManager;
use App\Services\CacheService;

class Status
{
    public static function getStatusImagePath(int $statusId, string $accountName, string $accountOwner): ?string
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT status_image FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
            $db->bind(':statusId', $statusId);
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $status = $db->single();
            if ($status) {
                $status = (object)$status;
                return $status->status_image;
            }
            return null;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving status image path: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public static function deleteStatus(int $statusId, string $accountName, string $accountOwner): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("DELETE FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
            $db->bind(':statusId', $statusId);
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $db->execute();
            
            self::clearStatusCache($accountOwner, $accountName);
            
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error deleting status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get status info for a user and account.
     *
     * @param string $username
     * @param string $account
     * @return array<int, array<string, mixed>>
     */
    public static function getStatusInfo(string $username, string $account): array
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM status_updates WHERE username = :username AND account = :account ORDER BY created_at DESC");
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving status info: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public static function saveStatus(string $accountName, string $accountOwner, string $status_content, string $image_name): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $sql = "INSERT INTO status_updates (username, account, status, created_at, status_image) VALUES (:username, :account, :status, NOW(), :status_image)";
            $db->query($sql);
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $accountName);
            $db->bind(':status', $status_content);
            $db->bind(':status_image', $image_name);
            $db->execute();
            
            self::clearStatusCache($accountOwner, $accountName);
            
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error saving status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get status updates for a user and account.
     *
     * @param string $username
     * @param string $account
     * @return array<int, array<string, mixed>>
     */
    public static function getStatusUpdates(string $username, string $account): array
    {
        if (!CACHE_ENABLED) {
            return self::fetchStatusUpdates($username, $account);
        }
        
        try {
            $cacheKey = "status:feed:{$username}:{$account}:all";
            $cache = CacheService::getInstance();
            
            return $cache->remember($cacheKey, CACHE_TTL_FEED, function () use ($username, $account) {
                return self::fetchStatusUpdates($username, $account);
            });
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving status updates: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Fetch status updates from database (uncached).
     *
     * @param string $username
     * @param string $account
     * @return array<int, array<string, mixed>>
     */
    private static function fetchStatusUpdates(string $username, string $account): array
    {
        $db = DatabaseManager::getInstance();
        $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
        $db->bind(':accountName', $account);
        $db->bind(':accountOwner', $username);
        return $db->resultSet();
    }

    /**
     * Count total statuses for a user and account.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return int
     */
    public static function countStatuses(string $accountName, string $accountOwner): int
    {
        if (!CACHE_ENABLED) {
            return self::fetchStatusCount($accountName, $accountOwner);
        }
        
        try {
            $cacheKey = "status:count:{$accountOwner}:{$accountName}";
            $cache = CacheService::getInstance();
            
            return $cache->remember($cacheKey, CACHE_TTL_STATUS, function () use ($accountName, $accountOwner) {
                return self::fetchStatusCount($accountName, $accountOwner);
            });
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error counting statuses: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Fetch status count from database (uncached).
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return int
     */
    private static function fetchStatusCount(string $accountName, string $accountOwner): int
    {
        $db = DatabaseManager::getInstance();
        $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account AND username = :username");
        $db->bind(':account', $accountName);
        $db->bind(':username', $accountOwner);
        $result = $db->single();
        if ($result) {
            $result = (object)$result;
            return $result->count;
        }
        return 0;
    }

    public static function deleteOldStatuses(string $accountName, string $accountOwner, int $deleteCount): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("DELETE FROM status_updates WHERE account = :account AND username = :username ORDER BY created_at ASC LIMIT :deleteCount");
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $db->bind(':deleteCount', $deleteCount, ParameterType::INTEGER);
            $db->execute();
            
            self::clearStatusCache($accountOwner, $accountName);
            
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error deleting old statuses: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public static function hasStatusBeenPosted(string $accountName, string $accountOwner, string $hour): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $start = date('Y-m-d ') . sprintf('%02d', $hour) . ':00:00';
            $end = date('Y-m-d ') . sprintf('%02d', $hour) . ':59:59';

            $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :start AND :end");
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $accountName);
            $db->bind(':start', $start);
            $db->bind(':end', $end);

            $result = $db->single();
            if ($result) {
                $result = (object)$result;
                return $result->count > 0;
            }
            return false;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error checking if status has been posted: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get the latest status update for a user and account.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return object|null
     */
    public static function getLatestStatusUpdate(string $accountName, string $accountOwner): ?object
    {
        if (!CACHE_ENABLED) {
            return self::fetchLatestStatusUpdate($accountName, $accountOwner);
        }
        
        try {
            $cacheKey = "status:latest:{$accountOwner}:{$accountName}";
            $cache = CacheService::getInstance();
            
            return $cache->remember($cacheKey, CACHE_TTL_STATUS, function () use ($accountName, $accountOwner) {
                return self::fetchLatestStatusUpdate($accountName, $accountOwner);
            });
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving latest status update: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Fetch latest status update from database (uncached).
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return object|null
     */
    private static function fetchLatestStatusUpdate(string $accountName, string $accountOwner): ?object
    {
        $db = DatabaseManager::getInstance();
        $db->query("SELECT * FROM status_updates WHERE account = :account AND username = :username ORDER BY created_at DESC LIMIT 1");
        $db->bind(':account', $accountName);
        $db->bind(':username', $accountOwner);
        $result = $db->single();
        return $result ? (object)$result : null;
    }
    
    /**
     * Clear cached status data for a specific user/account combination.
     * If account is null, clears cache for all accounts of the user.
     * If username is null, clears all status caches.
     *
     * @param string|null $username
     * @param string|null $account
     * @return void
     */
    public static function clearStatusCache(?string $username = null, ?string $account = null): void
    {
        if (!CACHE_ENABLED) {
            return;
        }
        
        try {
            $cache = CacheService::getInstance();
            
            if ($username === null) {
                // Clear all status-related cache entries and all RSS XML cache entries

        try {
            $cache = CacheService::getInstance();

            if ($username === null) {
                // Clear all status-related cache entries and all RSS XML cache entries.
                $cache->clear('status:');
                $cache->clear('rss:xml');
            } elseif ($account === null) {
                // Clear all status-related cache entries for the user and their RSS XML feeds.
                $cache->clear("status:feed:{$username}:");
                $cache->clear("status:count:{$username}:");
                $cache->clear("status:latest:{$username}:");
                $cache->clear("rss:xml:{$username}:");
            } else {
                // Clear status-related cache entries for the specific user/account combination
                // and invalidate the user's RSS XML feeds.
                $cache->delete("status:feed:{$username}:{$account}:all");
                $cache->delete("status:count:{$username}:{$account}");
                $cache->delete("status:latest:{$username}:{$account}");
                $cache->clear("rss:xml:{$username}:");
                $cache->clear("rss:xml:{$username}:");
            }
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error clearing status cache: " . $e->getMessage(), 'error');
        }
    }
}
