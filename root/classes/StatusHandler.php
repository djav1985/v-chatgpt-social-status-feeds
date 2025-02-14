<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: StatusHandler.php
 * Description: Handles status updates including saving, deleting, and retrieving status information.
 * License: MIT
 */

class StatusHandler
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
            ErrorHandler::logMessage("Error retrieving status image path: " . $e->getMessage(), 'error');
            return null;
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
            ErrorHandler::logMessage("Error deleting status: " . $e->getMessage(), 'error');
            return false;
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
            ErrorHandler::logMessage("Error retrieving status info: " . $e->getMessage(), 'error');
            return [];
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
            ErrorHandler::logMessage("Error saving status: " . $e->getMessage(), 'error');
            return false;
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
            ErrorHandler::logMessage("Error retrieving status updates: " . $e->getMessage(), 'error');
            return [];
        }
    }
    /**
     * Count the number of statuses for a specific account.
     *
     * @param string $accountName
     * @return int
     */
    public static function countStatuses(string $accountName): int
    {
        try {
            $db = new Database();
            $db->query("SELECT COUNT(*) as count FROM status_updates WHERE account = :account");
            $db->bind(':account', $accountName);
            return $db->single()->count;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error counting statuses: " . $e->getMessage(), 'error');
            return 0;
        }
    }

    /**
     * Delete old statuses for a specific account.
     *
     * @param string $accountName
     * @param int $deleteCount
     * @return bool
     */
    public static function deleteOldStatuses(string $accountName, int $deleteCount): bool
    {
        try {
            $db = new Database();
            $db->query("DELETE FROM status_updates WHERE account = :account ORDER BY created_at ASC LIMIT :deleteCount");
            $db->bind(':account', $accountName);
            $db->bind(':deleteCount', $deleteCount, PDO::PARAM_INT);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error deleting old statuses: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Check if a status has been posted within a specific time slot.
     *
     * @param string $accountName
     * @param string $accountOwner
     * @param string $currentTimeSlot
     * @return bool
     */
    public static function hasStatusBeenPosted(string $accountName, string $accountOwner, string $currentTimeSlot): bool
    {
        try {
            $db = new Database();
            $startTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' -15 minutes'));
            $endTime = date('Y-m-d H:i:s', strtotime($currentTimeSlot . ' +15 minutes'));

            $db->query("SELECT COUNT(*) as count FROM status_updates WHERE username = :username AND account = :account AND created_at BETWEEN :startTime AND :endTime");
            $db->bind(':username', $accountOwner);
            $db->bind(':account', $accountName);
            $db->bind(':startTime', $startTime);
            $db->bind(':endTime', $endTime);

            return $db->single()->count > 0;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error checking if status has been posted: " . $e->getMessage(), 'error');
            return false;
        }
    }
}
