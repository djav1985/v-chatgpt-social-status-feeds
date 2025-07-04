<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: AccountHandler.php
 * Description: Manages account operations including checking existence, updating, inserting, and deleting accounts.
 * License: MIT
 */

class AccountHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Get all accounts from the database.
     *
     * @return array
     */
    public static function getAllAccounts(): array
    {
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT * FROM accounts");
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving all accounts: " . $e->getMessage(), 'error');
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
            $db = new DatabaseHandler();
            $db->query("SELECT account FROM accounts WHERE username = :username");
            $db->bind(':username', $username);
            return $db->resultSet();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving all user accounts: " . $e->getMessage(), 'error');
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
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT 1 FROM accounts WHERE username = :accountOwner AND account = :accountName LIMIT 1");
            $db->bind(':accountOwner', $accountOwner);
            $db->bind(':accountName', $accountName);
            return (bool) $db->single();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error checking if account exists: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get account information from the database.
     *
     * @param string $username
     * @param string $account
     * @return mixed
     */
    public static function getAcctInfo(string $username, string $account): mixed
    {
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT * FROM accounts WHERE username = :username AND account = :account");
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            return $db->single();
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving account info: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get the account link for a specific user and account.
     *
     * @param string $username
     * @param string $account
     * @return string
     */
    public static function getAccountLink(string $username, string $account): string
    {
        try {
            $db = new DatabaseHandler();
            $db->query("SELECT link FROM accounts WHERE username = :username AND account = :account");
            $db->bind(':username', $username);
            $db->bind(':account', $account);
            $acctInfo = $db->single();
            return htmlspecialchars($acctInfo->link ?? '');
        } catch (Exception $e) {
            ErrorHandler::logMessage("Error retrieving account link: " . $e->getMessage(), 'error');
            throw $e;
        }
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
        $db = new DatabaseHandler();
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
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorHandler::logMessage("Error updating account: " . $e->getMessage(), 'error');
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
        $db = new DatabaseHandler();
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
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorHandler::logMessage("Error creating account: " . $e->getMessage(), 'error');
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
        $db = new DatabaseHandler();
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
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            ErrorHandler::logMessage("Error deleting account: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
}
