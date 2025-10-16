<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: config.php
 * Description: AI Social Status Generator
 */

// OpenAI API key for authentication
define('API_KEY', '');

// Endpoint for OpenAI's chat completion API
define('API_ENDPOINT', 'https://api.openai.com/v1/');

// Model identifier for the AI (e.g., GPT-3.5 or GPT-4)
define('MODEL', 'gpt-5-nano');

// Temperature setting for the AI's creativity
define('TEMPERATURE', 1);

// Domain where the status service is hosted
define('DOMAIN', 'http://localhost3');

// System prompt that guides the AI's output
define('SYSTEM_MSG', 'You are a social media marketer. You will respond with professional but fun social status update and nothing else.');

// Maximum width for image resizing in pixels
define('MAX_WIDTH', 720);

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', 50);

// Maximum days to keep images. Default is 360 and it should be kept at
// least this value in production.
define('IMG_AGE', 360);

// Session timeout limit in seconds (default: 30 minutes)
define('SESSION_TIMEOUT_LIMIT', 1800);

// Default permissions for creating directories
define('DIR_MODE', 0755);

// Cron runtime limits
define('CRON_MAX_EXECUTION_TIME', getenv('CRON_MAX_EXECUTION_TIME') !== false ? getenv('CRON_MAX_EXECUTION_TIME') : 0);
define('CRON_MEMORY_LIMIT', getenv('CRON_MEMORY_LIMIT') !== false ? getenv('CRON_MEMORY_LIMIT') : '512M');

// MySQL Database Connection Constants
define('DB_HOST', 'localhost'); // Database host or server
define('DB_USER', 'root'); // Database username
define('DB_PASSWORD', ''); // Database password
define('DB_NAME', 'localhost3'); // Database schema name

// SMTP settings for sending emails
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'user@example.com');
define('SMTP_PASSWORD', 'password');
define('SMTP_FROM_EMAIL', 'no-reply@example.com');
define('SMTP_FROM_NAME', 'ChatGPT API');


// Validate required configuration constants
$required_constants = ['DB_HOST', 'DB_USER', 'DB_NAME', 'API_KEY'];
$missing_config = [];

foreach ($required_constants as $constant) {
    if (!defined($constant) || empty(constant($constant))) {
        $missing_config[] = $constant;
    }
}

if (!empty($missing_config)) {
    error_log("Missing required configuration: " . implode(', ', $missing_config));
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die("Configuration error: Missing required settings. Please check server logs.");
    }
}
