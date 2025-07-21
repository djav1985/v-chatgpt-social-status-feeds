<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: Database.php
 * Description: AI Social Status Generator
 */

namespace App\Models;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Exception;
use App\Core\ErrorMiddleware;

class Database
{
    private static ?Connection $dbh = null;
    private static ?int $lastUsedTime = null;
    private static int $idleTimeout = 10;

    private string $sql = '';
    private array $params = [];
    private array $types = [];
    private ?Result $result = null;
    private ?int $affectedRows = null;

    public function __construct()
    {
        $this->connect();
    }

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
                ErrorMiddleware::logMessage('Database connection failed: ' . $e->getMessage(), 'error');
                throw new Exception('Database connection failed');
            }
        }

        self::$lastUsedTime = time();
    }

    private function closeConnection(): void
    {
        self::$dbh = null;
        self::$lastUsedTime = null;
    }

    private function reconnect(): void
    {
        $this->closeConnection();
        $this->connect();
    }

    public function query(string $sql): void
    {
        $this->connect();
        $this->sql = $sql;
        $this->params = [];
        $this->types = [];
        $this->result = null;
        $this->affectedRows = null;
    }

    public function bind(string $param, $value, ?int $type = null): void
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
            return true;
        } catch (DBALException $e) {
            if ($this->isConnectionError($e)) {
                ErrorMiddleware::logMessage('MySQL connection lost during execution. Attempting to reconnect...', 'warning');
                $this->reconnect();
                return $this->execute();
            }
            throw $e;
        }
    }

    public function resultSet(): array
    {
        $this->execute();
        return $this->result ? $this->result->fetchAllAssociative() : [];
    }

    public function single(): mixed
    {
        $this->execute();
        return $this->result ? $this->result->fetchAssociative() : null;
    }

    public function rowCount(): int
    {
        return $this->affectedRows ?? ($this->result ? $this->result->rowCount() : 0);
    }

    public function beginTransaction(): bool
    {
        try {
            $this->connect();
            self::$lastUsedTime = time();
            self::$dbh->beginTransaction();
            return true;
        } catch (DBALException $e) {
            if ($this->isConnectionError($e)) {
                ErrorMiddleware::logMessage('MySQL connection lost during transaction. Attempting to reconnect...', 'warning');
                $this->reconnect();
                self::$dbh->beginTransaction();
                return true;
            }
            throw $e;
        }
    }

    public function commit(): bool
    {
        self::$lastUsedTime = time();
        self::$dbh->commit();
        return true;
    }

    public function rollBack(): bool
    {
        self::$lastUsedTime = time();
        self::$dbh->rollBack();
        return true;
    }

    private function isConnectionError(DBALException $e): bool
    {
        $code = $e->getPrevious() ? $e->getPrevious()->getCode() : $e->getCode();
        $errors = ['2006', '2013', '1047', '1049'];
        return in_array((string) $code, $errors, true);
    }
}

