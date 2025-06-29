<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: ChatGPT API
 * Version: 3.0.0
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: config.php
 * Description: Defines configuration settings such as API keys, endpoints, model preferences, domain, system messages, and database connection details for the ChatGPT API Status Generator.
 */

// OpenAI API key for authentication
define('API_KEY', 'sk-ai-statuses-rEhlPL4JBco1cf509j9LT3BlbkFJThsFGS38xo7y9VXXT41l');

// Endpoint for OpenAI's chat completion API
define('API_ENDPOINT', 'https://api.openai.com/v1/');

// Model identifier for the AI (e.g., GPT-3.5 or GPT-4)
define('MODEL', 'gpt-4o-mini');

// Temperature setting for the AI's creativity
define('TEMPERATURE', 1);

// Maximum number of tokens to generate
define('TOKENS', 256);

// Domain where the status service is hosted
define('DOMAIN', 'https://ai-status.servicesbyv.com');

// System prompt that guides the AI's output
define('SYSTEM_MSG', 'You are a social media marketer. You will respond with professional but fun social status update and nothing else.');

// Maximum width for image resizing in pixels
define('MAX_WIDTH', 720);

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', 8);

// Maximum days to keep images. Should be over 360.
define('IMG_AGE', 180);

// MySQL Database Connection Constants
define('DB_HOST', 'localhost'); // Database host or server
define('DB_USER', 'gptstatus_status'); // Database username
define('DB_PASSWORD', '#rgN%&%dT7^1agCY'); // Database password
define('DB_NAME', 'gptstatus_status'); // Database schema name

// SMTP settings for sending emails
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'user@example.com');
define('SMTP_PASSWORD', 'password');
define('SMTP_FROM_EMAIL', 'no-reply@example.com');
define('SMTP_FROM_NAME', 'ChatGPT API');

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
