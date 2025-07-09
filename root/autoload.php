<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Simple PSR-4 autoloader for the App namespace.
 */

spl_autoload_register(
    function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/app/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
    }
);
