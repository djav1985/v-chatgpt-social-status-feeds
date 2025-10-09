<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: User.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Exception;
use App\Core\DatabaseManager;
use App\Core\ErrorManager;

class User
{
    /**
     * Get all users from the database.
     *
     * @return array
     */
    public static function getAllUsers(): array
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM users");
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving all users: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Check if a user exists in the database.
     *
     * @param string $username
     * @return object|null
     */
    public static function userExists(string $username): ?object
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM users WHERE username = :username");
            $db->bind(':username', $username);
            $result = $db->single();
            if (is_array($result)) {
                $result = (object)$result;
            }
            return $result ?: null; // Explicitly return null if no user is found
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error checking if user exists: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Save or update a user in the database.
     *
     * @param string $username
     * @param string $password
     * @param string $email
     * @param int $totalAccounts
     * @param int $maxApiCalls
     * @param int $usedApiCalls
     * @param string $expires
     * @param int $admin
     * @param bool $isUpdate
     * @return bool
     */
    public static function updateUser(string $username, string $password, string $email, int $totalAccounts, int $maxApiCalls, int $usedApiCalls, string $expires, int $admin, bool $isUpdate): bool
    {
        $db = DatabaseManager::getInstance();
        $db->beginTransaction();
        try {
            if ($isUpdate) {
                $db->query("UPDATE users SET password = :password, email = :email, total_accounts = :totalAccounts, max_api_calls = :maxApiCalls, used_api_calls = :usedApiCalls, admin = :admin, expires = :expires WHERE username = :username");
            } else {
                $db->query("INSERT INTO users (username, password, email, total_accounts, max_api_calls, used_api_calls, expires, admin) VALUES (:username, :password, :email, :totalAccounts, :maxApiCalls, :usedApiCalls, :expires, :admin)");
            }
            $db->bind(':username', $username);
            $db->bind(':password', $password);
            $db->bind(':email', $email);
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
            ErrorManager::getInstance()->log("Error updating user: " . $e->getMessage(), 'error');
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
        $db = DatabaseManager::getInstance();
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
            ErrorManager::getInstance()->log("Error deleting user: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET password = :password WHERE username = :username");
            $db->bind(':username', $username);
            $db->bind(':password', $hashedPassword);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error updating password: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM users WHERE username = :username");
            $db->bind(':username', $username);
            $result = $db->single();
            if (is_array($result)) {
                $result = (object)$result;
            }
            return $result;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving user info: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("SELECT * FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error retrieving all user accounts: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET used_api_calls = :used_api_calls WHERE username = :username");
            $db->bind(':used_api_calls', $usedApiCalls);
            $db->bind(':username', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error updating used API calls: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET max_api_calls = :max_api_calls WHERE username = :username");
            $db->bind(':max_api_calls', $maxApiCalls);
            $db->bind(':username', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error updating max API calls: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Set the limit email notification flag for a user.
     */
    public static function setLimitEmailSent(string $username, bool $sent): bool
    {
        try {
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET limit_email_sent = :sent WHERE username = :username");
            $db->bind(':sent', $sent ? 1 : 0);
            $db->bind(':username', $username);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error updating limit email flag: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET used_api_calls = 0, limit_email_sent = 0");
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error resetting all API usage: " . $e->getMessage(), 'error');
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
            $db = DatabaseManager::getInstance();
            $db->query("UPDATE users SET who = :who, `where` = :where, what = :what, goal = :goal WHERE username = :username");
            $db->bind(':username', $username);
            $db->bind(':who', $who);
            $db->bind(':where', $where);
            $db->bind(':what', $what);
            $db->bind(':goal', $goal);
            $db->execute();
            return true;
        } catch (Exception $e) {
            ErrorManager::getInstance()->log("Error updating profile: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
