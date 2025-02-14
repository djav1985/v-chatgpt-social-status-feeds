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
define('API_KEY', '');

// Endpoint for OpenAI's chat completion API
define('API_ENDPOINT', 'https://api.openai.com/v1/');

// Model identifier for the AI (e.g., GPT-3.5 or GPT-4)
define('MODEL', 'gpt-4o-mini');

// Temperature setting for the AI's creativity (0 to 2 where higher values mean more creative responses)
define('TEMPERATURE', 1);

// Maximum number of tokens to generate in a response
define('TOKENS', 256);

// Domain where the status service is hosted
define('DOMAIN', 'https://spectre3.hugev.xyz');

// System prompt that guides the AI's output
define('SYSTEM_MSG', 'You are a social media marketer. You will respond with professional but fun social status update and nothing else.');

// Maximum width for image resizing in pixels
define('MAX_WIDTH', 720);

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', 30);

// Maximum age of images in days before they are removed (should be over 360 days)
define('IMG_AGE', 360);

// MySQL Database Connection Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'localhost3');

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
