<?php

/**
 * Project: SocialRSS
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 3.0.0
 *
 * File: phpstan-bootstrap.php
 * Description: PHPStan bootstrap configuration.
 */

// Repo root directory
$rootDir = dirname(__DIR__);

// Load Composer autoload from root
$autoloadFiles = [
    $rootDir . '/root/vendor/autoload.php',
];
foreach ($autoloadFiles as $file) {
    if (is_file($file)) {
        require $file;
    }
}

// Define constants for PHPStan
if (!defined('VONTMENT_KEY')) {
    define('VONTMENT_KEY', '');
}
if (!defined('VONTMENT_PLUGINS')) {
    define('VONTMENT_PLUGINS', '');
}
if (!defined('VONTMENT_THEMES')) {
    define('VONTMENT_THEMES', '');
}
