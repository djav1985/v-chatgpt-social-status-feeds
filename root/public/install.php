<?php

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

require_once __DIR__ . '/../config.php';

// Keep database errors under explicit control so a partial installation is
// reported as a failure and the recovery files remain available.
mysqli_report(MYSQLI_REPORT_OFF);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
} catch (Throwable $e) {
    error_log('Database installation connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database installation failed. Check the server logs and correct the database connection before retrying.');
}

// Check connection
if ($conn->connect_error) {
    error_log('Database installation connection failed: ' . $conn->connect_error);
    http_response_code(500);
    $conn->close();
    exit('Database installation failed. Check the server logs and correct the database connection before retrying.');
}

// Read the SQL file
$sql = file_get_contents(__DIR__ . '/../install.sql');

if ($sql === false) {
    error_log('Database installation failed: unable to read install.sql.');
    http_response_code(500);
    $conn->close();
    exit('Database installation failed because the SQL file could not be read.');
}

// Execute every statement and stop at the first SQL error.
$errors = [];
if (!$conn->multi_query($sql)) {
    $errors[] = $conn->error;
} else {
    do {
        $result = $conn->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }

        if ($conn->errno) {
            $errors[] = $conn->error;
            break;
        }

        if (!$conn->more_results()) {
            break;
        }

        if (!$conn->next_result()) {
            $errors[] = $conn->error;
            break;
        }
    } while (true);
}

$conn->close();

if (!empty($errors)) {
    foreach ($errors as $error) {
        error_log('Database installation query failed: ' . $error);
    }
    http_response_code(500);
    exit('Database installation failed partway through. The installer and SQL file' .
        ' were retained; check the server logs before retrying.');
}

echo 'Database installed successfully.';

// Remove installer artifacts only after every statement succeeds.
@unlink(__FILE__);
@unlink(__DIR__ . '/../install.sql');
