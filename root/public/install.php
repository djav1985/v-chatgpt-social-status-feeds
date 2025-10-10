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
 * Description: Fresh database installation script
 *              For upgrades from old schema, use upgrade.php instead
 */

require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if any tables already exist
$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<h2>Error: Database already contains tables</h2>";
    echo "<p>This script is for fresh installations only. If you need to upgrade from an old schema, please use <a href='upgrade.php'>upgrade.php</a> instead.</p>";
    $conn->close();
    exit;
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
    echo "<h2>Database installed successfully!</h2>";
    echo "<p>Default admin credentials:</p>";
    echo "<ul><li>Username: admin</li><li>Password: admin</li></ul>";
    echo "<p><strong>Please change the default password after logging in.</strong></p>";
    $conn->close();

    // Delete this script after successful installation
    @unlink(__FILE__);
    
    // Delete the SQL file after successful installation
    @unlink(__DIR__ . '/../install.sql');
    exit;
} else {
    echo "Error installing database: " . $conn->error;
    $conn->close();
}
