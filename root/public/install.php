<?php
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
} else {
    echo "Error installing database: " . $conn->error;
}

// Close the connection
$conn->close();

// Delete this script
//unlink(__FILE__);
