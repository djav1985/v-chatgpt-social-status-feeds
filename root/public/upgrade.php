<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: upgrade.php
 * Description: Database upgrade/migration script
 *              Migrates old schema to new schema while preserving data
 */

require_once __DIR__ . '/../config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if database has any tables
$result = $conn->query("SHOW TABLES");
if (!$result || $result->num_rows === 0) {
    echo "<h2>Error: Database is empty</h2>";
    echo "<p>This script is for upgrading existing databases. For fresh installations, please use <a href='install.php'>install.php</a> instead.</p>";
    $conn->close();
    exit;
}

// Display warning and confirmation
echo "<h2>Database Upgrade</h2>";
echo "<p><strong>WARNING:</strong> This will upgrade your database schema to the latest version.</p>";
echo "<p><strong>IMPORTANT:</strong> Make sure you have backed up your database before proceeding!</p>";

// Simple confirmation check
if (!isset($_POST['confirm']) || $_POST['confirm'] !== 'yes') {
    echo '<form method="post" action="">';
    echo '<p><label><input type="checkbox" name="confirm" value="yes" required> I have backed up my database and want to proceed with the upgrade</label></p>';
    echo '<p><button type="submit">Upgrade Database</button></p>';
    echo '</form>';
    $conn->close();
    exit;
}

echo "<h3>Starting upgrade process...</h3>";

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/../upgrade.sql');

if ($sql === false) {
    die("Error reading the SQL file.");
}

// Execute the SQL script
echo "<p>Executing upgrade SQL...</p>";
$errors = [];

if ($conn->multi_query($sql)) {
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
        // Check for errors in each query
        if ($conn->errno) {
            $error = "Error executing query: " . $conn->error;
            echo "<p style='color: red;'>$error</p>";
            $errors[] = $error;
        }
    } while ($conn->next_result());
    
    if (empty($errors)) {
        echo "<h3 style='color: green;'>Database upgraded successfully!</h3>";
        echo "<p>Your database has been migrated to the new schema. All existing data has been preserved.</p>";
        echo "<p><a href='/'>Return to application</a></p>";
        
        // Delete this script after successful upgrade
        @unlink(__FILE__);
        
        // Delete the SQL file after successful upgrade
        @unlink(__DIR__ . '/../upgrade.sql');
    } else {
        echo "<h3 style='color: red;'>Upgrade completed with errors</h3>";
        echo "<p>Please review the errors above. Some changes may not have been applied.</p>";
        echo "<p>You may need to manually fix these issues or restore from backup.</p>";
    }
} else {
    echo "<h3 style='color: red;'>Error during upgrade:</h3>";
    echo "<p>" . $conn->error . "</p>";
}

$conn->close();
