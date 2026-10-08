<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: BlacklistModel.php
 * Description: IP blacklist management
 */

namespace App\Models;

use Exception;
use App\Core\DatabaseManager;
use App\Core\ErrorManager;

class BlacklistModel
{
    /**
     * Determine whether the current request originates from a blacklisted IP.
     */
    public static function isCurrentRequestIpBlacklisted(): bool
    {
        $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP);
        if (!$ip) {
            return false;
        }

        if (self::isBlacklisted($ip)) {
            ErrorManager::log("Blacklisted IP attempted access: $ip", 'error');
            return true;
        }

        return false;
    }

    /**
     * Update the failed login attempts for an IP address.
     *
     * Uses a single atomic upsert so concurrent requests cannot race.
     */
    public static function updateFailedAttempts(string $ip): void
    {
        try {
            $now = time();
            $db = DatabaseManager::getInstance();
            // MariaDB evaluates SET assignments left to right, so blacklisted sees the new count.
            $db->query(
                "INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp)
                 VALUES (:ip, 1, FALSE, :ts)
                 ON DUPLICATE KEY UPDATE
                     login_attempts = login_attempts + 1,
                     blacklisted = IF(login_attempts >= 3, TRUE, blacklisted),
                     timestamp = :ts2"
            );
            $db->bind(':ip', $ip);
            $db->bind(':ts', $now);
            $db->bind(':ts2', $now);
            $db->execute();
        } catch (Exception $e) {
            ErrorManager::log('Error updating failed attempts: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if an IP address is blacklisted.
     */
    public static function isBlacklisted(string $ip): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if (!$result) {
                return false;
            }

            $result = (object)$result;
            if (time() - $result->timestamp > (3 * 24 * 60 * 60)) {
                $db->query("UPDATE ip_blacklist SET blacklisted = FALSE, login_attempts = 0, timestamp = :now WHERE ip_address = :ip");
                $db->bind(':now', time());
                $db->bind(':ip', $ip);
                $db->execute();
                return false;
            }

            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log('Error checking blacklist status: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Clear the IP blacklist of old entries.
     */
    public static function clearIpBlacklist(): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $threeDaysAgo = time() - (3 * 24 * 60 * 60);
            $db->query("DELETE FROM ip_blacklist WHERE timestamp < :threeDaysAgo");
            $db->bind(':threeDaysAgo', $threeDaysAgo);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log('Error clearing IP blacklist: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
