<?php
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: UtilityHandler.php
 * Description: AI Social Status Generator
 */
namespace App\Models;

use Exception;
use App\Models\Database;
use App\Core\ErrorHandler;

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: UtilityHandler.php
 * Description: AI Social Status Generator
 */

class UtilityHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Update the failed login attempts for an IP address.
     *
     * @param string $ip
     * @return void
     */
    public static function updateFailedAttempts(string $ip): void
    {
        try {
            $db = new Database();
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
            ErrorHandler::logMessage("Error updating failed attempts: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if an IP address is blacklisted.
     *
     * @param string $ip
     * @return bool
     */
    public static function isBlacklisted(string $ip): bool
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM ip_blacklist WHERE ip_address = :ip AND blacklisted = TRUE");
            $db->bind(':ip', $ip);
            $result = $db->single();

            if ($result) {
                if (time() - $result->timestamp > (3 * 24 * 60 * 60)) {
                    $db->query("UPDATE ip_blacklist SET blacklisted = FALSE WHERE ip_address = :ip");
                    $db->bind(':ip', $ip);
                    $db->execute();
                    return false;
                }
                return true;
            }
            return false;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error checking blacklist status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Clear the IP blacklist.
     * This function clears the IP blacklist, removing entries older than 3 days.
     *
     * @return bool True on success, false on failure
     */
    public static function clearIpBlacklist(): bool
    {
        try {
            $db = new Database();
            $threeDaysAgo = time() - (3 * 24 * 60 * 60);
            $db->query("DELETE FROM ip_blacklist WHERE timestamp < :threeDaysAgo");
            $db->bind(':threeDaysAgo', $threeDaysAgo);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error clearing IP blacklist: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Display and clear session messages.
     *
     * @return void
     */
    public static function displayAndClearMessages(): void
    {
        if (isset($_SESSION['messages']) && count($_SESSION['messages']) > 0) {
            foreach ($_SESSION['messages'] as $message) {
                echo '<script>showToast(' . json_encode($message) . ');</script>';
            }
            unset($_SESSION['messages']);
        }
    }

}
