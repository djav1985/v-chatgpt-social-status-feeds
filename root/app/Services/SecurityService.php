<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: SecurityService.php
 * Description: IP blacklist management
 */

namespace App\Services;

use Exception;
use App\Core\Database;
use App\Core\ErrorMiddleware;

class SecurityService
{
    /**
     * Update the failed login attempts for an IP address.
     */
    public static function updateFailedAttempts(string $ip): void
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if ($result) {
                $attempts = $result->login_attempts + 1;
                $is_blacklisted = ($attempts >= 3);
                $timestamp = ($is_blacklisted) ? time() : $result->timestamp;
                $db->query("UPDATE ip_blacklist SET login_attempts = :attempts, blacklisted = :blacklisted, timestamp = :timestamp WHERE ip_address = :ip");
                $db->bind(':attempts', $attempts);
                $db->bind(':blacklisted', $is_blacklisted);
                $db->bind(':timestamp', $timestamp);
                $db->bind(':ip', $ip);
            } else {
                $db->query("INSERT INTO ip_blacklist (ip_address, login_attempts, blacklisted, timestamp) VALUES (:ip, 1, FALSE, :timestamp)");
                $db->bind(':ip', $ip);
                $db->bind(':timestamp', time());
            }
            $db->execute();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error updating failed attempts: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if an IP address is blacklisted.
     */
    public static function isBlacklisted(string $ip): bool
    {
        try {
            $db = Database::getInstance();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if (!$result) {
                return false;
            }

            if (time() - $result->timestamp > (3 * 24 * 60 * 60)) {
                $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
                $db->bind(':ip', $ip);
                $db->execute();
                return false;
            }

            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error checking blacklist status: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Clear the IP blacklist of old entries.
     */
    public static function clearIpBlacklist(): bool
    {
        try {
            $db = Database::getInstance();
            $threeDaysAgo = time() - (3 * 24 * 60 * 60);
            $db->query("DELETE FROM ip_blacklist WHERE timestamp < :threeDaysAgo");
            $db->bind(':threeDaysAgo', $threeDaysAgo);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage('Error clearing IP blacklist: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
