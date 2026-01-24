<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: DatabaseManager.php
 * Description: AI Social Status Generator
 */

namespace App\Core;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Exception;
use App\Core\ErrorManager;
class DatabaseManager
{
    private const MAX_RETRIES = 3;

    private static ?DatabaseManager $instance = null;
    private static ?Connection $dbh = null;
    private static ?int $lastUsedTime = null;
    private static int $idleTimeout = 10;

    private string $sql = '';
    /** @var array<string, mixed> */
    private array $params = [];
    /** @var array<string, int> */
    private array $types = [];
    private ?Result $result = null;
    private ?int $affectedRows = null;
    private int $retryCount = 0;

    /**
     * Create a new DatabaseManager instance and connect.
     *
     * @return void
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get the singleton DatabaseManager instance.
     *
     * @return DatabaseManager
     */
    public static function getInstance(): DatabaseManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prepare an SQL query.
     *
     * @param string $sql SQL statement to execute
     * @return void
     */
    public function query(string $sql): void
    {
        $this->connect();
        $this->sql = $sql;
        $this->params = [];
        $this->types = [];
        $this->result = null;
        $this->affectedRows = null;
    }

    /**
     * Bind a value to a query parameter.
     *
     * @param string   $param Parameter name with or without colon
     * @param mixed    $value Value to bind
     * @param int|null $type  Parameter type constant
     * @return void
     */
    public function bind(string $param, mixed $value, ?int $type = null): void
    {
        if ($type === null) {
            switch (true) {
                case is_int($value):
                    $type = ParameterType::INTEGER;
                    break;
                case is_bool($value):
                    $type = ParameterType::BOOLEAN;
                    break;
                case is_null($value):
                    $type = ParameterType::NULL;
                    break;
                default:
                    $type = ParameterType::STRING;
            }
        }

        $name = ltrim($param, ':');
        $this->params[$name] = $value;
        $this->types[$name] = $type;
    }

    /**
     * Execute the prepared statement.
     *
     * @return bool True on success
     */
    public function execute(): bool
    {
        try {
            self::$lastUsedTime = time();
            if (preg_match('/^\s*(SELECT|SHOW|DESCRIBE|PRAGMA)/i', $this->sql)) {
                $this->result = self::$dbh->executeQuery($this->sql, $this->params, $this->types);
                $this->affectedRows = $this->result->rowCount();
            } else {
                $this->affectedRows = self::$dbh->executeStatement($this->sql, $this->params, $this->types);
            }
            $this->retryCount = 0;
            return true;
        } catch (DBALException $e) {
            if ($this->isConnectionError($e)) {
                if ($this->retryCount < self::MAX_RETRIES) {
                    $this->retryCount++;
                    ErrorManager::getInstance()->log('MySQL connection lost during execution. Attempting to reconnect... Retry ' . $this->retryCount, 'warning');
                    $this->reconnect();
                    return $this->execute();
                } else {
                    ErrorManager::getInstance()->log('Max database reconnection attempts reached. Aborting query.', 'error');
                    throw new Exception('Max database reconnection attempts reached.');
                }
            }
            throw $e;
        }
    }

    /**
     * Execute the query and return all rows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resultSet(): array
    {
        $this->execute();
        return $this->result ? $this->result->fetchAllAssociative() : [];
    }

    /**
     * Execute the query and return a single row.
     *
     * @return array<string, mixed>|false
     */
    public function single(): array|false
    {
        $this->execute();
        return $this->result ? $this->result->fetchAssociative() : false;
    }

    /**
     * Get the number of affected rows.
     *
     * @return int
     */
    public function rowCount(): int
    {
        return $this->affectedRows ?? ($this->result ? $this->result->rowCount() : 0);
    }

    /**
     * Start a database transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        try {
            $this->connect();
            self::$lastUsedTime = time();
            self::$dbh->beginTransaction();
            return true;
        } catch (DBALException $e) {
            if ($this->isConnectionError($e)) {
                ErrorManager::getInstance()->log('MySQL connection lost during transaction. Attempting to reconnect...', 'warning');
                $this->reconnect();
                self::$dbh->beginTransaction();
                return true;
            }
            throw $e;
        }
    }

    /**
     * Commit the current transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        self::$lastUsedTime = time();
        self::$dbh->commit();
        return true;
    }

    /**
     * Roll back the current transaction.
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        self::$lastUsedTime = time();
        self::$dbh->rollBack();
        return true;
    }

    /**
     * Check if the exception indicates a lost connection.
     *
     * @param DBALException $e
     * @return bool
     */
    private function isConnectionError(DBALException $e): bool
    {
        $code = $e->getPrevious() ? $e->getPrevious()->getCode() : $e->getCode();
        $errors = ['2006', '2013', '1047', '1049'];
        return in_array((string) $code, $errors, true);
    }

    /**
     * Establish a database connection if needed.
     *
     * @return void
     */
    private function connect(): void
    {
        if (self::$dbh !== null && self::$lastUsedTime !== null && (time() - self::$lastUsedTime) > self::$idleTimeout) {
            $this->closeConnection();
        }

        if (self::$dbh === null) {
            $params = [
                'dbname'   => DB_NAME,
                'user'     => DB_USER,
                'password' => DB_PASSWORD,
                'host'     => DB_HOST,
                'driver'   => 'pdo_mysql',
                'charset'  => 'utf8mb4',
            ];

            try {
                self::$dbh = DriverManager::getConnection($params);
            } catch (DBALException $e) {
                ErrorManager::getInstance()->log('Database connection failed: ' . $e->getMessage(), 'error');
                throw new Exception('Database connection failed');
            }
        }

        self::$lastUsedTime = time();
    }

    /**
     * Close the current database connection.
     *
     * @return void
     */
    private function closeConnection(): void
    {
        self::$dbh = null;
        self::$lastUsedTime = null;
    }

    /**
     * Reconnect to the database.
     *
     * @return void
     */
    private function reconnect(): void
    {
        $this->closeConnection();
        $this->connect();
    }
}
