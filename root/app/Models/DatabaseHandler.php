<?php
/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: DatabaseHandler.php
 * Description: AI Social Status Generator 
 */
namespace App\Models;

use PDO;
use PDOException;
use Exception;

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com/
 * Version: 2.0.0
 * File: DatabaseHandler.php
 * Description: Manages database connection and queries using PDO.
 * License: MIT
 */

class DatabaseHandler // @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private static ?PDO $dbh = null;
    private static ?int $lastUsedTime = null; // Tracks the last time the connection was used
    private static int $idleTimeout = 10; // Timeout in seconds (e.g., 10 seconds)
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
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                        PDO::ATTR_PERSISTENT => true,
                        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
                       ];

            try {
                self::$dbh = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
            } catch (PDOException $e) {
                ErrorHandler::logMessage("Database connection failed: " . $e->getMessage(), 'error');
                throw new Exception("Database connection failed");
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
     */
    public function query(string $sql): void
    {
        try {
            $this->connect(); // Ensure the connection is active
            $this->stmt = self::$dbh->prepare($sql);
        } catch (PDOException $e) {
            if ($this->isConnectionError($e)) {
                ErrorHandler::logMessage("MySQL connection lost. Attempting to reconnect...", 'warning');
                $this->reconnect();
                $this->stmt = self::$dbh->prepare($sql); // Retry the query preparation
            } else {
                throw $e;
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
     */
    public function execute(): bool
    {
        try {
            self::$lastUsedTime = time(); // Update the last used time
            return $this->stmt->execute();
        } catch (PDOException $e) {
            if ($this->isConnectionError($e)) {
                ErrorHandler::logMessage("MySQL connection lost during execution. Attempting to reconnect...", 'warning');
                $this->reconnect();
                return $this->stmt->execute(); // Retry the execution
            } else {
                throw $e;
            }
        }
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
    public function single(): mixed
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
     */
    public function beginTransaction(): bool
    {
        try {
            $this->connect(); // Ensure the connection is active
            self::$lastUsedTime = time(); // Update the last used time
            return self::$dbh->beginTransaction();
        } catch (PDOException $e) {
            if ($this->isConnectionError($e)) {
                ErrorHandler::logMessage("MySQL connection lost during transaction. Attempting to reconnect...", 'warning');
                $this->reconnect();
                return self::$dbh->beginTransaction(); // Retry the transaction
            } else {
                throw $e;
            }
        }
    }

    /**
     * Commits a transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        self::$lastUsedTime = time(); // Update the last used time
        return self::$dbh->commit();
    }

    /**
     * Rolls back a transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        self::$lastUsedTime = time(); // Update the last used time
        return self::$dbh->rollBack();
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
