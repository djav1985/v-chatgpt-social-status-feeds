<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: install.php
 * Description: AI Social Status Generator
 */

// Only allow the install script when explicitly enabled
if (getenv('INSTALL_ENABLED') !== '1') {
    exit('Install script is disabled.');
}

require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/../install.sql');

if ($sql === false) {
    die("Error reading the SQL file.");
}

// Execute the SQL script
if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for errors in each query
        if ($conn->errno) {
            echo "Error executing query: " . $conn->error . "<br>";
        }
    } while ($conn->next_result());
    echo "Database installed successfully.";
    $conn->close();

    // Delete this script after successful installation unless disabled
    if (getenv('KEEP_INSTALL') !== '1') {
        @unlink(__FILE__);
    }
    exit;
} else {
    echo "Error installing database: " . $conn->error;
    $conn->close();
}
