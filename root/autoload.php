<?php

// Autoload function to automatically include class files
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    } else {
        die("Class file not found: " . $file);
    }
});
