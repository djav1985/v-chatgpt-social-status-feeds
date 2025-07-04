<?php

// Autoload function to automatically include class files
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Log the error instead of using die()
        error_log("Class file not found: " . $file);
        throw new Exception("Class file not found: " . $class_name);
    }
});
