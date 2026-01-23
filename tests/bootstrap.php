<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * PHPUnit Test Bootstrap
 *
 * Defines minimal constants required for tests to run without database connection.
 */

// Define database constants required by DatabaseManager
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'test_user');
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', 'test_pass');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'test_db');
}

// Define other constants that may be needed
if (!defined('API_KEY')) {
    define('API_KEY', 'test-api-key');
}
if (!defined('API_ENDPOINT')) {
    define('API_ENDPOINT', 'https://api.openai.com/v1/');
}
if (!defined('SYSTEM_MSG')) {
    define('SYSTEM_MSG', 'Test system message');
}
if (!defined('DOMAIN')) {
    define('DOMAIN', 'https://example.test');
}
if (!defined('MAX_STATUSES')) {
    define('MAX_STATUSES', 100);
}
if (!defined('IMG_AGE')) {
    define('IMG_AGE', 30);
}
if (!defined('CACHE_ENABLED')) {
    define('CACHE_ENABLED', false);
}
if (!defined('CACHE_TTL_FEED')) {
    define('CACHE_TTL_FEED', 3600);
}
if (!defined('CACHE_TTL_STATUS')) {
    define('CACHE_TTL_STATUS', 3600);
}
if (!defined('CACHE_TTL_USER')) {
    define('CACHE_TTL_USER', 3600);
}
if (!defined('CACHE_TTL_ACCOUNT')) {
    define('CACHE_TTL_ACCOUNT', 3600);
}

// Load Composer autoloader
require_once __DIR__ . '/../root/vendor/autoload.php';
