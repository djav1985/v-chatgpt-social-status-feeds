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

class Database
{
    private static ?PDO $dbh = null;
    private $stmt;

    public function __construct()
    {
        if (self::$dbh === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
            $options = [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];

            try {
                self::$dbh = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
            } catch (PDOException $e) {
                ErrorHandler::logMessage("Database connection not established: " . $e->getMessage(), 'error');
                throw new Exception("Database connection not established");
            }
        }
    }

    public function query(string $sql): void
    {
        $this->stmt = self::$dbh->prepare($sql);
    }

    public function bind(string $param, $value, int $type = null): void
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

    public function execute(): bool
    {
        try {
            return $this->stmt->execute();
        } catch (PDOException $e) {
            throw new Exception("A database error occurred.");
        }
    }

    public function resultSet(): array
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        try {
            return self::$dbh->beginTransaction();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function commit(): bool
    {
        try {
            return self::$dbh->commit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function rollBack(): bool
    {
        try {
            return self::$dbh->rollBack();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}