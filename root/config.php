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

// Temperature setting for the AI's creativity (0 to 2 where higher values mean more creative responses)
define('TEMPERATURE', 1);

// Maximum number of tokens to generate in a response
define('TOKENS', 256);

// Domain where the status service is hosted
define('DOMAIN', 'https://domain.com');

// System prompt that guides the AI's output
define('SYSTEM_MSG', 'You are a social media marketer. You will respond with professional but fun social status update and nothing else.');

// Maximum width for image resizing in pixels
define('MAX_WIDTH', 720);

// Maximum number of statuses allowed in each feed
define('MAX_STATUSES', 30);

// Maximum age of images in days before they are removed (should be over 360 days)
define('IMG_AGE', 360);

// MySQL Database Connection Constants
define('DB_HOST', 'localhost'); // Database host or server address
define('DB_USER', '  '); // Username for the database connection
define('DB_PASSWORD', '  '); // Password for the database connection
define('DB_NAME', '  '); // Name of the database schema

// Flag to check if the system has been installed correctly
define('INSTALLED', false);
