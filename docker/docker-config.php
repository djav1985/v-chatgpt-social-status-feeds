<?php

/**
 * Project: ChatGPT API
 * Author: Vontainment
 * URL: https://vontainment.com
 * Version: 2.0.0
 * File: config.php
 * Description: Defines configuration settings such as API keys, endpoints, model preferences, domain, system messages, and database connection details for the ChatGPT API Status Generator.
 */

// OpenAI API key for authentication
define('API_KEY', getenv('API_KEY'));

// Endpoint for OpenAI's chat completion API
define('API_ENDPOINT', getenv('API_ENDPOINT'));

// Model identifier for the AI (e.g., GPT-3.5 or GPT-4)
define('MODEL', getenv('MODEL'));

// Temperature setting for the AI's creativity (0 to 2 where higher values mean more creative responses)
define('TEMPERATURE', getenv('TEMPERATURE'));

// Maximum number of tokens to generate in a response
define('TOKENS', getenv('TOKENS'));

// Domain where the status service is hosted
define('DOMAIN', getenv('DOMAIN'));

// System prompt that guides the AI's output
define('SYSTEM_MSG', getenv('SYSTEM_MSG'));

// Maximum width for image resizing in pixels
define('MAX_WIDTH', getenv('MAX_WIDTH'));

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', getenv('MAX_STATUSES'));

// Maximum age of images in days before they are removed (should be over 360 days)
define('IMG_AGE', getenv('IMG_AGE'));

// Default permissions for creating directories
define('DIR_MODE', 0755);

// Cron runtime limits
define('CRON_MAX_EXECUTION_TIME', getenv('CRON_MAX_EXECUTION_TIME') ?: 0);
define('CRON_MEMORY_LIMIT', getenv('CRON_MEMORY_LIMIT') ?: '512M');

// MySQL Database Connection Constants
define('DB_HOST', getenv('DB_HOST'));
define('DB_USER', getenv('DB_USER'));
define('DB_PASSWORD', getenv('DB_PASSWORD'));
define('DB_NAME', getenv('DB_NAME'));

// SMTP settings for sending emails
define('SMTP_HOST', getenv('SMTP_HOST'));
define('SMTP_PORT', getenv('SMTP_PORT'));
define('SMTP_USER', getenv('SMTP_USER'));
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL'));
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME'));

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
