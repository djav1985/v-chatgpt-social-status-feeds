<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: /db.php
 * Description: This script sets up the database connection using PDO and initializes required tables.
 */

class Database
{
    // Database credentials
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASSWORD;
    private $dbname = DB_NAME;

    // Database handler and statement properties
    private $dbh;
    private $stmt;
    private $error;

    // Constructor to establish a database connection
    public function __construct()
    {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;
        $options = [
            PDO::ATTR_PERSISTENT => true, // Persistent connection
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Error handling
        ];

        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            throw new Exception("Database connection not established: " . $this->error);
        }
    }

    // Prepare SQL query
    public function query($sql)
    {
        if (!$this->dbh) {
            throw new Exception("Database connection not established.");
        }
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Bind parameters to the prepared statement
    public function bind($param, $value, $type = null)
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

    // Execute the prepared statement
    public function execute()
    {
        return $this->stmt->execute();
    }

    // Fetch multiple rows from the executed statement
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // Fetch a single row from the executed statement
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    // Get the row count from the last executed statement
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }
}

// Check if the application is already installed
if (!defined('INSTALLED') || !INSTALLED) {
    $db = new Database();

    // Create the IP blacklist table
    $db->query("CREATE TABLE IF NOT EXISTS ip_blacklist (
        ip_address VARCHAR(255) NOT NULL,
        login_attempts INT DEFAULT 0,
        blacklisted BOOLEAN DEFAULT FALSE,
        timestamp BIGINT UNSIGNED,
        PRIMARY KEY (ip_address)
    );");
    $db->execute();

    // Create tables and insert default data for status updates
    $db->query("CREATE TABLE IF NOT EXISTS status_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        account VARCHAR(255) NOT NULL,
        status TEXT,
        created_at DATETIME,
        status_image VARCHAR(255),
        INDEX (username)
    );");
    $db->execute();

    // Create and populate the accounts table
    $db->query("CREATE TABLE IF NOT EXISTS accounts (
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    prompt TEXT,
    hashtags BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    cron VARCHAR(255),
    days VARCHAR(255), // New days field
    image_prompt VARCHAR(255),
    platform VARCHAR(255) NOT NULL,
    PRIMARY KEY (account),
    INDEX username_idx (username)
);");
    $db->execute();

    // Insert an example account into the accounts table
    $db->query("INSERT INTO accounts (account, username, prompt, hashtags, link, cron, image_prompt, platform)
    VALUES ('admin', 'admin', 'Write a Facebook status update for my business page.', TRUE, 'https://domain.com/', '6,12,18' 'image_prompt_example.jpg', 'facebook');");
    $db->execute();

    // Create and populate the users table
    $db->query("CREATE TABLE IF NOT EXISTS users (
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        total_accounts INT DEFAULT 10,
        max_api_calls BIGINT DEFAULT 9999999999,
        used_api_calls BIGINT DEFAULT 0,
        admin TINYINT DEFAULT 0,
        PRIMARY KEY (username)
    );");
    $db->execute();

    // Insert a default admin user into the users table
    $db->query("INSERT INTO users (username, password, total_accounts, max_api_calls, used_api_calls, admin)
        VALUES ('admin', 'admin', 10, 9999999999, 0, 1);");
    $db->execute();

    // Update the config file to set INSTALLED to true
    $configFilePath = __DIR__ .  '/config.php';
    $configData = file_get_contents($configFilePath);
    $configData = str_replace("define('INSTALLED', false);", "define('INSTALLED', true);", $configData);
    file_put_contents($configFilePath, $configData);

    echo "Installation completed successfully.";
}
