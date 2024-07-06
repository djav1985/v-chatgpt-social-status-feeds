<?php

/**
 * Project: ChatGPT API
 * Version: 3.0.0
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: config.php
 * Description: Defines configuration settings such as API keys, endpoints, model preferences, domain, system messages, and database connection details for the ChatGPT API Status Generator.
 */

// OpenAI API key for authentication
define('API_KEY', 'sk-');

// Endpoint for OpenAI's chat completion API
define('API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');

// Model identifier for the AI (e.g., GPT-3.5 or GPT-4)
define('MODEL', 'gpt-4-turbo');

// Temperature setting for the AI's creativity
define('TEMPERATURE', 1);

// Maximum number of tokens to generate
define('TOKENS', 256);

// Domain where the status service is hosted
define('DOMAIN', 'https://domain.com');

// System prompt that guides the AI's output
define('SYSTEM_MSG', 'You are a social media marketer. You will respond with professional but fun social status update and nothing else.');

// Maximum width for image resizing in pixels
define('MAX_WIDTH', 720);

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', 30);

// MySQL Database Connection Constants
define('DB_HOST', 'localhost'); // Database host or server
define('DB_USER', '  '); // Database username
define('DB_PASSWORD', '  '); // Database password
define('DB_NAME', '  '); // Database schema name

// Flag to check if the system has been installed correctly
define('INSTALLED', false);
