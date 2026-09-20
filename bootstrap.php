<?php

// Start the session globally
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
require_once __DIR__ . '/config.php';

// Simple PSR-4-like autoloader for this mini framework.
// It converts namespace separators to directory separators
// and requires the corresponding PHP file if it exists.
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    $file = __DIR__ . '/' . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
