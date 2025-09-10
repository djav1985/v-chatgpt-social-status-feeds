<?php
// Repo root directory
$rootDir = dirname(__DIR__);

// Load Composer autoload from root
$autoloadFiles = [
    $rootDir . '/vendor/autoload.php',
    $rootDir . '/update-api/vendor/autoload.php',
];
foreach ($autoloadFiles as $file) {
    if (is_file($file)) {
        require $file;
    }
}

// Define constants for PHPStan
if (!defined('VONTMENT_KEY'))     define('VONTMENT_KEY', '');
if (!defined('VONTMENT_PLUGINS')) define('VONTMENT_PLUGINS', '');
if (!defined('VONTMENT_THEMES'))  define('VONTMENT_THEMES', '');
