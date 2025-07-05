<?php
// Only allow the install script when explicitly enabled
if (getenv('INSTALL_ENABLED') !== '1') {
    // Ensure a session is started to display messages if relevant, though unlikely for install script
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Set a more user-friendly message or log, then exit
    error_log("Install script access denied. INSTALL_ENABLED is not set to '1'.");
    exit('Install script is disabled. Please contact the administrator if you believe this is an error.');
}

require_once __DIR__ . '/../config.php'; // Defines DB_HOST, DB_USER, DB_PASSWORD, DB_NAME

try {
    // Establish PDO connection
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Crucial for error handling
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Recommended for security
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);

    // Read the SQL file
    $sqlFilePath = __DIR__ . '/../install.sql';
    $sql = file_get_contents($sqlFilePath);

    if ($sql === false) {
        throw new RuntimeException("Error reading the SQL file: " . $sqlFilePath);
    }

    // Basic way to split statements; might need improvement for complex SQL files
    // This simple split might fail with SQL comments, complex procedures, or if ';' is used within string literals.
    // For a typical install.sql with simple CREATE TABLE and INSERT statements, it often works.
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $pdo->beginTransaction();
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    $pdo->commit();

    echo "Database installed successfully using PDO.";

    // Delete this script after successful installation unless KEEP_INSTALL is set
    if (getenv('KEEP_INSTALL') !== '1') {
        if (@unlink(__FILE__)) {
            echo "<br>Install script deleted successfully.";
        } else {
            echo "<br>Warning: Failed to delete install script. Please remove it manually for security.";
            error_log("Warning: Failed to delete install.php. Manual removal required.");
        }
    }
    exit;

} catch (PDOException $e) {
    // Attempt to roll back if a transaction was started and an error occurred
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Database installation failed (PDOException): " . $e->getMessage());
    die("Database installation failed: " . $e->getMessage() . "<br>Please check server logs and ensure database credentials in config.php are correct and the database user has appropriate permissions.");
} catch (RuntimeException $e) {
    error_log("Installation script error (RuntimeException): " . $e->getMessage());
    die("Installation script error: " . $e->getMessage());
} catch (Throwable $e) {
    // Catch any other possible errors/exceptions
    error_log("An unexpected error occurred during installation: " . $e->getMessage());
    die("An unexpected error occurred during installation: " . $e->getMessage());
}
