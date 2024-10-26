<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: /db.php
 * Description: This script sets up the database connection using PDO and initializes required tables.
 */

class Database
{
    // Database credentials pulled from constants defined elsewhere in the application
    private $host = DB_HOST;         // Hostname of the database server
    private $user = DB_USER;         // Database username
    private $pass = DB_PASSWORD;     // Database password
    private $dbname = DB_NAME;       // Database name

    // Properties for database handler and statement, along with error handling
    private $dbh;                    // Database handler
    private $stmt;                   // Prepared statement
    private $error;                  // Error message

    // Constructor to establish a database connection
    public function __construct()
    {
        // Data Source Name string for PDO
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname;

        // Options for PDO connection
        $options = [
            PDO::ATTR_PERSISTENT => true, // Use persistent connections
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION // Exception mode for errors
        ];

        try {
            // Create a new PDO instance
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            // Capture any connection errors
            $this->error = $e->getMessage();
            throw new Exception("Database connection not established: " . $this->error);
        }
    }

    // Prepare an SQL query for execution
    public function query($sql)
    {
        if (!$this->dbh) {
            throw new Exception("Database connection not established.");
        }
        // Prepare the SQL statement
        $this->stmt = $this->dbh->prepare($sql);
    }

    // Bind parameters to the prepared statement
    public function bind($param, $value, $type = null)
    {
        // Automatically determine parameter type if it's not specified
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT; // Integer type
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL; // Boolean type
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL; // Null type
                    break;
                default:
                    $type = PDO::PARAM_STR; // Default to string type
            }
        }
        // Bind the value to the parameter
        $this->stmt->bindValue($param, $value, $type);
    }

    // Execute the prepared statement
    public function execute()
    {
        return $this->stmt->execute(); // Returns true on success
    }

    // Fetch multiple rows from the executed statement as an array of objects
    public function resultSet()
    {
        $this->execute(); // Execute the statement
        return $this->stmt->fetchAll(PDO::FETCH_OBJ); // Return all results
    }

    // Fetch a single row from the executed statement as an object
    public function single()
    {
        $this->execute(); // Execute the statement
        return $this->stmt->fetch(PDO::FETCH_OBJ); // Return a single result
    }

    // Get the number of rows affected by the last executed statement
    public function rowCount()
    {
        return $this->stmt->rowCount(); // Return the row count
    }
}

// Check if the application has already been installed
if (!defined('INSTALLED') || !INSTALLED) {
    // Create a new instance of the Database class
    $db = new Database();

    // Create the IP blacklist table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS ip_blacklist (
        ip_address VARCHAR(255) NOT NULL,
        login_attempts INT DEFAULT 0,
        blacklisted BOOLEAN DEFAULT FALSE,
        timestamp BIGINT UNSIGNED,
        PRIMARY KEY (ip_address)
    );");
    $db->execute(); // Execute the table creation

    // Create the status updates table if it doesn't exist
    $db->query("CREATE TABLE IF NOT EXISTS status_updates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        account VARCHAR(255) NOT NULL,
        status TEXT,
        created_at DATETIME,
        status_image VARCHAR(255),
        INDEX (username) // Index on username for faster queries
    );");
    $db->execute(); // Execute the table creation

    // Create the accounts table with various fields and an index on username
    $db->query("CREATE TABLE IF NOT EXISTS accounts (
    account VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    prompt TEXT,
    hashtags BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    cron VARCHAR(255),
    days VARCHAR(255),
    image_prompt VARCHAR(255),
    platform VARCHAR(255) NOT NULL,
    cta VARCHAR(255),
    PRIMARY KEY (account), // Primary key on account name
    INDEX username_idx (username) // Index on username for faster queries
);");
    $db->execute(); // Execute the table creation

    // Insert an example account into the accounts table
    $db->query("INSERT INTO accounts (account, username, prompt, hashtags, link, cron, image_prompt, platform)
    VALUES ('admin', 'admin', 'Write a Facebook status update for my business page.', TRUE, 'https://domain.com/', '6,12,18', 'image_prompt_example.jpg', 'facebook');");
    $db->execute(); // Execute the insert

    // Create the users table with fields for user management
    $db->query("CREATE TABLE IF NOT EXISTS users (
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        total_accounts INT DEFAULT 10,
        max_api_calls BIGINT DEFAULT 9999999999,
        used_api_calls BIGINT DEFAULT 0,
        expires DATE DEFAULT '9999-12-31',
        admin TINYINT DEFAULT 0,
        PRIMARY KEY (username) // Primary key on username
    );");
    $db->execute(); // Execute the table creation

    // Insert a default admin user into the users table
    $db->query("INSERT INTO users (username, password, total_accounts, max_api_calls, used_api_calls, admin)
        VALUES ('admin', 'admin', 10, 9999999999, 0, 1);");
    $db->execute(); // Execute the insert

    // Update the configuration file to mark the application as installed
    $configFilePath = __DIR__ . '/config.php';
    $configData = file_get_contents($configFilePath); // Read the configuration file
    $configData = str_replace("define('INSTALLED', false);", "define('INSTALLED', true);", $configData); // Update installation status
    file_put_contents($configFilePath, $configData); // Write changes back to the file

    // Output a success message upon completion
    echo "Installation completed successfully.";
}

// Check if the application is installed and the version is 1.0.0
if (defined('INSTALLED') && INSTALLED === true && defined('APP_VERSION') && APP_VERSION === '1.0.0') {
    // Create a new instance of the Database class
    $db = new Database();

    $db->query("ALTER TABLE accounts ADD COLUMN cta VARCHAR(255) AFTER platform;");
    $db->execute(); // Execute the table alteration

    // Alter the users table to add a new column expires in the correct order
    $db->query("ALTER TABLE users ADD COLUMN expires DATE DEFAULT '9999-12-31' AFTER used_api_calls;");
    $db->execute(); // Execute the table alteration

    // Update the APP_VERSION in config.php
    $configFilePath = __DIR__ . '/config.php';
    $configData = file_get_contents($configFilePath); // Read the configuration file
    $configData = str_replace("define('APP_VERSION', '1.0.0');", "define('APP_VERSION', '2.0.0');", $configData); // Update the version
    file_put_contents($configFilePath, $configData); // Write changes back to the file

    // Output a success message upon completion
    echo "Update completed successfully.";
}
