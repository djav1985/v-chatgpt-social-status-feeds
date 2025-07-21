<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Feed.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Exception;
use Doctrine\DBAL\ParameterType;
use App\Models\Database;
use App\Core\ErrorMiddleware;

class Feed
{
    /**
     * Get the status image path for a specific status.
     *
     * @param int $statusId
     * @param string $accountName
     * @param string $accountOwner
     * @return string|null
     */
    public static function getStatusImagePath(int $statusId, string $accountName, string $accountOwner): ?string
    {
        try {
            $db = new Database();
            $db->query("SELECT status_image FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
            $db->bind(':statusId', $statusId);
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $status = $db->single();
            return $status ? $status->status_image : null;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error retrieving status image path: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Delete a status update.
     *
     * @param int $statusId
     * @param string $accountName
     * @param string $accountOwner
     * @return bool
     */
    public static function deleteStatus(int $statusId, string $accountName, string $accountOwner): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_updates WHERE id = :statusId AND account = :account AND username = :username");
            $db->bind(':statusId', $statusId);
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error deleting status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get status information for a specific user and account.
     *
     * @param string $username
     * @param string $account
     * @return array
     */
    public static function getStatusInfo(string $username, string $account): array
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM status_updates WHERE username = :username AND account = :account ORDER BY created_at DESC");
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error retrieving status info: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Save a new status update.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @param string $status_content
     * @param string $image_name
     * @return bool
     */
    public static function saveStatus(string $accountName, string $accountOwner, string $status_content, string $image_name): bool
    {
        try {
            $db = new Database();
            $sql = "INSERT INTO status_updates (username, account, status, created_at, status_image) VALUES (:username, :account, :status, NOW(), :status_image)";
            $db->query($sql);
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $accountName);
            $db->bind(':status', $status_content);
            $db->bind(':status_image', $image_name);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error saving status: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get all status updates for a specific user and account.
     *
     * @param string $username
     * @param string $account
     * @return array
     */
    public static function getStatusUpdates(string $username, string $account): array
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM status_updates WHERE account = :accountName AND username = :accountOwner ORDER BY created_at DESC");
            $db->bind(':accountName', $account);
            $db->bind(':accountOwner', $username);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error retrieving status updates: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    /**
     * Count the number of statuses for a specific account.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return int
     */
    public static function countStatuses(string $accountName, string $accountOwner): int
    {
        try {
            $db = new Database();
            $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account AND username = :username");
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            return $db->single()->count;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error counting statuses: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Delete old statuses for a specific account.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @param int $deleteCount
     * @return bool
     */
    public static function deleteOldStatuses(string $accountName, string $accountOwner, int $deleteCount): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_updates WHERE account = :account AND username = :username ORDER BY created_at ASC LIMIT :deleteCount");
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            $db->bind(':deleteCount', $deleteCount, ParameterType::INTEGER);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error deleting old statuses: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if a status has been posted within a specific time slot.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @param string $hour
     * @return bool
     */
    public static function hasStatusBeenPosted(string $accountName, string $accountOwner, string $hour): bool
    {
        try {
            $db = new Database();
            $start = date('Y-m-d ') . sprintf('%02d', $hour) . ':00:00';
            $end = date('Y-m-d ') . sprintf('%02d', $hour) . ':59:59';

            $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :start AND :end");
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $accountName);
            $db->bind(':start', $start);
            $db->bind(':end', $end);

            return $db->single()->count > 0;
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error checking if status has been posted: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get the latest status update for a specific account.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @return object|null
     */
    public static function getLatestStatusUpdate(string $accountName, string $accountOwner): ?object
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM status_updates WHERE account = :account AND username = :username ORDER BY created_at DESC LIMIT 1");
            $db->bind(':account', $accountName);
            $db->bind(':username', $accountOwner);
            return $db->single();
        } catch (Exception $e) {
            ErrorMiddleware::logMessage("Error retrieving latest status update: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
