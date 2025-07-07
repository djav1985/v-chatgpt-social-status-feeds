<?php
namespace App\Models;

use Exception;
use App\Models\Database;
use App\Core\ErrorHandler;

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: UserHandler.php
 * Description: Handles user-related operations such as retrieving, saving, updating, and deleting user data.
 * License: MIT
 */

class UserHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Get all users from the database.
     *
     * @return array
     */
    public static function getAllUsers(): array
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM users");
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving all users: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if a user exists in the database.
     *
     * @param string $username
     * @return mixed
     */
    public static function userExists(string $username): mixed
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM users WHERE username = :username");
            $db->bind(':username', $username);
            return $db->single();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error checking if user exists: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Save or update a user in the database.
     *
     * @param string $username
     * @param string $password
     * @param int $totalAccounts
     * @param int $maxApiCalls
     * @param int $usedApiCalls
     * @param string $expires
     * @param int $admin
     * @param bool $isUpdate
     * @return bool
     */
    public static function updateUser(string $username, string $password, int $totalAccounts, int $maxApiCalls, int $usedApiCalls, string $expires, int $admin, bool $isUpdate): bool
    {
        ErrorHandler::logMessage("updateUser called with username: $username, isUpdate: $isUpdate", 'info');
        $db = new Database();
        $db->beginTransaction();
        try {
            if ($isUpdate) {
                $db->query("UPDATE users SET password = :password, total_accounts = :totalAccounts, max_api_calls = :maxApiCalls, used_api_calls = :usedApiCalls, admin = :admin, expires = :expires WHERE username = :username");
            } else {
                $db->query("INSERT INTO users (username, password, total_accounts, max_api_calls, used_api_calls, expires, admin) VALUES (:username, :password, :totalAccounts, :maxApiCalls, :usedApiCalls, :expires, :admin)");
            }
            $db->bind(':username', $username);
            $db->bind(':password', $password);
            $db->bind(':totalAccounts', $totalAccounts);
            $db->bind(':maxApiCalls', $maxApiCalls);
            $db->bind(':usedApiCalls', $usedApiCalls);
            $db->bind(':expires', $expires);
            $db->bind(':admin', $admin);
            $db->execute();
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorHandler::logMessage("Error updating user: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Delete a user and their related data from the database.
     *
     * @param string $username
     * @return bool
     */
    public static function deleteUser(string $username): bool
    {
        $db = new Database();
        $db->beginTransaction();
        try {
            $db->query("DELETE FROM users WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            $db->query("DELETE FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            $db->query("DELETE FROM status_updates WHERE username = :username");
            $db->bind(':username', $username);
            $db->execute();

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorHandler::logMessage("Error deleting user: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update the password for a user.
     *
     * @param string $username
     * @param string $hashedPassword
     * @return bool
     */
    public static function updatePassword(string $username, string $hashedPassword): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE users SET password = :password WHERE username = :username");
            $db->bind(':username', $username);
            $db->bind(':password', $hashedPassword);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error updating password: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get user information from the database.
     *
     * @param string $username
     * @return mixed
     */
    public static function getUserInfo(string $username): mixed
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM users WHERE username = :username");
            $db->bind(':username', $username);
            return $db->single();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving user info: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get all user accounts for a specific user.
     *
     * @param string $username
     * @return array
     */
    public static function getAllUserAccts(string $username): array
    {
        try {
            $db = new Database();
            $db->query("SELECT * FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving all user accounts: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update the used API calls for a user.
     *
     * @param string $username
     * @param int $usedApiCalls
     * @return bool
     */
    public static function updateUsedApiCalls(string $username, int $usedApiCalls): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username");
            $db->bind(':used_api_calls', $usedApiCalls);
            $db->bind(':username', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error updating used API calls: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update the maximum API calls for a user.
     *
     * @param string $username
     * @param int $maxApiCalls
     * @return bool
     */
    public static function updateMaxApiCalls(string $username, int $maxApiCalls): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE users SET max_api_calls = :max_api_calls WHERE username = :username");
            $db->bind(':max_api_calls', $maxApiCalls);
            $db->bind(':username', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error updating max API calls: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Reset the used API calls for all users.
     *
     * @return bool
     */
    public static function resetAllApiUsage(): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE users SET used_api_calls = 0");
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error resetting all API usage: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Update the profile for a user.
     *
     * @param string $username
     * @param string $who
     * @param string $where
     * @param string $what
     * @param string $goal
     * @return bool
     */
    public static function updateProfile(string $username, string $who, string $where, string $what, string $goal): bool
    {
        try {
            $db = new Database();
            $db->query("UPDATE users SET who = :who, `where` = :where, what = :what, goal = :goal WHERE username = :username");
            $db->bind(':username', $username);
            $db->bind(':who', $who);
            $db->bind(':where', $where);
            $db->bind(':what', $what);
            $db->bind(':goal', $goal);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error updating profile: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
