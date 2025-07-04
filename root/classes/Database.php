<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: db.php
 * Description: Manages database connection and queries using PDO.
 * License: MIT
 */

class Database // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static ?PDO $dbh = null;
    private static ?int $lastUsedTime = null; // Tracks the last time the connection was used
    private static int $idleTimeout = 10; // Timeout in seconds (e.g., 10 seconds)
    private static int $maxRetries = 3; // Maximum number of reconnection attempts
    private $stmt;

    /**
     * Constructor initializes the database connection if not already established.
     */
    public function __construct()
    {
        $this->connect();
    }

    /**
     * Establishes a database connection if not already connected or if the connection has timed out.
     */
    private function connect(): void
    {
        // Check if the connection exists and if it has been idle for too long
        if (self::$dbh !== null && self::$lastUsedTime !== null && (time() - self::$lastUsedTime) > self::$idleTimeout) {
            $this->closeConnection(); // Close the connection if it has been idle for too long
        }

        // Establish a new connection if none exists
        if (self::$dbh === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
            $options = [
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                       ];

            try {
                self::$dbh = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
            } catch (PDOException $e) {
                ErrorHandler::logMessage("Database connection failed: " . $e->getMessage(), 'error');
                throw $e; // Re-throw original PDOException to preserve details
            }
        }

        // Update the last used time
        self::$lastUsedTime = time();
    }

    /**
     * Closes the database connection.
     */
    private function closeConnection(): void
    {
        self::$dbh = null;
        self::$lastUsedTime = null;
    }

    /**
     * Reconnects to the database if the connection is lost or times out.
     */
    private function reconnect(): void
    {
        $this->closeConnection(); // Close the existing connection
        $this->connect(); // Establish a new connection
    }

    /**
     * Prepares a query and ensures the connection is active.
     *
     * @param string $sql
     * @throws PDOException
     */
    public function query(string $sql): void
    {
        $retryCount = 0;
        while ($retryCount <= self::$maxRetries) {
            try {
                $this->connect(); // Ensure the connection is active
                $this->stmt = self::$dbh->prepare($sql);
                return; // Success, exit the retry loop
            } catch (PDOException $e) {
                if ($this->isConnectionError($e) && $retryCount < self::$maxRetries) {
                    ErrorHandler::logMessage("MySQL connection lost. Attempting to reconnect... (Attempt " . ($retryCount + 1) . "/" . (self::$maxRetries + 1) . ")", 'warning');
                    $this->reconnect();
                    $retryCount++;
                } else {
                    throw $e; // Re-throw if not a connection error or max retries exceeded
                }
            }
        }
    }

    /**
     * Binds a value to a parameter in the prepared statement.
     *
     * @param string $param
     * @param mixed $value
     * @param int|null $type
     */
    public function bind(string $param, $value, ?int $type = null): void
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Executes the prepared statement and handles connection errors.
     *
     * @return bool
     * @throws PDOException
     */
    public function execute(): bool
    {
        $retryCount = 0;
        while ($retryCount <= self::$maxRetries) {
            try {
                self::$lastUsedTime = time(); // Update the last used time
                return $this->stmt->execute();
            } catch (PDOException $e) {
                if ($this->isConnectionError($e) && $retryCount < self::$maxRetries) {
                    ErrorHandler::logMessage("MySQL connection lost during execution. Attempting to reconnect... (Attempt " . ($retryCount + 1) . "/" . (self::$maxRetries + 1) . ")", 'warning');
                    $this->reconnect();
                    $retryCount++;
                } else {
                    throw $e; // Re-throw if not a connection error or max retries exceeded
                }
            }
        }
        return false; // This should never be reached, but added for completeness
    }

    /**
     * Fetches all results as an array of objects.
     *
     * @return array
     */
    public function resultSet(): array
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetches a single result as an object.
     *
     * @return mixed
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Returns the number of rows affected by the last query.
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Begins a transaction and ensures the connection is active.
     *
     * @return bool
     * @throws PDOException
     */
    public function beginTransaction(): bool
    {
        $retryCount = 0;
        while ($retryCount <= self::$maxRetries) {
            try {
                $this->connect(); // Ensure the connection is active
                self::$lastUsedTime = time(); // Update the last used time
                return self::$dbh->beginTransaction();
            } catch (PDOException $e) {
                if ($this->isConnectionError($e) && $retryCount < self::$maxRetries) {
                    ErrorHandler::logMessage("MySQL connection lost during transaction. Attempting to reconnect... (Attempt " . ($retryCount + 1) . "/" . (self::$maxRetries + 1) . ")", 'warning');
                    $this->reconnect();
                    $retryCount++;
                } else {
                    throw $e; // Re-throw if not a connection error or max retries exceeded
                }
            }
        }
        return false; // This should never be reached, but added for completeness
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     * @throws PDOException
     */
    public function commit(): bool
    {
        $retryCount = 0;
        while ($retryCount <= self::$maxRetries) {
            try {
                self::$lastUsedTime = time(); // Update the last used time
                return self::$dbh->commit();
            } catch (PDOException $e) {
                if ($this->isConnectionError($e) && $retryCount < self::$maxRetries) {
                    ErrorHandler::logMessage("MySQL connection lost during commit. Attempting to reconnect... (Attempt " . ($retryCount + 1) . "/" . (self::$maxRetries + 1) . ")", 'warning');
                    $this->reconnect();
                    $retryCount++;
                } else {
                    throw $e; // Re-throw if not a connection error or max retries exceeded
                }
            }
        }
        return false; // This should never be reached, but added for completeness
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool
     * @throws PDOException
     */
    public function rollBack(): bool
    {
        $retryCount = 0;
        while ($retryCount <= self::$maxRetries) {
            try {
                self::$lastUsedTime = time(); // Update the last used time
                return self::$dbh->rollBack();
            } catch (PDOException $e) {
                if ($this->isConnectionError($e) && $retryCount < self::$maxRetries) {
                    ErrorHandler::logMessage("MySQL connection lost during rollback. Attempting to reconnect... (Attempt " . ($retryCount + 1) . "/" . (self::$maxRetries + 1) . ")", 'warning');
                    $this->reconnect();
                    $retryCount++;
                } else {
                    throw $e; // Re-throw if not a connection error or max retries exceeded
                }
            }
        }
        return false; // This should never be reached, but added for completeness
    }

    /**
     * Checks if the exception is related to a lost MySQL connection.
     *
     * @param PDOException $e
     * @return bool
     */
    private function isConnectionError(PDOException $e): bool
    {
        $connectionErrors = [
                             '2006', // MySQL server has gone away
                             '2013', // Lost connection to MySQL server during query
                             '1047', // Unknown command
                             '1049', // Unknown database
                            ];

        return in_array($e->getCode(), $connectionErrors);
    }
}
